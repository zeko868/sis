<?php

require_once 'baza.class.php';
require_once 'administratorski_parametar.php';

function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

if (is_ajax()) {
    header('Content-Type: application/json');
    $userfile = $_FILES["upload-file"]['tmp_name'];
    if (is_uploaded_file($userfile)) {
        if (isset($_POST['naziv_tablice']) && isset($_POST['delimiter']) && isset($_POST['redoslijed_atributa']) && isset($_POST['koristi_brojace'])) {
            $nazivTablice = $_POST['naziv_tablice'];
            $delimiter = $_POST['delimiter'];
            $redoslijedAtributa = explode(',', $_POST['redoslijed_atributa']);
            $koristiBrojace = $_POST['koristi_brojace']==='false' ? false : true;

            $sqlUpit = "INSERT INTO $nazivTablice (";
            $brojAtributa = sizeof($redoslijedAtributa);
            for ($i=0;$i<$brojAtributa;$i++) {
                if ($i===0) {
                    $sqlUpit .= $redoslijedAtributa[$i];
                }
                else {
                    $sqlUpit .= ",$redoslijedAtributa[$i]";
                }
            }
            $upraviteljDatoteke = fopen($userfile, 'r');
            $sadrzajDatoteke = fread($upraviteljDatoteke, filesize($userfile));
            $redovi = explode(PHP_EOL, $sadrzajDatoteke);
            $sqlUpit .= ") VALUES ";
            $ukupanBrojRedova = sizeof($redovi);
            $brojIspravnihRedova = 0;
            for ($i=0;$i<$ukupanBrojRedova;$i++) {
                $slog = str_getcsv($redovi[$i], $delimiter);
                if (sizeof($slog) === $brojAtributa) {
                    if ($brojIspravnihRedova) {
                        $sqlUpit .= ',';
                    }
                    foreach ($slog as &$celija) {
                        $celija = "'" . $celija . "'";
                    }
                    $sqlUpit .= '(' . join(',', str_replace(",", "','", $slog)) . ')';
                    $brojIspravnihRedova++;
                }
            }
            if (trim($redovi[$ukupanBrojRedova-1])==='') {
                $ukupanBrojRedova--;
            }
            $pohranjenoRedaka = 0;
            if ($brojIspravnihRedova) {
                $vezaBaze = new Baza();
                $vezaBaze->uspostaviVezu();
                $vezaBaze->izvrsiNekiDrugiUpit("$sqlUpit;");
                $pohranjenoRedaka = $vezaBaze->dohvatiBrojObuhvacenihRedovaProsleRadnje();
                $vezaBaze->prekiniVezu();
                if ($pohranjenoRedaka===-1) {
                    $pohranjenoRedaka=0;
                }
            }
            fclose($upraviteljDatoteke);
            echo json_encode(array('primljeno' => $ukupanBrojRedova, 'ispravno' => $brojIspravnihRedova, 'pohranjeno' => $pohranjenoRedaka));
        }
        else {
            echo false;
        }
    }
    else {
        echo false;
    }
}
?>
