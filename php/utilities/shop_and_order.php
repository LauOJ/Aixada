<?php

require_once(__ROOT__ . 'local_config/config.php');
require_once(__ROOT__ . 'php/inc/database.php');
require_once(__ROOT__ . "php/utilities/general.php");
require_once(__ROOT__ . "php/utilities/sell_in_shop.php");

function create_empty_cart($uf_id, $date_for_shop) {
    if (!$uf_id || !$date_for_shop) {
        return 0;
    }
    $db = DBWrap::get_instance();
    $db->Execute(
        "insert into aixada_cart (
            uf_id, date_for_shop
        )
        values (
            {$uf_id}, '{$date_for_shop}'
        );"
    );
    return $db->last_insert_id();
}

function getSql_stock_providers()
{
    return "
        select distinct
            pv.id,
            pv.name
        from
            aixada_provider pv,
            aixada_product p
        where
            pv.active = 1
            and pv.id = p.provider_id
            and p.active = 1
            and p.sell_in_shop = 1
            and p.orderable_type_id in (1, 4)
        order by pv.name";
}

function getSql_shop_providers() {
    $sql = "select pv.id, pv.name
        from aixada_provider pv
	 	join aixada_product p on p.provider_id = pv.id
        where
            pv.active = 1
            and p.active = 1
            and p.sell_in_shop = 1
            and p.orderable_type_id in (1, 4)";
    $use_shop = get_config('use_shop', 'order_and_stock');
    if ($use_shop === false) {
        $sql .= " and 1=0";
    }
    return $sql . "
        group by pv.id, pv.name
        order by pv.name";
}

function getSql_shop_categories() {
    $sql = "select pc.id, pc.description
        from aixada_product_category pc
	 	join aixada_product p on p.category_id = pc.id
        join aixada_provider pv on pv.id = p.provider_id
        where
            pv.active = 1
            and p.active = 1
            and p.sell_in_shop = 1
            and p.orderable_type_id in (1, 4)";
    $use_shop = get_config('use_shop', 'order_and_stock');
    if ($use_shop === false) {
        $sql .= " and 1=0";
    }
    return $sql . "
        group by pc.id, pc.description
        order by pc.description";
}

function sql_order_date($date)
{
    $date = (string) $date;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return '0000-00-00';
    }
    return $date;
}

function getSql_orderable_provider_list()
{
    return "
        select distinct pv.id, pv.name
        from aixada_provider pv, aixada_product p
        where pv.active = 1
            and pv.id = p.provider_id
            and " . sql_can_be_ordered('p') . "
        order by pv.name";
}

function getSql_type_orderable_products($provider_id)
{
    $provider_id = (int) $provider_id;
    return "
        select
            p.id,
            p.name as name,
            pv.name as provider_name,
            p.active as is_active,
            (select count(*)
             from aixada_product_orderable_for_date po
             where po.product_id = p.id
               and po.date_for_order = '1234-01-23') as preorder
        from aixada_provider pv, aixada_product p
        where pv.id = {$provider_id}
            and p.provider_id = pv.id
            and pv.active = 1
            and " . sql_can_be_ordered('p') . "
        order by is_active desc, name asc";
}

function getSql_order_providers_for_date($date)
{
    $date = sql_order_date($date);
    return "
        select distinct pv.id, pv.name
        from aixada_provider pv
        join aixada_product p on p.provider_id = pv.id
        join aixada_product_orderable_for_date po on po.product_id = p.id
        where pv.active = 1
            and po.date_for_order = '{$date}'
            and " . sql_not_self_service_stock('p') . "
        order by pv.name";
}

function getSql_order_categories_for_date($date)
{
    $date = sql_order_date($date);
    return "
        select distinct pc.id, pc.description
        from aixada_provider pv
        join aixada_product p on p.provider_id = pv.id
        join aixada_product_orderable_for_date po on po.product_id = p.id
        join aixada_product_category pc on pc.id = p.category_id
        where pv.active = 1
            and p.active = 1
            and po.date_for_order = '{$date}'
            and " . sql_not_self_service_stock('p') . "
        order by pc.description";
}
?>
