<?php
$naziv_skripte = basename(__FILE__);
require_once 'header.php';

require_once 'sesija.class.php';

$listaPogresaka = array();
function ispisiPogresku($nazivPolja) {
    global $listaPogresaka;
    if (array_key_exists($nazivPolja, $listaPogresaka)) {
        return '<p class="greska">' . $listaPogresaka[$nazivPolja] . '</p>';
    }
    return '';
}

if (!empty($_POST)) {
    $id = (int) Sesija::dajPodatak('id');
    $stariEmail = Sesija::dajPodatak('email');
    foreach (array_diff_key($_POST, array('slika' => '')) as $kljuc => $vrijednost) {
        if (empty($vrijednost)) {
            $listaPogresaka[$kljuc] = "Ovo polje ne smije biti prazno!";
        } else {
            switch ($kljuc) {
                case 'ime':
                case 'prezime':
                    if (!preg_match("/(?!^[\\s\\-])^(((^|\\s|\\-)([A-ZČĆŠŽĐ])([a-zčćšžđ])*)+)$/u", $vrijednost)) {
                        $listaPogresaka[$kljuc] = "Uneseno $kljuc nije u duhu hrvatskog standardnog jezika (tj. njegovog pravopisa).";
                    }
                    break;
                case 'staralozinka':
                    require_once 'baza.class.php';
                    $vezaBaze = new Baza();
                    $vezaBaze->uspostaviVezu();
                    $hashiranaLozinka = $vezaBaze->izvrsiSelectUpit("SELECT lozinka FROM korisnik WHERE id=?;", array($id))->fetch_row()[0];
                    if (!password_verify($vrijednost, $hashiranaLozinka)) {
                        $listaPogresaka['staralozinka'] = 'Pogrešno unesena stara lozinka!';
                    }
                    $vezaBaze->prekiniVezu();
                    break;
                case 'lozinka':
                    if (!preg_match("/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\\$\\?#!])(.{8,})/", $vrijednost)) {
                        $listaPogresaka['lozinka'] = 'Unesena lozinka ne sadrži barem jedno malo slovo, jedno veliko slovo, jedan broj, jedan simbol i da pritom ima minimalno 8 znakova!';
                    }
                    break;
                case 'email':
                    if (!preg_match("/(?=.*@.*\\..*)(^(?!.*\\.{2,}))(^[\\w!#\\$%&'\\*\\+\\-\\/\\=\\?\\^`\\{\\|\\}~][\\w!#\\$%&'\\*\\+\\-\\/\\=\\?\\^`\\{\\|\\}~\\.]{0,63})@([A-Za-z0-9][A-Za-z0-9\\-\\.]{0,63})\\.([A-Za-z0-9]+$)/", $vrijednost)) {
                        $listaPogresaka['email'] = 'Unesena e-mail adresa nije u valjanom formatu!';
                    } else {
                        $email = $_POST['email'];
                        if ($email!==$stariEmail) {
                            require_once 'baza.class.php';
                            $instancaBaze = new Baza();
                            $instancaBaze->uspostaviVezu();
                            $rezultatUpita = $instancaBaze->izvrsiSelectUpit("SELECT email FROM korisnik WHERE email=?;", array($email));
                            if ($rezultatUpita->num_rows !== 0) {
                                $listaPogresaka['email'] = 'U bazi podataka već postoji neki drugi korisnik sa navedenom e-mail adresom!';
                            }
                            $rezultatUpita->close();
                            $instancaBaze->prekiniVezu();
                        }
                    }
                    break;
            }
        }
    }

    $userfile = null;
    $nazivSlike;
    if (!empty($_FILES['slika']['name'])) {
        $userfile = $_FILES['slika']['tmp_name'];
        $nazivSlike = $_FILES['slika']['name'];
        if (!preg_match('/((\\.jpg)|(\\.jpeg)|(\\.png)|(\\.bmp)|(\\.gif)|(\\.tiff))$/i', $nazivSlike)) {
            $listaPogresaka['slika'] = 'Format slike nije podržan! Dozvoljeni formati slike su .jpg, .jpeg, .bmp, .gif, .png i .tiff.';
        }
    }

    if (empty($listaPogresaka)) {
        require_once 'trenutno_vrijeme.php';
        $instancaBaze = new Baza();
        $ime = $_POST['ime'];
        $prezime = $_POST['prezime'];
        $lozinka = password_hash($_POST['lozinka']);
        $email = $_POST['email'];
        $urlSlike = 'link_profilna_slika';
        $makniSliku = false;
        if (isset($_POST['brisisliku'])) {
            if (filter_input(INPUT_POST, 'brisisliku') === 'da') {
                $makniSliku = true;
            }
        }
        if (!$makniSliku) {
            if (is_uploaded_file($userfile)) {
                $nazivSlike = md5($email . $nazivSlike . date('Y-m-d H:i:s', TrenutnoVrijeme())) . substr($nazivSlike, strrpos($nazivSlike, '.'));
                move_uploaded_file($userfile, "img/$nazivSlike");
                $urlSlike = "img/$nazivSlike";
                $makniSliku = true;
            }
        }
        else {
            $urlSlike = null;
        }

        $naredba = <<<SQL_NAREDBA
UPDATE korisnik SET
ime=?,
prezime=?,
lozinka=?,
email=?,
link_profilna_slika=?
WHERE id=?;
SQL_NAREDBA;
        $instancaBaze->uspostaviVezu();
        $rezultat = $instancaBaze->izvrsiSelectUpit("SELECT link_profilna_slika FROM korisnik WHERE id=?;", array($id));
        $linkDoStareSlike = $rezultat->fetch_assoc()['link_profilna_slika'];
        $rezultat->close();
        $instancaBaze->izvrsiNekiDrugiUpit($naredba, array($ime, $prezime, $lozinka, $email, $urlSlike, $id));
        $instancaBaze->prekiniVezu();
        if ($linkDoStareSlike && $makniSliku) {
            unlink($linkDoStareSlike);
        }
        $prosliPodaciSesije = Sesija::dajRelevantnePodatke();
        $prosliPodaciSesije['email'] = $email;
        if ($urlSlike!==null) {
            $prosliPodaciSesije['slika'] = $urlSlike;
        }
        else {
            $prosliPodaciSesije['slika'] = null;
        }
        $prosliPodaciSesije['ime'] = $ime;
        $prosliPodaciSesije['prezime'] = $prezime;
        Sesija::kreirajRelevantnePodatke($prosliPodaciSesije);
        header('Location: index.php');
        exit();
    } else {
        $poruka = '';
        foreach ($listaPogresaka as $linija) {
            $poruka .= $linija . '<br/>';
        }
        require_once 'dnevnik.php';
        ZapisiULog(join(' ', $listaPogresaka), 'PR');
    }
}
?>

<form id="promjena-podataka" action="<?php echo htmlentities($_SERVER['REQUEST_URI']); ?>" method="post" enctype="multipart/form-data">
    <label for="ime" class="labela">Ime</label>
    <input type="text" name="ime" id="ime" class="input" value="<?php echo htmlspecialchars(Sesija::dajPodatak('ime')); ?>"/>
    <?php echo ispisiPogresku('ime');?>
    <br/>
    <label for="prezime" class="labela">Prezime</label>
    <input type="text" name="prezime" id="prezime" class="input" value="<?php echo htmlspecialchars(Sesija::dajPodatak('prezime')); ?>"/>
    <?php echo ispisiPogresku('prezime');?>
    <br/>
    <label for="email" class="labela">E-mail</label>
    <input type="text" name="email" id="email" class="input" value="<?php echo htmlspecialchars(Sesija::dajPodatak('email')); ?>"/>
    <?php echo ispisiPogresku('email');?>
    <br/>
    <label for="staralozinka" class="labela">Stara lozinka</label>
    <input type="password" name="staralozinka" id="staralozinka" class="input"/>
    <?php echo ispisiPogresku('staralozinka');?>
    <br/>
    <label for="lozinka" class="labela">Nova lozinka</label>
    <input type="password" name="lozinka" id="lozinka" class="input"/>
    <?php echo ispisiPogresku('lozinka');?>
    <br/>
    <label for="slika" class="labela">Trenutna slika</label>
    <img id="slika" src="<?php echo (Sesija::dajPodatak('slika') === null ? 'img/slikaosobe.png' : Sesija::dajPodatak('slika')) ; ?>" alt="slika osobe" height="250"/>
    <br/>
    <label for="novaslika" class="labela">Dodaj novu sliku</label>
    <input type="file" name="slika" id="novaslika"/>
    <?php echo ispisiPogresku('slika');?>
    <br/>
    <?php
    if (Sesija::dajPodatak('slika')!==null) {
        ?>
        <label for="brisisliku" class="labela">Ne želim imati više sliku</label>
        <input id="brisisliku" name="brisisliku" type="checkbox" value="da"/>
        <br/>
        <?php
    }
    ?>
    <br/>
    <label for="spremi" class="labela">Spremi promjene</label>
    <input type="submit" id="spremi" value="Spremi"/>
</form>

<?php
require_once 'footer.php';
?>