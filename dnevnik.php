<?php
require_once 'sesija.class.php';
require_once 'baza.class.php';
require_once 'trenutno_vrijeme.php';

function ZapisiULog($poruka,$tip) {
    $korisnik = (int) Sesija::dajPodatak('id');
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $vrijeme = date('Y-m-d H:i:s',TrenutnoVrijeme());
    $vezaBaze->izvrsiNekiDrugiUpit("INSERT INTO dnevnik VALUES (null,?,?,?,?);", array($korisnik, $poruka, $vrijeme, $tip), false);
    $vezaBaze->prekiniVezu();
}

?>