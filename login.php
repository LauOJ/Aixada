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
	<title> <?php print $Text['global_title'] . " - " . $Text['ti_login_news'];?> </title>
	
	<link rel="stylesheet" type="text/css"   media="screen" href="css/aixada_main.css" />
    <link rel="stylesheet" type="text/css"   media="screen" href="css/ui-themes/<?=$default_theme;?>/jqueryui.css"/>
    <link rel="stylesheet" type="text/css"   media="screen" href="css/vinagreta-custom.css?v=4.4"/>
	
   
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
    /* Estils per mòbil */
    @media (max-width: 768px) {
        #logonWrap {
            max-width: 90vw !important;
            width: 90vw !important;
            margin: 20px auto !important;
            position: relative !important;
        }
        #logonWrap .ui-widget-content {
            max-width: 100% !important;
            width: 100% !important;
        }
        .tblForms {
            width: 100% !important;
            table-layout: fixed !important;
        }
        .tblForms td:first-child {
            width: 30% !important;
        }
        .tblForms td:last-child {
            width: 70% !important;
        }
        .inputTxtSmall {
            width: 100% !important;
            box-sizing: border-box !important;
        }
    }
    </style>
	   	
	
	   	
	<script type="text/javascript">
		$(function(){
			$.ajaxSetup({ cache: false });
			/**
			 *	logon stuff
			 */
			$('#btn_logon').button();
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
						console.log('AJAX SUCCESS - Response:', response);
						console.log('Redirecting to dashboard.php');
					    top.location.href = 'dashboard.php';
					    
					},
					error : function(XMLHttpRequest, textStatus, errorThrown){
						console.log('=== AJAX ERROR ===');
						console.log('Status:', textStatus);
						console.log('Error:', errorThrown);
						console.log('Response Text:', XMLHttpRequest.responseText);
						console.log('Response Status:', XMLHttpRequest.status);
						$.updateTips('#logonMsg','error',XMLHttpRequest.responseText);
                                          
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
        <!-- Logo de La Vinagreta - imatge de la caixa de verdures -->
                <a href="https://lavinagreta.org">
                    <img src="https://lavinagreta.pangea.org/aixada/local_config/custom_img/logo-vinagreta.png" alt="La Vinagreta" style="height: 50px; width: auto;">
                </a>
    </div>
    
    <nav class="nav-links">
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
						<td><label class="formLabel" for="login"><?=$Text['logon'];?>:</label></td>
						<td><input type="text" class="inputTxtSmall ui-widget-content ui-corner-all " name="login" id="login" style="width: 100%; box-sizing: border-box;"/></td>
					</tr>
					<tr>
						<td><label class="formLabel" for="password"><?=$Text['pwd'];?>:</label></td>
						<td><input type="password" class="inputTxtSmall ui-widget-content ui-corner-all" name="password" id="password" style="width: 100%; box-sizing: border-box;"/></td>
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