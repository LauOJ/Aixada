<?php
if (!defined('DS'))       define('DS', DIRECTORY_SEPARATOR);
if (!defined('__ROOT__')) define('__ROOT__', dirname(__DIR__) . DS);
include __ROOT__ . 'php/inc/header.inc.php';

$uf_id = get_session_value('uf_id');
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estoc &middot; La Vinagreta</title>
    <script src="../js/jquery/jquery.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f4; color: #333; min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }
        .app-header {
            background: #4a5f6f; color: #fff; padding: 14px 16px;
            display: flex; align-items: center; gap: 12px;
            position: sticky; top: 0; z-index: 20;
        }
        .app-header .back { font-size: 1.4rem; line-height: 1; cursor: pointer; padding: 2px 6px; }
        .app-header .title { font-weight: 600; font-size: 1.05rem; flex: 1; }
        .app-header .step-label { font-size: 0.72rem; opacity: 0.8; }

        .app-main { padding: 16px; max-width: 520px; margin: 0 auto; }
        .step { display: none; }
        .step.active { display: block; }

        .section-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;
            color: #7a8894; margin: 4px 2px 12px; }
        .context-bar { background: #e4e9ed; border-radius: 10px; padding: 10px 14px;
            font-size: 0.9rem; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
        .context-bar strong { color: #2f3e4a; }

        .list-btn {
            display: flex; align-items: center; gap: 12px; width: 100%;
            background: #fff; border: none; border-radius: 12px; padding: 18px 16px;
            font-size: 1.05rem; font-weight: 600; color: #222; text-align: left;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); cursor: pointer; margin-bottom: 10px;
        }
        .list-btn:active { transform: scale(0.98); }
        .list-btn .lb-label { flex: 1; }
        .list-btn .lb-arrow { color: #bbb; font-size: 1.1rem; }

        .product-row {
            background: #fff; border-radius: 12px; padding: 12px 14px; margin-bottom: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 12px;
        }
        .product-row.out { opacity: 0.55; }
        .prod-info { flex: 1; min-width: 0; }
        .prod-name { font-weight: 600; font-size: 0.98rem; }
        .prod-meta { font-size: 0.78rem; color: #7a8894; margin-top: 2px; }
        .prod-stock { font-size: 0.74rem; margin-top: 3px; }
        .prod-stock.low { color: #b04a3a; }
        .prod-stock.ok { color: #5c7a1e; }
        .qty-box { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .qty-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #cdd5db;
            background: #f6f8f9; font-size: 1.3rem; line-height: 1; color: #4a5f6f; cursor: pointer; }
        .qty-btn:active { background: #e4e9ed; }
        .qty-input { width: 52px; height: 34px; text-align: center; font-size: 1rem;
            border: 1px solid #cdd5db; border-radius: 8px; }

        .summary-item { background: #fff; border-radius: 12px; padding: 12px 14px; margin-bottom: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .summary-item .si-top { display: flex; justify-content: space-between; gap: 10px; }
        .summary-item .si-name { font-weight: 600; }
        .summary-item .si-sub { font-size: 0.8rem; color: #7a8894; margin-top: 2px; }
        .summary-total { display: flex; justify-content: space-between; font-size: 1.15rem;
            font-weight: 700; padding: 14px 4px; }

        .action-btn { display: block; width: 100%; border: none; border-radius: 12px;
            padding: 16px; font-size: 1.05rem; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .action-primary { background: #6b8e23; color: #fff; }
        .action-primary:active { background: #5c7a1e; }
        .action-secondary { background: #fff; color: #4a5f6f; border: 1px solid #cdd5db; }
        .action-primary:disabled { background: #b6c68f; cursor: default; }

        .sticky-footer { position: sticky; bottom: 0; background: #f0f2f4; padding: 10px 0 4px; margin-top: 8px; }
        .empty-msg { text-align: center; color: #7a8894; padding: 40px 20px; }
        .spinner { text-align: center; color: #7a8894; padding: 30px; }
        .msg-error { background: #f8d7da; color: #842029; border-radius: 10px; padding: 12px 14px;
            margin-bottom: 12px; font-size: 0.9rem; }
        .done-screen { text-align: center; padding: 50px 20px; }
        .done-screen .check { font-size: 3.5rem; }
        .done-screen h2 { margin: 16px 0 8px; color: #2f3e4a; }
        .done-screen p { color: #7a8894; margin-bottom: 24px; }
    </style>
</head>
<body>

<header class="app-header">
    <span class="back" id="btn-back">&#8249;</span>
    <div class="title">Estoc <div class="step-label" id="step-label"></div></div>
</header>

<main class="app-main">

    <!-- Pas 1: proveïdora -->
    <section class="step active" id="step-provider">
        <div class="section-title">Tria la proveïdora</div>
        <div id="provider-list"><div class="spinner">Carregant…</div></div>
        <div class="sticky-footer">
            <button class="action-btn action-primary" id="btn-view-summary">Veure la compra (<span id="cart-count-1">0</span>)</button>
        </div>
    </section>

    <!-- Pas 2: productes -->
    <section class="step" id="step-products">
        <div class="context-bar"><span><strong id="ctx-provider"></strong></span></div>
        <div class="section-title">Afegeix productes de l'estoc</div>
        <div id="product-list"><div class="spinner">Carregant productes…</div></div>
        <div class="sticky-footer">
            <button class="action-btn action-primary" id="btn-products-done">Fet, veure la compra</button>
        </div>
    </section>

    <!-- Pas 3: resum -->
    <section class="step" id="step-summary">
        <div class="section-title">Resum de la compra</div>
        <div id="summary-error"></div>
        <div id="summary-list"></div>
        <div class="summary-total"><span>Total</span><span id="summary-total-val">0,00 &euro;</span></div>
        <button class="action-btn action-secondary" id="btn-add-provider">+ Afegir d'una altra proveïdora</button>
        <div class="sticky-footer">
            <button class="action-btn action-primary" id="btn-validate">Validar la compra</button>
        </div>
    </section>

    <!-- Confirmació -->
    <section class="step" id="step-done">
        <div class="done-screen">
            <div class="check">&#9989;</div>
            <h2>Compra registrada!</h2>
            <p>La teva compra de l'estoc s'ha validat correctament.</p>
            <button class="action-btn action-primary" id="btn-done-home">Tornar a l'inici</button>
        </div>
    </section>

</main>

<script>
(function () {
    'use strict';

    var CTRL     = '../php/ctrl/ShopAndOrder.php';
    var VALIDATE = '../php/ctrl/Validate.php';
    var DATES    = '../php/ctrl/Dates.php';
    var SESSION_UF = <?= (int)$uf_id ?>;

    var today = null;
    var selectedProviderName = '';
    // cart: { product_id: {id,name,unit,price,iva,revtax,qty,provider_name} }
    var cart = {};

    function fmt(n) { return (Math.round(n * 100) / 100).toFixed(2).replace('.', ','); }
    function parseNum(s) { s = (s || '').toString().replace(',', '.'); var n = parseFloat(s); return isNaN(n) ? 0 : n; }
    function rowVal(row, tag) { return $(row).children(tag).text(); }

    function showStep(id) {
        $('.step').removeClass('active');
        $('#step-' + id).addClass('active');
        window.scrollTo(0, 0);
        var labels = { provider: 'Pas 1 de 3', products: 'Pas 2 de 3', summary: 'Resum', done: '' };
        $('#step-label').text(labels[id] || '');
    }

    function cartCount() {
        var n = 0;
        for (var k in cart) { if (cart.hasOwnProperty(k) && cart[k].qty > 0) n++; }
        return n;
    }
    function refreshCartCount() { $('#cart-count-1').text(cartCount()); }

    // ── Inici: data d'avui, després proveïdores ──
    function init() {
        $.ajax({ type: 'POST', url: DATES + '?oper=getToday&responseFormat=array', dataType: 'json' })
            .done(function (dates) { today = (dates && dates[0]) ? dates[0] : null; })
            .always(function () { loadProviders(); });
    }

    // ── Pas 1: proveïdores ──
    function loadProviders() {
        var $list = $('#provider-list').html('<div class="spinner">Carregant…</div>');
        $.ajax({ type: 'POST', url: CTRL + '?oper=getShopProviders', dataType: 'xml' })
        .done(function (xml) {
            $list.empty();
            var rows = $(xml).find('row');
            if (rows.length === 0) { $list.html('<div class="empty-msg">No hi ha proveïdores amb estoc.</div>'); return; }
            rows.each(function () {
                var id = rowVal(this, 'id'), name = rowVal(this, 'name');
                if (!id || id < 0) return;
                $('<button class="list-btn">')
                    .append('<span class="lb-label">' + name + '</span>')
                    .append('<span class="lb-arrow">&rsaquo;</span>')
                    .on('click', function () { selectProvider(id, name); })
                    .appendTo($list);
            });
        })
        .fail(function () { $list.html('<div class="msg-error">No s\'han pogut carregar les proveïdores.</div>'); });
    }

    // ── Pas 2: productes ──
    function selectProvider(id, name) {
        selectedProviderName = name;
        $('#ctx-provider').text(name);
        showStep('products');
        var $list = $('#product-list').html('<div class="spinner">Carregant productes…</div>');
        $.ajax({ type: 'POST', url: CTRL + '?oper=getToShopProducts&provider_id=' + id + '&date=0', dataType: 'xml' })
        .done(function (xml) {
            $list.empty();
            var rows = $(xml).find('row');
            if (rows.length === 0) { $list.html('<div class="empty-msg">Aquesta proveïdora no té productes en estoc.</div>'); return; }
            rows.each(function () {
                var typeId = rowVal(this, 'orderable_type_id');
                if (typeId === '3') return;   // productes-nota: no gestionats aquí
                var prod = {
                    id: rowVal(this, 'id'),
                    name: rowVal(this, 'name'),
                    unit: rowVal(this, 'unit'),
                    price: parseNum(rowVal(this, 'unit_price')),
                    iva: parseNum(rowVal(this, 'iva_percent')),
                    revtax: parseNum(rowVal(this, 'rev_tax_percent')),
                    stock: parseNum(rowVal(this, 'stock_actual')),
                    provider_name: name
                };
                renderProductRow($list, prod);
            });
        })
        .fail(function () { $list.html('<div class="msg-error">No s\'han pogut carregar els productes.</div>'); });
    }

    function renderProductRow($list, prod) {
        var current = cart[prod.id] ? cart[prod.id].qty : 0;
        var outOfStock = prod.stock <= 0;
        var $row = $('<div class="product-row">');
        if (outOfStock) $row.addClass('out');
        var $info = $('<div class="prod-info">')
            .append('<div class="prod-name">' + prod.name + '</div>')
            .append('<div class="prod-meta">' + fmt(prod.price) + ' &euro; / ' + prod.unit + '</div>')
            .append('<div class="prod-stock ' + (outOfStock ? 'low' : 'ok') + '">Estoc: ' + fmt(prod.stock) + ' ' + prod.unit + '</div>');
        $row.append($info);

        if (outOfStock) { $list.append($row); return; }

        var $qtyInput = $('<input class="qty-input" type="text" inputmode="decimal">').val(current > 0 ? fmt(current) : '');
        var $box = $('<div class="qty-box">');
        $('<button class="qty-btn" type="button">&minus;</button>').on('click', function () {
            var v = Math.max(0, parseNum($qtyInput.val()) - 1);
            $qtyInput.val(v > 0 ? fmt(v) : ''); updateCartItem(prod, v);
        }).appendTo($box);
        $box.append($qtyInput);
        $('<button class="qty-btn" type="button">+</button>').on('click', function () {
            var v = parseNum($qtyInput.val()) + 1;
            $qtyInput.val(fmt(v)); updateCartItem(prod, v);
        }).appendTo($box);
        $qtyInput.on('change', function () {
            var v = Math.max(0, parseNum($qtyInput.val()));
            $qtyInput.val(v > 0 ? fmt(v) : ''); updateCartItem(prod, v);
        });
        $row.append($box);
        $list.append($row);
    }

    function updateCartItem(prod, qty) {
        if (qty > 0) {
            cart[prod.id] = $.extend({}, prod, { qty: qty });
        } else if (cart[prod.id]) {
            delete cart[prod.id];
        }
        refreshCartCount();
    }

    // ── Pas 3: resum ──
    function renderSummary() {
        var $list = $('#summary-list').empty();
        var total = 0, count = 0;
        for (var k in cart) {
            if (!cart.hasOwnProperty(k)) continue;
            var it = cart[k];
            if (it.qty <= 0) continue;
            count++;
            var line = it.qty * it.price;
            total += line;
            $('<div class="summary-item">').append(
                '<div class="si-top"><span class="si-name">' + it.name + '</span>' +
                '<span>' + fmt(line) + ' &euro;</span></div>' +
                '<div class="si-sub">' + fmt(it.qty) + ' &times; ' + fmt(it.price) + ' &euro; / ' + it.unit +
                ' &middot; ' + (it.provider_name || '') + '</div>'
            ).appendTo($list);
        }
        if (count === 0) $list.html('<div class="empty-msg">Encara no has afegit cap producte.</div>');
        $('#summary-total-val').html(fmt(total) + ' &euro;');
    }

    // ── Validar: commit (crea carret) → head (ts_last_saved) → validate (finalitza) ──
    function buildItemArrays() {
        var data = { quantity: [], product_id: [], price: [], iva_percent: [], rev_tax_percent: [], preorder: [] };
        for (var k in cart) {
            if (!cart.hasOwnProperty(k)) continue;
            var it = cart[k];
            if (it.qty <= 0) continue;
            data.quantity.push(it.qty);
            data.product_id.push(it.id);
            data.price.push(it.price);
            data.iva_percent.push(it.iva);
            data.rev_tax_percent.push(it.revtax);
            data.preorder.push('false');
        }
        return data;
    }

    function validatePurchase() {
        $('#summary-error').empty();
        if (cartCount() === 0) {
            $('#summary-error').html('<div class="msg-error">No has afegit cap producte.</div>');
            return;
        }
        var $btn = $('#btn-validate').prop('disabled', true).text('Validant…');
        var items = buildItemArrays();

        function fail(msg) {
            $('#summary-error').html('<div class="msg-error">' + msg + '</div>');
            $btn.prop('disabled', false).text('Validar la compra');
        }

        // Pas 1: commit del carret de botiga
        var commitData = $.extend({ what: 'Shop', oper: 'commit', date: today, cart_id: 0, ts_last_saved: 0 }, items);
        $.ajax({ type: 'POST', url: CTRL, data: commitData })
        .done(function (cartId) {
            cartId = $.trim(String(cartId));
            // Pas 2: obtenir ts_last_saved del carret
            $.ajax({ type: 'POST', url: VALIDATE + '?oper=getShopCartHead&cart_id=' + encodeURIComponent(cartId), dataType: 'xml' })
            .done(function (xml) {
                var ts = $(xml).find('ts_last_saved').first().text();
                // Pas 3: validar (descompta estoc i cobra)
                var valData = $.extend({ cart_id: cartId, ts_last_saved: ts }, items);
                $.ajax({
                    type: 'POST',
                    url: VALIDATE + '?oper=commit&uf_id=' + SESSION_UF + '&date=' + encodeURIComponent(today),
                    data: valData
                })
                .done(function () {
                    cart = {}; refreshCartCount();
                    showStep('done');
                })
                .fail(function (xhr) { fail('No s\'ha pogut validar: ' + (xhr.responseText || 'error')); });
            })
            .fail(function () { fail('No s\'ha pogut llegir el carret per validar.'); });
        })
        .fail(function (xhr) { fail('No s\'ha pogut desar la compra: ' + (xhr.responseText || 'error')); });
    }

    // ── Navegació ──
    $('#btn-view-summary').on('click', function () { renderSummary(); showStep('summary'); });
    $('#btn-products-done').on('click', function () { renderSummary(); showStep('summary'); });
    $('#btn-add-provider').on('click', function () { showStep('provider'); });
    $('#btn-validate').on('click', validatePurchase);
    $('#btn-done-home').on('click', function () { window.location.href = 'index.php'; });

    $('#btn-back').on('click', function () {
        var cur = $('.step.active').attr('id');
        if (cur === 'step-products') { showStep('provider'); }
        else if (cur === 'step-summary') { showStep('provider'); }
        else { window.location.href = 'index.php'; }
    });

    // Inici
    showStep('provider');
    init();
}());
</script>
</body>
</html>
