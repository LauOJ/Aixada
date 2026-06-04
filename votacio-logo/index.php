<?php
require_once __DIR__ . '/../php/inc/header.inc.base.php';

if (!is_created_session()) {
    header('Location: /aixada/login.php?originating_uri=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once __DIR__ . '/../php/inc/header.inc.php';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Votació nova imatge La Vinagreta</title>
<style>
body {
    font-family: Arial, sans-serif;
    max-width: 900px;
    margin: auto;
    padding: 20px;
}

h1 {
    text-align: center;
}

.deadline {
    text-align: center;
    font-weight: bold;
    margin-bottom: 30px;
}

.grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
}

.card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
}

.card img {
    width: 100%;
    border-radius: 6px;
}

.boto-pdf {
    display: inline-block;
    margin-top: 10px;
    margin-right: 8px;
    padding: 10px 16px;
    background: #444;
    color: white;
    text-decoration: none;
    text-align: center;
    border-radius: 5px;
    font-size: 0.9em;
}

.boto-pdf:hover {
    background: #222;
}

.links-proposta {
    margin-top: 12px;
}

.boto-votar {
    display: block;
    margin: 40px auto;
    width: 260px;
    padding: 15px;
    background: #2e7d32;
    color: white;
    text-align: center;
    font-size: 18px;
    text-decoration: none;
    border-radius: 8px;
}

.boto-votar:hover {
    background: #1b5e20;
}
</style>
</head>
<body>

<h1>Votació nova imatge de La Vinagreta</h1>

<p style="text-align: center;">Aquestes són les tres propostes finalistes per a la nova imatge de La Vinagreta. Opina sobre les tres i vota la que més t'agrada.</p>

<p class="deadline">Tens fins <strong>abans de la propera assemblea</strong> per votar.</p>

<div class="grid">

    <div class="card">
        <h2>AloKaos</h2>
        <img src="Alokaos.png" alt="Proposta AloKaos">
        <div class="links-proposta">
            <a class="boto-pdf" href="https://www.canva.com/design/DAHKM5zO6H4/Cj-fUyuuukK47Feg9TzaIw/edit" target="_blank">Veure proposta 1</a>
            <a class="boto-pdf" href="https://www.canva.com/design/DAHGQhorE2Q/CguRiEYVseNWV-jVNCIjxA/edit" target="_blank">Veure proposta 2</a>
        </div>
    </div>

    <div class="card">
        <h2>Katze</h2>
        <img src="Katze.png" alt="Proposta Katze">
        <div class="links-proposta">
            <a class="boto-pdf" href="proposta-final-katze.pdf" target="_blank">Proposta final</a>
            <a class="boto-pdf" href="manual-de-marca-katze.pdf" target="_blank">Manual de marca</a>
        </div>
    </div>

    <div class="card">
        <h2>Nec Studio</h2>
        <img src="nec-studio.png" alt="Proposta Nec Studio">
        <div class="links-proposta">
            <a class="boto-pdf" href="manual-de-marca-nec-studio.pdf" target="_blank">Manual de marca</a>
        </div>
    </div>

</div>

<a class="boto-votar" href="https://tally.so/r/68PD2o" target="_blank">VOTA</a>

</body>
</html>
