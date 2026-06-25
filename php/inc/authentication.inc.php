<?php

/**
 * @package Aixada
 */

require_once(__ROOT__ . 'local_config' . DS . 'config.php');
require_once(__ROOT__ . 'php' . DS . 'utilities' . DS . 'general.php');
require_once(__ROOT__ . 'php' . DS . 'inc' . DS . 'database.php');
require_once(__ROOT__ . 'php' . DS . 'lib' . DS . 'table_with_ref.php');
require_once(__ROOT__ . 'local_config' . DS . 'lang' . DS . get_session_language() . '.php');

/**
 * Handles user authentication: password hashing, verification, and credential checking.
 *
 * Based on an implementation from George Schlossnagle, Advanced PHP Programming, p.341.
 *
 * @package Aixada
 * @subpackage Authentication
 */
class Authentication
{
    /**
     * Returns all roles assigned to a user.
     *
     * @param DBWrap $db
     * @param int $user_id
     * @return string[]
     */
    private function _ask_roles($db, $user_id)
    {
        $rs = $db->Execute('SELECT role FROM aixada_user_role WHERE user_id = :1q', $user_id);
        $roles = array();
        while ($row = $rs->fetch_assoc()) {
            $roles[] = $row['role'];
        }
        return $roles;
    }

    /**
     * Generates a password hash using MD5-crypt with a random salt.
     *
     * @param string $password Plain text password.
     * @return string Hashed password.
     */
    public function generate_password_hash($password)
    {
        $random_seed = substr(md5(rand()), 0, 7);
        return crypt($password, '$1$' . $random_seed . '$');
    }

    /**
     * Verifies a plain text password against a stored hash.
     * Supports both the legacy 'ax' DES format and the newer MD5-crypt format.
     *
     * @param string $password      Plain text password.
     * @param string $password_hash Stored hash from the database.
     * @return bool
     */
    public function check_password_hash($password, $password_hash)
    {
        if (!strncmp($password_hash, 'ax', 2)) {
            // Legacy Aixada DES hash (CRYPT_STD_DES)
            return crypt($password, 'ax') == $password_hash;
        } elseif (!strncmp($password_hash, '$1$', 3)) {
            // Newer Aixada MD5-crypt hash
            $random_seed = substr($password_hash, 0, 11);
            return crypt($password, $random_seed) == $password_hash;
        }
        return false;
    }

    /**
     * Checks whether a password is correct for a given login or user_id.
     *
     * @param string $login         User login name.
     * @param string $plain_text_pwd Plain text password.
     * @param int    $user_id       User ID (optional alternative to login).
     * @return bool
     */
    public function check_password($login, $plain_text_pwd, $user_id = 0)
    {
        $db = DBWrap::get_instance();
        $rs = do_stored_query('retrieve_credentials', $login, $user_id);
        $row = $rs->fetch_assoc();
        $db->free_next_results();
        return $this->check_password_hash($plain_text_pwd, $row['password']);
    }

    /**
     * Authenticates a user by login and password.
     * On success, returns user properties needed to initialize the session.
     *
     * @param string $login    User login name.
     * @param string $password Plain text password.
     * @return array [user_id, login, uf_id, member_id, provider_id, roles[], current_role, language, theme]
     * @throws AuthException if credentials are wrong, user has no UF assigned, or user is deactivated.
     */
    public function check_credentials($login, $password)
    {
        global $Text;

        $db = DBWrap::get_instance();
        $rs = do_stored_query('retrieve_credentials', $login, 0);
        $row = $rs->fetch_assoc();
        $db->free_next_results();

        if (!$this->check_password_hash($password, $row['password'])) {
            throw new AuthException($Text['msg_err_incorrectLogon']);
        }

        if (!array_key_exists('uf_id', $row) || intval($row['uf_id']) == 0) {
            throw new AuthException($Text['msg_err_noUfAssignedYet']);
        }

        if (!array_key_exists('is_active_member', $row) || intval($row['is_active_member']) == 0) {
            throw new AuthException($Text['msg_err_deactivatedUser']);
        }

        $roles        = $this->_ask_roles($db, $row['id']);
        $current_role = in_array('Consumer', $roles) ? 'Consumer' : (isset($roles[0]) ? $roles[0] : '');
        $theme        = $row['gui_theme'] ?: 'start';

        return array($row['id'], $row['login'], $row['uf_id'], $row['member_id'], $row['provider_id'], $roles, $current_role, $row['language'], $theme);
    }
}
