<?php
require_once 'trenutno_vrijeme.php';
require_once 'administratorski_parametar.php';

class Sesija {

    const RELEVANTNI_PODACI = "relevantni_podaci";
    const SESSION_NAME = "prijava_sesija";

    static function kreirajSesiju() {
        session_name(self::SESSION_NAME);

        $trajanjeSesije = Administratorski_parametar::dohvatiPodatak(Administratorski_parametar::TRAJANJE_SESIJE)['trajanje_sesije'];
        if (session_id() == "") {
            ini_set('session.gc_maxlifetime', "$trajanjeSesije");
            session_set_cookie_params($trajanjeSesije);
            session_start();
        }
        if (isset($_SESSION[self::RELEVANTNI_PODACI])) {
            if ((TrenutnoVrijeme() - $_SESSION[self::RELEVANTNI_PODACI]['vrijeme'] > $trajanjeSesije)) {
                session_unset();
                session_destroy();
            }
            else {
                $_SESSION[self::RELEVANTNI_PODACI]['vrijeme'] = TrenutnoVrijeme() + $trajanjeSesije;
            }
        }
    }

    static function kreirajRelevantnePodatke($listaPodataka) {
        self::kreirajSesiju();
        $_SESSION[self::RELEVANTNI_PODACI] = $listaPodataka;
    }

    static function dajRelevantnePodatke() {
        self::kreirajSesiju();
        if (isset($_SESSION[self::RELEVANTNI_PODACI])) {
            $listaPodataka = $_SESSION[self::RELEVANTNI_PODACI];
        } else {
            return null;
        }
        return $listaPodataka;
    }
    
    static function dajPodatak($naziv) {
        self::kreirajSesiju();
        if (isset($_SESSION[self::RELEVANTNI_PODACI])) {
            if (array_key_exists($naziv, $_SESSION[self::RELEVANTNI_PODACI])) {
                return $_SESSION[self::RELEVANTNI_PODACI][$naziv];                
            }
            else {
                return null;
            }
        }
        else {
            return null;
        }
    }

    static function obrisiSesiju() {
        self::kreirajSesiju();

        if (session_id() != "") {
            session_unset();
            session_destroy();
        }
    }

}
