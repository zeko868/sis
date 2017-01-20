<?php
require_once 'sesija.class.php';
require_once 'trenutno_vrijeme.php';
ZapisiULog("Korisnik je uspješno odjavljen", 'PO');
Sesija::obrisiSesiju();
header('Location: index.php');
exit(0);
?>