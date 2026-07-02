<?php

define('DS', DIRECTORY_SEPARATOR);
define('__ROOT__', dirname(__DIR__, 2) . DS);


require_once(__ROOT__ . "external/php53_2/jquery-fileupload/UploadHandler.php");
require_once(__ROOT__ . "local_config/config.php");

require_once(__ROOT__ . "php/lib/import_products.php");
require_once(__ROOT__ . "php/lib/import_providers.php");

require_once(__ROOT__ . "php/lib/export_providers.php");
require_once(__ROOT__ . "php/lib/export_products.php");
require_once(__ROOT__ . "php/lib/export_cart.php");
require_once(__ROOT__ . "php/lib/export_order.php");
require_once(__ROOT__ . "php/lib/export_dates4products.php");
require_once(__ROOT__ . "php/lib/export_members.php");
require_once(__ROOT__ . "php/utilities/general.php");

try{
	validate_session(); // The user must be logged in.

 	switch (get_param('oper')) {

 		//upload files from clients computer
 		case 'uploadFile':
			$options = array(	
				'upload_dir' => __ROOT__ . 'local_config/upload/',
				'accept_file_types' => '/\.(gif|jpe?g|png|csv|xlsx|xls|ods|xml|tsv|txt)$/i'
			);
			$upload_handler = new UploadHandler($options);
			exit; 
			
		//fetch file from online URL	
 		case 'fetchFile':
			$url = get_param('url');
 			$saveFileTo = __ROOT__ . 
			    'local_config/upload/tmpdownload_' .
			    parse_url($url, PHP_URL_SCHEME) . '_' . PHP_VERSION_ID .
			        // ^ suffixes that help determine from & where it was created
			    '.csv';

			// Code fron /php/lib/gdrive.php on gDrive::fetchFile()
			$outhandle = fopen($saveFileTo, 'w');
			if (!$outhandle)
			    throw new Exception("Export exception. Could not open {$saveFileTo} to store fetched file. Make sure that local_config/upload is a writable directory");

			$a = file_get_contents(
			    $url,
			    false,
			    stream_context_create(array(
			        "ssl" => array(
			            "verify_peer"      => false,
			            "verify_peer_name" => false,
			        ),
			    ))
			);
			fwrite($outhandle, $a);
			fclose($outhandle);

 	 		echo $saveFileTo; 
 			exit; 
 		
 		//parse the file and retur HTML table
 		case 'parseFile':
 			$path = get_param('fullpath','');
 			if ($path == ''){ 
	 			$path = __ROOT__ .'local_config/upload/' . get_param('file');
 			}
 			
 			$dt = abstract_import_manager::parse_file($path, get_param('import2Table',''));
            $import_template = get_param('import_template');
            if ($import_template != '') {
                $new_dt = $dt->parse_data($import_template);
                // If the data have been parsed by the template then can import directly.
                if ($new_dt) {
                    $template_options = $new_dt['template_options'];
                    switch(get_param('import2Table')){
                        case 'aixada_product':
                            $pi = new import_products(
                                    $new_dt['data'], $new_dt['map'],
                                    get_param('provider_id')
                            );
                            if (isset($template_options['deactivate_products']) &&
                                      $template_options['deactivate_products']) {
                                $pi->deactivate_products();
                            }
                            break;
                        case 'aixada_product_orderable_for_date':
                            echo 0;
                            exit;
                        case 'aixada_provider':
                            $pi = new import_providers($new_dt['data'], $new_dt['map']);
                            break;
                        default:
                            echo 0;
                            exit;
                    }
                    $append_new = false;
                    $keep_match_field = false;
                    if (isset($template_options['import_mode'])) {
                        switch($template_options['import_mode']) {
                            case '2':
                                $append_new = true;
                                $keep_match_field = true;
                                break;
                            case '1':
                                $append_new = true;
                                break;
                        }
                    }
                    echo $pi->import($append_new, $keep_match_field);
                    exit;
                }
            }
			echo $dt->get_html_table();
			$_SESSION['import_file'] = $path;
            session_commit();
 			exit;

 	
    		
    		
 		case 'import':
 			
 			
 			//$map = array('custom_product_ref'=>0, 'unit_price'=>1, 'name'=>2);
		 	$map = array();
			foreach($_REQUEST['table_col'] as $key => $value){
				$map[$value] = $key;
			}
 			
            switch(get_param('import_mode')){
                case '2':
                    $append_new = true;
                    $keep_match_field = true;
                    break;
                case '1':
                    $append_new = true;
                    $keep_match_field = false;
                    break;
                default:
                    $append_new = false;
                    $keep_match_field = false;
            }
 			switch(get_param('import2Table')){
 				case 'aixada_product':
 					$dt = abstract_import_manager::parse_file($_SESSION['import_file'], 'aixada_product');
 					$pi = new import_products($dt, $map, get_param('provider_id'));
					echo $pi->import($append_new, $keep_match_field);
 					exit; 
 				
 				case 'aixada_product_orderable_for_date':
                    echo 0;
 					exit;  
 					
 				case 'aixada_provider':
 					$dt = abstract_import_manager::parse_file($_SESSION['import_file'], 'aixada_provider');
 					$pi = new import_providers($dt, $map);
 					echo $pi->import($append_new, $keep_match_field);
 					exit; 
 				
 			}
			

			echo 0; 
 			exit; 
 	 	case 'getImportTemplates':
    		printXML(get_import_templates_list(get_param('table'))); 
    		exit;
 	
 		case 'getAllowedFields':
    		printXML(get_import_rights(get_param('table'))); 
    		exit; 
 			
 		//exports provider info only. Should have option for including provider products?!!	
		case 'exportProviderInfo':
			$publish = (get_param('makePublic','off')=='on')? 1:0; 	
		    $ep = new export_providers(get_param('exportName'), get_param('providerId',0));
		    $ep->export($publish, get_param('exportFormat', 'csv'), get_param('email',''), get_param('password',''));
	    	break;


		case 'exportProducts':	
			$publish = (get_param('makePublic','off')=='on')? 1:0; 		
			$ep = new export_products(get_param('exportName'), get_param('providerId',0), get_param('productIds',0));
		    $ep->export($publish, get_param('exportFormat', 'csv'), get_param('email',''), get_param('password',''));
	    	break;
	    	
		case 'orderableProductsForDateRange':
			$publish = (get_param('makePublic','off')=='on')? 1:0; 
			$ep = new export_dates4products(get_param('exportName'), get_param('providerId'), get_param('fromDate',''), get_param('toDate',''));
			$ep->export($publish, get_param('exportFormat', 'csv'), get_param('email',''), get_param('password',''));		    
			break;
			
		case 'exportMembers':
			$publish = (get_param('makePublic','off')=='on')? 1:0; 
			$active = (get_param('onlyActiveUfs','off')=='on')? 1:0;
			$ep = new export_members(get_param('exportName'), $active);
			$ep->export($publish, get_param('exportFormat', 'csv'), get_param('email',''), get_param('password',''));
			break;
			
		case 'exportCart':
			$publish = (get_param('makePublic','off')=='on')? 1:0;
			$ep = new export_cart(get_param('exportName'), get_param('shopId'));
			$ep->export($publish, get_param('exportFormat', 'csv'), get_param('email',''), get_param('password',''));
			break;
			
		case 'exportOrder':
			$publish = (get_param('makePublic','off')=='on')? 1:0;
			$ep = new export_order(get_param('exportName'), get_param('order_id',0), get_param('provider_id',0), get_param('date_for_order',0));
			$ep->export($publish, get_param('exportFormat', 'csv'), get_param('email',''), get_param('password',''));
			break;


	default:
	    throw new Exception("ctrl Import: operation {$_REQUEST['oper']} not supported");
    
  	}

} 

catch(Exception $e) {
  header('HTTP/1.0 401 ' . $e->getMessage());
  die($e->getMessage());
}  
?>
