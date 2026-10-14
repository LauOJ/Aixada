<?php

/**
 * Stock-shop visibility flag.
 *
 * A stock product appears on the Estoc screen only when sell_in_shop = 1.
 * Stock with the flag off is ordered like any other order, and the quantity
 * at the premises is still tracked. Existing stock and cumulative products
 * are marked the first time the column is added.
 */

require_once(__ROOT__ . 'php/inc/database.php');

function ensure_sell_in_shop()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $db = DBWrap::get_instance();
    $column = get_row_query(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'aixada_product'
           AND COLUMN_NAME = 'sell_in_shop'"
    );
    if (!$column) {
        $db->Execute(
            "ALTER TABLE aixada_product
             ADD COLUMN sell_in_shop tinyint NOT NULL DEFAULT 0
             AFTER active"
        );
        $db->Execute(
            "UPDATE aixada_product
             SET sell_in_shop = 1
             WHERE orderable_type_id IN (1, 4)"
        );
    }
}

/**
 * Products a member can order: order products, plus stock that is not
 * self-service. Self-service stock stays on the Estoc screen only.
 */
function sql_can_be_ordered($alias = 'p')
{
    if (get_config('orders_allow_stock', false)) {
        return "{$alias}.orderable_type_id >= 1";
    }
    return "(
        {$alias}.orderable_type_id >= 2
        OR ({$alias}.orderable_type_id = 1 AND {$alias}.sell_in_shop = 0)
    )";
}

function sql_not_self_service_stock($alias = 'p')
{
    if (get_config('orders_allow_stock', false)) {
        return '1=1';
    }
    return "NOT ({$alias}.orderable_type_id = 1 AND {$alias}.sell_in_shop = 1)";
}

function is_self_service_stock($row)
{
    if (get_config('orders_allow_stock', false)) {
        return false;
    }
    return (int) $row['orderable_type_id'] === 1
        && (int) $row['sell_in_shop'] === 1;
}

function is_shown_in_stock_shop($row)
{
    $type = (int) $row['orderable_type_id'];
    return (int) $row['sell_in_shop'] === 1 && ($type === 1 || $type === 4);
}

/**
 * Order products are never sold from the stock shop, even if the flag is set.
 */
function normalize_product_sell_in_shop(&$request)
{
    if (!isset($request['table']) || $request['table'] !== 'aixada_product') {
        return;
    }
    $type = isset($request['orderable_type_id']) ? (int) $request['orderable_type_id'] : 0;
    if ($type !== 1 && $type !== 4) {
        $request['sell_in_shop'] = 0;
    }
}

function shop_products_xml()
{
    $use_shop = get_config('use_shop', 'order_and_stock');
    return products_detail_xml(
        ($use_shop ? get_param('provider_id', 0) : -1),
        get_param('category_id', 0),
        get_param('like', ''),
        ($use_shop === 'only_stock' ? '1234-01-01' : '0'),
        get_param('all', 0),
        -1,
        'shop'
    );
}

function order_products_xml()
{
    return products_detail_xml(
        get_param('provider_id', 0),
        get_param('category_id', 0),
        get_param('like', ''),
        get_param('date'),
        get_param('all', 0),
        get_param('product_id', 0),
        'order'
    );
}

function products_detail_xml($provider_id, $category_id, $like, $date, $all, $product_id, $mode)
{
    $rs = do_stored_query(
        'get_products_detail',
        $provider_id,
        $category_id,
        $like,
        $date,
        $all,
        $product_id
    );
    $strXML = '';
    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            if ($mode === 'shop' && !is_shown_in_stock_shop($row)) {
                continue;
            }
            if ($mode === 'order' && is_self_service_stock($row)) {
                continue;
            }
            $strXML .= array_to_XML($row);
        }
        $rs->free();
    }
    DBWrap::get_instance()->free_next_results();
    return '<rowset>' . $strXML . '</rowset>';
}
