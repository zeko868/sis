<?php
$redirectString = 'Location: detalji_korisnika.php';
if (isset($_GET['kod'])) {
    require_once 'baza.class.php';
    require_once 'trenutno_vrijeme.php';
    require_once 'administratorski_parametar.php';
    $instancaBaze = new Baza();
    $instancaBaze->uspostaviVezu();
    $aktivacijskiKod = urldecode($_GET['kod']);
    $status = 'N';
    $rezultat = $instancaBaze->izvrsiSelectUpit("SELECT korisnik,datum_izrade_linka FROM aktivacija_korisnika WHERE aktivacijski_kod=?;", array($aktivacijskiKod));
    if ($rezultat->num_rows) {
        $podaci = $rezultat->fetch_assoc();
        $korisnik = (int)$podaci['korisnik'];
        $datumIstekaLinka = strtotime($podaci['datum_izrade_linka'])+Administratorski_parametar::dohvatiPodatak(Administratorski_parametar::TRAJANJE_AKTIVACIJSKOG_LINKA)[Administratorski_parametar::TRAJANJE_AKTIVACIJSKOG_LINKA]*60*60;
        if ($instancaBaze->izvrsiSelectUpit("SELECT COUNT(*) FROM korisnik WHERE id=? AND tip!='N';", array($korisnik))->fetch_row()[0]==1) {
            $status = 'A';
        }
        else if ($datumIstekaLinka > TrenutnoVrijeme()) {
            $instancaBaze->izvrsiNekiDrugiUpit("UPDATE korisnik SET tip='R' WHERE id=? AND tip='N';", array($korisnik));
            $status = 'UA';
        } else {
            $status = 'B';
        }
    }
    $rezultat->close();
    $instancaBaze->prekiniVezu();
    
    setcookie('aktivacija', $status, time() + 60);
    header('Location: prijava.php');
    exit(0);
}
?>