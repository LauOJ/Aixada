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

body{
font-family: Arial, sans-serif;
max-width:900px;
margin:auto;
padding:20px;
}

h1{
text-align:center;
}

.deadline{
text-align:center;
font-weight:bold;
margin-bottom:30px;
}

.grid{
display:grid;
grid-template-columns:1fr;
gap:30px;
}

.card{
border:1px solid #ddd;
border-radius:8px;
padding:15px;
}

.card img{
width:100%;
border-radius:6px;
}

.boto-pdf{
display:block;
margin-top:10px;
padding:10px;
background:#444;
color:white;
text-decoration:none;
text-align:center;
border-radius:5px;
}

.boto-votar{
display:block;
margin:40px auto;
width:260px;
padding:15px;
background:#2e7d32;
color:white;
text-align:center;
font-size:18px;
text-decoration:none;
border-radius:8px;
}

</style>
</head>

<body>

<h1>Votació nova imatge de La Vinagreta</h1>

<p style="text-align: center;">Aquestes són les tres propostes finalistes per a la nova imatge de La Vinagreta. Opina sobre les tres i vota la que més t'agrada</p>

<p class="deadline">
Tens fins al <strong>20 de març</strong> per votar.
</p>


<div class="grid">

<div class="card">
<h2>AloKaos</h2>
<a href="AloKaos.pdf" target="_blank">
<img src="AloKaos.png">
</a>
<a class="boto-pdf" href="AloKaos.pdf" target="_blank">Veure PDF</a>
</div>

<div class="card">
<h2>Katze</h2>
<a href="Katze.pdf" target="_blank">
<img src="Katze.png">
</a>
<a class="boto-pdf" href="Katze.pdf" target="_blank">Veure PDF</a>
</div>

<div class="card">
<h2>Nec Studio</h2>
<a href="Nec-Studio.pdf" target="_blank">
<img src="Nec-Studio.png">
</a>
<a class="boto-pdf" href="Nec-Studio.pdf" target="_blank">Veure PDF</a>
</div>

</div>

<a class="boto-votar" href="https://tally.so/r/VLlr16" target="_blank">
🗳️ VOTA
</a>

</body>
</html>