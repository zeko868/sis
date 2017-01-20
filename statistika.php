<?php

$naziv_skripte = basename(__FILE__);
require_once 'header.php';
?>

<label for="filter-tip">Tip pretrage</label>
<select id="filter-tip">
    <option value="stranica">Stranica</option>
    <option value="upit">Upit</option>
</select>
<br/>
<label for="filter-datum-od">Od datuma:</label>
<input type="text" id="filter-datum-od" class="datepicker"/>
<br/>
<label for="filter-datum-do">Do datuma:</label>
<input type="text" id="filter-datum-do" class="datepicker"/>
<br/>

<div id="podatkovna-tablica"></div>
<div id="stranicenje-wrapper"><div id="stranicenje"></div></div>


<?php
require_once 'footer.php';
?>