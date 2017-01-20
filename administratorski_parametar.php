<?php

require_once 'baza.class.php';

class Administratorski_parametar {
    const BROJ_ZAPISA_PO_STRANICI = 'broj_zapisa_po_stranici';
    const POMAK_VREMENA = 'pomak_virtualnog_vremena';
    const TRAJANJE_SESIJE = 'trajanje_sesije';
    const TRAJANJE_AKTIVACIJSKOG_LINKA = 'trajanje_aktivacijskog_linka';
    const MAX_ZAPISA_BEZ_STRANICENJA = 'maksimalan_broj_zapisa_bez_stranicenja';
    
    static public function dohvatiPodatak () {
        $brArgumenata = func_num_args();
        if ($brArgumenata===0) {
            return null;
        }
        $parametri = func_get_args();
        $sqlNaredba = "SELECT naziv_parametra,vrijednost_parametra FROM administrativni_parametar WHERE naziv_parametra=?";
        for ($i=1;$i<$brArgumenata;$i++) {
            $sqlNaredba .= " UNION SELECT naziv_parametra,vrijednost_parametra FROM administrativni_parametar WHERE naziv_parametra=?";
        }
        $sqlNaredba .= ';';
        $vezaBaze = new Baza();
        $vezaBaze->uspostaviVezu();
        $skupZapisa = $vezaBaze->izvrsiSelectUpit($sqlNaredba, $parametri, false);
        $rezultat = array();
        while ($slog = $skupZapisa->fetch_assoc()) {
            $rezultat[$slog['naziv_parametra']] = $slog['vrijednost_parametra'];
        }
        $skupZapisa->close();
        $vezaBaze->prekiniVezu();
        return $rezultat;
    }
}

?>