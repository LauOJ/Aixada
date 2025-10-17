<?php include "php/inc/header.inc.php" ?>
<?php
// Verificar que l'usuari està logat
if (!is_created_session()) {
    header('Location: login.php');
    exit;
}

// Obtenir dades de l'usuari
$member_id = get_session_member_id();
$uf_id = get_session_value('uf_id');
$login_name = get_session_value('login');

// Obtenir nom de l'usuari
$member_name = '';
$last_order_date = '';
$current_balance = 0;

try {
    $db = DBWrap::get_instance();
    
    // Nom de l'usuari
    $rs = $db->Execute('SELECT name FROM aixada_member WHERE id = :1q', $member_id);
    if ($row = $rs->fetch_assoc()) {
        $member_name = $row['name'];
    }
    
    // Data de l'última comanda
    $rs = $db->Execute('SELECT MAX(date_for_order) as last_order FROM aixada_order_item WHERE uf_id = :1q', $uf_id);
    if ($row = $rs->fetch_assoc()) {
        if ($row['last_order'] && $row['last_order'] != '0000-00-00') {
            $last_order_date = date('d/m/Y', strtotime($row['last_order']));
        }
    }
    
    // Saldo actual (des de aixada_account)
    $account_id = $uf_id + 1000; // Per a les UF, account_id = uf_id + 1000
    $rs = $db->Execute('SELECT balance FROM aixada_account WHERE account_id = :1q ORDER BY ts DESC LIMIT 1', $account_id);
    if ($row = $rs->fetch_assoc()) {
        $current_balance = $row['balance'];
    }
    
    DBWrap::get_instance()->free_next_results();
} catch (Exception $e) {
    // En cas d'error, continuar amb valors per defecte
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$language;?>" lang="<?=$language;?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $Text['global_title']; ?> - Tauler Personal</title>
    
    <link rel="stylesheet" type="text/css" media="screen" href="css/aixada_main.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="css/ui-themes/<?=$default_theme;?>/jqueryui.css"/>
    <link rel="stylesheet" type="text/css" media="screen" href="css/vinagreta-custom.css?v=4.6"/>
    
    <script type="text/javascript" src="js/jquery/jquery.js"></script>
    <script type="text/javascript" src="js/jqueryui/jqueryui.js"></script>
    <?php echo aixada_js_src(false); ?>
    
            <script type="text/javascript">
            console.log('=== DASHBOARD DEBUG ===');
            console.log('Dashboard page loaded');
            console.log('User logged in successfully');
            
            function toggleSection(sectionId) {
                console.log('Toggling section:', sectionId);
                var section = document.getElementById(sectionId);
                if (section.style.display === 'none') {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            }
            </script>
</head>
<body>

        <!-- Capçalera personalitzada -->
        <header class="login-header">
            <div class="logo">
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

<div id="wrap" style="margin-top: 70px;">
    
    <!-- Missatge personalitzat -->
    <div class="dashboard-welcome">
        <h1>Hola, <?php echo htmlspecialchars($member_name); ?>!</h1>
        <div class="user-info-grid">
            <div class="info-card">
                <h3>Unitat Familiar</h3>
                <p class="info-value"><?php echo $uf_id; ?></p>
            </div>
            <div class="info-card">
                <h3>Última Comanda</h3>
                <p class="info-value"><?php echo $last_order_date ?: 'Cap comanda'; ?></p>
            </div>
            <div class="info-card">
                <h3>Saldo Actual</h3>
                <p class="info-value"><?php echo number_format($current_balance, 2); ?> €</p>
            </div>
        </div>
    </div>

    <!-- Contingut principal en dues columnes -->
    <div class="dashboard-content">
        <!-- Columna esquerra -->
        <div class="dashboard-left">
            
            <!-- Aixada -->
            <div class="dashboard-section aixada">
                <h2>Aixada (gestor de comandes)</h2>
                <p>Fes clic a la següent imatge per accedir a l'<a href="aixada_main.php" class="dashboard-link">Aixada</a></p>
                <div class="dashboard-icon">
                            <a href="aixada_main.php">
                                <img src="https://lavinagreta.pangea.org/aixada/local_config/custom_img/logo-aixada.png" alt="Aixada" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                            </a>
                </div>
            </div>

            <!-- Dades de l'associació -->
            <div class="dashboard-section dades">
                <h2>Dades de l'associació</h2>
                
                <p><strong>Dades fiscals</strong> <span class="toggle-link" onclick="toggleSection('fiscal')">[mostrar/amagar]</span></p>
                <div id="fiscal" class="fiscal-info" style="display: none;">
                    <p>Associació de Consum Responsable La Vinagreta</p>
                    <p>C/ de l'Ametller, 3, baixos</p>
                    <p>08800 Vilanova i la Geltrú</p>
                    <p>NIF G-65713471</p>
                </div>

                <p style="margin-top: 15px;"><strong>Dades bancàries</strong> <span class="toggle-link" onclick="toggleSection('bank')">[mostrar/amagar]</span></p>
                <div id="bank" class="bank-info" style="display: none;">
                    <p><strong>CC:</strong> ES86 1491 0001 2720 2990 2422</p>
                    <p><strong>Entitat:</strong> Triodos Bank</p>
                    <p><strong>Titular:</strong> ASSOCIACIÓ DE CONSUM RESPONSABLE LA VINAGRETA</p>
                </div>

                <div style="margin-top: 15px;">
                    <p><strong>Documentació:</strong></p>
                    <p><a href="http://lavinagreta.org/wp-content/uploads/2025/10/ESTATUTS_2.0-La-Vinagreta.pdf" class="dashboard-link" target="_blank">Estatuts de la Vinagreta</a></p>
                    <p><a href="https://lavinagreta.org/tutorial-renovar-junta/" class="dashboard-link" target="_blank">Tutorial per renovar la Junta</a></p>
                </div>
            </div>

            <!-- Llista de distribució -->
            <div class="dashboard-section distribucio">
                <h2>Llista de distribució</h2>
                <p>Allotjada a: <a href="https://lists.riseup.net/" class="dashboard-link" target="_blank">https://lists.riseup.net/</a></p>
                <p>Email: <a href="mailto:lavinagreta@lists.riseup.net" class="dashboard-link">lavinagreta@lists.riseup.net</a></p>
            </div>

        </div>

        <!-- Columna central -->
        <div class="dashboard-center">
            
            <!-- Repartiment i neteja -->
            <div class="dashboard-section repartiment">
                <h2>Repartiment i neteja</h2>
                <div class="button-group">
                    <a href="https://docs.google.com/spreadsheets/d/1Owm0KrG_EdHweBR-yCO3bath_qOasJIpiagEguWO_VI/edit?gid=1698359793#gid=1698359793" target="_blank" class="dashboard-button">REPARTIMENT I NETEJA</a>
                </div>
            </div>

            <!-- Responsables de comanda -->
            <div class="dashboard-section responsables">
                <h2>Responsables de comanda</h2>
                <div class="button-group">
                    <a href="https://lavinagreta.org/responsables" target="_blank" class="dashboard-button">RESPONSABLES DE COMANDA</a>
                </div>
            </div>

            <!-- Actes de les assemblees -->
            <div class="dashboard-section actes">
                <h2>Actes de les assemblees</h2>
                <div>
                    <p><strong>Assemblees 2025:</strong></p>
                    <p>
                        <a href="https://lavinagreta.org/acta-gener-2025" class="dashboard-link" target="_blank">GENER</a> – 
                        <a href="https://lavinagreta.org/acta-marc-2025" class="dashboard-link" target="_blank">MARÇ</a> – 
                        <a href="https://lavinagreta.org/acta-maig-2025" class="dashboard-link" target="_blank">MAIG</a> – 
                        <a href="https://lavinagreta.org/acta-octubre-2025" class="dashboard-link" target="_blank">OCTUBRE</a>
                    </p>
                </div>
                <div style="margin-top: 15px;">
                    <p><a href="https://lavinagreta.org/assemblees-anteriors" class="dashboard-link" target="_blank">Veure totes les assemblees</a></p>
                </div>
            </div>

        </div>

        <!-- Columna dreta -->
        <div class="dashboard-right">
            
            <!-- Llistats de famílies -->
            <div class="dashboard-section contactes">
                <h2>Llistats d'unitats familiars</h2>
                <div class="button-group">
                    <a href="https://www.lavinagreta.org/wp-content/plugins/llistats/PR1families.php" target="_blank" class="dashboard-button">SIMPLE</a>
                    <a href="https://www.lavinagreta.org/wp-content/plugins/llistats/PR1contactes.php" target="_blank" class="dashboard-button">AMB CONTACTES</a>
                </div>
            </div>

            <!-- Full de càlcul per quadrar ESTOC -->
            <div class="dashboard-section estoc">
                <h2>Full de càlcul per quadrar ESTOC</h2>
                <div class="button-group">
                    <a href="https://docs.google.com/spreadsheets/d/1wU7kBcaIItXDhBWNGl9CqL2952N4clnJ/edit?gid=1031703495#gid=1031703495" target="_blank" class="dashboard-button">QUADRAR ESTOC</a>
                </div>
            </div>

            <!-- Reserva de local -->
            <div class="dashboard-section reserva">
                <h2>Reserva de local</h2>
                <div class="button-group">
                    <a href="https://docs.google.com/forms/d/e/1FAIpQLScWNb51Wsw3lwbuhqFWqC8vwduLrSgUf3ePsub34ueQF_Qv4g/viewform" target="_blank" class="dashboard-button">RESERVA LOCAL</a>
                </div>
            </div>

        </div>
    </div>

</div>

</body>
</html>
