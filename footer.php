<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="//cdn.datatables.net/s/dt/jq-2.1.4,dt-1.10.10/datatables.min.js" type="text/javascript"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script src="js/petsestak3_jquery.js" type="text/javascript"></script>

<!--
<div id="dialog" title="Upozorenje o isteku sesije">
  <p>Vaša korisnička sesija je istekla! U sljedećoj kartici će Vam se otvoriti stranica sa prijavom u web-aplikaciju, pa se za daljnje surfanje ponovno prijavite.</p>
</div>
-->
    
<?php
if ($naziv_skripte === 'index.php' && $idKorisnika !== null) {
    ?>
    <script src="js/geolociranje.js" type="text/javascript"></script>
    <script async defer src="//maps.googleapis.com/maps/api/js?key=AIzaSyA6fzrRgZtbQORsmaJSVYkl1QumPFBCn70&callback=initMap" type="text/javascript"></script>
    <?php
}
else if ($naziv_skripte === 'rad-s-podacima.php') {
    echo '<script type="text/javascript">$("#tabs").tabs()</script>';
}

else if ($naziv_skripte === 'statistika.php') {
    echo "<script type=\"text/javascript\">$('.datepicker').datepicker({ dateFormat: 'dd-mm-yy' });</script>";
}
?>
</body>
</html>