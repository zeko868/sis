<?php
require_once 'sesija.class.php';
require_once 'baza.class.php';
require_once 'trenutno_vrijeme.php';

if (isset($_POST['stranica'])) {
    $protokol = 'http';
    if ($_POST['stranica']==='prijava.php' || $_POST['stranica']==='registracija.php' || $_POST['stranica']==='moj-profil.php') {
        $protokol = 'https';
    }
    $direktorij = dirname($_SERVER['REQUEST_URI']);
    header("Location: $protokol://$_SERVER[HTTP_HOST]$direktorij/$_POST[stranica]");
    exit();
}

if ($naziv_skripte==='prijava.php' || $naziv_skripte==='registracija.php' || $naziv_skripte==='moj-profil.php') {
    if($_SERVER["HTTPS"] != "on") {
        header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

$vezaBaze = new Baza();
$vezaBaze->uspostaviVezu();
$skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT id FROM stranica_upit WHERE naziv=? LIMIT 1;", array($naziv_skripte), false);
$idStranice;
if ($skupZapisa->num_rows) {
    $idStranice = $skupZapisa->fetch_row()[0];
}
else {
    $vezaBaze->izvrsiNekiDrugiUpit("INSERT INTO stranica_upit VALUES (null,?,'S');", array($naziv_skripte));
    $idStranice = (int) $vezaBaze->izvrsiSelectUpit("SELECT id FROM stranica_upit WHERE naziv=? LIMIT 1;", array($naziv_skripte), false)->fetch_row()[0];
}
$skupZapisa->close();
$vrijeme = date('Y-m-d H:i:s', TrenutnoVrijeme());
$idKorisnika = Sesija::dajPodatak('id');
$vezaBaze->izvrsiNekiDrugiUpit("INSERT INTO posjet_stranica_upit VALUES (null, ?, ?, ?);", array($idStranice, $idKorisnika, $vrijeme));

$tipKorisnika = Sesija::dajPodatak('tip');
if ($tipKorisnika===null) {
    $tipKorisnika=' ';
}
$skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT DISTINCT stranica_upit.naziv,navigacija.naziv_stranice FROM navigacija,stranica_upit,tip_korisnika tk1,tip_korisnika tk2 WHERE tk1.id=? AND ((tk1.id=navigacija.tip_korisnika) OR (tk1.prioritet>tk2.prioritet AND navigacija.tip_korisnika=tk2.id AND navigacija.gornje_nasljedjivanje=1)) AND stranica_upit.id=navigacija.skripta ORDER BY navigacija.red_u_listi ASC;", array($tipKorisnika));
$listaStranica = array();
$naziv_stranice;
while ($redak = $skupZapisa->fetch_assoc()) {
    array_push($listaStranica, $redak);
    if ($redak['naziv']===$naziv_skripte) {
        $naziv_stranice = $redak['naziv_stranice'];
    }
}
$skupZapisa->close();
$vezaBaze->prekiniVezu();
if (!$naziv_stranice) {
    $direktorij = dirname($_SERVER['REQUEST_URI']);
    header("Location: http://$_SERVER[HTTP_HOST]$direktorij/index.php");
}

?>
<!DOCTYPE html>
<html>
    <head lang="hr">
        <meta charset="utf-8"/>
        <title><?php echo htmlspecialchars($naziv_stranice); ?></title>
        <link rel="stylesheet" type="text/css" href="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables_themeroller.css">
        <link rel="stylesheet" type="text/css" href="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css">
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
        <link rel="stylesheet" type="text/css" href="css/petsestak3.css">
        <?php
        if ($naziv_skripte==='registracija.php') {
            echo '<script src="https://www.google.com/recaptcha/api.js"></script>';
        }
        ?>
    </head>
    <body>
        <div id="cover"></div>
        <header>
            <form action="header.php" method="POST">
                <label for="popisstranica">Trenutna stranica</label>
                <select id="popisstranica" name="stranica">
                    <?php
                        foreach ($listaStranica as $stranica) {
                            $oznacen = '';
                            if ($naziv_stranice===$stranica['naziv_stranice']) {
                                $oznacen = ' selected';
                            }
                            $stranica['naziv_stranice'] = htmlspecialchars($stranica['naziv_stranice']);
                            $stranica['naziv'] = htmlspecialchars($stranica['naziv']);

                            echo "<option value=\"$stranica[naziv]\"$oznacen>$stranica[naziv_stranice]</option>";
                        }
                    ?>
                </select>
                <noscript>
                    <input type="submit" value="Idi na stranicu"/>
                </noscript>
            </form>
            <div>
                <?php
                if ($idKorisnika === null) {
                    echo '<a href="prijava.php">Prijavi se</a>';
                } else {
                    $vezaBaze = new Baza();
                    $vezaBaze->uspostaviVezu();
                    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT ime,prezime,link_profilna_slika FROM korisnik WHERE id=? LIMIT 1;", array($idKorisnika));
                    $vezaBaze->prekiniVezu();
                    $rezultat = $skupZapisa->fetch_assoc();
                    $linkSlike = $rezultat['link_profilna_slika'] === null ? 'img/slikaosobe.png' : $rezultat['link_profilna_slika'];
                    $rezultat['ime'] = htmlspecialchars($rezultat['ime']);
                    $rezultat['prezime'] = htmlspecialchars($rezultat['prezime']);
                    echo "<p>$rezultat[ime] $rezultat[prezime]</p><img id=\"slika-profila\" src=\"$linkSlike\" alt=\"slika osobe\"/>";
                    $skupZapisa->close();
                    echo '<img id="prijavljen-opcije" src="img/opcije.png" alt="slika opcija"/><div id="padajuci-izbornik"><ul><li><a href="moj-profil.php">Moj profil</a></li><li><a href="odjava.php">Odjava</a></li></ul></div>';
                }
                ?>
            </div>
        </header>