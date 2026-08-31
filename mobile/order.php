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
    <title>Fer comanda &middot; La Vinagreta</title>
    <script src="../js/jquery/jquery.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f4; color: #333; min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }

        /* ── Capçalera ── */
        .app-header {
            background: #4a5f6f; color: #fff; padding: 14px 16px;
            display: flex; align-items: center; gap: 12px;
            position: sticky; top: 0; z-index: 20;
        }
        .app-header .back { font-size: 1.4rem; line-height: 1; cursor: pointer; padding: 2px 6px; }
        .app-header .title { font-weight: 600; font-size: 1.05rem; flex: 1; }
        .app-header .step-label { font-size: 0.72rem; opacity: 0.8; }

        /* ── Cos ── */
        .app-main { padding: 16px; max-width: 520px; margin: 0 auto; }
        .step { display: none; }
        .step.active { display: block; }

        .section-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;
            color: #7a8894; margin: 4px 2px 12px; }
        .context-bar { background: #e4e9ed; border-radius: 10px; padding: 10px 14px;
            font-size: 0.9rem; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
        .context-bar strong { color: #2f3e4a; }

        /* ── Botons de llista (data/proveïdora) ── */
        .list-btn {
            display: flex; align-items: center; gap: 12px; width: 100%;
            background: #fff; border: none; border-radius: 12px; padding: 18px 16px;
            font-size: 1.05rem; font-weight: 600; color: #222; text-align: left;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); cursor: pointer; margin-bottom: 10px;
        }
        .list-btn:active { transform: scale(0.98); }
        .list-btn .lb-label { flex: 1; }
        .list-btn .lb-arrow { color: #bbb; font-size: 1.1rem; }
        .list-btn .lb-count { background: #6b8e23; color: #fff; border-radius: 20px;
            font-size: 0.78rem; padding: 2px 9px; font-weight: 600; }

        /* ── Productes ── */
        .product-row {
            background: #fff; border-radius: 12px; padding: 12px 14px; margin-bottom: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 12px;
        }
        .product-row.locked { opacity: 0.55; }
        .prod-info { flex: 1; min-width: 0; }
        .prod-name { font-weight: 600; font-size: 0.98rem; }
        .prod-meta { font-size: 0.78rem; color: #7a8894; margin-top: 2px; }
        .prod-locked-tag { font-size: 0.72rem; color: #b04a3a; margin-top: 3px; }
        .qty-box { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .qty-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #cdd5db;
            background: #f6f8f9; font-size: 1.3rem; line-height: 1; color: #4a5f6f; cursor: pointer; }
        .qty-btn:active { background: #e4e9ed; }
        .qty-input { width: 52px; height: 34px; text-align: center; font-size: 1rem;
            border: 1px solid #cdd5db; border-radius: 8px; }

        /* ── Resum ── */
        .summary-item { background: #fff; border-radius: 12px; padding: 12px 14px; margin-bottom: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .summary-item .si-top { display: flex; justify-content: space-between; gap: 10px; }
        .summary-item .si-name { font-weight: 600; }
        .summary-item .si-sub { font-size: 0.8rem; color: #7a8894; margin-top: 2px; }
        .summary-item.locked { opacity: 0.6; }
        .summary-total { display: flex; justify-content: space-between; font-size: 1.15rem;
            font-weight: 700; padding: 14px 4px; }

        /* ── Accions ── */
        .action-btn { display: block; width: 100%; border: none; border-radius: 12px;
            padding: 16px; font-size: 1.05rem; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .action-primary { background: #6b8e23; color: #fff; }
        .action-primary:active { background: #5c7a1e; }
        .action-secondary { background: #fff; color: #4a5f6f; border: 1px solid #cdd5db; }
        .action-primary:disabled { background: #b6c68f; cursor: default; }

        .sticky-footer { position: sticky; bottom: 0; background: #f0f2f4;
            padding: 10px 0 4px; margin-top: 8px; }

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
    <div class="title">Fer comanda <div class="step-label" id="step-label"></div></div>
</header>

<main class="app-main">

    <!-- Pas 1: data -->
    <section class="step active" id="step-date">
        <div class="section-title">Tria el dia de comanda</div>
        <div id="date-list"><div class="spinner">Carregant dates…</div></div>
    </section>

    <!-- Pas 2: proveïdora -->
    <section class="step" id="step-provider">
        <div class="context-bar"><span>Dia: <strong id="ctx-date-1"></strong></span></div>
        <div class="section-title">Tria la proveïdora</div>
        <div id="provider-list"><div class="spinner">Carregant proveïdores…</div></div>
        <div class="sticky-footer">
            <button class="action-btn action-primary" id="btn-view-summary">Veure la comanda (<span id="cart-count-1">0</span>)</button>
        </div>
    </section>

    <!-- Pas 3: productes -->
    <section class="step" id="step-products">
        <div class="context-bar">
            <span>Dia: <strong id="ctx-date-2"></strong></span>
            <span><strong id="ctx-provider"></strong></span>
        </div>
        <div class="section-title">Afegeix productes</div>
        <div id="product-list"><div class="spinner">Carregant productes…</div></div>
        <div class="sticky-footer">
            <button class="action-btn action-primary" id="btn-products-done">Fet, veure la comanda</button>
        </div>
    </section>

    <!-- Pas 4: resum -->
    <section class="step" id="step-summary">
        <div class="context-bar"><span>Dia: <strong id="ctx-date-3"></strong></span></div>
        <div class="section-title">Resum de la comanda</div>
        <div id="summary-error"></div>
        <div id="summary-list"></div>
        <div class="summary-total"><span>Total</span><span id="summary-total-val">0,00 &euro;</span></div>
        <button class="action-btn action-secondary" id="btn-add-provider">+ Demanar d'una altra proveïdora</button>
        <div class="sticky-footer">
            <button class="action-btn action-primary" id="btn-save">Desar la comanda</button>
        </div>
    </section>

    <!-- Confirmació -->
    <section class="step" id="step-done">
        <div class="done-screen">
            <div class="check">&#9989;</div>
            <h2>Comanda desada!</h2>
            <p>La teva comanda per al <strong id="done-date"></strong> s'ha guardat correctament.</p>
            <button class="action-btn action-primary" id="btn-done-home">Tornar a l'inici</button>
            <button class="action-btn action-secondary" id="btn-done-edit">Seguir editant</button>
        </div>
    </section>

</main>

<script>
(function () {
    'use strict';

    var CTRL   = '../php/ctrl/ShopAndOrder.php';
    var DATES  = '../php/ctrl/Dates.php';

    // Estat
    var selectedDate = null;
    var selectedProviderName = '';
    // cart: { product_id: {id,name,unit,price,iva,revtax,qty,notes,provider_name,locked} }
    var cart = {};

    // ── Utils ──
    function fmt(n) { return (Math.round(n * 100) / 100).toFixed(2).replace('.', ','); }
    function parseNum(s) { s = (s || '').toString().replace(',', '.'); var n = parseFloat(s); return isNaN(n) ? 0 : n; }
    function rowVal(row, tag) { return $(row).children(tag).text(); }

    function showStep(id) {
        $('.step').removeClass('active');
        $('#step-' + id).addClass('active');
        window.scrollTo(0, 0);
        var labels = { date: 'Pas 1 de 4', provider: 'Pas 2 de 4', products: 'Pas 3 de 4', summary: 'Resum', done: '' };
        $('#step-label').text(labels[id] || '');
    }

    function cartCount() {
        var n = 0;
        for (var k in cart) { if (cart.hasOwnProperty(k) && cart[k].qty > 0) n++; }
        return n;
    }
    function refreshCartCount() { $('#cart-count-1').text(cartCount()); }

    // ── Pas 1: dates ──
    function loadDates() {
        $.ajax({
            type: 'POST', url: DATES + '?oper=getOrderableDates&responseFormat=array', dataType: 'json'
        }).done(function (dates) {
            var $list = $('#date-list').empty();
            var shown = 0;
            $.each(dates, function (i, d) {
                if (d === '1234-01-23') return;   // comanda oberta / preorder: no gestionat a mòbil v1
                shown++;
                var label = formatDateLabel(d);
                $('<button class="list-btn">')
                    .append('<span class="lb-label">' + label + '</span>')
                    .append('<span class="lb-arrow">&rsaquo;</span>')
                    .on('click', function () { selectDate(d); })
                    .appendTo($list);
            });
            if (shown === 0) $list.html('<div class="empty-msg">No hi ha cap dia obert per fer comanda ara mateix.</div>');
        }).fail(function (xhr) {
            $('#date-list').html('<div class="msg-error">No s\'han pogut carregar les dates.<br>' + (xhr.responseText || '') + '</div>');
        });
    }

    var MESOS = ['gen', 'feb', 'mar', 'abr', 'mai', 'juny', 'jul', 'ag', 'set', 'oct', 'nov', 'des'];
    var DIES  = ['dg', 'dl', 'dt', 'dc', 'dj', 'dv', 'ds'];
    function formatDateLabel(ymd) {
        var p = ymd.split('-');
        if (p.length !== 3) return ymd;
        var dt = new Date(+p[0], +p[1] - 1, +p[2]);
        return DIES[dt.getDay()] + ' ' + (+p[2]) + ' ' + MESOS[+p[1] - 1] + ' ' + p[0];
    }

    function selectDate(d) {
        selectedDate = d;
        var label = formatDateLabel(d);
        $('#ctx-date-1, #ctx-date-2, #ctx-date-3').text(label);
        cart = {};
        loadExistingCart(d, function () {
            loadProviders(d);
            showStep('provider');
        });
    }

    // ── Carrega la comanda existent d'aquell dia (per no perdre res en desar) ──
    function loadExistingCart(d, cb) {
        $.ajax({
            type: 'POST', url: CTRL + '?oper=getOrderCart&date=' + encodeURIComponent(d), dataType: 'xml'
        }).done(function (xml) {
            $(xml).find('row').each(function () {
                var preorder = rowVal(this, 'preorder');
                if (preorder === 'true') return;   // ítems de comanda oberta: no gestionat aquí
                var pid = rowVal(this, 'id');
                var qty = parseNum(rowVal(this, 'quantity'));
                if (qty <= 0) return;
                var orderId  = $.trim(rowVal(this, 'order_id'));
                var timeLeft = parseNum(rowVal(this, 'time_left'));
                var locked = (orderId !== '' && orderId !== '0') || timeLeft < 0;
                cart[pid] = {
                    id: pid,
                    name: rowVal(this, 'name'),
                    unit: rowVal(this, 'unit'),
                    price: parseNum(rowVal(this, 'unit_price')),
                    iva: parseNum(rowVal(this, 'iva_percent')),
                    revtax: parseNum(rowVal(this, 'rev_tax_percent')),
                    qty: qty,
                    notes: rowVal(this, 'notes') || '',
                    provider_name: rowVal(this, 'provider_name'),
                    locked: locked
                };
            });
            refreshCartCount();
            if (cb) cb();
        }).fail(function () { if (cb) cb(); });
    }

    // ── Pas 2: proveïdores ──
    function loadProviders(d) {
        var $list = $('#provider-list').html('<div class="spinner">Carregant proveïdores…</div>');
        $.ajax({
            type: 'POST', url: CTRL + '?oper=getOrderProviders&date=' + encodeURIComponent(d), dataType: 'xml'
        }).done(function (xml) {
            $list.empty();
            var rows = $(xml).find('row');
            if (rows.length === 0) { $list.html('<div class="empty-msg">No hi ha proveïdores obertes per aquest dia.</div>'); return; }
            rows.each(function () {
                var id = rowVal(this, 'id'), name = rowVal(this, 'name');
                if (!id || id < 0) return;
                $('<button class="list-btn">')
                    .append('<span class="lb-label">' + name + '</span>')
                    .append('<span class="lb-arrow">&rsaquo;</span>')
                    .on('click', function () { selectProvider(id, name); })
                    .appendTo($list);
            });
        }).fail(function (xhr) {
            $list.html('<div class="msg-error">No s\'han pogut carregar les proveïdores.</div>');
        });
    }

    // ── Pas 3: productes ──
    function selectProvider(id, name) {
        selectedProviderName = name;
        $('#ctx-provider').text(name);
        showStep('products');
        var $list = $('#product-list').html('<div class="spinner">Carregant productes…</div>');
        $.ajax({
            type: 'POST',
            url: CTRL + '?oper=getToOrderProducts&provider_id=' + id + '&date=' + encodeURIComponent(selectedDate),
            dataType: 'xml'
        }).done(function (xml) {
            $list.empty();
            var rows = $(xml).find('row');
            if (rows.length === 0) { $list.html('<div class="empty-msg">Aquesta proveïdora no té productes actius.</div>'); return; }
            rows.each(function () {
                var typeId = rowVal(this, 'orderable_type_id');
                if (typeId === '3') return;   // productes-nota: no gestionats a mòbil v1
                var pid = rowVal(this, 'id');
                var timeLeft = parseNum(rowVal(this, 'time_left'));
                var prod = {
                    id: pid,
                    name: rowVal(this, 'name'),
                    unit: rowVal(this, 'unit'),
                    price: parseNum(rowVal(this, 'unit_price')),
                    iva: parseNum(rowVal(this, 'iva_percent')),
                    revtax: parseNum(rowVal(this, 'rev_tax_percent')),
                    provider_name: name
                };
                renderProductRow($list, prod, timeLeft < 0);
            });
        }).fail(function () {
            $list.html('<div class="msg-error">No s\'han pogut carregar els productes.</div>');
        });
    }

    function renderProductRow($list, prod, closed) {
        var current = cart[prod.id] ? cart[prod.id].qty : 0;
        var $row = $('<div class="product-row">');
        if (closed) $row.addClass('locked');
        var $info = $('<div class="prod-info">')
            .append('<div class="prod-name">' + prod.name + '</div>')
            .append('<div class="prod-meta">' + fmt(prod.price) + ' &euro; / ' + prod.unit + '</div>');
        $row.append($info);

        if (closed) {
            $info.append('<div class="prod-locked-tag">Comanda tancada</div>');
            $list.append($row);
            return;
        }

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
            cart[prod.id] = $.extend({}, prod, { qty: qty, notes: (cart[prod.id] && cart[prod.id].notes) || '', locked: false });
        } else if (cart[prod.id]) {
            delete cart[prod.id];
        }
        refreshCartCount();
    }

    // ── Pas 4: resum ──
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
            var $item = $('<div class="summary-item">');
            if (it.locked) $item.addClass('locked');
            $item.append(
                '<div class="si-top"><span class="si-name">' + it.name + '</span>' +
                '<span>' + fmt(line) + ' &euro;</span></div>' +
                '<div class="si-sub">' + fmt(it.qty) + ' &times; ' + fmt(it.price) + ' &euro; / ' + it.unit +
                ' &middot; ' + (it.provider_name || '') + (it.locked ? ' &middot; <em>tancada</em>' : '') + '</div>'
            );
            $list.append($item);
        }
        if (count === 0) $list.html('<div class="empty-msg">Encara no has afegit cap producte.</div>');
        $('#summary-total-val').html(fmt(total) + ' &euro;');
    }

    // ── Desar (commit de tot el cart) ──
    function saveOrder() {
        $('#summary-error').empty();
        var data = {
            what: 'Order', oper: 'commit', date: selectedDate,
            cart_id: 0, ts_last_saved: 0,
            quantity: [], product_id: [], price: [], iva_percent: [], rev_tax_percent: [], notes: [], preorder: []
        };
        for (var k in cart) {
            if (!cart.hasOwnProperty(k)) continue;
            var it = cart[k];
            if (it.locked) continue;        // comandes tancades: no s'envien
            if (it.qty <= 0) continue;
            data.quantity.push(it.qty);
            data.product_id.push(it.id);
            data.price.push(it.price);
            data.iva_percent.push(it.iva);
            data.rev_tax_percent.push(it.revtax);
            data.notes.push(it.notes || '');
            data.preorder.push('false');
        }

        var $btn = $('#btn-save').prop('disabled', true).text('Desant…');
        $.ajax({ type: 'POST', url: CTRL, data: data, traditional: false })
            .done(function () {
                showStep('done');
                $('#done-date').text(formatDateLabel(selectedDate));
            })
            .fail(function (xhr) {
                $('#summary-error').html('<div class="msg-error">No s\'ha pogut desar: ' +
                    (xhr.responseText || 'error desconegut') + '</div>');
            })
            .always(function () { $btn.prop('disabled', false).text('Desar la comanda'); });
    }

    // ── Navegació ──
    $('#btn-view-summary').on('click', function () { renderSummary(); showStep('summary'); });
    $('#btn-products-done').on('click', function () { renderSummary(); showStep('summary'); });
    $('#btn-add-provider').on('click', function () { showStep('provider'); });
    $('#btn-save').on('click', saveOrder);
    $('#btn-done-home').on('click', function () { window.location.href = 'index.php'; });
    $('#btn-done-edit').on('click', function () { renderSummary(); showStep('summary'); });

    $('#btn-back').on('click', function () {
        var cur = $('.step.active').attr('id');
        if (cur === 'step-date') { window.location.href = 'index.php'; }
        else if (cur === 'step-provider') { showStep('date'); }
        else if (cur === 'step-products') { showStep('provider'); }
        else if (cur === 'step-summary') { showStep('provider'); }
        else { window.location.href = 'index.php'; }
    });

    // Inici
    showStep('date');
    loadDates();
}());
</script>
</body>
</html>
