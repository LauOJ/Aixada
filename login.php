<?php
require_once("php/inc/header.inc.base.php");

require_once(__ROOT__ . 'php'.DS.'inc'.DS.'authentication.inc.php');

if (!isset($_SESSION)) {
    session_start();
    $_SESSION['aixada'] = true;
    session_commit(); // Force write session to create it and able to open $_SESSION faster.
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$language?>" lang="<?=$language?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title> <?php print $Text['global_title'] . " - " . $Text['ti_login_news'];?> </title>
	
	<link rel="stylesheet" type="text/css"   media="screen" href="css/aixada_main.css" />
    <link rel="stylesheet" type="text/css"   media="screen" href="css/ui-themes/<?=$default_theme;?>/jqueryui.css"/>
    <?= aixada_custom_css() ?>
	
   
	<script type="text/javascript" src="js/jquery/jquery.js"></script>
	<script type="text/javascript" src="js/jqueryui/jqueryui.js"></script>
	<?php echo aixada_js_src(false); ?>	
   	
    <style><?php
        $login_header_image =
            get_config('login_header_image', 'img/aixada_header800.150.png');
        if ($login_header_image) {
            echo "p#logonHeader {background-image: url({$login_header_image});}";
        } else {
            echo "p#logonHeader {background-image: none;}";
        }
    ?>
    /* Estils NOMÉS per a mòbil (l'escriptori no es toca).
       Especificitat body.login-page div#... per guanyar a custom.css. */
    .login-burger { display: none; }
    @media (max-width: 768px) {
        html, body { overflow-x: hidden; }
        body.login-page div#wrap { width: 100% !important; min-width: 0 !important; margin-top: 90px !important; }
        body.login-page div#stagewrap { min-width: 0 !important; width: 100% !important; }
        body.login-page div#stagewrap > div:not(#logonWrap) { display: none !important; }
        body.login-page div#logonWrap {
            float: none !important; position: static !important;
            top: auto !important; left: auto !important; right: auto !important;
            transform: none !important;
            width: 86% !important; max-width: 380px !important; min-width: 0 !important;
            margin: 34px auto !important;
        }
        body.login-page div#logonWrap .ui-widget-content { max-width: 100% !important; width: 100% !important; }
        /* Etiquetes a sobre de l'input perquè no es tallin */
        body.login-page div#logonWrap .tblForms td {
            display: block !important; width: 100% !important;
            text-align: left !important; padding: 4px 2px !important;
        }
        body.login-page div#logonWrap .formLabel { display: block; float: none !important; margin-bottom: 4px; font-size: 1rem; text-align: left !important; }
        .tblForms { width: 100% !important; table-layout: fixed !important; }
        .inputTxtSmall,
        input[type="text"], input[type="password"] {
            width: 100% !important; box-sizing: border-box !important;
            font-size: 16px !important; min-height: 42px !important;
        }
        #btn_logon { font-size: 1.05rem; padding: 10px 20px; }
        /* Capçalera mòbil: logo petit a l'esquerra + menú hamburguesa */
        body.login-page .login-header {
            flex-direction: row !important; justify-content: space-between !important;
            align-items: center !important; padding: 8px 16px !important;
        }
        body.login-page .login-header .logo img { height: 32px !important; }
        .login-burger {
            display: block; background: none; border: none; padding: 4px 8px;
            font-size: 1.8rem; line-height: 1; color: #4a5f6f; cursor: pointer;
        }
        body.login-page .login-header .nav-links {
            display: none; position: absolute; top: 100%; left: 0; right: 0;
            background: #fff; border-bottom: 1px solid #ddd; box-shadow: 0 4px 8px rgba(0,0,0,0.08);
            padding: 6px 0; margin: 0;
        }
        body.login-page .login-header .nav-links.open { display: block; }
        body.login-page .login-header .nav-links ul {
            flex-direction: column !important; align-items: stretch !important;
            gap: 0 !important; padding: 0 !important; margin: 0 !important;
        }
        body.login-page .login-header .nav-links li { width: 100%; }
        body.login-page .login-header .nav-links li a { display: block; padding: 11px 18px; }
        body.login-page .login-header .nav-links .submenu { display: none !important; }
    }
    #logonMsg {
        line-height: 1.4;
    }
    .login-error-actions {
        margin-top: 10px;
    }
    .login-error-actions .ui-button {
        font-size: 0.9em;
    }
    </style>
	   	
	
	   	
	<script type="text/javascript">
		$(function(){
			$.ajaxSetup({ cache: false });
			var incorrectLogonMsg = <?php echo json_encode($Text['msg_err_incorrectLogon']); ?>;
			var resetPwdBtnLabel = <?php echo json_encode($Text['btn_reset_pwd']); ?>;
			/**
			 *	logon stuff
			 */
			$('#btn_logon').button();

			// Menú hamburguesa del login (mòbil)
			$('#login-burger').on('click', function(){
				var open = $('#login-nav').hasClass('open');
				$('#login-nav').toggleClass('open', !open);
				$(this).attr('aria-expanded', String(!open));
			});
			function showLoginError(message){
				$('#logonMsg')
					.text(message)
					.addClass('ui-state-error');
			}

			function recoverPassword(loginValue){
				$.ajax({
					type: "POST",
					url: "php/ctrl/Login.php",
					data: {
						oper: "recoverPassword",
						login: loginValue
					},
					success: function(msg){
						$('#logonMsg')
							.text(msg)
							.removeClass('ui-state-error');
					},
					error: function(XMLHttpRequest){
						showLoginError(XMLHttpRequest.responseText);
					}
				});
			}

			$('#login').submit(function(){
				console.log('=== LOGIN DEBUG START ===');
				console.log('Form submitted');
				
				var dataSerial = $(this).serialize();
				console.log('Form data serialized:', dataSerial);
				console.log('AJAX URL: php/ctrl/Login.php');
				
				$.ajax({
					type: "POST",
                    url: "php/ctrl/Login.php",
					data:dataSerial,		
					success: function(response) {
					    top.location.href = '<?= htmlspecialchars(get_config('post_login_redirect', 'aixada_main.php')) ?>';
					},
					error : function(XMLHttpRequest, textStatus, errorThrown){
						console.log('=== AJAX ERROR ===');
						console.log('Status:', textStatus);
						console.log('Error:', errorThrown);
						console.log('Response Text:', XMLHttpRequest.responseText);
						console.log('Response Status:', XMLHttpRequest.status);

						var msg = XMLHttpRequest.responseText || '';
						var enteredLogin = $.trim($('input[name=login]').val());

						if (msg === incorrectLogonMsg && enteredLogin !== '') {
							$('#logonMsg')
								.removeClass('ui-state-error')
								.html(
									$('<div/>').text(msg).html() +
									'<div class="login-error-actions">' +
										'<button type="button" id="btnResetPwdLogin">' +
											$('<div/>').text(resetPwdBtnLabel).html() +
										'</button>' +
									'</div>'
								)
								.addClass('ui-state-error');

							$('#btnResetPwdLogin')
								.button({
									icons: {primary: "ui-icon-locked"}
								})
								.off('click')
								.on('click', function(e){
								e.preventDefault();
								recoverPassword(enteredLogin);
							});
						} else {
							showLoginError(msg);
						}
                                          
					}
				}); //end ajax retrieve date
 				return false;
			});

			
			
			/**
			 * forgot pwd dialog
			 */
			$('#dialog-recuperatePwd').dialog({
				autoOpen:false,
				buttons: {  
					"<?=$Text['btn_ok'];?>" : function(){
							$.ajax({
								type: "POST",
								url: '',
								success: function(txt){
									
								},
								error : function(XMLHttpRequest, textStatus, errorThrown){
									$.showMsg({
										msg:XMLHttpRequest.responseText,
										type: 'error'});
									
								}
							});
		
						
						},
							
					"<?=$Text['btn_close'];?>"	: function(){
							$( this ).dialog( "close" );
						}
				}
			});
			
	
				
			/**
			 *	incidents - DESACTIVAT per evitar 401 Unauthorized al login
			 */
			// $('#newsWrap').xml2html('init',{
			//		url: 'php/ctrl/Incidents.php',
			//		params : 'oper=getIncidentsListing&filter=pastWeek&type=3',
			//		loadOnInit: true
			// });


			


			/**
			 *	reset different intput fields
			 */
			$('input').focus(function(){
				$(this).removeClass('ui-state-error');
				}); 

			$('#login, #password').focus(function(){
					$('#logonMsg')
						.text('')
						.removeClass('ui-state-error');
				}); 

		

		});
	</script>    
	
</head>
<body class="login-page">

<!-- Capçalera personalitzada per al login -->
<header class="login-header">
    <div class="logo">
        <img src="<?php echo get_coop_logo(); ?>" alt="<?php echo get_config('coop_name', 'Aixada'); ?>" style="height: 50px; width: auto;">
    </div>

    <button class="login-burger" id="login-burger" aria-label="Menú" aria-expanded="false">&#9776;</button>

    <nav class="nav-links" id="login-nav">
        <ul>
            <li><a href="https://lavinagreta.org">INICI</a></li>
            <li class="has-submenu">
                <a href="https://lavinagreta.org/activitats">ACTIVITATS</a>
                <ul class="submenu">
                    <li><a href="https://lavinagreta.org/carnaval">Carnaval</a></li>
                    <li><a href="https://lavinagreta.org/dprofit">Dinar de Profit</a></li>
                    <li><a href="https://docsforaction.actiu.info/">Docs for Action</a></li>
                </ul>
            </li>
            <li><a href="https://lavinagreta.org/contacta">CONTACTA</a></li>
            <li class="active"><a href="https://lavinagreta.org/aixada">INTRANET</a></li>
        </ul>
    </nav>
</header>

<div id="wrap">
	<div id="headwrap">
		<p id="logonHeader"><span><?php 
            if (get_config('login_header_show_name', false)) {
                echo $Text['coop_name']; 
            } ?></span></p>
	</div>

	<div id="stagewrap" class="ui-widget">
		
		<div class="floatLeft aix-layout-splitW20 aix-layout-widget-left-col hidden">
			<div class="ui-widget-content ui-corner-all">
				<h4 class="ui-widget-header">Global info</h4>
			</div>
		</div>
		
		<div class="floatLeft aix-layout-splitW50 aix-layout-widget-center-col">
			<div id="newsWrap">
				<!-- Incidents desactivats per evitar 401 Unauthorized -->
			</div>
		</div>
	
		
		<div id="logonWrap" class="aix-layout-splitW20" style="max-width: 90vw; width: 90vw; margin: 20px auto; position: relative;">
			<div class="ui-widget-content ui-corner-all">
			<h4 class="ui-widget-header ui-corner-all">
				<?php echo $Text['login'];?>
				<div class="login-subtitle">Fes servir les credencials de l'Aixada</div>
			</h4>
			<p id="logonMsg" class="user_tips  minPadding"></p>
			<form id="login" method="post" class="padding15x10">
				<input type="hidden" name="oper" value="login">
				<table class="tblForms" style="width: 100%; table-layout: fixed;">
					<tr>
						<td><label class="formLabel" for="login">Usuari/a:</label></td>
						<td><input type="text" class="inputTxtSmall ui-widget-content ui-corner-all " name="login" id="login" autocomplete="username" style="width: 100%; box-sizing: border-box;"/></td>
					</tr>
					<tr>
						<td><label class="formLabel" for="password"><?=$Text['pwd'];?>:</label></td>
						<td><input type="password" class="inputTxtSmall ui-widget-content ui-corner-all" name="password" id="password" autocomplete="current-password" style="width: 100%; box-sizing: border-box;"/></td>
					</tr>
					<tr>
						<td colspan="2"><div>&nbsp;</div></td>
					</tr>
					<tr>
						
						<td colspan="2">
							<div class="textAlignLeft">
								<button name="submitted" id="btn_logon"><?=$Text['btn_login'];?></button>
							</div>
						</td>
					</tr>
				</table>
				<input type="hidden" name="originating_uri" value="<?=(isset($_REQUEST['originating_uri']) ? $_REQUEST['originating_uri'] : 'login.php') ?>">
			</form>
		</div>
	</div><!-- end logonwrap -->
	
	
	
	</div><!-- end stagewrap -->
	

</div>
<div id="dialog-message" title="">
	<p class="minPadding ui-corner-all"></p>
</div>
<div id="dialog-recuperatePwd">
		<p>Please enter your email address here:</p>
		<input type="text" name="email" value="" />
</div>

</body>
</html>