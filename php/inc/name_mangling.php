<?php

/**
 * Naming convention helpers for the generic table/jqGrid system.
 * Handles SQL aliases for tables and fields, especially when a table
 * is referenced more than once as a foreign key in the same query.
 *
 * @package Aixada
 * @subpackage Naming_conventions
 */

/**
 * Builds a SQL alias for a table that is referenced more than once as a foreign key.
 * Example: ('aixada_unit_measure', 'unit_measure_order_id') -> 'aixada_unit_measure_order'
 *
 * @param string $table_name The base table name.
 * @param string $field      The foreign key field name (ending in '_id').
 * @return string The table alias.
 */
function get_table_alias($table_name, $field)
{
    $tmp = substr($field, 0, strlen($field) - 3); // remove '_id'
    return $table_name . substr($tmp, strrpos($tmp, '_'));
}

/**
 * Strips the trailing '_id' from a foreign key field name.
 * Example: 'unit_measure_order_id' -> 'unit_measure_order'
 *
 * @param string $foreign_key Field name ending in '_id'.
 * @return string Field name without '_id'.
 * @throws Exception If the field name does not end in '_id'.
 */
function get_fkey_alias($foreign_key)
{
    if (substr($foreign_key, -3) != '_id') {
        throw new Exception('get_field_alias: ' . $foreign_key . ' doesnt end in _id');
    }
    return substr($foreign_key, 0, strlen($foreign_key) - 3);
}

/**
 * Returns the list of foreign key tables that are referenced more than once
 * among the given fields (these require SQL aliases to avoid ambiguity).
 *
 * @param string[] $fields       List of field names.
 * @param array    $foreign_keys Map of field name => [table, id_field, desc_field].
 * @return string[] Table names that appear more than once as a foreign key.
 */
function get_doubled_foreign_keys($fields, $foreign_keys)
{
    $doubled_foreign_keys = array();
    $key_count = array();
    foreach ($fields as $field) {
        if (isset($foreign_keys[$field]) && $foreign_keys[$field] != '') {
            list($ftable_name, $ftable_id, $ftable_desc) = $foreign_keys[$field];
            if (!isset($key_count[$ftable_name])) {
                $key_count[$ftable_name] = 1;
            } else {
                $key_count[$ftable_name]++;
                $doubled_foreign_keys[] = $ftable_name;
            }
        }
    }
    return $doubled_foreign_keys;
}

/**
 * Computes SQL-ready field names, field aliases, and table aliases for a set of fields.
 * Fields that are foreign keys get aliased to avoid collisions in JOIN queries.
 *
 * @param string   $table_name   The main table name.
 * @param string[] $fields       List of field names.
 * @param array    $foreign_keys Map of field name => [table, id_field, desc_field].
 * @return array [substituted_names, substituted_aliases, table_aliases]
 */
function get_substituted_names($table_name, $fields, $foreign_keys)
{
    $substituted_name  = array();
    $substituted_alias = array();
    $table_alias       = array();

    $doubled_foreign_keys = get_doubled_foreign_keys($fields, $foreign_keys);

    foreach ($fields as $field) {
        if (!isset($foreign_keys[$field]) || $foreign_keys[$field] == '') {
            $substituted_name[$field] = $table_name . '.' . $field;
        } else {
            list($ftable_name, $ftable_id, $ftable_desc) = $foreign_keys[$field];
            $substituted_alias[$field] = get_fkey_alias($field);
            $table_alias[$field] = in_array($ftable_name, $doubled_foreign_keys)
                ? get_table_alias($ftable_name, $field)
                : $ftable_name;
            $substituted_name[$field] = $table_alias[$field] . '.' . $ftable_desc;
        }
    }
    return array($substituted_name, $substituted_alias, $table_alias);
}
