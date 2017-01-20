<?php

require_once '../baza.class.php';
require_once '../trenutno_vrijeme.php';

if (!empty($_GET)) {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $vezaBaze = new Baza();
        $vezaBaze->uspostaviVezu();
        $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT tip,email,ime,prezime,link_profilna_slika FROM korisnik WHERE id=$id;");
        if ($skupZapisa->num_rows) {
            $redak = $skupZapisa->fetch_assoc();
            Sesija::kreirajRelevantnePodatke(array('id' => $id, 'tip' => $redak['tip'], 'email' => $redak['email'], 'ime' => $redak['ime'], 'prezime' => $redak['prezime'], 'vrijeme' => TrenutnoVrijeme(), 'slika' => $redak['link_profilna_slika']));
            $skupZapisa->close();
            $vezaBaze->prekiniVezu();
            header('Location: ../index.php');
            exit();
        }
        $skupZapisa->close();
        $vezaBaze->prekiniVezu();
    }
}

?>

<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Ispis svih korisnika</title>
        <meta charset="utf-8"/>
        <style type="text/css">
            caption {
                font-weight: bold;
                font-size: 24px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                border: solid 2px black;
            }
            th,td {
                text-align: center;
                border: solid 1px gray;
            }
            th {
                font-weight: bold;
            }
            .onemogucen {
                color: red;
            }
        </style>
    </head>
    <body>
<?php

$naziv_skripte = basename(__FILE__);

$vezaBaze = new Baza();
$vezaBaze->uspostaviVezu();
$skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT id,email,lozinka,tip FROM korisnik;");
echo '<table><caption>Popis svih korisnika aplikacije</caption><thead><tr><th>E-mail</th><th>Hashirana lozinka s kriptografskom soli, cijenom i šifrom algoritma</th><th>Tip korisnika</th><th>Prijava u sustav sa njegovim računom</th></tr></thead><tbody>';
$brojZapisa = $skupZapisa->num_rows;
for ($i=0;$i<$brojZapisa;$i++) {
    $redak = $skupZapisa->fetch_assoc();
    $prijava = "$naziv_skripte?id=$redak[id]";
    $klasa = $redak['tip']==='N' ? ' class="onemogucen"' : '';
    echo '<tr$klasa><td>' . htmlspecialchars($redak['email']) . '</td><td>' . htmlspecialchars($redak['lozinka']) . '</td><td>' . htmlspecialchars($redak['tip']) . '</td><td><a href=\"$prijava\">Prijava u sustav</a></td></tr>';
}
echo '</tbody></table>';
$skupZapisa->close();
$vezaBaze->prekiniVezu();

?>

    </body>
</html>
