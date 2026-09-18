<?php
function write_tpl_header_item($content) {
    if ($content) {
        echo  $content.'<br>';
    }
}
function write_tpl_header() {
    $coop_header_logo = get_config('coop_header_logo', 'img/tpl_header_logo.png');
    if ($coop_header_logo) {
        echo '<div id="logo"><img alt="coop logo" style="height:80px; width:auto;" src="../'.$coop_header_logo.
                '"/></div>';
    }
    ?>
    <div id="address" class="floatRight txtAlignRight">
        <p style="margin:0; font-weight:bold; font-size:1.15em;"><?php echo get_config('coop_name');?></p>
        <p style="margin:0;">CIF/NIF: <?php echo get_config('coop_VAT_number'); ?><br>
            <?php
            write_tpl_header_item(get_config('coop_address'));
            write_tpl_header_item(get_config('coop_city'));
            write_tpl_header_item(get_config('coop_contact_inf')); ?></p>
    </div>
    <div style="clear: both; margin-bottom: 10px;">&nbsp;</div>
    <?php
}
?>
