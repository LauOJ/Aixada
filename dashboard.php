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
    $rs = $db->Execute('SELECT MAX(order_date) as last_order FROM aixada_order WHERE uf_id = :1q', $uf_id);
    if ($row = $rs->fetch_assoc() && $row['last_order']) {
        $last_order_date = date('d/m/Y', strtotime($row['last_order']));
    }
    
    // Saldo actual
    $rs = $db->Execute('SELECT balance FROM aixada_account WHERE uf_id = :1q', $uf_id);
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
    <link rel="stylesheet" type="text/css" media="screen" href="css/vinagreta-custom.css?v=2.5"/>
    
    <script type="text/javascript" src="js/jquery/jquery.js"></script>
    <script type="text/javascript" src="js/jqueryui/jqueryui.js"></script>
    <?php echo aixada_js_src(false); ?>
</head>
<body>

<!-- Capçalera personalitzada -->
<header class="login-header">
    <div class="logo">
        <img src="local_config/custom_img/caixadeverdures.png" alt="La Vinagreta" style="height: 50px; width: auto;">
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
            <li class="active"><a href="https://lavinagreta.org/aixada">A ON SOM</a></li>
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
            <div class="dashboard-section">
                <h2>Aixada (gestor de comandes)</h2>
                <p>Feu clic a la següent imatge per accedir a l'<a href="index.php" class="dashboard-link">Aixada</a></p>
                <div class="dashboard-icon">
                    <a href="index.php">
                        <div class="icon-placeholder">🔧</div>
                    </a>
                </div>
                <p>Fent un clic <a href="#" class="dashboard-link">aquí</a> podreu accedir als <a href="#" class="dashboard-link">TUTORIALS</a> sobre com fer servir l'Aixada. Si encara no teniu nom d'usuàri/a o us sorgeix algun dubte, contacteu amb la Lau (f6) o el Pau (f28).</p>
            </div>

            <!-- Dades fiscals -->
            <div class="dashboard-section">
                <h2>Dades fiscals (per factures, etc)</h2>
                <div class="fiscal-info">
                    <p><strong>Associació de Consum Responsable La Vinagreta</strong></p>
                    <p>C/ de l'Ametller, 3, baixos<br>
                    08800 Vilanova i la Geltrú<br>
                    NIF G-65713471</p>
                </div>
            </div>

            <!-- Llista de distribució -->
            <div class="dashboard-section">
                <h2>Llista de distribució</h2>
                <p>Allotjada a: <a href="https://lists.riseup.net/" class="dashboard-link" target="_blank">https://lists.riseup.net/</a></p>
                <p>Email: <a href="mailto:lavinagreta@lists.riseup.net" class="dashboard-link">lavinagreta@lists.riseup.net</a></p>
            </div>

            <!-- Tutorial per renovar la Junta -->
            <div class="dashboard-section">
                <h2>Tutorial per renovar la Junta</h2>
                <p><a href="#" class="dashboard-link">Renovació de junta (última actualització: 03/25)</a></p>
            </div>

            <!-- Estatuts -->
            <div class="dashboard-section">
                <h2>Estatuts</h2>
                <p><a href="#" class="dashboard-link">Aquí teniu els estatuts de la Vinagreta</a></p>
            </div>

        </div>

        <!-- Columna dreta -->
        <div class="dashboard-right">
            
            <!-- Repartiment i neteja -->
            <div class="dashboard-section">
                <h2>Repartiment i neteja</h2>
                <div class="button-group">
                    <a href="https://docs.google.com/spreadsheets/d/1Owm0KrG_EdHweBR-yCO3bath_qOasJIpiagEguWO_VI/edit?gid=1698359793#gid=1698359793" target="_blank" class="dashboard-button">REPARTIMENT</a>
                    <a href="#" class="dashboard-button">NETEJA</a>
                </div>
            </div>

            <!-- Llistats de famílies -->
            <div class="dashboard-section">
                <h2>Llistats de famílies</h2>
                <div class="button-group">
                    <a href="#" class="dashboard-button">SIMPLE</a>
                    <a href="#" class="dashboard-button">AMB CONTACTES</a>
                </div>
            </div>

            <!-- Responsables de comanda -->
            <div class="dashboard-section">
                <h2>Responsables de comanda</h2>
                <p>Accediu a <a href="#" class="dashboard-link">aquesta pàgina</a> per a qualsevol pregunta o comentari sobre una comanda concreta.</p>
            </div>

            <!-- Full de càlcul per quadrar ESTOC -->
            <div class="dashboard-section">
                <h2>Full de càlcul per quadrar ESTOC</h2>
                <p><a href="#" class="dashboard-link">Accediu al full de quadrar estoc</a></p>
            </div>

            <!-- Reserva de local -->
            <div class="dashboard-section">
                <h2>Reserva de local</h2>
                <p><a href="#" class="dashboard-link">Accediu al formulari de reserves</a> per fer una reserva de local.</p>
            </div>

            <!-- Dades bancàries -->
            <div class="dashboard-section">
                <h2>Dades bancàries</h2>
                <div class="bank-info">
                    <p><strong>CC:</strong> ES86 1491 0001 2720 2990 2422</p>
                    <p><strong>Entitat:</strong> Triodos Bank</p>
                    <p><strong>Titular:</strong> ASSOCIACIÓ DE CONSUM RESPONSABLE LA VINAGRETA</p>
                </div>
            </div>

        </div>
    </div>

</div>

</body>
</html>
