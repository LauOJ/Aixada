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
    <title>La meva comanda &middot; La Vinagreta</title>
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
        .hdr-brand { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .hdr-brand img { height: 26px; display: block; }
        .hdr-brand span { font-size: 0.82rem; font-weight: 600; opacity: 0.9; }

        .app-main { padding: 16px; max-width: 520px; margin: 0 auto; }

        .list-btn {
            display: flex; align-items: center; gap: 12px; width: 100%;
            background: #fff; border: none; border-radius: 12px; padding: 18px 16px;
            font-size: 1.05rem; font-weight: 600; color: #222; text-align: left;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); cursor: pointer; margin-bottom: 10px;
        }
        .list-btn:active { transform: scale(0.98); }
        .list-btn .lb-label { flex: 1; }
        .list-btn .lb-total { color: #4a5f6f; font-weight: 600; font-size: 0.95rem; margin-right: 8px; }
        .list-btn .lb-arrow { color: #bbb; font-size: 1.1rem; transition: transform 0.15s; }
        .acc-header.open { border-radius: 12px 12px 0 0; margin-bottom: 0; }
        .acc-header.open .lb-arrow { transform: rotate(90deg); }
        .acc-body { margin: 0 0 10px; background: #fff; border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); overflow: hidden; }
        .acc-line { border-top: 1px solid #eef1f3; padding: 12px 16px; }
        .acc-line .al-top { display: flex; justify-content: space-between; gap: 10px; }
        .acc-line .al-name { font-weight: 600; font-size: 0.95rem; }
        .acc-line .al-sub { font-size: 0.8rem; color: #7a8894; margin-top: 2px; }

        .grand-total { display: flex; justify-content: space-between; font-size: 1.2rem;
            font-weight: 700; padding: 16px 4px; border-top: 2px solid #cdd5db; margin-top: 6px; }
        .empty-msg { text-align: center; color: #7a8894; padding: 40px 20px; }
        .spinner { text-align: center; color: #7a8894; padding: 30px; }
        .msg-error { background: #f8d7da; color: #842029; border-radius: 10px; padding: 12px 14px;
            margin-bottom: 12px; font-size: 0.9rem; }
    </style>
</head>
<body>

<header class="app-header">
    <span class="back" id="btn-back">&#8249;</span>
    <div class="title">La meva comanda</div>
    <div class="hdr-brand"><img src="../img/logo-vinagreta.png" alt="La Vinagreta"></div>
</header>

<main class="app-main">
    <div id="days-list"><div class="spinner">Carregant…</div></div>
    <div class="grand-total" id="grand-total" style="display:none"><span>Total general</span><span id="grand-total-val">0,00 &euro;</span></div>
</main>

<script>
(function () {
    'use strict';

    var CTRL  = '../php/ctrl/ShopAndOrder.php';
    var DATES = '../php/ctrl/Dates.php';

    function fmt(n) { return (Math.round(n * 100) / 100).toFixed(2).replace('.', ','); }
    function parseNum(s) { s = (s || '').toString().replace(',', '.'); var n = parseFloat(s); return isNaN(n) ? 0 : n; }
    function rowVal(row, tag) { return $(row).children(tag).text(); }

    var MESOS = ['gen', 'feb', 'mar', 'abr', 'mai', 'juny', 'jul', 'ag', 'set', 'oct', 'nov', 'des'];
    var DIES  = ['dg', 'dl', 'dt', 'dc', 'dj', 'dv', 'ds'];
    function formatDateLabel(ymd) {
        var p = ymd.split('-');
        if (p.length !== 3) return ymd;
        var dt = new Date(+p[0], +p[1] - 1, +p[2]);
        return DIES[dt.getDay()] + ' ' + (+p[2]) + ' ' + MESOS[+p[1] - 1] + ' ' + p[0];
    }

    function loadAll() {
        $.ajax({
            type: 'POST', url: DATES + '?oper=getOrderableDates&responseFormat=array', dataType: 'json'
        }).done(function (dates) {
            var days = [];
            $.each(dates, function (i, d) { if (d !== '1234-01-23') days.push(d); });
            if (days.length === 0) {
                $('#days-list').html('<div class="empty-msg">No hi ha comandes.</div>');
                return;
            }
            var results = {}, pending = days.length;
            $.each(days, function (i, d) {
                $.ajax({
                    type: 'POST', url: CTRL + '?oper=getOrderCart&date=' + encodeURIComponent(d), dataType: 'xml'
                }).done(function (xml) {
                    var items = [];
                    $(xml).find('row').each(function () {
                        if (rowVal(this, 'preorder') === 'true') return;
                        var qty = parseNum(rowVal(this, 'quantity'));
                        if (qty <= 0) return;
                        items.push({
                            name: rowVal(this, 'name'),
                            qty: qty,
                            price: parseNum(rowVal(this, 'unit_price')),
                            unit: rowVal(this, 'unit'),
                            provider_name: rowVal(this, 'provider_name')
                        });
                    });
                    results[d] = items;
                }).fail(function () { results[d] = []; })
                  .always(function () { pending--; if (pending === 0) render(days, results); });
            });
        }).fail(function (xhr) {
            $('#days-list').html('<div class="msg-error">No s\'han pogut carregar les comandes.<br>' + (xhr.responseText || '') + '</div>');
        });
    }

    function render(days, results) {
        var $list = $('#days-list').empty();
        var grand = 0, anyDay = false;
        $.each(days, function (i, d) {
            var items = results[d] || [];
            if (items.length === 0) return;
            anyDay = true;
            var dayTotal = 0;
            var $body = $('<div class="acc-body" style="display:none"></div>');
            $.each(items, function (j, it) {
                var line = it.qty * it.price;
                dayTotal += line;
                $body.append(
                    '<div class="acc-line"><div class="al-top">' +
                    '<span class="al-name">' + it.name + '</span>' +
                    '<span>' + fmt(line) + ' &euro;</span></div>' +
                    '<div class="al-sub">' + fmt(it.qty) + ' &times; ' + fmt(it.price) + ' &euro; / ' + it.unit +
                    ' &middot; ' + (it.provider_name || '') + '</div></div>'
                );
            });
            grand += dayTotal;
            var $header = $('<button class="list-btn acc-header">')
                .append('<span class="lb-label">' + formatDateLabel(d) + '</span>')
                .append('<span class="lb-total">' + fmt(dayTotal) + ' &euro;</span>')
                .append('<span class="lb-arrow">&rsaquo;</span>');
            $header.on('click', function () {
                if ($body.is(':visible')) { $body.slideUp(120); $header.removeClass('open'); }
                else { $header.addClass('open'); $body.slideDown(120); }
            });
            $list.append($header).append($body);
        });
        if (!anyDay) {
            $list.html('<div class="empty-msg">Encara no has fet cap comanda.</div>');
            return;
        }
        $('#grand-total-val').html(fmt(grand) + ' &euro;');
        $('#grand-total').show();
    }

    $('#btn-back').on('click', function () { window.location.href = 'index.php'; });

    loadAll();
}());
</script>
</body>
</html>
