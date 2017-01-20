<?php

$naziv_skripte = basename(__FILE__);
require_once 'header.php';

$odabranaTablica = 'administrativni_parametar';
if (!empty($_POST)) {
    if (isset($_POST['tablica'])) {
        $odabranaTablica = $_POST['tablica'];
    }
}
require_once 'baza.class.php';
$vezaBaze = new Baza();
$vezaBaze->uspostaviVezu();
$skupZapisa = $vezaBaze->izvrsiSelectUpit('SHOW TABLES;');
echo '<form action="' . $naziv_skripte . '" method="POST"><label for="odabirtablice">Trenutna tablica za rad: </label><select name="tablica" id="odabirtablice">';
while ($redak = $skupZapisa->fetch_row()) {
    if (preg_match('/.*(_zadaca0[1-5])$/', $redak[0])) {
        continue;
    }
    $odabran = '';
    if ($odabranaTablica===$redak[0]) {
        $odabran = ' selected';
    }
    echo "<option value=\"$redak[0]\"$odabran>$redak[0]</option>";
}
echo '</select>';
echo '<noscript><br/><input type="submit" value="Prikaži"/></noscript></form>';
$skupZapisa->close();
$vezaBaze->prekiniVezu();

?>

<div id="tabs">
    <ul>
        <li><a href="#tabs-1">Prikaz i rad s postojećim podacima</a></li>
        <li><a href="#tabs-2">Unos novih podataka (ručno)</a></li>
        <li><a href="#tabs-3">Unos novih podataka (CSV)</a></li>
    </ul>
    <div id="tabs-1">
        <input id="filter-pojam" type="text" placeholder="Pretraži po pojmu"/><select id="filter-atribut"></select>
        <div id="podatkovna-tablica"></div>
        <div id="stranicenje-wrapper"><div id="stranicenje"></div></div>
    </div>
    <div id="tabs-2">
        <div id="obrazac-za-unos"></div>
    </div>
    <div id="tabs-3">
        <div id="csv-upload"></div>
    </div>
</div>

<?php

require_once 'footer.php';

?>