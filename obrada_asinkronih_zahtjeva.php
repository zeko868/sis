<?php
require_once 'baza.class.php';
require_once 'administratorski_parametar.php';
require_once 'sesija.class.php';

function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function dioDinamickogGeneriranjaSelectUpita($vezaBaze,$tablica,$atribut,&$listaOdnosa,&$listaJednakosti,&$listaVanjskihTablica,$listaZapisanihAtributaZaSelect) {
    $podstring_za_sql_upit = '';
    $kljucListeSvihOdnosa = json_encode(array('tablica' => $tablica, 'atribut' => $atribut));
    if (array_key_exists($kljucListeSvihOdnosa, $listaOdnosa)) {
        $refTablica = $listaOdnosa[$kljucListeSvihOdnosa]['referencirana_tablica'];
        $refAtribut = $listaOdnosa[$kljucListeSvihOdnosa]['referencirani_atribut'];
        array_push($listaJednakosti,"$tablica.$atribut=$refTablica.$refAtribut");
        array_push($listaVanjskihTablica, $refTablica);
        $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT repr_atribut_tablice FROM reprezentant_tablice WHERE naziv_tablice=? LIMIT 1;", array($refTablica));
        $repr_podstupci = $skupZapisa->fetch_row()[0];
        $skupZapisa->close();
        $polje_repr_podstupaca = explode(' ', $repr_podstupci);
        $brojPodatributa = 0;
        $prvi = true;
        foreach ($listaZapisanihAtributaZaSelect as $indeks => $brZapisanih) {
            if ($brZapisanih) {
                $prvi = false;
                $podstring_za_sql_upit .= ",',',";
                break;
            }
        }
        foreach ($polje_repr_podstupaca as $repr_podstupac) {
            array_push($listaZapisanihAtributaZaSelect, $brojPodatributa);
            $podstring_za_sql_upit .= dioDinamickogGeneriranjaSelectUpita($vezaBaze,$refTablica,$repr_podstupac,$listaOdnosa,$listaJednakosti,$listaVanjskihTablica,$listaZapisanihAtributaZaSelect);
            $brojPodatributa++;
        }
    }
    else {
        if ($listaZapisanihAtributaZaSelect[sizeof($listaZapisanihAtributaZaSelect)-1]===0) {   //ili if ($prvi)
            $podstring_za_sql_upit .= $tablica . '.' . $atribut;
        }
        else {
            $podstring_za_sql_upit .= ",','," . $tablica . '.' . $atribut;
        }
    }
    return $podstring_za_sql_upit;
}

if (is_ajax()) {
    if (isset($_POST['potraznja']) && !empty($_POST['potraznja'])) {
        header("Content-Type: application/json");
        $potraznja = $_POST['potraznja'];
        switch ($potraznja) {
            case 'najposudjivanije_knjige_neke_knjiznice':
                if (isset($_POST['idKnjiznice'])) {
                    $idKnjiznice = $_POST['idKnjiznice'];
                    vratiNajposudjivanijeKnjige($idKnjiznice);
                }
                break;
            case 'knjiznice':
                vratiKnjiznice();
                break;
            case 'trenutna_geografska_pozicija':
                if (isset($_POST['pozicija'])) {
                    $pozicija = $_POST['pozicija'];
                    dohvatiGeopoziciju($pozicija);
                }
                break;
            case 'provjera_dostupnosti_emaila':
                if (isset($_POST['eadresa'])) {
                    vratiOdgovorODostupnostiEMaila($_POST['eadresa']);
                }
                break;
            case 'vrati_strukturu_i_sadrzaj_tablice':
                if (isset($_POST['tablica']) && isset($_POST['atribut_filtriranja']) && isset($_POST['vrijednost_filtriranja'])) {
                    $brojStranice = isset($_POST['broj_stranice']) ? $_POST['broj_stranice'] : 1;
                    if (isset($_POST['redoslijed_sortiranja'])) {
                        vratiStrukturuISadrzajTablice($_POST['tablica'], $brojStranice, $_POST['atribut_filtriranja'], $_POST['vrijednost_filtriranja'], $_POST['redoslijed_sortiranja']);
                    }
                    else {
                        vratiStrukturuISadrzajTablice($_POST['tablica'], $brojStranice, $_POST['atribut_filtriranja'], $_POST['vrijednost_filtriranja']);
                    }
                }
                break;
            case 'pohrani_promjene':
                if (isset($_POST['akcija'])) {
                    switch ($_POST['akcija']) {
                        case "dodaj":
                            if (isset($_POST['tablica']) && isset($_POST['novi_podaci'])) {
                                vratiPorukuOUspjehuUnosa($_POST['tablica'], $_POST['novi_podaci']);
                            }
                            break;
                        case "promijeni":
                            if (isset($_POST['tablica']) && isset($_POST['stare_vrijednosti_kljuca']) && isset($_POST['novi_podaci'])) {
                                vratiPorukuOUspjehuAzuriranja($_POST['tablica'], $_POST['stare_vrijednosti_kljuca'], $_POST['novi_podaci']);
                            }
                            break;
                        case "obrisi":
                            if (isset($_POST['tablica']) && isset($_POST['stare_vrijednosti_kljuca'])) {
                                vratiPorukuOUspjehuBrisanja($_POST['tablica'], $_POST['stare_vrijednosti_kljuca']);
                            }
                            break;
                    }
                }
                break;
            case 'vrati_sadrzaj_comboboxa':
                if (isset($_POST['tablica']) && isset($_POST['referencirana_tablica'])) {
                    vratiSadrzajComboboxa($_POST['tablica'], $_POST['referencirana_tablica']);
                }
                break;
            case 'resetiraj_lozinku':
                if (isset($_POST['email'])) {
                    $lozinka = '';
                    $paletaZnakova = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'), str_split('!#$%&/()=?*+-_.:,;<>'));
                    $velicinaPalete = count($paletaZnakova);
                    $postojiVeliko = $postojiMalo = $postojiBroj = $postojiSimbol = false;
                    for ($i=0;$i<8 || (!$postojiVeliko || !$postojiMalo || !$postojiBroj || !$postojiSimbol);$i++) {
                        $znak = $paletaZnakova[rand(0, $velicinaPalete-1)];
                        if (strcmp($znak,'0')>=0 && strcmp($znak,'9')<=0) {
                            $postojiBroj = true;
                        }
                        else if (strcmp($znak,'a')>=0 && strcmp($znak,'z')<=0) {
                            $postojiMalo = true;
                        }
                        else if (strcmp($znak,'A')>=0 && strcmp($znak,'Z')<=0) {
                            $postojiVeliko = true;
                        }
                        else {
                            $postojiSimbol = true;
                        }
                        $lozinka .= $znak;
                    }
                    $email = $_POST['email'];
                    $vezaBaze = new Baza();
                    $vezaBaze->uspostaviVezu();
                    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT id FROM korisnik WHERE email=?;", array($email));
                    $salji = false;
                    if ($skupZapisa->num_rows) {
                        $salji = true;
                    }
                    $skupZapisa->close();
                    if ($salji && mail($email, '=?UTF-8?B?'.base64_encode("Resetiranje lozinke za Vaš korisnički račun na portalu KnjižnicaApp").'?=' , "Sukladno zaprimljenom zahtjevu za resetiranjem lozinke, u nastavku se nalazi nova: $lozinka", 'From: =?UTF-8?B?'.base64_encode('Sustav za resetiranje lozinki korisničkog računa web-aplikacije Knjižnica').'?= <WebDiP@foi.hr>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n')) {
                        $hashiranaLozinka = password_hash($lozinka, PASSWORD_DEFAULT);
                        $vezaBaze->izvrsiNekiDrugiUpit("UPDATE korisnik SET lozinka=? WHERE email=?;", array($hashiranaLozinka, $email));
                        echo json_encode(true);
                    }
                    else {
                        echo json_encode(false);
                    }
                    $vezaBaze->prekiniVezu();
                }
                break;
            case 'provjera_ispravnosti_stare_lozinke':
                if (isset($_POST['stara_lozinka'])) {
                    vratiPorukuOIspravnostiLozinke($_POST['stara_lozinka']);
                }
                break;
            case 'provjera_dostupnosti_emaila_i_ispravnosti_lozinke':
                if (isset($_POST['stara_lozinka']) && isset($_POST['eadresa'])) {
                    $dostupnostEmaila = vratiOdgovorODostupnostiEMaila($_POST['eadresa'], false);
                    $ispravnostLozinke = vratiPorukuOIspravnostiLozinke($_POST['stara_lozinka'], false);
                    echo json_encode(array('email' => $dostupnostEmaila , 'lozinka' => $ispravnostLozinke));
                }
                break;
            case 'dostupne_vrijednosti_kljuca':
                if (isset($_POST['nove_vrijednosti']) && isset($_POST['tablica'])) {
                    vratiPorukuODostupnostiVrijednostiKljuceva($_POST['nove_vrijednosti'], $_POST['tablica']);
                }
        }
    }
}

function vratiPorukuOIspravnostiLozinke($lozinka, $saljiOdmah = true) {
    $id = (int) Sesija::dajPodatak('id');
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $hashiranaLozinka = $vezaBaze->izvrsiSelectUpit("SELECT lozinka FROM korisnik WHERE id=?;", array($id))->fetch_row()[0];
    $rezultat = password_verify($lozinka, $hashiranaLozinka);
    $vezaBaze->prekiniVezu();
    if ($saljiOdmah) {
        echo json_encode($rezultat);
    }
    else {
        return $rezultat;
    }
}

function vratiNajposudjivanijeKnjige($idKnjiznice) {
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $skupZapisa = $vezaBaze->pozoviSpremljenuProceduru('najposudjivanije_knjige_medju_korisnicima', array($idKnjiznice));
    $rezultat = array();
    while ($slog = $skupZapisa->fetch_assoc()) {
        array_push($rezultat, $slog);
    }
    echo json_encode($rezultat);
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
}

function vratiKnjiznice() {
    $parametri = Administratorski_parametar::dohvatiPodatak(Administratorski_parametar::BROJ_ZAPISA_PO_STRANICI,  Administratorski_parametar::MAX_ZAPISA_BEZ_STRANICENJA);
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT knjiznica.id, knjiznica.naziv AS naziv, concat(grad.naziv, ', ', ulica.naziv, ' ', knjiznica.broj) AS lokacija FROM knjiznica, ulica, grad WHERE ulica.id = knjiznica.ulica AND grad.id = ulica.grad;");
    $knjiznice = array();
    while ($slog = $skupZapisa->fetch_assoc()) {
        array_push($knjiznice, $slog);
    }
    $rezultat = array();
    array_push($rezultat, $parametri, $knjiznice);
    echo json_encode($rezultat);
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
}

function dohvatiGeopoziciju($pozicija) {
    $sadrzajSesije = Sesija::dajRelevantnePodatke();
    $sadrzajSesije['geopozicija'] = array('lat' => $pozicija['lat'], 'lng' => $pozicija['lng']);
    Sesija::kreirajRelevantnePodatke($sadrzajSesije);
}

function vratiOdgovorODostupnostiEMaila($eadresa, $saljiOdmah = true) {
    if (Sesija::dajPodatak('email')===$eadresa) {
        return true;
    }
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT id FROM korisnik WHERE email=?;", array($eadresa));
    $rezultat = !$skupZapisa->num_rows;
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
    if ($saljiOdmah) {
        echo json_encode($rezultat);
    }
    else {
        return $rezultat;
    }
}

function vratiStrukturuISadrzajTablice ($tablica,$stranica=1,$atributFiltriranja='',$vrijednostFiltriranja='',$redoslijedSortiranja=null) {
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $relacijskaShema = array();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SHOW COLUMNS IN $tablica;");
    for ($i=0;$i<$skupZapisa->num_rows;$i++) {
        $atribut = $skupZapisa->fetch_assoc();
        $relacijskaShema[$atribut['Field']] = array_merge(array_diff_key($atribut, array('Field' => '')),array('ReferencedTable' => ''));
    }
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
    $skupZapisa = $vezaBaze->dohvatiVezeTablice();
    $vanjskeTablice = array();
    $vanjskiKljucevi = array();
    $vazeceJednakosti = array();
    $sviOdnosi = array();
    for ($i=0;$i<$skupZapisa->num_rows;$i++) {
        $redak = $skupZapisa->fetch_assoc();
        $sviOdnosi[json_encode(array('tablica' => $redak['table_name'], 'atribut' => $redak['column_name']))] = array('referencirana_tablica' => $redak['referenced_table_name'], 'referencirani_atribut' => $redak['referenced_column_name']);
        if ($redak['table_name']===$tablica) {
            array_push($vazeceJednakosti,"$tablica.$redak[column_name]=$redak[referenced_table_name].$redak[referenced_column_name]");
            array_push($vanjskeTablice, $redak['referenced_table_name']);
            array_push($vanjskiKljucevi, $redak['column_name']);
            $relacijskaShema[$redak['column_name']]['ReferencedTable'] = $redak['referenced_table_name'];
        }
    }
    $skupZapisa->close();
    if ($stranica==='0') {
        echo json_encode(array('relacijska_shema' => $relacijskaShema));
        exit();
    }
    $sqlUpit = "SELECT ";
    $brojac = 0;
    $vezaBaze->uspostaviVezu();
    foreach ($relacijskaShema as $nazivAtributa => $ostalaSvojstva) {
        $prikazuj = true;
        $pozicijaKljuca = 0;
        foreach ($vanjskiKljucevi as $vanjskiKljuc) {
            if ($vanjskiKljuc===$nazivAtributa) {
                $prikazuj = false;
                $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT repr_atribut_tablice FROM reprezentant_tablice WHERE naziv_tablice=? LIMIT 1;", array($vanjskeTablice[$pozicijaKljuca]));
                $repr_stupci = $skupZapisa->fetch_row()[0];
                $skupZapisa->close();
                $polje_repr_stupaca = explode(' ', $repr_stupci);
                $podstring_za_sql_upit = '';
                $brojAtributa = 0;
                foreach ($polje_repr_stupaca as $repr_stupac) {
                    $podstring_za_sql_upit .= dioDinamickogGeneriranjaSelectUpita ($vezaBaze,$vanjskeTablice[$pozicijaKljuca],$repr_stupac,$sviOdnosi,$vazeceJednakosti,$vanjskeTablice,array($brojAtributa));
                    $brojAtributa++;
                }
                if ($brojAtributa > 1) {
                    $podstring_za_sql_upit = "concat($podstring_za_sql_upit)";
                }
                if ($brojac===0) {
                    $sqlUpit .= "$podstring_za_sql_upit as $nazivAtributa";
                }
                else {
                    $sqlUpit .= ",$podstring_za_sql_upit as $nazivAtributa";
                }
                break;
            }
            $pozicijaKljuca++;
        }
        if ($prikazuj) {
            if ($brojac===0) {
                $sqlUpit .= "$tablica.$nazivAtributa";
            }
            else {
                $sqlUpit .= ",$tablica.$nazivAtributa";
            }            
        }
        $brojac++;
    }
    $sqlUpit .= " FROM $tablica";
    foreach ($vanjskeTablice as $nazivVanjskeTablice) {
        $sqlUpit .= ",$nazivVanjskeTablice";
    }
    $brojac = 0;
    foreach ($vazeceJednakosti as $jednakost) {
        if ($brojac===0) {
            $sqlUpit .= " WHERE $jednakost";
        }
        else {
            $sqlUpit .= " AND $jednakost";
        }
        $brojac++;
    }
    
    if ($vrijednostFiltriranja) {
        $sqlUpit = "SELECT * FROM ($sqlUpit) glavni";
        $vrijednostFiltriranja = str_replace('%', '\\%', str_replace('_', '\\_', strtolower($vrijednostFiltriranja)));
        if ($atributFiltriranja) {
            $sqlUpit .= " WHERE LOWER(glavni.$atributFiltriranja) LIKE '%$vrijednostFiltriranja%'";
        }
        else {
            $brojAtributa = 0;
            foreach ($relacijskaShema as $nazivAtributa => $ostalaSvojstva) {
                if ($brojAtributa===0) {
                    $sqlUpit .= " WHERE LOWER(glavni.$nazivAtributa) LIKE '%$vrijednostFiltriranja%'";
                }
                else {
                    $sqlUpit .= " OR LOWER(glavni.$nazivAtributa) LIKE '%$vrijednostFiltriranja%'";
                }
                $brojAtributa++;
            }
        }
    }
    if ($redoslijedSortiranja) {
        foreach ($redoslijedSortiranja as $index => $pravilo) {
            if ($index===0) {
                $sqlUpit .= " ORDER BY $pravilo[atribut] $pravilo[smjer]";
            }
            else {
                $sqlUpit .= ",$pravilo[atribut] $pravilo[smjer]";
            }
        }
    }
    $limit = (int) Administratorski_parametar::dohvatiPodatak(Administratorski_parametar::BROJ_ZAPISA_PO_STRANICI)[Administratorski_parametar::BROJ_ZAPISA_PO_STRANICI];
    $offset = $limit*($stranica-1);
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("$sqlUpit LIMIT ? OFFSET ?;", array($limit, $offset));
    $ukupanBrojZapisa = $skupZapisa->num_rows;
    $podaci = array();
    for ($i=0;$i<$ukupanBrojZapisa;$i++) {
        array_push($podaci, $skupZapisa->fetch_assoc());
    }
    $skupZapisa->close();
    if ($vrijednostFiltriranja) {
        $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT COUNT(*) FROM ($sqlUpit) cjelokupan;");
    }
    else {
        $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT COUNT(*) FROM $tablica;");
    }
    $ukupanBrojZapisa = $skupZapisa->fetch_row()[0];
    echo json_encode(array('relacijska_shema' => $relacijskaShema,'podaci' => $podaci, 'ukupan_broj_zapisa' => $ukupanBrojZapisa, 'broj_zapisa_po_stranici' => $limit));
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
}

function vratiSadrzajComboboxa($tablica, $refTablica) {
    $hardkodiraniUvjeti = '';
    if ($refTablica==='korisnik' && ($tablica==='knjiznica_knjiznicari' || $tablica==='jedinka_knjige' || $tablica==='knjiga_kategorija')) {
        $hardkodiraniUvjeti = "(korisnik.tip='M' OR korisnik.tip='A')";
    }
    echo json_encode(dohvatiReprezentantnePodatkeTablice($refTablica, '', '', $hardkodiraniUvjeti));
}

function dohvatiReprezentantnePodatkeTablice($tablica, $trazenaVrijednost='', $trazeniTekst='', $hardkodiraniUvjeti='') {
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT repr_atribut_tablice FROM reprezentant_tablice WHERE naziv_tablice=? LIMIT 1;", array($tablica));
    $relevantniAtributi = $skupZapisa->fetch_row()[0];
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
    $listaRelAtributa = explode(' ', $relevantniAtributi);

    $skupZapisa = $vezaBaze->dohvatiVezeTablice();
    $vanjskeTablice = array();
    $vanjskiKljucevi = array();
    $vazeceJednakosti = array();
    $sviOdnosi = array();
    for ($i=0;$i<$skupZapisa->num_rows;$i++) {
        $redak = $skupZapisa->fetch_assoc();
        $sviOdnosi[json_encode(array('tablica' => $redak['table_name'], 'atribut' => $redak['column_name']))] = array('referencirana_tablica' => $redak['referenced_table_name'], 'referencirani_atribut' => $redak['referenced_column_name']);
        if ($redak['table_name']===$tablica && in_array($redak['column_name'], $listaRelAtributa)) {
            array_push($vazeceJednakosti,"$tablica.$redak[column_name]=$redak[referenced_table_name].$redak[referenced_column_name]");
            array_push($vanjskeTablice, $redak['referenced_table_name']);
            array_push($vanjskiKljucevi, $redak['column_name']);
        }
    }

    $skupZapisa->close();
    
    $brojac = 0;
    $vezaBaze->uspostaviVezu();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SHOW COLUMNS IN $tablica;");
    $redak;
    do {
        $redak = $skupZapisa->fetch_assoc();
    } while ($redak['Key']!=='PRI');
    $skupZapisa->close();
    $sqlUpit = "SELECT * FROM (SELECT $tablica.$redak[Field] AS vrijednost, concat(";
    
    foreach ($listaRelAtributa as $relAtribut) {
        $prikazuj = true;
        $pozicijaKljuca = array_search($relAtribut, $vanjskiKljucevi);
        if ($pozicijaKljuca!==false) {
            $prikazuj = false;
            $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT repr_atribut_tablice FROM reprezentant_tablice WHERE naziv_tablice=? LIMIT 1;", array($vanjskeTablice[$pozicijaKljuca]));
            $repr_stupci = $skupZapisa->fetch_row()[0];
            $skupZapisa->close();
            $polje_repr_stupaca = explode(' ', $repr_stupci);
            $podstring_za_sql_upit = '';
            $brojAtributa = 0;
            foreach ($polje_repr_stupaca as $repr_stupac) {
                $podstring_za_sql_upit .= dioDinamickogGeneriranjaSelectUpita ($vezaBaze,$vanjskeTablice[$pozicijaKljuca],$repr_stupac,$sviOdnosi,$vazeceJednakosti,$vanjskeTablice,array($brojAtributa));
                $brojAtributa++;
            }
            if ($brojAtributa > 1) {
                $podstring_za_sql_upit = "concat($podstring_za_sql_upit)";
            }
            if ($brojac===0) {
                $sqlUpit .= "$podstring_za_sql_upit";
            }
            else {
                $sqlUpit .= ",',',$podstring_za_sql_upit";
            }
        }
        if ($prikazuj) {
            if ($brojac===0) {
                $sqlUpit .= "$tablica.$relAtribut";
            }
            else {
                $sqlUpit .= ",',',$tablica.$relAtribut";
            }            
        }
        $brojac++;
    }
    
    $sqlUpit .= ") AS tekst FROM $tablica";
    foreach ($vanjskeTablice as $nazivVanjskeTablice) {
        $sqlUpit .= ",$nazivVanjskeTablice";
    }
    $brojac = 0;
    foreach ($vazeceJednakosti as $jednakost) {
        if ($brojac===0) {
            $sqlUpit .= " WHERE $jednakost";
        }
        else {
            $sqlUpit .= " AND $jednakost";
        }
        $brojac++;
    }
    if ($hardkodiraniUvjeti) {
        if ($brojac===0) {
            $sqlUpit .= " WHERE $hardkodiraniUvjeti";
        }
        else {
            $sqlUpit .= " AND $hardkodiraniUvjeti";
        }
    }
    $sqlUpit .= ') glavni';
    $brojac = 0;
    if ($trazenaVrijednost) {
        if ($brojac===0) {
            $sqlUpit .= " WHERE glavni.vrijednost='$trazenaVrijednost'";
        }
        else {
            $sqlUpit .= " AND glavni.vrijednost='$trazenaVrijednost'";
        }
        $brojac++;
    }
    if ($trazeniTekst) {
        if ($brojac===0) {
            $sqlUpit .= " WHERE glavni.tekst='$trazeniTekst'";
        }
        else {
            $sqlUpit .= " AND glavni.tekst='$trazeniTekst'";
        }
    }
    $rezultat = array();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("$sqlUpit ORDER BY glavni.tekst ASC;");
    for ($i=0;$i<$skupZapisa->num_rows;$i++) {
        array_push($rezultat, $skupZapisa->fetch_assoc());
    }
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
    return $rezultat;
}

function vratiPorukuOUspjehuUnosa ($tablica, $noveVrijednosti) {
    $noveVrijednosti = json_decode($noveVrijednosti);
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SHOW COLUMNS IN $tablica;");
    $tabliceINullability = array();
    for ($i=0;$i<$skupZapisa->num_rows;$i++) {
        $redak = $skupZapisa->fetch_assoc();
        $tabliceINullability[$redak['Field']] = $redak['Null'];
    }
    $skupZapisa->close();
    $upit = "INSERT INTO $tablica (";
    $brojac = 0;
    foreach ($noveVrijednosti as $nazivAtributa => $vrijednost) {
        if ($brojac===0) {
            $upit .= $nazivAtributa;
        }
        else {
            $upit .= ",$nazivAtributa";
        }
        $brojac++;
    }
    $upit .= ') VALUES (';
    $brojac = 0;
    foreach ($noveVrijednosti as $nazivAtributa => $vrijednost) {
        if ($brojac) {
            $upit .= ',';
        }
        if ($tabliceINullability[$nazivAtributa]==='YES' && $vrijednost==='') {
            $upit .= "null";
        }
        else {
            $upit .= "'$vrijednost'";
        }
        $brojac++;
    }
    $vezaBaze->izvrsiNekiDrugiUpit("$upit);");
    echo json_encode($vezaBaze->sifraPogreske()===0);
    $vezaBaze->prekiniVezu();
}

function vratiPorukuOUspjehuAzuriranja ($tablica, $dosadasnjiKljucevi, $noveVrijednosti) {
    $dosadasnjiKljucevi = json_decode($dosadasnjiKljucevi, true);
    $noveVrijednosti = json_decode($noveVrijednosti, true);
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SHOW COLUMNS IN $tablica;");
    $tabliceINullability = array();
    for ($i=0;$i<$skupZapisa->num_rows;$i++) {
        $redak = $skupZapisa->fetch_assoc();
        $tabliceINullability[$redak['Field']] = $redak['Null'];
    }
    $skupZapisa->close();
    $upit = "UPDATE $tablica SET ";
    $brojac = 0;
    foreach ($noveVrijednosti as $kljuc => $vrijednost) {
        if ($brojac) {
            $upit .= ',';
        }
        if ($tabliceINullability[$kljuc]==='YES' && $vrijednost==='') {
            $upit .= "$kljuc=null";
        }
        else {
            $upit .= "$kljuc='$vrijednost'";
        }
        $brojac++;
    }
    $brojac = 0;
    $upit .= ' WHERE ';
    foreach ($dosadasnjiKljucevi as $kljuc => $vrijednost) {
        if ($brojac) {
            $upit .= ' AND ';
        }
        $upit .= "$kljuc='$vrijednost'";
        $brojac++;        
    }
    $vezaBaze->izvrsiNekiDrugiUpit("$upit;");
    echo json_encode($vezaBaze->sifraPogreske()===0);
    $vezaBaze->prekiniVezu();
}

function vratiPorukuOUspjehuBrisanja($tablica, $dosadasnjiKljucevi) {
    $dosadasnjiKljucevi = json_decode($dosadasnjiKljucevi, true);
    $vezaBaze = new Baza();
    $skupZapisa = $vezaBaze->dohvatiVezeTablice($tablica);
    $vanjskiKljuceviIReference = array();
    for ($i=0;$i<$skupZapisa->num_rows;$i++) {
        $redak = $skupZapisa->fetch_assoc();
        $vanjskiKljuceviIReference[$redak['column_name']] = $redak['referenced_table_name'];
    }
    $skupZapisa->close();
    
    $upit = "DELETE FROM $tablica WHERE ";
    $brojac = 0;
    foreach ($dosadasnjiKljucevi as $kljuc => $vrijednost) {
        if ($brojac) {
            $upit .= 'AND ';
        }
        if (array_key_exists($kljuc, $vanjskiKljuceviIReference)) {
            $vrijednost = dohvatiReprezentantnePodatkeTablice($vanjskiKljuceviIReference[$kljuc], '', $vrijednost)[0]['vrijednost'];
        }
        $upit .= "$kljuc='$vrijednost'";
        $brojac++;        
    }
    $vezaBaze->uspostaviVezu();
    $vezaBaze->izvrsiNekiDrugiUpit("$upit;");
    echo json_encode(!!$vezaBaze->dohvatiBrojObuhvacenihRedovaProsleRadnje());
    $vezaBaze->prekiniVezu();
}

function vratiDostupnostVrijednosti($novaVrijednost, $tablica, $atribut) {
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("SELECT $atribut FROM $tablica WHERE $atribut='$novaVrijednost' LIMIT 1;");
    echo json_encode(!$skupZapisa->num_rows);
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
}

function vratiPorukuODostupnostiVrijednostiKljuceva($noveVrijednosti, $nazivTablice) {
    $vezaBaze = new Baza();
    $vezaBaze->uspostaviVezu();
    $sqlUpit = "SELECT * FROM $nazivTablice WHERE ";
    $brojac = 0;
    foreach ($noveVrijednosti as $kljuc => $vrijednost) {
        if ($brojac) {
            $sqlUpit .= ' AND ';
        }
        $sqlUpit .= "$kljuc='$vrijednost'";
        $brojac++;
    }
    $skupZapisa = $vezaBaze->izvrsiSelectUpit("$sqlUpit;");
    echo json_encode(!$skupZapisa->num_rows);
    $skupZapisa->close();
    $vezaBaze->prekiniVezu();
}
?>