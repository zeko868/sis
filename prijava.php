<?php

if (!empty($_POST)) {
    require_once 'baza.class.php';
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $eadresa = $_POST['eadresa'];
    $lozinka = $_POST['lozinka'];
    $rezultatUpita = $vezaBaze->izvrsiSelectUpit("SELECT k.id,k.ime,k.prezime,k.tip,k.email,k.lozinka,k.broj_pogresnih_prijava,k.link_profilna_slika,a.datum_izrade_linka FROM korisnik k, aktivacija_korisnika a WHERE email=? AND k.id=a.korisnik;", array($eadresa));
    if ($rezultatUpita->num_rows) {
        require_once 'sesija.class.php';
        require_once 'trenutno_vrijeme.php';
        $redak = $rezultatUpita->fetch_assoc();
        if ($redak['broj_pogresnih_prijava']>=4) {
            ZapisiULog("Pokušaj prijave s blokiranim korisničkim računom (e-mail: $eadresa)", 'PO');
            echo 'Vaš korisnički račun je blokiran!';
        }
        else if (password_verify($lozinka, $redak['lozinka'])) {
            require_once 'administratorski_parametar.php';
            if ($redak['tip']==='N') {
                if (TrenutnoVrijeme() > strtotime($redak['datum_izrade_linka']) + Administratorski_parametar::dohvatiPodatak(Administratorski_parametar::TRAJANJE_AKTIVACIJSKOG_LINKA)[Administratorski_parametar::TRAJANJE_AKTIVACIJSKOG_LINKA]*3600) {
                    echo 'Vaš korisnički račun je blokiran zbog isteka aktivacijskog linka! Kontaktirajte administratora sustava kako bi Vam otključao korisnički račun.';
                }
                else {
                    echo 'Ne možete koristiti Vaš korisnički račun dok ga ne aktivirate. Aktivirajte ga što prije kako Vam nebi bio blokiran.';
                }
                $vezaBaze->izvrsiNekiDrugiUpit("UPDATE korisnik SET broj_pogresnih_prijava = 0 WHERE email=?;", array($eadresa));
            }
            else {
                Sesija::kreirajRelevantnePodatke(array('id' => $redak['id'], 'tip' => $redak['tip'], 'email' => $eadresa, 'ime' => $redak['ime'], 'prezime' => $redak['prezime'], 'vrijeme' => TrenutnoVrijeme(), 'slika' => $redak['link_profilna_slika']));

                if (isset($_POST['zapamtime'])) {
                    setcookie('korisnik', $eadresa, time() + 60 * 60 * 24 * 3, '', '', true, true);
                } else {
                    if (isset($_COOKIE['korisnik'])) {
                        unset($_COOKIE['korisnik']);
                    }
                }
                ZapisiULog("Korisnik se je uspješno prijavio u sustav", 'PO');
                $vezaBaze->izvrsiNekiDrugiUpit("UPDATE korisnik SET broj_pogresnih_prijava = 0 WHERE email=?;", array($eadresa));
                $putanja = dirname($_SERVER['PHP_SELF']);
                header("Location: http://$_SERVER[HTTP_HOST]$putanja/index.php");
                $vezaBaze->prekiniVezu();
                exit();
            }
        }
        else {
            echo 'Unijeli ste pogrešne korisničke podatke';
            ZapisiULog("Pogrešno unesena lozinka za korisnički račun sa e-adresom $eadresa", 'PO');
            $vezaBaze->izvrsiNekiDrugiUpit("UPDATE korisnik SET broj_pogresnih_prijava = broj_pogresnih_prijava + 1 WHERE email=?;", array($eadresa));
        }
    } else {
        echo 'Unijeli ste pogrešne korisničke podatke';
        ZapisiULog("Pokušaj prijave s nepostojećim korisničkim računom (unesena e-adresa: $eadresa)", 'PO');
    }
    $rezultatUpita->close();
    $vezaBaze->prekiniVezu();
}
else {
    if (isset($_COOKIE['aktivacija'])) {
        switch ($_COOKIE['aktivacija']) {
            case 'A':
                echo 'Vaš korisnički račun je već prije bio aktiviran!';
                break;
            case 'UA':
                echo 'Vaš korisnički račun je upravo aktiviran! Možete se prijaviti!';
                break;
            case 'N':
                echo 'Uneseni aktivacijski link ne postoji u bazi! Provjerite da li ste uistinu posjetili poveznicu koju ste zaprimili u e-mailu.';
                break;
            case 'B':
                echo 'Vaš korisnički račun je blokiran i nije ga više moguće aktivirati bez administratorske intervencije!';
                break;
        }
        unset($_COOKIE['aktivacija']);
        setcookie('aktivacija', '', 0);
    }
}

$naziv_skripte = basename(__FILE__);
require_once 'header.php';
?>

<form id="prijava" action="prijava.php" method="POST" accept-charset="UTF-8">
    <label for="eadresa">E-mail</label>
    <input type="text" id="eadresa" name="eadresa" <?php if (isset($_COOKIE['korisnik'])) echo 'value="' . $_COOKIE[korisnik] . '"'; ?>/>
    <br/>
    <label for="lozinka">Lozinka</label>
    <input type="password" id="lozinka" name="lozinka"/>
    <br/>
    <label for="zapamtime">Zapamti me?</label>
    <input type="checkbox" id="zapamtime" name="zapamtime"/>
    <br/>
    <input type="submit" value="Prijavi se!"/>
    <br/>
    <p id="zaboravljena-lozinka">Zaboravljena lozinka</p>
</form>

<?php
require_once 'footer.php';
?>