<?php include "php/inc/header.inc.php" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $language ?>" lang="<?= $language ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= $Text['global_title'] ?> - Gestió de torns</title>
    <link rel="stylesheet" type="text/css" media="screen" href="css/aixada_main.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="css/ui-themes/<?= $default_theme ?>/jqueryui.css" />
    <?= aixada_custom_css() ?>
    <script type="text/javascript" src="js/jquery/jquery.js"></script>
    <script type="text/javascript" src="js/jqueryui/jqueryui.js"></script>
    <?= aixada_js_src() ?>
    <style>
        .torns-section      { margin: 20px 0; padding: 16px; border: 1px solid #ccc; border-radius: 4px; background: #fafafa; }
        .torns-section h2   { margin: 0 0 12px; font-size: 1.1rem; }
        .torns-grid         { display: flex; gap: 24px; flex-wrap: wrap; }
        .torns-col          { flex: 1; min-width: 220px; }
        .torns-col label    { display: block; margin-bottom: 4px; font-weight: bold; font-size: 0.9rem; }
        .torns-col input[type=number] { width: 60px; }
        .uf-checkboxes      { max-height: 180px; overflow-y: auto; border: 1px solid #ddd; padding: 6px; border-radius: 3px; background: #fff; }
        .uf-checkboxes label { font-weight: normal; display: flex; align-items: center; gap: 6px; padding: 2px 0; }
        .incompatible-list  { list-style: none; padding: 0; margin: 0 0 8px; }
        .incompatible-list li { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .incompatible-add   { display: flex; gap: 8px; align-items: center; margin-top: 6px; }
        .week-block         { margin-bottom: 20px; }
        .week-header        { background: #3a3a5c; color: white; padding: 6px 12px; border-radius: 3px 3px 0 0; font-weight: bold; }
        .week-body          { border: 1px solid #ccc; border-top: none; padding: 10px 12px; }
        .task-label         { font-size: 0.8rem; font-weight: bold; color: #666; text-transform: uppercase; margin: 8px 0 4px; }
        .torn-row           { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
        .torn-row .uf-name  { min-width: 180px; }
        .torn-row.responsable .uf-name { font-weight: bold; color: #1b5e20; }
        .responsable-badge  { background: #2e7d32; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; }
        .btn-generate       { margin-top: 10px; }
        .generate-row       { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 10px; }
        .generate-row label { font-weight: bold; font-size: 0.9rem; display: block; margin-bottom: 4px; }
        select.uf-select    { min-width: 160px; }
        .no-torns           { color: #999; font-style: italic; font-size: 0.9rem; }
        .neteja-row         { border-left: 3px solid #1565c0; padding-left: 8px; }
        .repartiment-row    { border-left: 3px solid #2e7d32; padding-left: 8px; }
    </style>
</head>
<body>
<div id="wrap">
    <?php include "php/inc/menu.inc.php" ?>
    <div id="stagewrap" class="ui-widget <?= negative_balances_stagewrap_class() ?>">
        <div id="titlewrap"><h1>Gestió de torns</h1></div>

        <!-- CONFIG -->
        <div class="torns-section">
            <h2>Configuració</h2>
            <div class="torns-grid">
                <div class="torns-col">
                    <label>Repartiment — UF per torn</label>
                    <input type="number" id="repartiment_count" min="1" max="20" value="6" />
                    <label style="margin-top:10px">Neteja — UF per torn</label>
                    <input type="number" id="neteja_count" min="1" max="20" value="3" />
                    <label style="margin-top:10px">Mesos d'antel·lació</label>
                    <input type="number" id="advance_months" min="1" max="12" value="2" />
                </div>
                <div class="torns-col">
                    <label>UF excloses (no participen mai)</label>
                    <div class="uf-checkboxes" id="excluded_ufs"></div>
                </div>
                <div class="torns-col">
                    <label>UF que no poden ser responsables</label>
                    <div class="uf-checkboxes" id="no_responsible_ufs"></div>
                </div>
                <div class="torns-col">
                    <label>Parelles incompatibles (no al mateix torn)</label>
                    <ul class="incompatible-list" id="incompatible_list"></ul>
                    <div class="incompatible-add">
                        <select id="incompat_uf1" class="uf-select"></select>
                        <span>+</span>
                        <select id="incompat_uf2" class="uf-select"></select>
                        <button onclick="addIncompatible()">Afegir</button>
                    </div>
                </div>
            </div>
            <br>
            <button onclick="saveConfig()">Desa configuració</button>
        </div>

        <!-- GENERATE -->
        <div class="torns-section">
            <h2>Genera torns</h2>
            <div class="generate-row">
                <div>
                    <label>Repartiment — data d'inici</label>
                    <input type="date" id="start_repartiment" />
                    <button class="btn-generate" onclick="generate('repartiment')">Genera repartiment</button>
                </div>
                <div>
                    <label>Neteja — data d'inici</label>
                    <input type="date" id="start_neteja" />
                    <button class="btn-generate" onclick="generate('neteja')">Genera neteja</button>
                </div>
            </div>
            <small style="color:#666">Es generaran els torns des de la data triada fins a avui + mesos d'antel·lació configurats. Els torns existents en aquest període s'esborraran.</small>
        </div>

        <!-- REVIEW -->
        <div class="torns-section">
            <h2>Torns programats</h2>
            <div id="torns_list"><p class="no-torns">Carregant...</p></div>
        </div>

    </div>
</div>

<script>
var allUfs = [];
var incompatiblePairs = [];

$(document).ready(function() {
    loadUfs(function() {
        loadConfig();
        loadUpcoming();
    });
    // default start dates to today
    var today = new Date().toISOString().slice(0,10);
    $('#start_repartiment').val(today);
    $('#start_neteja').val(today);
});

function loadUfs(callback) {
    $.post('php/ctrl/Torns.php', {oper:'getUfs'}, function(data) {
        allUfs = JSON.parse(data);
        renderUfCheckboxes();
        renderIncompatSelects();
        if (callback) callback();
    });
}

function renderUfCheckboxes() {
    var excHtml = '', respHtml = '';
    allUfs.forEach(function(uf) {
        var label = uf.id + ' - ' + uf.name;
        excHtml  += '<label><input type="checkbox" class="exc-cb"  value="'+uf.id+'"> '+label+'</label>';
        respHtml += '<label><input type="checkbox" class="resp-cb" value="'+uf.id+'"> '+label+'</label>';
    });
    $('#excluded_ufs').html(excHtml);
    $('#no_responsible_ufs').html(respHtml);
}

function renderIncompatSelects() {
    var opts = allUfs.map(function(uf) {
        return '<option value="'+uf.id+'">'+uf.id+' - '+uf.name+'</option>';
    }).join('');
    $('#incompat_uf1, #incompat_uf2').html(opts);
}

function loadConfig() {
    $.post('php/ctrl/Torns.php', {oper:'getConfig'}, function(data) {
        var cfg = JSON.parse(data);
        $('#repartiment_count').val(cfg.repartiment_count || 6);
        $('#neteja_count').val(cfg.neteja_count || 3);
        $('#advance_months').val(cfg.advance_months || 2);

        (cfg.excluded || []).forEach(function(id) {
            $('.exc-cb[value="'+id+'"]').prop('checked', true);
        });
        (cfg.no_responsible || []).forEach(function(id) {
            $('.resp-cb[value="'+id+'"]').prop('checked', true);
        });
        incompatiblePairs = (cfg.incompatible || []).map(function(p) {
            return [parseInt(p[0]), parseInt(p[1])];
        });
        renderIncompatList();
    });
}

function renderIncompatList() {
    var html = '';
    incompatiblePairs.forEach(function(pair, i) {
        var n1 = ufName(pair[0]), n2 = ufName(pair[1]);
        html += '<li>'+pair[0]+' ('+n1+') + '+pair[1]+' ('+n2+')'
              + ' <button onclick="removeIncompat('+i+')">✕</button></li>';
    });
    $('#incompatible_list').html(html || '<li class="no-torns">Cap parella incompatible</li>');
}

function ufName(id) {
    var uf = allUfs.find(function(u) { return parseInt(u.id) === parseInt(id); });
    return uf ? uf.name : '?';
}

function addIncompatible() {
    var a = parseInt($('#incompat_uf1').val());
    var b = parseInt($('#incompat_uf2').val());
    if (a === b) { alert('Tria dues UF diferents.'); return; }
    var p1 = Math.min(a,b), p2 = Math.max(a,b);
    var exists = incompatiblePairs.some(function(p) { return p[0]===p1 && p[1]===p2; });
    if (!exists) {
        incompatiblePairs.push([p1, p2]);
        renderIncompatList();
    }
}

function removeIncompat(i) {
    incompatiblePairs.splice(i, 1);
    renderIncompatList();
}

function saveConfig() {
    var excluded      = $('.exc-cb:checked').map(function() { return parseInt($(this).val()); }).get();
    var no_responsible = $('.resp-cb:checked').map(function() { return parseInt($(this).val()); }).get();
    $.post('php/ctrl/Torns.php', {
        oper:              'saveConfig',
        repartiment_count: $('#repartiment_count').val(),
        repartiment_freq:  1,
        neteja_count:      $('#neteja_count').val(),
        neteja_freq:       2,
        advance_months:    $('#advance_months').val(),
        excluded:          JSON.stringify(excluded),
        no_responsible:    JSON.stringify(no_responsible),
        incompatible:      JSON.stringify(incompatiblePairs)
    }, function() {
        $.showMsg({msg: 'Configuració desada.', type: 'ok'});
    });
}

function generate(task) {
    var start = $('#start_'+task).val();
    if (!start) { alert('Tria una data d\'inici.'); return; }
    var months = $('#advance_months').val();
    $.post('php/ctrl/Torns.php', {oper:'generateTorns', task:task, start:start, months:months},
        function(data) {
            renderUpcoming(JSON.parse(data));
        }
    );
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
        html += '<div class="week-block">'
              + '<div class="week-header">Del '+from+' al '+to+'</div>'
              + '<div class="week-body">';

        if (week.repartiment && week.repartiment.length > 0) {
            html += '<div class="task-label" style="color:#2e7d32">Repartiment</div>';
            week.repartiment.forEach(function(entry) {
                var resp = entry.is_responsible ? '<span class="responsable-badge">Responsable</span>' : '';
                var cls  = entry.is_responsible ? 'torn-row repartiment-row responsable' : 'torn-row repartiment-row';
                html += '<div class="'+cls+'" data-date="'+entry.date+'" data-uf="'+entry.uf_id+'" data-task="repartiment">'
                      + '<span class="uf-name">'+entry.uf_id+' - '+entry.name+'</span>'
                      + resp
                      + ' <button class="btn-canviar" onclick="showEdit(this)">Canvia</button>'
                      + (entry.is_responsible ? '' : ' <button onclick="setResponsable(\''+entry.date+'\','+entry.uf_id+')">Fer responsable</button>')
                      + ' <button onclick="deleteTorn(\''+entry.date+'\','+entry.uf_id+',\'repartiment\')">✕</button>'
                      + '</div>';
            });
        }

        if (week.neteja && week.neteja.length > 0) {
            html += '<div class="task-label" style="color:#1565c0">Neteja</div>';
            week.neteja.forEach(function(entry) {
                html += '<div class="torn-row neteja-row" data-date="'+entry.date+'" data-uf="'+entry.uf_id+'" data-task="neteja">'
                      + '<span class="uf-name">'+entry.uf_id+' - '+entry.name+'</span>'
                      + ' <button class="btn-canviar" onclick="showEdit(this)">Canvia</button>'
                      + ' <button onclick="deleteTorn(\''+entry.date+'\','+entry.uf_id+',\'neteja\')">✕</button>'
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

    if (row.find('select.edit-select').length) { row.find('select.edit-select').remove(); row.find('.btn-confirm-edit').remove(); return; }

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

function setResponsable(date, uf) {
    $.post('php/ctrl/Torns.php', {oper:'setResponsable', date:date, uf:uf}, function() { loadUpcoming(); });
}

function deleteTorn(date, uf, task) {
    $.post('php/ctrl/Torns.php', {oper:'deleteTorn', date:date, uf:uf, task:task}, function() { loadUpcoming(); });
}

function formatDate(dateStr) {
    var p = dateStr.split('-');
    return p[2]+'/'+p[1]+'/'+p[0];
}
</script>
</body>
</html>
