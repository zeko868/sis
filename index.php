<?php

$naziv_skripte = basename(__FILE__);
require_once 'header.php';
?>


<div id="knjiznice">
</div>


<div class="sakrij">    
    <table id="detailsTable">
        <thead> 
            <tr>
                <th>Naziv knjige</th>
                <th>Naziv autora</th>
                <th>Izdavač</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<?php
if ($idKorisnika !== null) {
    ?>
    <div id="map" style="height:500px"></div>
    <?php
    if (Sesija::dajPodatak('geopozicija')!==null) {
        $pozicija = Sesija::dajPodatak('geopozicija');
        $pozicija['lat'] = htmlspecialchars($pozicija['lat']);
        $pozicija['lng'] = htmlspecialchars($pozicija['lng']);
        echo "<p id=\"lat\" class=\"sakrij\">$pozicija[lat]</p><p id=\"lng\" class=\"sakrij\">$pozicija[lng]</p>";
    }
}
?>

<?php
require_once 'footer.php';
?>