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
        .week-block             { margin-bottom: 12px; border: 1px solid #ccc; border-radius: 4px; overflow: hidden; }
        .week-header            { background: #3a3a5c; color: white; padding: 5px 12px; font-weight: bold; font-size: 0.88rem; }
        .week-body              { padding: 8px 12px; }
        .task-label             { font-size: 0.75rem; font-weight: bold; color: #555; text-transform: uppercase; letter-spacing: 0.04em; margin: 6px 0 3px; }
        .torn-row               { display: flex; align-items: center; gap: 8px; margin-bottom: 3px; font-size: 0.88rem; padding: 2px 0; }
        .torn-row .uf-name      { min-width: 120px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .torn-row.responsable .uf-name { font-weight: bold; color: #1b5e20; }
        .responsable-badge      { background: #2e7d32; color: white; font-size: 0.68rem; padding: 1px 5px; border-radius: 10px; white-space: nowrap; }
        .neteja-row             { border-left: 3px solid #1565c0; padding-left: 8px; }
        .repartiment-row        { border-left: 3px solid #2e7d32; padding-left: 8px; }
        .my-torn .uf-name       { background: #fff9c4; padding: 0 4px; border-radius: 2px; }
        select.uf-select        { min-width: 140px; }
        .no-torns               { color: #999; font-style: italic; font-size: 0.85rem; margin: 4px 0; }
        .intro-text             { color: #555; font-size: 0.9rem; margin: 0 0 16px; }
        button {
            padding: 2px 9px; font-size: 0.82rem; cursor: pointer;
            border: 1px solid #aaa; background: linear-gradient(to bottom, #f7f7f7, #e4e4e4);
            border-radius: 3px; color: #333;
        }
        button:hover  { background: linear-gradient(to bottom, #ececec, #d8d8d8); border-color: #888; }
        button:active { background: #d0d0d0; }
    </style>
</head>
<body>
<div id="wrap">
    <?php include "php/inc/menu.inc.php" ?>
    <div id="stagewrap" class="ui-widget <?= negative_balances_stagewrap_class() ?>">
        <div id="titlewrap"><h1>Torns</h1></div>
        <p class="intro-text">Si no pots fer el teu torn, coordina't amb algú i feu servir "Canvia" per actualitzar l'assignació.</p>
        <div id="torns_list"><p class="no-torns">Carregant...</p></div>
    </div>
</div>

<script>
var allUfs = [];
var currentUfId = <?= $currentUfId ?>;
var dayNamesCat = ['Diumenge','Dilluns','Dimarts','Dimecres','Dijous','Divendres','Dissabte'];

$(document).ready(function() {
    loadUfs(function() {
        loadUpcoming();
    });
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
    var html = '';
    weeks.forEach(function(week) {
        var from = formatDate(week.week_start), to = formatDate(week.week_end);
        var repDateStr = '';
        if (week.repartiment && week.repartiment.length > 0) {
            var d = new Date(week.repartiment[0].date + 'T00:00:00');
            repDateStr = ' <span style="font-weight:normal;font-size:0.8rem;opacity:0.8">('+dayNamesCat[d.getDay()]+' '+d.getDate()+'/'+(d.getMonth()+1)+')</span>';
        }
        html += '<div class="week-block">'
              + '<div class="week-header">Del '+from+' al '+to+repDateStr+'</div>'
              + '<div class="week-body">';

        if (week.repartiment && week.repartiment.length > 0) {
            html += '<div class="task-label" style="color:#2e7d32">Repartiment</div>';
            week.repartiment.forEach(function(entry) {
                var isMine = (entry.uf_id === currentUfId);
                var resp   = entry.is_responsible ? '<span class="responsable-badge">Responsable</span>' : '';
                var cls    = 'torn-row repartiment-row' + (entry.is_responsible ? ' responsable' : '') + (isMine ? ' my-torn' : '');
                html += '<div class="'+cls+'" data-date="'+entry.date+'" data-uf="'+entry.uf_id+'" data-task="repartiment">'
                      + '<span class="uf-name">'+entry.uf_id+' - '+entry.name+'</span>'
                      + resp
                      + ' <button class="btn-canviar" onclick="showEdit(this)">Canvia</button>'
                      + '</div>';
            });
        }

        if (week.neteja && week.neteja.length > 0) {
            html += '<div class="task-label" style="color:#1565c0">Neteja</div>';
            week.neteja.forEach(function(entry) {
                var isMine = (entry.uf_id === currentUfId);
                var cls    = 'torn-row neteja-row' + (isMine ? ' my-torn' : '');
                html += '<div class="'+cls+'" data-date="'+entry.date+'" data-uf="'+entry.uf_id+'" data-task="neteja">'
                      + '<span class="uf-name">'+entry.uf_id+' - '+entry.name+'</span>'
                      + ' <button class="btn-canviar" onclick="showEdit(this)">Canvia</button>'
                      + '</div>';
            });
        }

        if ((!week.repartiment || !week.repartiment.length) && (!week.neteja || !week.neteja.length)) {
            html += '<p class="no-torns">Sense torns assignats.</p>';
        }
        html += '</div></div>';
    });
    $('#torns_list').html(html);
}

function showEdit(btn) {
    var row    = $(btn).closest('.torn-row');
    var date   = row.data('date');
    var old_uf = row.data('uf');
    var task   = row.data('task');

    if (row.find('select.edit-select').length) {
        row.find('select.edit-select, .btn-confirm-edit').remove();
        return;
    }

    var sel = $('<select class="edit-select uf-select"></select>');
    allUfs.forEach(function(uf) {
        sel.append($('<option>').val(uf.id).text(uf.id+' - '+uf.name));
    });
    sel.val(old_uf);
    var btnOk = $('<button class="btn-confirm-edit">OK</button>').click(function() {
        var new_uf = parseInt(sel.val());
        if (new_uf === old_uf) { sel.remove(); $(this).remove(); return; }
        $.post('php/ctrl/Torns.php', {oper:'updateTorn', date:date, old_uf:old_uf, new_uf:new_uf, task:task},
            function() { loadUpcoming(); });
    });
    row.append(sel).append(btnOk);
}

function formatDate(dateStr) {
    var p = dateStr.split('-');
    return p[2]+'/'+p[1]+'/'+p[0];
}
</script>
</body>
</html>
