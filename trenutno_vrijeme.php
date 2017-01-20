<?php
require_once 'administratorski_parametar.php';

function TrenutnoVrijeme() {
    return time() + (Administratorski_parametar::dohvatiPodatak(Administratorski_parametar::POMAK_VREMENA)[Administratorski_parametar::POMAK_VREMENA]*60*60);
}
?>