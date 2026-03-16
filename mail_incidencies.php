<?php include "php/inc/header.inc.php" ?>
<?php
// Temporary override to allow email sending while running on localhost.
$host_name = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($host_name, 'localhost') !== false || strpos($host_name, '127.0.0.1') !== false) {
    configuration_vars::get_instance()->internet_connection = true;
}

$mail_sent = false;
$mail_error = '';

$form_values = array(
    'delivery_date' => '',
    'uf_responsible' => '',
    'incidents' => '',
    'claims' => '',
    'other_comments' => ''
);

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function get_plain_email_address($raw_email) {
    $raw_email = trim((string)$raw_email);
    if ($raw_email === '') {
        return '';
    }

    if (preg_match('/<([^>]+)>/', $raw_email, $matches)) {
        return trim($matches[1]);
    }

    return $raw_email;
}

function normalize_delivery_date($raw_date) {
    $raw_date = trim($raw_date);
    if ($raw_date === '') {
        return '';
    }

    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw_date, $matches)) {
        $day = (int)$matches[1];
        $month = (int)$matches[2];
        $year = (int)$matches[3];
        if (checkdate($month, $day, $year)) {
            return sprintf('%02d-%02d-%04d', $day, $month, $year);
        }
        return '';
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw_date, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        if (checkdate($month, $day, $year)) {
            return sprintf('%02d-%02d-%04d', $day, $month, $year);
        }
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_values['delivery_date'] = trim(get_param('delivery_date', ''));
    $form_values['uf_responsible'] = trim(get_param('uf_responsible', ''));
    $form_values['incidents'] = trim(get_param('incidents', ''));
    $form_values['claims'] = trim(get_param('claims', ''));
    $form_values['other_comments'] = trim(get_param('other_comments', ''));

    $formatted_delivery_date = normalize_delivery_date($form_values['delivery_date']);
    if ($formatted_delivery_date !== '') {
        $form_values['delivery_date'] = $formatted_delivery_date;
    }

    if ($form_values['delivery_date'] === '' || $form_values['uf_responsible'] === '') {
        $mail_error = 'Cal omplir com a minim la data del repartiment i la UF responsable.';
    } else {
        $subject_date = ($formatted_delivery_date !== '') ? $formatted_delivery_date : $form_values['delivery_date'];
        $subject = 'Repartiment ' . $subject_date;

<<<<<<< Updated upstream
        $message = '<div style="font-size:16px; margin-bottom:10px;">Hola!<br>Aquest és el resum del repartiment d\'avui.</div>';
=======
        $message = '<p>Hola!</p>';
        $message .= '<p>Aquest es el resum del repartiment d\'avui.</p>';
>>>>>>> Stashed changes
        $message .= '<p><strong>Data del repartiment:</strong> ' . h($subject_date) . '</p>';
        $message .= '<p><strong>UF responsable:</strong> ' . h($form_values['uf_responsible']) . '</p>';
        $message .= '<p><strong>Incidencies:</strong><br>' . nl2br(h($form_values['incidents'])) . '</p>';
        $message .= '<p><strong>Reclamacions:</strong><br>' . nl2br(h($form_values['claims'])) . '</p>';
        $message .= '<p><strong>Altres comentaris:</strong><br>' . nl2br(h($form_values['other_comments'])) . '</p>';
        $message .= '<p><strong>Enviat per:</strong> ' . h(get_session_value('login')) . '</p>';

        // Set "From" display name as "L'Aixada - usuari",
        // while keeping the configured sender email address for delivery.
        $base_from_email = get_plain_email_address(get_config('admin_email'));
        $from_display_name = "L'Aixada - " . trim(get_session_value('login'));
        if ($base_from_email !== '') {
            $escaped_display_name = str_replace(array('\\', '"'), array('\\\\', '\\"'), $from_display_name);
            configuration_vars::get_instance()->admin_email = '"' . $escaped_display_name . '" <' . $base_from_email . '>';
        }

        $mail_sent = send_mail('proves.lavinagreta@lists.riseup.net', $subject, $message, array(
            'prepend_coop_name' => false
        ));
        if (!$mail_sent) {
            $mail_error = $Text['msg_err_emailed'];
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$language;?>" lang="<?=$language;?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $Text['global_title'] . " - " . $Text['head_ti_wiz_incidents_mail']; ?></title>

    <link rel="stylesheet" type="text/css" media="screen" href="css/aixada_main.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="js/fgmenu/fg.menu.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="css/ui-themes/<?=$default_theme;?>/jqueryui.css"/>

    <script type="text/javascript" src="js/jquery/jquery.js"></script>
    <script type="text/javascript" src="js/jqueryui/jqueryui.js"></script>
    <?php echo aixada_js_src(); ?>

    <script type="text/javascript">
    $(function(){
        var allowSubmit = false;
        var $form = $('#mail_incidents_form');
        var mailSent = <?php echo $mail_sent ? 'true' : 'false'; ?>;

        $('#btn_send_mail').button({
            icons: {
                primary: "ui-icon-mail-closed"
            }
        });

        $('#confirm_send_dialog').dialog({
            autoOpen: false,
            modal: true,
            resizable: false,
            width: 420,
            buttons: {
                "Si, envia": function() {
                    allowSubmit = true;
                    $(this).dialog('close');
                    $form[0].submit();
                },
                "No, cancel.la": function() {
                    $(this).dialog('close');
                }
            }
        });

        $form.on('submit', function(e) {
            if (!allowSubmit) {
                e.preventDefault();
                $('#confirm_send_dialog').dialog('open');
            }
        });

        $('#send_success_dialog').dialog({
            autoOpen: false,
            modal: true,
            resizable: false,
            width: 420,
            closeOnEscape: false,
            open: function() {
                $('.ui-dialog-titlebar-close', $(this).parent()).hide();
            },
            buttons: {
                "D'acord": function() {
                    window.location.href = 'aixada_main.php';
                }
            }
        });

        if (mailSent) {
            $('#send_success_dialog').dialog('open');
        }
    });
    </script>
    <style type="text/css">
        .mail-incidents-form {
            max-width: 760px;
            margin: 0 auto;
            margin-bottom: 20px;
            text-align: left;
            background: #f7faff;
            border: 1px solid #d3deef;
            border-radius: 10px;
            padding: 22px 24px 18px 24px;
            box-shadow: 0 2px 10px rgba(54, 93, 160, 0.08);
        }
        .mail-form-group {
            margin-bottom: 16px;
            background: #ffffff;
            border: 1px solid #dce6f4;
            border-radius: 8px;
            padding: 12px 14px;
        }
        .mail-form-title {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #365da0;
            border-bottom: 2px solid #d3deef;
            padding-bottom: 4px;
        }
        .mail-field {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #c6d5ec;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 1em;
            background: #fff;
        }
        .mail-help {
            margin: 0 0 8px 0;
            padding: 6px 10px;
            color: #3e3e3e;
            background: #eff4fc;
            border-left: 4px solid #365da0;
            line-height: 1.35;
            border-radius: 0 4px 4px 0;
            font-size: 0.8em;
        }
        .mail-help-note {
            display: block;
            margin-top: 8px;
            padding: 6px 8px;
            background: #e8f0fb;
            border: 1px solid #bcd0ec;
            border-radius: 4px;
            color: #2f4d79;
            font-weight: bold;
        }
        .mail-submit-wrap {
            margin-top: 18px;
            text-align: right;
        }
    </style>
</head>
<body>
<div id="wrap">
    <div id="headwrap">
        <?php include "php/inc/menu.inc.php" ?>
    </div>

    <div id="stagewrap" class="ui-widget <?= negative_balances_stagewrap_class() ?>">
        <div id="titlewrap">
            <h1><?php echo $Text['head_ti_wiz_incidents_mail']; ?></h1>
        </div>

        <?php if ($mail_error !== '') { ?>
            <div class="ui-state-error ui-corner-all" style="padding:0.8em; margin-bottom:1em;">
                <?php echo h($mail_error); ?>
            </div>
        <?php } ?>

        <form method="post" action="mail_incidencies.php" class="mail-incidents-form" id="mail_incidents_form">
            <div class="mail-form-group">
                <label class="mail-form-title" for="delivery_date">Data del repartiment</label>
                <input class="mail-field" type="text" id="delivery_date" name="delivery_date" value="<?php echo h($form_values['delivery_date']); ?>" placeholder="dd-mm-aaaa" required="required" />
            </div>

            <div class="mail-form-group">
                <label class="mail-form-title" for="uf_responsible">UF Responsable</label>
                <input class="mail-field" type="text" id="uf_responsible" name="uf_responsible" value="<?php echo h($form_values['uf_responsible']); ?>" required="required" />
            </div>

            <div class="mail-form-group">
                <label class="mail-form-title" for="incidents">Incidències</label>
                <p class="mail-help">Productes que no han arribat <strong>(i no surten a l'albarà)</strong> o que han arribat en menys quantitat <strong>(i així queda reflectit a l'albarà)</strong>.</p>
                <textarea class="mail-field" id="incidents" name="incidents" rows="5"><?php echo h($form_values['incidents']); ?></textarea>
            </div>

            <div class="mail-form-group">
                <label class="mail-form-title" for="claims">Reclamacions</label>
                <p class="mail-help">
                    Productes que <strong>surten a l'albarà</strong> i no han arribat o han arribat en mal estat.
                    <span class="mail-help-note">Aquesta secció va dirigida sobretot a les UF responsables de comanda.</span>
                </p>
                <textarea class="mail-field" id="claims" name="claims" rows="5"><?php echo h($form_values['claims']); ?></textarea>
            </div>

            <div class="mail-form-group">
                <label class="mail-form-title" for="other_comments">Altres comentaris</label>
                <textarea class="mail-field" id="other_comments" name="other_comments" rows="5"><?php echo h($form_values['other_comments']); ?></textarea>
            </div>

            <p class="mail-submit-wrap">
                <button type="submit" id="btn_send_mail"><?php echo $Text['btn_submit']; ?></button>
            </p>
        </form>

        <div id="confirm_send_dialog" title="Confirmacio" class="hidden">
            <p>Segur que vols enviar aquest mail a tothom?</p>
        </div>

        <div id="send_success_dialog" title="Mail enviat" class="hidden">
            <p><?php echo $Text['msg_wiz_incidents_mail_sent']; ?></p>
        </div>
    </div>
</div>
</body>
</html>
