<?php
/**
 * Integració WordPress amb Aixada
 * Permet mostrar informació de l'usuari d'Aixada a WordPress
 */

// Incloure les funcions d'Aixada
require_once('AixadaVinagreta/php/utilities/general.php');
require_once('AixadaVinagreta/local_config/config.php');

/**
 * Funció per obtenir informació de l'usuari d'Aixada
 */
function get_aixada_user_info() {
    if (is_created_session()) {
        return array(
            'login' => get_session_login(),
            'uf_id' => get_session_uf_id(),
            'member_id' => get_session_member_id(),
            'user_id' => get_session_user_id(),
            'current_role' => get_current_role(),
            'language' => get_session_language(),
            'theme' => get_session_theme()
        );
    }
    return false;
}

/**
 * Shortcode per mostrar el nom d'usuari d'Aixada
 */
function mostrar_nom_aixada() {
    $aixada_user = get_aixada_user_info();
    if ($aixada_user) {
        return $aixada_user['login'];
    }
    return '';
}
add_shortcode('nom_aixada', 'mostrar_nom_aixada');

/**
 * Shortcode per mostrar el número d'UF
 */
function mostrar_uf_aixada() {
    $aixada_user = get_aixada_user_info();
    if ($aixada_user) {
        return $aixada_user['uf_id'];
    }
    return '';
}
add_shortcode('uf_aixada', 'mostrar_uf_aixada');

/**
 * Shortcode per mostrar el rol actual
 */
function mostrar_rol_aixada() {
    $aixada_user = get_aixada_user_info();
    if ($aixada_user) {
        return $aixada_user['current_role'];
    }
    return '';
}
add_shortcode('rol_aixada', 'mostrar_rol_aixada');

/**
 * Shortcode per mostrar informació completa de l'usuari
 */
function mostrar_info_completa_aixada() {
    $aixada_user = get_aixada_user_info();
    if ($aixada_user) {
        return sprintf(
            'Usuari: %s | UF: %s | Rol: %s',
            $aixada_user['login'],
            $aixada_user['uf_id'],
            $aixada_user['current_role']
        );
    }
    return '';
}
add_shortcode('info_aixada', 'mostrar_info_completa_aixada');

/**
 * Shortcode per mostrar salutació personalitzada
 */
function salutacio_aixada() {
    $aixada_user = get_aixada_user_info();
    if ($aixada_user) {
        return 'Hola, ' . $aixada_user['login'] . '!';
    }
    return '';
}
add_shortcode('salutacio_aixada', 'salutacio_aixada');

/**
 * Widget per mostrar informació d'Aixada
 */
class Aixada_User_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'aixada_user_widget',
            'Informació Usuari Aixada',
            array('description' => 'Mostra informació de l\'usuari d\'Aixada')
        );
    }
    
    public function widget($args, $instance) {
        $aixada_user = get_aixada_user_info();
        if ($aixada_user) {
            echo $args['before_widget'];
            echo $args['before_title'] . 'Informació Aixada' . $args['after_title'];
            echo '<div class="aixada-user-info">';
            echo '<p><strong>Usuari:</strong> ' . esc_html($aixada_user['login']) . '</p>';
            echo '<p><strong>UF:</strong> ' . esc_html($aixada_user['uf_id']) . '</p>';
            echo '<p><strong>Rol:</strong> ' . esc_html($aixada_user['current_role']) . '</p>';
            echo '<p><strong>Idioma:</strong> ' . esc_html($aixada_user['language']) . '</p>';
            echo '</div>';
            echo $args['after_widget'];
        }
    }
    
    public function form($instance) {
        echo '<p>Aquest widget mostra informació de l\'usuari d\'Aixada quan està connectat.</p>';
    }
    
    public function update($new_instance, $old_instance) {
        return array();
    }
}

// Registrar el widget
function register_aixada_widget() {
    register_widget('Aixada_User_Widget');
}
add_action('widgets_init', 'register_aixada_widget');

/**
 * Funció per verificar si l'usuari està connectat a Aixada
 */
function is_aixada_logged_in() {
    return is_created_session();
}

/**
 * Funció per obtenir el nom complet del membre d'Aixada
 */
function get_aixada_member_name() {
    if (is_created_session()) {
        global $db;
        $member_id = get_session_member_id();
        $rs = $db->Execute('SELECT name FROM aixada_member WHERE id = ?', $member_id);
        $row = $rs->fetch_array();
        return $row ? $row[0] : '';
    }
    return '';
}

/**
 * Shortcode per mostrar el nom del membre
 */
function mostrar_nom_membre_aixada() {
    $member_name = get_aixada_member_name();
    return $member_name ?: get_session_login();
}
add_shortcode('nom_membre_aixada', 'mostrar_nom_membre_aixada');

?>
