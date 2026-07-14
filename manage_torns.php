<?php include "php/inc/header.inc.php" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $language ?>" lang="<?= $language ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= $Text['global_title'] ?> - Gestió de torns</title>
    <link rel="stylesheet" type="text/css" media="screen" href="css/aixada_main.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="css/ui-themes/<?= $default_theme ?>/jqueryui.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="js/fgmenu/fg.menu.css" />
    <?= aixada_custom_css() ?>
    <script type="text/javascript" src="js/jquery/jquery.js"></script>
    <script type="text/javascript" src="js/jqueryui/jqueryui.js"></script>
    <?= aixada_js_src() ?>
    <style>
        .torns-section          { margin: 20px 0; padding: 14px 16px; border: 1px solid #ccc; border-radius: 4px; background: #fafafa; }
        .torns-section h2       { margin: 0 0 12px; font-size: 1.05rem; }
        .torns-grid             { display: flex; gap: 20px; flex-wrap: wrap; }
        .torns-col              { flex: 1; min-width: 200px; }
        .torns-col > label      { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.85rem; }
        .config-row             { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; }
        .config-row label       { font-weight: bold; font-size: 0.85rem; min-width: 185px; margin: 0; }
        .config-row input[type=number] { width: 55px; }
        .uf-checkboxes          { max-height: 160px; overflow-y: auto; border: 1px solid #ddd; padding: 4px 6px; border-radius: 3px; background: #fff; font-size: 0.82rem; }
        .uf-checkboxes label    { font-weight: normal; display: flex; align-items: center; gap: 5px; padding: 1px 0; }
        .incompatible-list      { list-style: none; padding: 0; margin: 0 0 6px; font-size: 0.82rem; }
        .incompatible-list li   { display: flex; align-items: center; gap: 6px; margin-bottom: 3px; }
        .incompatible-add       { display: flex; gap: 6px; align-items: center; margin-top: 6px; font-size: 0.82rem; }
        .incompatible-add select { font-size: 0.82rem; max-width: 130px; }
        .generate-row           { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 8px; }
        .generate-row > div > label { display: block; font-weight: bold; font-size: 0.85rem; margin-bottom: 3px; }
        .month-block        { margin-bottom: 24px; }
        .month-header       { background: #3a3a5c; color: white; padding: 7px 14px; font-size: 0.92rem;
                              font-weight: bold; text-transform: uppercase; letter-spacing: 0.07em;
                              border-radius: 4px 4px 0 0; }
        .torns-table        { width: 100%; border-collapse: collapse; border: 1px solid #ccc; border-top: none; }
        .torns-table thead th { background: #f0f0f4; padding: 5px 12px; font-size: 0.78rem;
                                text-transform: uppercase; color: #555; letter-spacing: 0.04em;
                                text-align: left; border-bottom: 2px solid #ccc; }
        .torns-table tbody tr:last-child td { border-bottom: none; }
        .torns-table td     { padding: 6px 12px; vertical-align: top; border-bottom: 1px solid #eee; }
        .week-cell          { width: 110px; background: #fafafa; }
        .week-dates         { font-weight: bold; font-size: 0.88rem; color: #333; }
        .week-rep-day       { font-size: 0.78rem; color: #2e7d32; margin-top: 3px; }
        .rep-cell           { border-left: 3px solid #2e7d32; }
        .net-cell           { border-left: 3px solid #1565c0; }
        select.uf-select    { min-width: 140px; }
        .group-table        { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .group-table th     { font-size: 0.75rem; color: #888; text-align: left; padding: 2px 6px 4px;
                              border-bottom: 1px solid #e0e0e0; white-space: nowrap; }
        .group-table td     { padding: 4px 6px; vertical-align: middle; border-bottom: 1px solid #f2f2f2; }
        .group-table tbody tr:last-child td { border-bottom: none; }
        .group-table tr.responsable td { font-weight: bold; color: #1b5e20; }
        .col-uf             { width: 42px; color: #777; white-space: nowrap; }
        .col-phone          { white-space: nowrap; color: #555; }
        .no-torns           { color: #999; font-style: italic; font-size: 0.85rem; margin: 4px 0; }
        .torns-btn, .btn-sm {
            padding: 2px 9px; font-size: 0.82rem; cursor: pointer;
            border: 1px solid #aaa; background: linear-gradient(to bottom, #f7f7f7, #e4e4e4);
            border-radius: 3px; color: #333;
        }
        .btn-sm { padding: 1px 6px; font-size: 0.75rem; border-color: #ccc; background: #f5f5f5; }
        .torns-btn:hover, .btn-sm:hover { background: linear-gradient(to bottom, #ececec, #d8d8d8); border-color: #888; }
        .torns-btn:active, .btn-sm:active { background: #d0d0d0; }
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
                    <div class="config-row">
                        <label>Repartiment — UFs per torn</label>
                        <input type="number" id="repartiment_count" min="1" max="20" value="6" />
                    </div>
                    <div class="config-row">
                        <label>Neteja — UFs per torn</label>
                        <input type="number" id="neteja_count" min="1" max="20" value="3" />
                    </div>
                    <div class="config-row">
                        <label>Mesos d'antel·lació</label>
                        <input type="number" id="advance_months" min="1" max="12" value="2" />
                    </div>
                    <div class="config-row">
                        <label>Dia del repartiment</label>
                        <select id="repartiment_day">
                            <option value="1">Dilluns</option>
                            <option value="2">Dimarts</option>
                            <option value="3">Dimecres</option>
                            <option value="4" selected>Dijous</option>
                            <option value="5">Divendres</option>
                            <option value="6">Dissabte</option>
                            <option value="0">Diumenge</option>
                        </select>
                    </div>
                </div>
                <div class="torns-col">
                    <label>UF excloses</label>
                    <div class="uf-checkboxes" id="excluded_ufs"></div>
                </div>
                <div class="torns-col">
                    <label>UF que no poden ser responsables</label>
                    <div class="uf-checkboxes" id="no_responsible_ufs"></div>
                </div>
                <div class="torns-col">
                    <label>UFs noves <span style="font-weight:normal;font-size:0.82rem;color:#666">(màxim 3 per torn)</span></label>
                    <div class="uf-checkboxes" id="nova_ufs"></div>
                </div>
            </div>
            <div style="margin-top:14px">
                <label style="font-weight:bold;font-size:0.85rem;display:block;margin-bottom:6px">Parelles incompatibles (no al mateix torn)</label>
                <ul class="incompatible-list" id="incompatible_list"></ul>
                <div class="incompatible-add">
                    <select id="incompat_uf1" style="min-width:200px"></select>
                    <span>+</span>
                    <select id="incompat_uf2" style="min-width:200px"></select>
                    <button class="torns-btn" onclick="addIncompatible()">Afegir</button>
                </div>
            </div>
            <br>
            <button class="torns-btn" onclick="saveConfig()">Desa configuració</button>
        </div>

        <!-- GENERATE -->
        <div class="torns-section">
            <h2>Genera torns</h2>
            <div class="generate-row">
                <div>
                    <label>Repartiment</label>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:4px">
                        <input type="date" id="start_repartiment" />
                        <span style="color:#888;font-size:0.85rem">fins a</span>
                        <input type="date" id="end_repartiment" />
                        <button class="torns-btn" onclick="generate('repartiment')">Genera repartiment</button>
                    </div>
                </div>
                <div>
                    <label>Neteja</label>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:4px">
                        <input type="date" id="start_neteja" />
                        <span style="color:#888;font-size:0.85rem">fins a</span>
                        <input type="date" id="end_neteja" />
                        <button class="torns-btn" onclick="generate('neteja')">Genera neteja</button>
                    </div>
                </div>
            </div>
            <small style="color:#777">Els torns existents en el període seleccionat s'esborraran i es regeneraran.</small>
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
var monthNamesCat = ['Gener','Febrer','Març','Abril','Maig','Juny','Juliol','Agost','Setembre','Octubre','Novembre','Desembre'];
var dayNamesCat   = ['Dg','Dl','Dm','Dc','Dj','Dv','Ds'];
var shortMonths   = ['gen','feb','mar','abr','mai','jun','jul','ago','set','oct','nov','des'];

$(document).ready(function() {
    loadUfs(function() {
        loadConfig();
        loadUpcoming();
    });
    var today = new Date().toISOString().slice(0,10);
    var twoMonths = new Date(); twoMonths.setMonth(twoMonths.getMonth() + 2);
    var twoMonthsStr = twoMonths.toISOString().slice(0,10);
    $('#start_repartiment, #start_neteja').val(today);
    $('#end_repartiment, #end_neteja').val(twoMonthsStr);
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
    var excHtml = '', respHtml = '', novaHtml = '';
    allUfs.forEach(function(uf) {
        var label = uf.id + ' - ' + uf.name;
        excHtml  += '<label><input type="checkbox" class="exc-cb"  value="'+uf.id+'"> '+label+'</label>';
        respHtml += '<label><input type="checkbox" class="resp-cb" value="'+uf.id+'"> '+label+'</label>';
        novaHtml += '<label><input type="checkbox" class="nova-cb" value="'+uf.id+'"> '+label+'</label>';
    });
    $('#excluded_ufs').html(excHtml);
    $('#no_responsible_ufs').html(respHtml);
    $('#nova_ufs').html(novaHtml);
}

$(document).on('change', '.exc-cb', function() {
    var id = $(this).val();
    if ($(this).is(':checked')) {
        $('.resp-cb[value="'+id+'"]').prop('checked', true);
        $('.nova-cb[value="'+id+'"]').prop('checked', false).closest('label').hide();
    } else {
        $('.resp-cb[value="'+id+'"]').prop('checked', false);
        $('.nova-cb[value="'+id+'"]').closest('label').show();
    }
});

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
        $('#repartiment_day').val(cfg.repartiment_day !== undefined ? cfg.repartiment_day : 4);

        (cfg.excluded || []).forEach(function(id) {
            $('.exc-cb[value="'+id+'"]').prop('checked', true);
            $('.resp-cb[value="'+id+'"]').prop('checked', true);
            $('.nova-cb[value="'+id+'"]').prop('checked', false).closest('label').hide();
        });
        (cfg.no_responsible || []).forEach(function(id) {
            $('.resp-cb[value="'+id+'"]').prop('checked', true);
        });
        (cfg.nova || []).forEach(function(id) {
            $('.nova-cb[value="'+id+'"]').prop('checked', true);
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
    if (!exists) { incompatiblePairs.push([p1, p2]); renderIncompatList(); }
}

function removeIncompat(i) {
    incompatiblePairs.splice(i, 1);
    renderIncompatList();
}

function saveConfig() {
    var excluded       = $('.exc-cb:checked').map(function() { return parseInt($(this).val()); }).get();
    var no_responsible = $('.resp-cb:checked').map(function() { return parseInt($(this).val()); }).get();
    var nova           = $('.nova-cb:checked').map(function() { return parseInt($(this).val()); }).get();
    $.post('php/ctrl/Torns.php', {
        oper:              'saveConfig',
        repartiment_count: $('#repartiment_count').val(),
        repartiment_freq:  1,
        neteja_count:      $('#neteja_count').val(),
        neteja_freq:       2,
        advance_months:    $('#advance_months').val(),
        repartiment_day:   $('#repartiment_day').val(),
        excluded:          JSON.stringify(excluded),
        no_responsible:    JSON.stringify(no_responsible),
        nova:              JSON.stringify(nova),
        incompatible:      JSON.stringify(incompatiblePairs)
    }, function() {
        $.showMsg({msg: 'Configuració desada.', type: 'ok'});
    });
}

function generate(task) {
    var start = $('#start_'+task).val();
    var end   = $('#end_'+task).val();
    if (!start || !end) { alert('Tria la data d\'inici i la data de fi.'); return; }
    if (end <= start)   { alert('La data de fi ha de ser posterior a l\'inici.'); return; }
    $.post('php/ctrl/Torns.php', {oper:'generateTorns', task:task, start:start, end:end},
        function(data) { renderUpcoming(JSON.parse(data)); }
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
                repHtml = '<table class="group-table"><thead><tr><th>UF</th><th>Nom</th><th>Telèfon</th><th></th></tr></thead><tbody>';
                week.repartiment.forEach(function(entry) {
                    var isResp = !!entry.is_responsible;
                    var cls    = isResp ? 'responsable' : '';
                    var ufNum  = entry.uf_id + (isResp ? ' ★' : '');
                    repHtml += '<tr class="'+cls+'" data-date="'+entry.date+'" data-uf="'+entry.uf_id+'" data-task="repartiment">'
                             + '<td class="col-uf">'+ufNum+'</td>'
                             + '<td>'+entry.name+'</td>'
                             + '<td class="col-phone">'+(entry.phone || '')+'</td>'
                             + '<td>'
                             + '<button class="btn-sm" onclick="showEdit(this)">canvia</button>'
                             + (isResp ? '' : ' <button class="btn-sm" onclick="setResponsable(\''+entry.date+'\','+entry.uf_id+')">resp.</button>')
                             + ' <button class="btn-sm" onclick="deleteTorn(\''+entry.date+'\','+entry.uf_id+',\'repartiment\')">✕</button>'
                             + '</td>'
                             + '</tr>';
                });
                repHtml += '</tbody></table>';
            } else {
                repHtml = '<span class="no-torns">—</span>';
            }

            // Neteja column
            var netHtml = '';
            if (week.neteja && week.neteja.length > 0) {
                netHtml = '<table class="group-table"><thead><tr><th>UF</th><th>Nom</th><th>Telèfon</th><th></th></tr></thead><tbody>';
                week.neteja.forEach(function(entry) {
                    netHtml += '<tr data-date="'+entry.date+'" data-uf="'+entry.uf_id+'" data-task="neteja">'
                             + '<td class="col-uf">'+entry.uf_id+'</td>'
                             + '<td>'+entry.name+'</td>'
                             + '<td class="col-phone">'+(entry.phone || '')+'</td>'
                             + '<td>'
                             + '<button class="btn-sm" onclick="showEdit(this)">canvia</button>'
                             + ' <button class="btn-sm" onclick="deleteTorn(\''+entry.date+'\','+entry.uf_id+',\'neteja\')">✕</button>'
                             + '</td>'
                             + '</tr>';
                });
                netHtml += '</tbody></table>';
            } else {
                netHtml = '<span class="no-torns">—</span>';
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
    var row    = $(btn).closest('tr');
    var date   = row.data('date');
    var old_uf = row.data('uf');
    var task   = row.data('task');
    var td     = $(btn).closest('td');

    if (td.find('select.edit-select').length) {
        td.find('select.edit-select, .btn-confirm-edit').remove();
        return;
    }

    var sel = $('<select class="edit-select" style="font-size:0.82rem;max-width:160px"></select>');
    allUfs.forEach(function(uf) {
        sel.append($('<option>').val(uf.id).text(uf.id+' - '+uf.name));
    });
    sel.val(old_uf);
    var btnOk = $('<button class="btn-confirm-edit btn-sm">OK</button>').click(function() {
        var new_uf = parseInt(sel.val());
        if (new_uf === old_uf) { sel.remove(); $(this).remove(); return; }
        $.post('php/ctrl/Torns.php', {oper:'updateTorn', date:date, old_uf:old_uf, new_uf:new_uf, task:task},
            function() { loadUpcoming(); });
    });
    entry.append(sel).append(btnOk);
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
