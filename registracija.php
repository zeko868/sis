<?php

$listaPogresaka = array();
function ispisiPogresku($nazivPolja) {
    global $listaPogresaka;
    if (array_key_exists($nazivPolja, $listaPogresaka)) {
        return '<p class="greska">' . $listaPogresaka[$nazivPolja] . '</p>';
    }
    return '';
}

function pripremiVrijednost($nazivVarijable, $defaultnaVrijednost = "") {
    return isset($_POST[$nazivVarijable]) ? $_POST[$nazivVarijable] : $defaultnaVrijednost;
}

if (!empty($_POST)) {
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
                case 'lozinka':
                    if (!preg_match("/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\\$\\?#!])(.{8,})/", $vrijednost)) {
                        $listaPogresaka['lozinka'] = 'Unesena lozinka ne sadrži barem jedno malo slovo, jedno veliko slovo, jedan broj, jedan simbol i da pritom ima minimalno 8 znakova!';
                    }
                    break;
                case 'lozinka2':
                    if (!preg_match('/^(' . preg_quote($vrijednost) . ')$/', filter_input(INPUT_POST, 'lozinka'))) {
                        $listaPogresaka['lozinka2'] = 'Lozinka i ponovljena lozinka se ne podudaraju!';
                    }
                    break;
                case 'email':
                    if (!preg_match("/(?=.*@.*\\..*)(^(?!.*\\.{2,}))(^[\\w!#\\$%&'\\*\\+\\-\\/\\=\\?\\^`\\{\\|\\}~][\\w!#\\$%&'\\*\\+\\-\\/\\=\\?\\^`\\{\\|\\}~\\.]{0,63})@([A-Za-z0-9][A-Za-z0-9\\-\\.]{0,63})\\.([A-Za-z0-9]+$)/", $vrijednost)) {
                        $listaPogresaka['email'] = 'Unesena e-mail adresa nije u valjanom formatu!';
                    }
                    else {
                        require_once 'baza.class.php';
                        $instancaBaze = new Baza();
                        $instancaBaze->uspostaviVezu();
                        $email = $_POST['email'];
                        $rezultatUpita = $instancaBaze->izvrsiSelectUpit("SELECT email FROM korisnik WHERE email=?;", array($email));
                        if ($rezultatUpita->num_rows !== 0) {
                            $listaPogresaka['email'] = 'U bazi podataka već postoji korisnik sa navedenom e-mail adresom!';
                        }
                        $rezultatUpita->close();
                        $instancaBaze->prekiniVezu();
                    }
                    break;
                case 'g-recaptcha-response':
                    $secretKey = '6LeK_h4TAAAAAI2Ke9cms1yQeOKEtAe3TqBj37np';
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $secretKey . "&response=" . $vrijednost . "&remoteip=" . $ip);
                    $responseKeys = json_decode($response, true);
                    if (intval($responseKeys["success"]) !== 1) {
                        $listaPogresaka['g-recaptcha-response'] = 'Pogrešno riješena anti-botovska provjera! Jeste li uistinu robot?';
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
        $trenutnoVrijeme = TrenutnoVrijeme();
        require_once 'trenutno_vrijeme.php';
        $instancaBaze = new Baza();
        $ime = $_POST['ime'];
        $prezime = $_POST['prezime'];
        $hashiranaLozinka = password_hash($_POST['lozinka'], PASSWORD_DEFAULT);
        $datum = date('Y-m-d H:i:s', $trenutnoVrijeme);
        $email = $_POST['email'];
        $urlSlike = null;
        if ($userfile!==null) {
            $nazivSlike = md5($email . $nazivSlike . $datum) . substr($nazivSlike, strrpos($nazivSlike, '.'));
            if (is_uploaded_file($userfile)) {
                move_uploaded_file($userfile, "img/$nazivSlike");
                $urlSlike = "img/$nazivSlike";
            }            
        }

        $uzorakZaKod = md5($trenutnoVrijeme . $email);
        $ponovi = true;
        $aktivacijskiKod;
        $instancaBaze->uspostaviVezu();
        while ($ponovi) {
            $aktivacijskiKod = '';
            for ($i = 0; $i < 10; $i++) {
                $aktivacijskiKod .= $uzorakZaKod[rand(0, strlen($uzorakZaKod) - 1)];
            }
            $rezultat = $instancaBaze->izvrsiSelectUpit("SELECT * FROM aktivacija_korisnika WHERE aktivacijski_kod=?;", array($aktivacijskiKod));
            $ponovi = $rezultat->num_rows;
            $rezultat->close();
        }

        $naredba = <<<SQL_NAREDBA
INSERT INTO korisnik VALUES(
default,
?,
?,
?,
?,
?,
default,
?,
default
);
SQL_NAREDBA;
        $enkodiraniAktivacijskiKod = urlencode($aktivacijskiKod);
        $direktorij = dirname($_SERVER['PHP_SELF']);
        if (!mail($email, '=?UTF-8?B?'.base64_encode("Aktivacija korisnika s imenom $ime $prezime na stranici portala Knjižnica").'?=', "Kliknite na sljedeću poveznicu kako biste aktivirali Vaš korisnički račun: https://$_SERVER[HTTP_HOST]$direktorij/aktivacija.php?kod=$enkodiraniAktivacijskiKod", 'From: =?UTF-8?B?'.base64_encode('Sustav za potvrdu registracija web-aplikacije Knjižnica').'?= <WebDiP@foi.hr>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n')) {
            $listaPogresaka['ostalo'] = 'Dogodila se pogreška prilikom slanja aktivacijskog linka! Provjerite ispravnost e-mail adrese ili pokušajte kasnije';
            $instancaBaze->prekiniVezu();
        } else {
            $instancaBaze->izvrsiNekiDrugiUpit($naredba, array($email, $hashiranaLozinka, $ime, $prezime, $datum, $urlSlike));
            $instancaBaze->izvrsiNekiDrugiUpit("INSERT INTO aktivacija_korisnika VALUES((SELECT id FROM korisnik WHERE email=?),?,?);", array($email, $aktivacijskiKod, $datum));
            $instancaBaze->prekiniVezu();
            header('Location: prijava.php');
            exit(0);
        }
    }
}

$naziv_skripte = basename(__FILE__);
require_once 'header.php';

?>


<form id="registracija" action="registracija.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <label for="ime" class="labela">Ime</label>
    <input type="text" name="ime" id="ime" class="input" value="<?php echo htmlspecialchars(pripremiVrijednost('ime')); ?>"/>
    <?php echo ispisiPogresku('ime');?>
    <br/>
    <label for="prezime" class="labela">Prezime</label>
    <input type="text" name="prezime" id="prezime" class="input" value="<?php echo htmlspecialchars(pripremiVrijednost('prezime')); ?>"/>
    <?php echo ispisiPogresku('prezime');?>
    <br/>
    <label for="email" class="labela">E-mail</label>
    <input type="text" name="email" id="email" class="input" value="<?php echo htmlspecialchars(pripremiVrijednost('email', '')); ?>"/>
    <?php echo ispisiPogresku('email');?>
    <br/>
    <label for="lozinka" class="labela">Lozinka</label>
    <input type="password" name="lozinka" id="lozinka" class="input"/>
    <?php echo ispisiPogresku('lozinka');?>
    <br/>
    <label for="lozinka2" class="labela">Ponovljeni unos lozinke</label>
    <input type="password" name="lozinka2" id="lozinka2" class="input"/>
    <?php echo ispisiPogresku('lozinka2');?>
    <br/>
    <label for="notrobot" class="labela">Potvrdite da niste robot</label>
    <div id="notrobot" class="g-recaptcha" data-sitekey="6LeK_h4TAAAAAF1JOdcVzeAzr452z9W5YN62buJu"></div>
    <?php echo ispisiPogresku('g-recaptcha-response');?>
    <br/>
    <label for="slika" class="labela">Slika</label>
    <input type="file" name="slika" id="slika" class="input" accept="image/*"/>
    <?php echo ispisiPogresku('slika');?>
    <br/>
    <input type="submit" value="Registriraj se!" class="slanje"/>
    <?php echo ispisiPogresku('ostalo');?>
</form>

<?php

require_once 'footer.php';

?>