<?php include "php/inc/header.inc.php" ?>
<?php $currentUfId = (int)get_session_value('uf_id'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $language ?>" lang="<?= $language ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= $Text['global_title'] ?> - Torns</title>
    <link rel="stylesheet" type="text/css" media="screen" href="css/aixada_main.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="css/ui-themes/<?= $default_theme ?>/jqueryui.css" />
    <?= aixada_custom_css() ?>
    <script type="text/javascript" src="js/jquery/jquery.js"></script>
    <script type="text/javascript" src="js/jqueryui/jqueryui.js"></script>
    <?= aixada_js_src() ?>
    <style>
        .intro-text         { color: #555; font-size: 0.9rem; margin: 0 0 20px; }
        .month-block        { margin-bottom: 28px; }
        .month-header       { background: #3a3a5c; color: white; padding: 8px 14px; font-size: 0.95rem;
                              font-weight: bold; text-transform: uppercase; letter-spacing: 0.07em;
                              border-radius: 4px 4px 0 0; }
        .torns-table        { width: 100%; border-collapse: collapse; border: 1px solid #ccc; border-top: none; }
        .torns-table thead th { background: #f0f0f4; padding: 5px 12px; font-size: 0.78rem;
                                text-transform: uppercase; color: #555; letter-spacing: 0.04em;
                                text-align: left; border-bottom: 2px solid #ccc; }
        .torns-table tbody tr:last-child td { border-bottom: none; }
        .torns-table td     { padding: 7px 12px; vertical-align: top; border-bottom: 1px solid #eee; }
        .week-cell          { width: 110px; background: #fafafa; }
        .week-dates         { font-weight: bold; font-size: 0.88rem; color: #333; }
        .week-rep-day       { font-size: 0.78rem; color: #2e7d32; margin-top: 3px; }
        .rep-cell           { border-left: 3px solid #2e7d32; }
        .net-cell           { border-left: 3px solid #1565c0; }
        .uf-entry           { display: flex; align-items: center; gap: 6px; padding: 2px 0; font-size: 0.88rem; }
        .uf-entry .uf-name  { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 180px; }
        .uf-entry.responsable .uf-name { font-weight: bold; color: #1b5e20; }
        .uf-entry.my-torn .uf-name { background: #fff9c4; padding: 0 4px; border-radius: 2px; }
        .resp-star          { color: #2e7d32; font-size: 0.85rem; min-width: 12px; text-align: center; }
        .no-data            { color: #bbb; font-size: 0.85rem; font-style: italic; }
        button.btn-canvia-sm {
            padding: 1px 6px; font-size: 0.75rem; cursor: pointer;
            border: 1px solid #ccc; background: #f5f5f5; border-radius: 2px; color: #666;
        }
        button.btn-canvia-sm:hover { background: #e8e8e8; border-color: #999; color: #333; }
        .no-torns           { color: #999; font-style: italic; font-size: 0.88rem; }
    </style>
</head>
<body>
<div id="wrap">
    <?php include "php/inc/menu.inc.php" ?>
    <div id="stagewrap" class="ui-widget <?= negative_balances_stagewrap_class() ?>">
        <div id="titlewrap"><h1>Torns</h1></div>
        <p class="intro-text">Si no pots fer el teu torn, coordina't amb algú i feu servir "canvia" per actualitzar l'assignació. El teu torn surt destacat en groc.</p>
        <div id="torns_list"><p class="no-torns">Carregant...</p></div>
    </div>
</div>

<script>
var allUfs = [];
var currentUfId = <?= $currentUfId ?>;
var monthNamesCat = ['Gener','Febrer','Març','Abril','Maig','Juny','Juliol','Agost','Setembre','Octubre','Novembre','Desembre'];
var dayNamesCat   = ['Dg','Dl','Dm','Dc','Dj','Dv','Ds'];
var shortMonths   = ['gen','feb','mar','abr','mai','jun','jul','ago','set','oct','nov','des'];

$(document).ready(function() {
    loadUfs(function() { loadUpcoming(); });
});

function loadUfs(callback) {
    $.post('php/ctrl/Torns.php', {oper:'getUfs'}, function(data) {
        allUfs = JSON.parse(data);
        if (callback) callback();
    });
}

function loadUpcoming() {
    $.post('php/ctrl/Torns.php', {oper:'getUpcoming'}, function(data) {
        renderUpcoming(JSON.parse(data));
    });
}

function renderUpcoming(weeks) {
    if (!weeks || weeks.length === 0) {
        $('#torns_list').html('<p class="no-torns">No hi ha torns programats.</p>');
        return;
    }

    // Group by month of week_start
    var months = {}, monthOrder = [];
    weeks.forEach(function(week) {
        var d   = new Date(week.week_start + 'T00:00:00');
        var key = d.getFullYear() + '-' + d.getMonth();
        if (!months[key]) {
            months[key] = {year: d.getFullYear(), month: d.getMonth(), weeks: []};
            monthOrder.push(key);
        }
        months[key].weeks.push(week);
    });

    var html = '';
    monthOrder.forEach(function(key) {
        var m = months[key];
        html += '<div class="month-block">'
              + '<div class="month-header">'+monthNamesCat[m.month]+' '+m.year+'</div>'
              + '<table class="torns-table">'
              + '<thead><tr><th>Setmana</th><th>Repartiment</th><th>Neteja</th></tr></thead>'
              + '<tbody>';

        m.weeks.forEach(function(week) {
            var s = new Date(week.week_start + 'T00:00:00');
            var e = new Date(week.week_end   + 'T00:00:00');
            var weekLabel = s.getMonth() === e.getMonth()
                ? s.getDate() + '–' + e.getDate() + ' ' + shortMonths[e.getMonth()]
                : s.getDate() + ' ' + shortMonths[s.getMonth()] + ' – ' + e.getDate() + ' ' + shortMonths[e.getMonth()];

            var repDayLabel = '';
            if (week.repartiment && week.repartiment.length > 0) {
                var rd = new Date(week.repartiment[0].date + 'T00:00:00');
                repDayLabel = '<div class="week-rep-day">'+dayNamesCat[rd.getDay()]+' '+rd.getDate()+'/'+(rd.getMonth()+1)+'</div>';
            }

            // Repartiment column
            var repHtml = '';
            if (week.repartiment && week.repartiment.length > 0) {
                week.repartiment.forEach(function(entry) {
                    var isMine = (entry.uf_id === currentUfId);
                    var isResp = !!entry.is_responsible;
                    var cls    = 'uf-entry' + (isResp ? ' responsable' : '') + (isMine ? ' my-torn' : '');
                    var star   = '<span class="resp-star">'+(isResp ? '★' : '')+'</span>';
                    repHtml += '<div class="'+cls+'" data-date="'+entry.date+'" data-uf="'+entry.uf_id+'" data-task="repartiment">'
                             + star
                             + '<span class="uf-name">'+entry.name+'</span>'
                             + ' <button class="btn-canvia-sm" onclick="showEdit(this)">canvia</button>'
                             + '</div>';
                });
            } else {
                repHtml = '<span class="no-data">—</span>';
            }

            // Neteja column
            var netHtml = '';
            if (week.neteja && week.neteja.length > 0) {
                week.neteja.forEach(function(entry) {
                    var isMine = (entry.uf_id === currentUfId);
                    var cls    = 'uf-entry' + (isMine ? ' my-torn' : '');
                    netHtml += '<div class="'+cls+'" data-date="'+entry.date+'" data-uf="'+entry.uf_id+'" data-task="neteja">'
                             + '<span class="resp-star"></span>'
                             + '<span class="uf-name">'+entry.name+'</span>'
                             + ' <button class="btn-canvia-sm" onclick="showEdit(this)">canvia</button>'
                             + '</div>';
                });
            } else {
                netHtml = '<span class="no-data">—</span>';
            }

            html += '<tr>'
                  + '<td class="week-cell"><div class="week-dates">'+weekLabel+'</div>'+repDayLabel+'</td>'
                  + '<td class="rep-cell">'+repHtml+'</td>'
                  + '<td class="net-cell">'+netHtml+'</td>'
                  + '</tr>';
        });

        html += '</tbody></table></div>';
    });
    $('#torns_list').html(html);
}

function showEdit(btn) {
    var entry  = $(btn).closest('.uf-entry');
    var date   = entry.data('date');
    var old_uf = entry.data('uf');
    var task   = entry.data('task');

    if (entry.find('select.edit-select').length) {
        entry.find('select.edit-select, .btn-confirm-edit').remove();
        return;
    }

    var sel = $('<select class="edit-select" style="font-size:0.82rem;max-width:160px"></select>');
    allUfs.forEach(function(uf) {
        sel.append($('<option>').val(uf.id).text(uf.id + ' - ' + uf.name));
    });
    sel.val(old_uf);

    var btnOk = $('<button class="btn-confirm-edit" style="padding:1px 6px;font-size:0.78rem">OK</button>').click(function() {
        var new_uf = parseInt(sel.val());
        if (new_uf === old_uf) { sel.remove(); $(this).remove(); return; }
        $.post('php/ctrl/Torns.php', {oper:'updateTorn', date:date, old_uf:old_uf, new_uf:new_uf, task:task},
            function() { loadUpcoming(); });
    });
    entry.append(sel).append(btnOk);
}
</script>
</body>
</html>
