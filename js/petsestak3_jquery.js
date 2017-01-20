var iTableCounter = 1;
var oInnerTable;
var oTable;
var detailsTableHtml;

function fnFormatDetails(table_id, html) {
    var sOut = "<table id=\"exampleTable_" + table_id + "\"><caption>Trenutno najposuđivanije knjige među članovima u navedenoj knjižnici:</caption>";
    sOut += html;
    sOut += "</table>";
    return sOut;
}

function pronadji_nezadovoljavajuce_grupe_u_regexu(regex, uText) {
    if (regex.test(uText)) {
        return true;
    }
    var textRegex = regex.toString();
    textRegex = textRegex.substring(1, textRegex.lastIndexOf("/"));
    var izlaz = [];
    for (var i = 0; i < textRegex.length; i++) {
        if (textRegex[i] === '(' && !(i >= 2 && textRegex[i - 2] === '\\' && textRegex[i - 1] === '\\')) {
            var otvoreneVSzatvorene = 1;
            for (var j = i + 1; otvoreneVSzatvorene !== 0; j++) {
                if (textRegex[j] === ')' && !(j >= 2 && textRegex[j - 2] === '\\' && textRegex[j - 1] === '\\')) {
                    otvoreneVSzatvorene--;
                } else if (textRegex[j] === '(' && !(j >= 2 && textRegex[j - 2] === '\\' && textRegex[j - 1] === '\\')) {
                    otvoreneVSzatvorene++;
                }
            }
            izlaz.push((new RegExp(textRegex.substring(i, j))).test(uText));
        }
    }
    return izlaz;
}

$(function() {
    $( "#dialog" ).dialog({
        autoOpen: false
    });
});

/*
function istekSesijePopUp() {
    $( "#dialog" ).dialog( "open" );
    var trenutnaAdresa = $(location).attr('href');
    $(window).open('HTTPS' + trenutnaAdresa.substring(trenutnaAdresa.indexOf('://'), trenutnaAdresa.lastIndexOf('/')+1) + 'prijava.php', 'prijava');
}
*/

$(document).ready(function () {
    if ($('#odabirtablice').length) {
        DohvatiStrukturuISadrzajTablice($('#odabirtablice').val(), 1, true, '','');
    }
    if ($('#knjiznice').length) {
        detailsTableHtml = $("#detailsTable").html();
        var dataToSend = {potraznja: "knjiznice"};
        $.ajax({
            url : "obrada_asinkronih_zahtjeva.php",
            type: "POST",
            data : dataToSend,
            dataType: "json",
            success: function(data) {
                var brZapisaPoStranici = parseInt(data[0].broj_zapisa_po_stranici);
                var maxBrZapisaBezStranicenja = parseInt(data[0].maksimalan_broj_zapisa_bez_stranicenja);
                var ukljuciStranicenje = false;
                data = data[1];
                if (data.length > maxBrZapisaBezStranicenja) {
                    ukljuciStranicenje = true;
                }
                var tablica = $('<table id="tablica" class="display">');
                tablica.append('<thead><tr><th></th><th class=\"sakrij\">Id</th><th>Naziv</th><th>Lokacija</th></tr></thead>');

                var tbody = $("<tbody>");
                for (i = 0; i < data.length; i++) {
                    var red = "<tr>";
                    red += "<td><img src=\"//i.imgur.com/SD7Dz.png\" alt=\"prosiri slika\"/></td>";
                    red += "<td class=\"sakrij\">" + data[i].id + "</td>";
                    red += "<td>" + data[i].naziv + "</td>";
                    red += "<td>" + data[i].lokacija + "</td>";
                    red += "</tr>";
                    tbody.append(red);
                }
                tablica.append(tbody);
                $('#knjiznice').html(tablica);
                oTable = $('#tablica').dataTable({
                    "aaSorting": [[2, "asc"], [3, "asc"]],
                    "pageLength": brZapisaPoStranici,
                    "bPaginate": ukljuciStranicenje,
                    "bLengthChange": false,
                    "bFilter": true,
                    "bSort": true,
                    "bInfo": false,
                    "bAutoWidth": true,
                    "sPaginationType": "full_numbers",
                    "aoColumnDefs" : [{
                      'bSortable' : false,
                      'aTargets' : [ 0 ]
                    }],
                    "columns": [
                      { "width": "2px" },
                      null,
                      null,
                      null
                    ],
                    "oLanguage": {
                        "oPaginate":{
                        "sFirst": "Prva",
                        "sPrevious": "Prethodna",
                        "sNext": "Sljedeća",
                        "sLast": "Posljednja"
                        },
                        "sSearch": "Pretraga: "
                    },
                    "fnInitComplete":function(){
                        if (data.length <= brZapisaPoStranici) {
                            $('.dataTables_paginate').hide();
                        }
                    },
                    "fnPreDrawCallback": function() {
                        var instanca = this;
                        $('#tablica > tbody > tr > td > img').each(function() {
                            var trenutniRed = $(this).parents('tr')[0];
                            if (instanca.fnIsOpen(trenutniRed)) {
                                $(this).attr('src',"//i.imgur.com/SD7Dz.png");
                                instanca.fnClose(trenutniRed);
                            }
                        });                    
                    }
                });
                
                var map = $('#map');
                if (map.length>0) {
                    var geocoder = new google.maps.Geocoder();
                    $.each(data, function(i){
                       geocodeAddress(geocoder, data[i].lokacija, data[i].naziv);
                    });
                }
            },
            error: function() {
                $('#knjiznice').html('<table id="tablica"><caption>Pojavila se pogreška prilikom dohvaćanja knjižnica!</caption></table>');
            }
        });
        
    }
});

$('#knjiznice').on('click', 'img', function(){
    var nTr = $(this).parents('tr')[0];
    var nTds = $(this);

    if (oTable.fnIsOpen(nTr)) {
        this.src = "//i.imgur.com/SD7Dz.png";
        oTable.fnClose(nTr);
    }
    else {
        var sifraKnjiznice = $(nTr).children(':nth-child(2)').text();
        var dataToSend = {potraznja: "najposudjivanije_knjige_neke_knjiznice", idKnjiznice: sifraKnjiznice};
        $.ajax({
            url : "obrada_asinkronih_zahtjeva.php",
            type: "POST",
            data : dataToSend,
            dataType: "json",
            success: function(data) {
                nTds.attr('src',"//i.imgur.com/d4ICC.png");
                if (data.length) {
                    oTable.fnOpen(nTr, fnFormatDetails(iTableCounter, detailsTableHtml), 'details');
                    oInnerTable = $("#exampleTable_" + iTableCounter).dataTable({
                        "bJQueryUI": true,
                        "bFilter": false,
                        "aaData": data,
                        "bSort" : true,
                        "bAutoWidth": true,
                        "aoColumns": [
                            { "mDataProp": "naslov" },
                            { "mDataProp": "autori"},
                            { "mDataProp": "izdavac"}
                        ],
                        "bPaginate": false,
                        "sDom": ''
                    });
                }
                else {
                    oTable.fnOpen(nTr, '<table id="exampleTable_'+iTableCounter+'" class="podtablica"><caption>Trenutno ne postoje raspoloživi podaci za navedenu knjižnicu.</caption></table>');
                }
                iTableCounter++;
            },
            error: function() {
                nTds.src = "//i.imgur.com/d4ICC.png";
                oTable.fnOpen(nTr, '<table id="exampleTable_'+iTableCounter+'" class="podtablica"><caption>Pojavila se pogreška prilikom dohvaćanja najposuđivanijih knjiga među korisnicima!</caption></table>');
                iTableCounter++;
            }
        });
    }
});

$('#popisstranica').change(function(){
    var protokol = 'http';
    if ($(this).val()==='prijava.php' || $(this).val()==='registracija.php' ||$(this).val()==='moj-profil.php') {
        protokol = 'https';
    }
    var trenutnaAdresa = $(location).attr('href');
    $(location).attr('href', protokol + trenutnaAdresa.substring(trenutnaAdresa.indexOf('://'), trenutnaAdresa.lastIndexOf('/')+1) + $(this).val());
});

$('#odabirtablice').change(function(){
    var kartica = $("#tabs").tabs("option", "active");
    switch (kartica) {
        case 0:
            DohvatiStrukturuISadrzajTablice($(this).val(),1, true, '', '');
            $('#filter-pojam').val('');
            break;
        case 1:
            GenerirajPoljaZaUnos($(this).val());
            break;
        case 2:
            UploadCSV($(this).val());
            break;
    }
});

function DohvatiStrukturuISadrzajTablice(nazivTablice, brojStranice, promijenjenaTablica, vrijednostFiltriranja, atributFiltriranja) {
    var redoslijedSortiranja = {};
    if (!promijenjenaTablica) {
        $('th:not(th[colspan=2])').each(function(){
            var prioritet = $(this).children('span#redoslijed');
            if (prioritet.text()) {
                redoslijedSortiranja[parseInt(prioritet.text())-1] = { atribut: $(this).children('span#naziv').text() , smjer: $(this).children('img:visible').attr('id') };
            }
        });
    }
    var dataToSend = { potraznja : 'vrati_strukturu_i_sadrzaj_tablice' , tablica : nazivTablice , broj_stranice : brojStranice , vrijednost_filtriranja : vrijednostFiltriranja , atribut_filtriranja : atributFiltriranja , redoslijed_sortiranja : redoslijedSortiranja};
    $.ajax({
        url : "obrada_asinkronih_zahtjeva.php",
        type: "POST",
        data : dataToSend,
        dataType: "json",
        success: function(data) {
            var omoguciBrisanje = true;
            if (nazivTablice==='administrativni_parametar') {
                omoguciBrisanje = false;
            }
            if (promijenjenaTablica) {
                var sadrzaj = '<table><thead><tr>';
                var filterAtribut = $('#filter-atribut');
                filterAtribut.html('<option value="">*svi atributi*</option>');
                for (var nazivAtributa in data['relacijska_shema']) {
                    var tipIVelicina = data['relacijska_shema'][nazivAtributa].Type;
                    var pocetakDuljineTipa = tipIVelicina.indexOf('(');
                    if (pocetakDuljineTipa===-1) {
                        sadrzaj += '<th class="type-' + tipIVelicina;
                    }
                    else {
                        sadrzaj += '<th class="type-' + tipIVelicina.substring(0, pocetakDuljineTipa) + ' length-' + tipIVelicina.substring(pocetakDuljineTipa+1,tipIVelicina.indexOf(')'));
                    }
                    var svojstvo =  data['relacijska_shema'][nazivAtributa].Key;
                    if (svojstvo) {
                        sadrzaj += " constraint-" + svojstvo;
                    }
                    var referenciranaTablica = data['relacijska_shema'][nazivAtributa].ReferencedTable;
                    if (referenciranaTablica) {
                        sadrzaj += " foreign-key reftable-" + referenciranaTablica;
                    }
                    if (data['relacijska_shema'][nazivAtributa].Null==='YES') {
                        sadrzaj += " nullable";
                    }
                    sadrzaj += '"><span id="naziv">' + nazivAtributa + '</span><img id="asc" src="img/asc.gif" alt="asc" style="display: none"/><img id="desc" src="img/desc.gif" alt="desc" style="display: none"/><span id="redoslijed"></span></th>';
                    filterAtribut.html(filterAtribut.html() + '<option value="' + nazivAtributa + '">' + nazivAtributa + '</option>');
                }
                sadrzaj += '<th colspan="' + (omoguciBrisanje ? 2 : 1) + '"></th></tr></thead><tbody></tbody></table>';
                $('#podatkovna-tablica').html(sadrzaj);
            }
            var sadrzaj = '';
            for (var i in data['podaci']) {
                sadrzaj += '<tr>';
                for (var j in data['podaci'][i]) {
                    sadrzaj += '<td>' + data['podaci'][i][j] + '</td>';
                }
                if (omoguciBrisanje) {
                    sadrzaj += '<td class="update">Promijeni</td><td class="delete">Izbriši</td></tr>';                    
                }
                else {
                    sadrzaj += '<td class="update">Promijeni</td></tr>';
                }
            }
            $('#podatkovna-tablica tbody').html(sadrzaj);
            
            var brPodatakaPoStranici = parseInt(data['broj_zapisa_po_stranici']);
            
            if (promijenjenaTablica) {
                if (parseInt(data['ukupan_broj_zapisa'])<=brPodatakaPoStranici) {
                    $('div#stranicenje').html('');
                }
                else {
                    var ukupanBrojStranica = Math.ceil(data['ukupan_broj_zapisa']/data['broj_zapisa_po_stranici']);
                    $('div#stranicenje').html('<p id="prva" class="navigacija-tipka" style="visibility: hidden;">Prva</p><p id="prethodna" class="navigacija-tipka" style="visibility: hidden;">Prethodna</p><p><input id="broj-stranice" type="number" min="1" max="' + ukupanBrojStranica + '" value="1"/> od <span id="ukupno-stranica">' + ukupanBrojStranica + '</span></p><p id="sljedeca" class="navigacija-tipka">Sljedeća</p><p id="posljednja" class="navigacija-tipka">Posljednja</p>');
                }
            }
            else {
                var ukupanBrojZapisa = parseInt(data['ukupan_broj_zapisa']);
                if (ukupanBrojZapisa<=brPodatakaPoStranici) {
                    $('div#stranicenje').html('');
                }
                else {
                    var ukupanBrojStranica = Math.ceil(ukupanBrojZapisa/data['broj_zapisa_po_stranici']);
                    if ($('div#stranicenje').children().length===0) {
                        $('div#stranicenje').html('<p id="prva" class="navigacija-tipka" style="visibility: hidden;">Prva</p><p id="prethodna" class="navigacija-tipka" style="visibility: hidden;">Prethodna</p><p><input id="broj-stranice" type="number" min="1" max="' + ukupanBrojStranica + '" value="1"/> od <span id="ukupno-stranica">' + ukupanBrojStranica + '</span></p><p id="sljedeca" class="navigacija-tipka">Sljedeća</p><p id="posljednja" class="navigacija-tipka">Posljednja</p>');
                    }
                    else {
                        $('span#ukupno-stranica').html(ukupanBrojStranica);
                        var inputBox = $('div#stranicenje input#broj-stranice');
                        if (ukupanBrojStranica !== parseInt(inputBox.attr('max'))) {
                            inputBox.attr('max', ukupanBrojStranica);
                            if (parseInt(inputBox.val()) > ukupanBrojStranica ) {
                                inputBox.val(ukupanBrojStranica);
                            }
                        }
                    }
                }
            }
        },
        error: function() {
        }
    });
}

$('#zaboravljena-lozinka').click(function() {
    var emailPolje = $('#eadresa');
    if (ispravanEmail(emailPolje)) {
        var dataToSend = { potraznja : 'resetiraj_lozinku' , email : emailPolje.val()};
        $.ajax({
            url : "obrada_asinkronih_zahtjeva.php",
            type: "POST",
            data : dataToSend,
            dataType: "json",
            success: function(data) {
                if (data===true) {
                    alert('Na e-mail adresu Vam je poslana nova lozinka.');
                }
                else {
                    alert('Pojavila se pogreška prilikom slanja nove lozinke na priloženu e-adresu');
                }
            },
            error: function() {
                alert('Nema povratne informacije o uspješnosti resetiranja lozinke...');
            }
        });
        
    }
});

$(document).mouseup(function (e){
    var container = $('div#padajuci-izbornik');
    if (!container.is(e.target) && container.has(e.target).length === 0 && !$('img#prijavljen-opcije').is(e.target)) {
        container.hide();
    }
});

$('img#prijavljen-opcije').click(function(){
    var padajuciIzbornik = $(this).next();
    padajuciIzbornik.toggle();
});

$('form#registracija .input, form#promjena-podataka .input').focusout(function(){
    ispravnostCelije($(this), false);
});

$('input[type=file]').change(function(){
    ispravnostCelije($(this), false);
});

$('form#prijava').submit(function(event){
    var salji = true;
    var email = $('#eadresa');
    if (!email.val()) {
        if (!email.next().hasClass('greska')) {
            email.after('<p class="greska">Morate unijeti e-mail adresu da biste se prijavili!</p>');
        }
        salji = false;
    }
    else {
        if (email.next().hasClass('greska')) {
            email.next().remove();
        }
    }
    var lozinka = $('#lozinka');
    if (!lozinka.val()) {
        if (!lozinka.next().hasClass('greska')) {
            lozinka.after('<p class="greska">Morate unijeti lozinku da biste se prijavili!</p>');
        }
        salji = false;
    }
    else {
        if (lozinka.next().hasClass('greska')) {
            lozinka.next().remove();
        }
    }
    if (!salji) {
        event.preventDefault();
    }
});

$('form#registracija, form#promjena-podataka').submit(function(event){
    event.preventDefault();
    var salji = true;

    $('.input').each(function() {
        salji = ispravnostCelije($(this), true);
    });
    
    if ($(this).attr('id') === 'registracija') {
        var notrobotKontrola = $('div#notrobot');
        if (notrobotKontrola.next().hasClass('greska')) {
            notrobotKontrola.next().remove();
        }
        if (!rijesenaRecaptcha()) {
            notrobotKontrola.after('<p class="greska">Morate dokazati da niste robot!</p>');
            salji = false;
        }
    }
    var obrazac = $(this);
    if (salji) {
        var dataToSend;
        if (obrazac.attr('id') === 'registracija') {
            dataToSend = { potraznja: 'provjera_dostupnosti_emaila' , eadresa: $('#email').val() };
            $.ajax({
                url : "obrada_asinkronih_zahtjeva.php",
                type: "POST",
                data : dataToSend,
                dataType: "json",
                success: function(data) {
                    if (data===false) {
                        var emailPolje = $('#email');
                        if (!emailPolje.next().hasClass('greska')) {
                            emailPolje.after('<p class="greska">Na navedenu e-mail adresu je već registriran neki drugi korisnik na ovoj stranici!</p>');
                        }
                    }
                    else {
                        obrazac.off('submit');
                        obrazac.submit();
                    }
                }
            });        
        }
        else {
            dataToSend = { potraznja: 'provjera_dostupnosti_emaila_i_ispravnosti_lozinke' , eadresa: $('#email').val() , stara_lozinka: $('#staralozinka').val() };
            $.ajax({
                url : "obrada_asinkronih_zahtjeva.php",
                type: "POST",
                data : dataToSend,
                dataType: "json",
                success: function(data) {
                    if (data.email===false) {
                        salji = false;
                        var emailPolje = $('#email');
                        if (!emailPolje.next().hasClass('greska')) {
                            emailPolje.after('<p class="greska">Na navedenu e-mail adresu je već registriran neki drugi korisnik na ovoj stranici!</p>');
                        }
                    }
                    if (data.lozinka===false) {
                        salji = false;
                        var staraLozinkaPolje = $('#staralozinka');
                        if (!staraLozinkaPolje.next().hasClass('greska')) {
                            staraLozinkaPolje.after('<p class="greska">Pogrešno unesena stara lozinka!</p>');
                        }
                    }
                    if (salji) {
                        obrazac.off('submit');
                        obrazac.submit();
                    }
                }
            });        
        }
    }
});

function ispravnostCelije(celija, predSlanje) {
    if (celija.next().hasClass('greska')) {
        celija.next().remove();
    }
    var salji = true;
    if (!celija.val()) {
        if (celija.attr('id')!=='slika') {
            celija.after('<p class="greska">Ovo polje ne smije biti prazno!</p>');
            salji = false;
        }
    }
    else {
        switch (celija.attr('id')) {
            case 'ime':
            case 'prezime':
                salji = salji && ispravnoPrezIme(celija);
                break;
            case 'email':
                salji = salji && ispravanEmail(celija, predSlanje);
                break;
            case 'lozinka':
                salji = salji && ispravnaLozinka(celija);
                break;
            case 'lozinka2':
                salji = salji && podudarnostLozinki(celija);
                break;
            case 'staralozinka':
                salji = salji && dobroUnesenaStaraLozinka(celija, predSlanje);
                break;
            case 'slika':
            case 'novaslika':
                salji = salji && ispravnostSlike(celija);
                break;
        }
    }
    return salji;
}

function ispravnoPrezIme(celija) {
    if (!/(?!^[\s\-])^(((^|\s|\-)([A-ZČĆŠŽĐ])([a-zčćšžđ])*)+)$/.test(celija.val())) {
        celija.after('<p class="greska">Ovo polje dozvoljava unos klasičnih hrvatskih riječi (razdvojenih razmakom ili crticom) koje počinju velikim slovom!');
        return false;
    }
    return true;
}

function ispravanEmail(celija, predSlanje) {
    var tablicaIstinitosti = pronadji_nezadovoljavajuce_grupe_u_regexu(/(?=.+@.+\..+$)(?=^((?!\.{2,}).)*$)(?=^.{1,64}@)(^[\w!#\$%&'\*\+\-\/\=\?\^`\{\|\}~][\w!#\$%&'\*\+\-\/\=\?\^`\{\|\}~\.]*)(?=@.{1,64}\.[^\.]+$)(@[A-Za-z0-9][A-Za-z0-9\-\.]*)(\.[A-Za-z0-9]+$)/, celija.val());
    if (tablicaIstinitosti!==true) {
        var opisGreske = [];
		if (!tablicaIstinitosti[0]) {
			opisGreske.push("E-adresa nije u formatu 'nesto@nesto.nesto'!");
		}
		if (!tablicaIstinitosti[1]) {
			opisGreske.push("Po RFC standardima znak '.' se ne može pojaviti dva ili više puta uzastopno u e-mail adresi!");
		}
		if (!tablicaIstinitosti[4] || !tablicaIstinitosti[5]) {
			opisGreske.push("Korisničko ime unutar e-mail adrese nije valjano po RFC 3222 standardu!");
		}
		if (!tablicaIstinitosti[6] || !tablicaIstinitosti[7]) {
			opisGreske.push("Naziv poslužitelja e-adrese nije valjan po RFC 1123 formatu!");
		}
		if (!tablicaIstinitosti[8]) {
			opisGreske.push("Nedostaje domena na kraju e-mail adrese!");
		}
        if (opisGreske.length) {
            celija.after('<p class="greska">' + opisGreske.join('<br/>') + '</p>');
        }
        else {
            celija.after('<p class="greska">Nepoznata greška se pojavila kod provjere ispravnosti e-mail adrese.</p>');
        }
        return false;
    }
    if (!predSlanje && $('form#prijava').length===0) {
        var dataToSend = { potraznja: 'provjera_dostupnosti_emaila' , eadresa: celija.val() };
        $.ajax({
            url : "obrada_asinkronih_zahtjeva.php",
            type: "POST",
            data : dataToSend,
            dataType: "json",
            success: function(data) {
                if (data===false) {
                    celija.after('<p class="greska">Na navedenu e-mail adresu je već registriran jedan korisnik na ovoj stranici!</p>');
                }
            }
        });
    }
    return true;
}

function ispravnaLozinka(celija) {
    var tablicaIstinitosti = pronadji_nezadovoljavajuce_grupe_u_regexu (/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\$\?#!])(.{8,})/, celija.val());
    if (tablicaIstinitosti!==true) {
        var opisGreske = [];
        if (!tablicaIstinitosti[0]) {
            opisGreske.push("Lozinka treba sadržavati barem jedno veliko slovo!");
        }
        if (!tablicaIstinitosti[1]) {
            opisGreske.push("Lozinka treba sadržavati barem jedno malo slovo!");
        }
        if (!tablicaIstinitosti[2]) {
            opisGreske.push("Lozinka treba sadržavati barem jedan broj!");
        }
        if (!tablicaIstinitosti[3]) {
            opisGreske.push("Lozinka treba sadržavati barem jedan definirani simbol iz sljedećeg skupa: {$,?,#,!}!");
        }
        if (!tablicaIstinitosti[4]) {
            opisGreske.push("Lozinka treba sadržavati minimalno 8 znakova! (za sada sadrži " + celija.val().length + ")");
        }
        if (opisGreske.length) {
            celija.after('<p class="greska">' + opisGreske.join('<br/>') + '</p>');
        }
        else {
            celija.after('<p class="greska">Nepoznata greška se pojavila kod provjere ispravnosti lozinke.</p>');
        }
        return false;
    }
    return true;
}

function podudarnostLozinki(celija) {
    if (!new RegExp('^' + celija.val().replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1") + '$').test($('#lozinka').val())) {
        celija.after('<p class="greska">Ponovljena lozinka se ne poklapa sa prvotno unesenom!</p>');
        return false;
    }
    return true;
}

function dobroUnesenaStaraLozinka(celija, predSlanje) {
    if (!predSlanje) {
        var dataToSend = { potraznja: 'provjera_ispravnosti_stare_lozinke' , stara_lozinka: celija.val() };
        $.ajax({
            url : "obrada_asinkronih_zahtjeva.php",
            type: "POST",
            data : dataToSend,
            dataType: "json",
            success: function(data) {
                if (data===false) {
                    celija.after('<p class="greska">Pogrešno unesena stara lozinka!</p>');
                }
            }
        });
    }
}

function ispravnostSlike(celija) {
    if (!/((\.jpg)|(\.jpeg)|(\.png)|(\.bmp)|(\.gif)|(\.tiff))$/i.test(celija.val())) {
        celija.after('<p class="greska">Dozvoljeni formati slike su .jpg, .jpeg, .png, .bmp, .gif i .tiff!</p>');
        celija.val(null);
        return false;
    }
    return true;
}

function rijesenaRecaptcha() {
    var response = grecaptcha.getResponse();
    if (response.length == 0) {
        return false;
    }
    else {
        return true;
    }
}

$('div#podatkovna-tablica').on('click','td.update',function() {
    $(this).text('Spremi');
    $(this).attr('class','save');
    if ($('#odabirtablice').val()==='administrativni_parametar') {
        var nazivPostavke = $(this).siblings('td:nth-child(1)');
        var vrijednostPostavke = $(this).siblings('td:nth-child(2)');
        nazivPostavke.data('stara-vrijednost-PRI', nazivPostavke.text());
        if (nazivPostavke.text()==='pomak_virtualnog_vremena') {
            vrijednostPostavke.html('<a id="virtualno-vrijeme" href="http://barka.foi.hr/WebDiP/pomak_vremena/vrijeme.html" target="virtualno-vrijeme">' + vrijednostPostavke.text() + '</a>');
        }
        else {
            vrijednostPostavke.html('<input type="number" value="' + vrijednostPostavke.text() + '"/>');
        }
    }
    else {
        $.each($(this).siblings(':not(.update,.delete)'), function(index) {
            var zaglavljeStupca = $('th:nth-child(' + (index+1) + ')');
            var popisKlasa = zaglavljeStupca.attr('class');
            var staraVrijednost = $(this).text();
            if (zaglavljeStupca.hasClass('foreign-key')) {
                var pocetakNazivaRefTablice = popisKlasa.indexOf('reftable-')+'reftable-'.length;
                var krajNazivaRefTablice = popisKlasa.indexOf(' ', pocetakNazivaRefTablice);
                if (krajNazivaRefTablice===-1) {
                    krajNazivaRefTablice = popisKlasa.length;
                }
                var refTablica = popisKlasa.substring(pocetakNazivaRefTablice, krajNazivaRefTablice);
                var celija = $(this);
                dataToSend = { potraznja : 'vrati_sadrzaj_comboboxa' , tablica : $('#odabirtablice').val() , referencirana_tablica : refTablica };
                $.ajax({
                    url : "obrada_asinkronih_zahtjeva.php",
                    type: "POST",
                    data : dataToSend,
                    dataType: "json",
                    success: function(data) {
                        var comboBoxDefinicija = '<select>';
                        var nadjen = false;
                        if (zaglavljeStupca.hasClass('nullable')) {
                            if (!staraVrijednost) {
                                comboBoxDefinicija += '<option value="null" selected>null</option>';
                                staraVrijednost = 'null';
                                nadjen = true;
                            }
                            else {
                                comboBoxDefinicija += '<option value="null">null</option>';
                            }
                        }
                        var i = 0;
                        if (!nadjen) {
                            for (;i<data.length;i++) {
                                if (staraVrijednost===data[i].tekst) {
                                    comboBoxDefinicija += '<option value="' + data[i].vrijednost + '" selected>' + data[i].tekst + '</option>';
                                    staraVrijednost = data[i].vrijednost;
                                    break;
                                }
                                else {
                                    comboBoxDefinicija += '<option value="' + data[i].vrijednost + '">' + data[i].tekst + '</option>';
                                }
                            }                        
                        }
                        i++;
                        for (;i<data.length;i++) {
                            comboBoxDefinicija += '<option value="' + data[i].vrijednost + '">' + data[i].tekst + '</option>';
                        }

                        comboBoxDefinicija += '</select>';
                        celija.html(comboBoxDefinicija);
                        if (zaglavljeStupca.hasClass('constraint-PRI')) {
                            celija.data('stara-vrijednost-PRI', staraVrijednost);
                        }
                        else if (zaglavljeStupca.hasClass('constraint-UNI')) {
                            celija.data('stara-vrijednost-UNI', staraVrijednost);
                        }
                    }
                });
            }
            else {
                var pocetnaPozicijaTipa = popisKlasa.indexOf('type-')+'type-'.length;
                var zavrsnaPozicijaTipa = popisKlasa.indexOf(' ', pocetnaPozicijaTipa);
                if (zavrsnaPozicijaTipa===-1) {
                    zavrsnaPozicijaTipa = popisKlasa.length;
                }
                var tip = popisKlasa.substring(pocetnaPozicijaTipa, zavrsnaPozicijaTipa);
                var tipInputa;
                switch (tip) {
                    case 'char':
                    case 'varchar':
                        tipInputa = 'text';
                        break;
                    case 'smallint':
                    case 'int':
                    case 'bigint':
                        tipInputa = 'number';
                        break;
                    case 'tinyint':
                        tipInputa = 'checkbox';
                        break;
                    case 'date':
                        tipInputa = 'date';
                        break;
                    case 'time':
                        tipInputa = 'time';
                        break;
                    case 'datetime':
                        tipInputa = 'datetime';
                        break;
                    default:
                        tipInputa = 'text';
                }
                var pocetnaPozicijaDuljine = popisKlasa.indexOf('length-');
                var zavrsnaPozicijaDuljine = popisKlasa.indexOf(' ', pocetnaPozicijaDuljine);
                if (zavrsnaPozicijaDuljine===-1) {
                    zavrsnaPozicijaDuljine = popisKlasa.length;
                }
                var duljina = -1;
                if (pocetnaPozicijaDuljine!==-1) {
                    pocetnaPozicijaDuljine += 'length-'.length;
                    duljina = popisKlasa.substring(pocetnaPozicijaDuljine, zavrsnaPozicijaDuljine);
                }
                if (popisKlasa.indexOf('nullable') !== -1 && staraVrijednost==='null') {
                    staraVrijednost = '';
                }
                $(this).html('<input type="' + tipInputa + (tipInputa==='checkbox'? ('"' + (staraVrijednost==='1'?' checked':'')):('" value="' + staraVrijednost + '"')) + (duljina===-1?'':(' maxlength="'+duljina+'"')) + '/>');
                if (zaglavljeStupca.hasClass('constraint-PRI')) {
                    $(this).data('stara-vrijednost-PRI', staraVrijednost);
                }
                else if (zaglavljeStupca.hasClass('constraint-UNI')) {
                    $(this).data('stara-vrijednost-UNI', staraVrijednost);
                }
                if (popisKlasa.indexOf('nullable') === -1) {
                    $('input[type=date]',$(this)).datepicker( { dateFormat: 'yy-mm-dd' } );
                }
                else {
                    $('input[type=date]',$(this)).datepicker({ dateFormat: 'yy-mm-dd' }).keyup(function(e) {
                        if(e.keyCode == 8 || e.keyCode == 46) {
                            $(this).datepicker('setDate', null);
                        }
                    });;
                }
            }
        });
    }
});

$('div#podatkovna-tablica').on('click','td.save',function() {
    var dosadasnjeVrijednostiKljuceva = {};
    var slog = {};
    $.each($(this).siblings(':not(.save,.delete)'), function(index) {
        var zaglavljeStupca = $('th:nth-child(' + (index+1) + ')');
        var nazivAtributa = zaglavljeStupca.children('span#naziv').text();
        if ($(this).data('stara-vrijednost-PRI')) {
            dosadasnjeVrijednostiKljuceva[nazivAtributa] = $(this).data('stara-vrijednost-PRI');            
        }
        var input = $(this).children('input,select');
        if (input.length) {
            if (input.attr('type')==='checkbox') {
                slog[nazivAtributa] = input.is(':checked') ? 1 : 0;
            }
            else {
                slog[nazivAtributa] = input.val();
            }
        }
        else {
            var virtualnoVrijeme = $('a#virtualno-vrijeme',$(this));
            if (virtualnoVrijeme.length) {
                slog[nazivAtributa] = virtualnoVrijeme.text();
            }
        }
    });
    var tipka = $(this);
    var dataToSend = {potraznja : 'pohrani_promjene' , akcija : 'promijeni' , tablica : $('#odabirtablice').val() , stare_vrijednosti_kljuca : JSON.stringify(dosadasnjeVrijednostiKljuceva) , novi_podaci : JSON.stringify(slog)};
    $.ajax({
        url : "obrada_asinkronih_zahtjeva.php",
        type: "POST",
        data : dataToSend,
        dataType: "json",
        success: function(data) {
            if (data===true) {
                $.each(tipka.siblings(':not(.save,.delete)'), function() {
                    if ($('input',$(this)).length) {
                        switch ($(this).children('input').attr('type')) {
                            case 'checkbox':
                                $(this).html($(this).children('input').is(':checked') ? '1' : '0');
                                break;
                            default:
                                var unesenaVrijednost = $(this).children('input').val();
                                if (unesenaVrijednost==='') {
                                    unesenaVrijednost = 'null';
                                }
                                $(this).html(unesenaVrijednost);
                        }
                    }
                    else if ($('select',$(this)).length) {
                        $(this).html($('select option:selected', $(this)).text());
                    }
                    else {
                        $(this).html($(this).text());
                    }
                });
                tipka.text('Promijeni');
                tipka.attr('class','update');
            }
            else {
                alert("Došlo je do pogreške prilikom ažuriranja zapisa! Vrijednost neke komponente primarnog ključa se vjerojatno koristi kod nekog drugog zapisa u bazi.");
                tipka.effect("highlight", { color: 'red'}, 3000);
            }
        }
    });
});

$('div#podatkovna-tablica').on('click','td.delete',function() {
    var dosadasnjeVrijednostiKljuceva = {};
    $.each($(this).siblings(':not(.save,.delete)'), function(index) {
        var zaglavljeStupca = $('th:nth-child(' + (index+1) + ')');
        var nazivAtributa = zaglavljeStupca.children('span#naziv').text();
        if ($(this).prev().hasClass('save')) {
            if ($(this).data('stara-vrijednost-PRI')) {
                dosadasnjeVrijednostiKljuceva[nazivAtributa] = $(this).data('stara-vrijednost-PRI');            
            }            
        }
        else {
            if (zaglavljeStupca.hasClass('constraint-PRI')) {
                dosadasnjeVrijednostiKljuceva[nazivAtributa] = $(this).text();
            }
        }
    });
    var tipka = $(this);
    var dataToSend = {potraznja : 'pohrani_promjene' , akcija : 'obrisi' , tablica : $('#odabirtablice').val() , stare_vrijednosti_kljuca : JSON.stringify(dosadasnjeVrijednostiKljuceva)};
    $.ajax({
        url : "obrada_asinkronih_zahtjeva.php",
        type: "POST",
        data : dataToSend,
        dataType: "json",
        success: function(data) {
            if (data===true) {
                var brojStraniceZaPrikazati;
                tipka.parents('tr').remove();
                if ($('div#stranicenje').children().length) {
                    brojStraniceZaPrikazati = parseInt($('#broj-stranice').val());                    
                    if ($('div#podatkovna-tablica tr').length===1) {
                        brojStraniceZaPrikazati--;
                    }
                }
                else {
                    brojStraniceZaPrikazati = 1;
                }
                DohvatiStrukturuISadrzajTablice($('#odabirtablice').val(), brojStraniceZaPrikazati, false, $('#filter-pojam').val(), $('#filter-atribut').val())
            }
            else {
                alert("Postoje podaci u drugim tablicama koji se referenciraju na ovaj zapis! Za brisanje navedenog zapisa potrebno je prvo njih izbrisati.");
                tipka.effect("highlight", { color: 'red'}, 3000);
            }
        }
    });

});

$('div#podatkovna-tablica').on('focusout','td:not(.save,.update,.delete)',function() {
    if ($(this).siblings('td.save').length) {
        var zaglavljeStupca = $('th:nth-child(' + ($(this).index() + 1) + ')');
        var atribut = zaglavljeStupca.children('span#naziv').text();
        var staraVrijednost;
        var input = $(this).children();
        var provjeri = true;
        if (!input.val() && input.attr('type')==='number') {
            input.val(0);
        }
        var dijeloviKljuca = {};
        if (zaglavljeStupca.hasClass('constraint-PRI')) {
            if (!input.val()) {
                provjeri = false;
                input.effect("highlight", { color: 'red'}, 3000);                
            }
            else {
                dijeloviKljuca[atribut] = input.val();
                var neprazniKljucniElementi = true;
                var sviNepromijenjeni = true;
                staraVrijednost = $(this).data('stara-vrijednost-PRI');
                if (input.val()!==staraVrijednost) {
                    sviNepromijenjeni = false;
                }
                $.each($(this).siblings('td:not(.save,.delete)'), function() {
                    var drugoZaglavlje = $('th:nth-child(' + ($(this).index() + 1) + ')');
                    var drugiAtribut = drugoZaglavlje.children('span#naziv').text();
                    if (drugoZaglavlje.hasClass('constraint-PRI')) {
                        var vrijednost = $(this).children(':input').val();
                        if (vrijednost) {
                            dijeloviKljuca[drugiAtribut] = vrijednost;
                            var drugaStaraVrijednost = $(this).data('stara-vrijednost-PRI');
                            if (vrijednost!==drugaStaraVrijednost) {
                                sviNepromijenjeni = false;
                            }
                        }
                        else {
                            neprazniKljucniElementi = false;
                            return;
                        }
                    }
                });
                if (sviNepromijenjeni) {
                    provjeri = false;
                }
                else {
                    if (!neprazniKljucniElementi) {
                        provjeri = false;
                    }
                }
            }
        }
        else if (zaglavljeStupca.hasClass('constraint-UNI')) {
            staraVrijednost = $(this).data('stara-vrijednost-UNI');
            if (zaglavljeStupca.hasClass('nullable') && input.val()==='') {
                provjeri = false;
            }
            else if (input.val()==='') {
                provjeri = false;
                input.effect("highlight", { color: 'red'}, 3000);                
            }
            else if (input.val()===staraVrijednost) {
                provjeri = false;
            }
            else {
                dijeloviKljuca[atribut] = input.val();
            }
        }
        if (provjeri) {
            var dataToSend = { potraznja : 'dostupne_vrijednosti_kljuca' , nove_vrijednosti : dijeloviKljuca , tablica : $('#odabirtablice').val() };
            $.ajax({
                url : "obrada_asinkronih_zahtjeva.php",
                type : "POST",
                data : dataToSend,
                dataType : "json",
                success: function(data) {
                    if (data===false) {
                        var redak = input.parents('tr');
                        for (var kljuc in dijeloviKljuca) {
                            var indeks = 0;
                            $.each($('th',input.parents('table')), function(){
                                indeks++;
                                if ($(this).text()===kljuc) {
                                    return false;
                                }
                            });
                            redak.children('td:nth-child(' + (indeks) + ')').children(':input').effect("highlight", { color: 'red'}, 3000);
                        };
                    }
                }
            });
        }
    }
});

function promijeniStranicu () {
    var inputBox = $('#broj-stranice');
    var najvecaDozvoljenaVrijednost = parseInt(inputBox.attr('max'));
    var trenutnaVrijednost = parseInt(inputBox.val());
    if (trenutnaVrijednost <= 1) {
        inputBox.val(trenutnaVrijednost = 1);
        $('#prva,#prethodna').css('visibility','hidden')
        $('#posljednja,#sljedeca').css('visibility', 'visible');
    }
    else if (trenutnaVrijednost >= najvecaDozvoljenaVrijednost) {
        inputBox.val(trenutnaVrijednost = najvecaDozvoljenaVrijednost);
        $('#posljednja,#sljedeca').css('visibility', 'hidden');
        $('#prva,#prethodna').css('visibility', 'visible');
    }
    else {
        $('div#stranicenje').children().css('visibility', 'visible');
    }
    DohvatiStrukturuISadrzajTablice($('#odabirtablice').val(), trenutnaVrijednost, false, $('#filter-pojam').val(), $('#filter-atribut').val());
}

$('div#stranicenje').on('change','#broj-stranice', function() {
    var postavljenaVrijednost = parseInt($(this).val());
    if (isNaN(postavljenaVrijednost)) {
        postavljenaVrijednost = 1;
    }
    $(this).val(postavljenaVrijednost);
    promijeniStranicu();
});

$('div#stranicenje').on('click','#prva', function() {
    $('#broj-stranice').val(1);
    promijeniStranicu();
});

$('div#stranicenje').on('click','#posljednja', function() {
    var inputBox = $('#broj-stranice');
    inputBox.val(inputBox.attr('max'));
    promijeniStranicu();
});

$('div#stranicenje').on('click','#sljedeca', function() {
    var inputBox = $('#broj-stranice');
    inputBox.val(parseInt(inputBox.val())+1);
    promijeniStranicu();
});

$('div#stranicenje').on('click','#prethodna', function() {
    var inputBox = $('#broj-stranice');
    inputBox.val(parseInt(inputBox.val())-1);
    promijeniStranicu();
});

var timeout;
$('#filter-pojam').keyup(function(){
    var pojam = $(this).val();
    if(timeout) {
        clearTimeout(timeout);
        timeout = null;
    }

    timeout = setTimeout(function(){
        DohvatiStrukturuISadrzajTablice($('#odabirtablice').val(), 1, false, pojam, $('#filter-atribut').val());
    }, 1000);
});

$('#filter-atribut').change(function(){
    DohvatiStrukturuISadrzajTablice($('#odabirtablice').val(), 1, false, $('#filter-pojam').val(), $(this).val());
});

$('div#podatkovna-tablica').on('click', 'th:not(th[colspan=2])', function(){
    var asc = $(this).children('img#asc');
    var desc = $(this).children('img#desc');
    var prioritet = $(this).children('span#redoslijed');
    if (asc.is(':visible')) {
        var dosadasnjiPrioritet = prioritet.text();
        $(this).siblings(':not(th[colspan=2])').each(function(){
            var poljePrioritetStupca = $(this).children('span#redoslijed');
            if (poljePrioritetStupca.text()) {
                var prioritetStupca = parseInt(poljePrioritetStupca.text());
                if (prioritetStupca > dosadasnjiPrioritet) {
                    poljePrioritetStupca.text(prioritetStupca-1);
                }
            }
        });
        prioritet.text('');
        asc.hide();
    }
    else {
        asc.show();
        if (desc.is(':visible')) {
            desc.hide();
        }
        else {
            var dostupanPrioritet = 1;
            $(this).siblings(':not(th[colspan=2])').each(function(){
                if ($(this).children('span#redoslijed').text()) {
                    dostupanPrioritet++;
                }
            });
            prioritet.text(dostupanPrioritet);
        }
    }
    DohvatiStrukturuISadrzajTablice($('#odabirtablice').val(), $('#broj-stranice').val(), false, $('#filter-pojam').val(), $('#filter-atribut').val());
});

$('div#podatkovna-tablica').on('contextmenu', 'th:not(th[colspan=2])', function(){
    var asc = $(this).children('img#asc');
    var desc = $(this).children('img#desc');
    var prioritet = $(this).children('span#redoslijed');
    if (desc.is(':visible')) {
        var dosadasnjiPrioritet = prioritet.text();
        $(this).siblings(':not(th[colspan=2])').each(function(){
            var poljePrioritetStupca = $(this).children('span#redoslijed');
            if (poljePrioritetStupca.text()) {
                var prioritetStupca = parseInt(poljePrioritetStupca.text());
                if (prioritetStupca > dosadasnjiPrioritet) {
                    poljePrioritetStupca.text(prioritetStupca-1);
                }
            }
        });
        prioritet.text('');
        desc.hide();
    }
    else {
        desc.show();
        if (asc.is(':visible')) {
            asc.hide();
        }
        else {
            var dostupanPrioritet = 1;
            $(this).siblings(':not(th[colspan=2])').each(function(){
                if ($(this).children('span#redoslijed').text()) {
                    dostupanPrioritet++;
                }
            });
            prioritet.text(dostupanPrioritet);
        }
    }
    DohvatiStrukturuISadrzajTablice($('#odabirtablice').val(), $('#broj-stranice').val(), false, $('#filter-pojam').val(), $('#filter-atribut').val());
    return false;
});

$(window).on("blur focus", function(e) {
    var virtualnoVrijemeLink = $('a#virtualno-vrijeme');
    if ($('#odabirtablice').val()==='administrativni_parametar' && virtualnoVrijemeLink.length) {
        var prevType = $(this).data("prevType");
        if (prevType !== e.type) {
            if (e.type==='focus') {
                var dataToSend = { format : 'json' };
                $.ajax({
                    url : "http://barka.foi.hr/WebDiP/pomak_vremena/pomak.php",
                    type: "GET",
                    data : dataToSend,
                    dataType: "json",
                    success: function(data) {
                        virtualnoVrijemeLink.text(parseInt(data.WebDiP.vrijeme.pomak.brojSati));
                    }
                });
            }
        }
        $(this).data("prevType", e.type);
    }
});

function napuniComboboxSPodacima(padajuciIzbornik, dataToSend) {
    $.ajax({
        url : "obrada_asinkronih_zahtjeva.php",
        type: "POST",
        data : dataToSend,
        dataType: "json",
        success: function(data) {
            for (var i=0;i<data.length;i++) {
                padajuciIzbornik.append('<option value="' + data[i].vrijednost + '">' + data[i].tekst + '</option>');
            }
        }
    });
}

function GenerirajPoljaZaUnos(nazivTablice) {
    var dataToSend = { potraznja : 'vrati_strukturu_i_sadrzaj_tablice' , tablica : nazivTablice , broj_stranice : 0 , vrijednost_filtriranja : '' , atribut_filtriranja : ''};
    $.ajax({
        url : "obrada_asinkronih_zahtjeva.php",
        type: "POST",
        data : dataToSend,
        dataType: "json",
        success: function(data) {
            $('div#obrazac-za-unos').html('<form></form>');
            var sadrzaj = $('div#obrazac-za-unos form');
            for (var nazivAtributa in data['relacijska_shema']) {
                var refTablica = data['relacijska_shema'][nazivAtributa].ReferencedTable;
                sadrzaj.append('<label for="' + nazivAtributa + '">' + nazivAtributa + '</label>');
                var labela = $('label[for=' + nazivAtributa + ']');
                var ogranicenje = data['relacijska_shema'][nazivAtributa].Key;
                var inputPolje;
                if (refTablica) {
                    labela.after('<select id="' + nazivAtributa + '"></select><br/>');
                    inputPolje = $('select#' + nazivAtributa);
                    if (ogranicenje) {
                        inputPolje.attr('class', 'constraint-' + ogranicenje);
                    }
                    if (data['relacijska_shema'][nazivAtributa].Null==='YES') {
                        inputPolje.append('<option value="null">null</option>');
                    }
                    dataToSend = { potraznja : 'vrati_sadrzaj_comboboxa' , tablica : nazivTablice , referencirana_tablica : refTablica };
                    napuniComboboxSPodacima(inputPolje, dataToSend);
                }
                else {
                    labela.after('<input id="' + nazivAtributa + '"/><br/>');
                    inputPolje = $('input#' + nazivAtributa);
                    if (ogranicenje) {
                        inputPolje.attr('class', 'constraint-' + ogranicenje);
                    }
                    var tipIVelicina = data['relacijska_shema'][nazivAtributa].Type;
                    var pocetakDuljineTipa = tipIVelicina.indexOf('(');
                    var tip;
                    if (pocetakDuljineTipa===-1) {
                        tip = tipIVelicina;
                    }
                    else {
                        tip = tipIVelicina.substring(0, pocetakDuljineTipa);
                    }
                    var tipInputa;
                    switch (tip) {
                        case 'char':
                        case 'varchar':
                            tipInputa = 'text';
                            break;
                        case 'smallint':
                        case 'int':
                        case 'bigint':
                            tipInputa = 'number';
                            break;
                        case 'tinyint':
                            tipInputa = 'checkbox';
                            break;
                        case 'date':
                            tipInputa = 'date';
                            break;
                        case 'time':
                            tipInputa = 'time';
                            break;
                        case 'datetime':
                            tipInputa = 'datetime';
                            break;
                        default:
                            tipInputa = 'text';
                    }
                    if (pocetakDuljineTipa!==-1) {
                        inputPolje.attr('maxlength', tipIVelicina.substring(pocetakDuljineTipa+1, tipIVelicina.lastIndexOf(')')));
                    }
                    inputPolje.attr('type', tipInputa);
                    if (data['relacijska_shema'][nazivAtributa].Extra==='auto_increment') {
                        inputPolje.addClass('autoinc');
                    }
                }
                if (data['relacijska_shema'][nazivAtributa].Null==='YES') {
                    inputPolje.addClass('nullable');
                    inputPolje.filter('[type=date]').datepicker({ dateFormat: 'yy-mm-dd' }).keyup(function(e) {
                        if(e.keyCode == 8 || e.keyCode == 46) {
                            $(this).datepicker('setDate', null);
                        }
                    });
                }
                else {
                    inputPolje.filter('[type=date]').datepicker( { dateFormat: 'yy-mm-dd' } );
                }
            }
            sadrzaj.append('<input id="tipka-unesi" type="button" value="Unesi zapis"/>');
        }
    });
}

$('div#obrazac-za-unos').on('focusout', '.constraint-PRI, .constraint-UNI', function(){
    var atribut = $(this).attr('id');
    if ($(this).hasClass('autoinc')) {
        if (!$(this).val().isNumeric() && !$(this).val()==='') {
            $(this).effect("hightlight", { color: 'red'}, 3000);
            return;
        }
    }
    else {
        if (!$(this).val() && $(this).attr('type')==='number') {
            $(this).val(0);
        }
    }
    var provjeri = true;
    var dijeloviKljuca = {};
    if ($(this).hasClass('constraint-PRI')) {
        if (!$(this).val()) {
            provjeri = false;
            input.effect("highlight", { color: 'red'}, 3000);                
        }
        else {
            dijeloviKljuca[atribut] = $(this).val();
            var neprazniKljucniElementi = true;
            $.each($(this).siblings(':input.constraint-PRI'), function() {
                var drugiAtribut = $(this).attr('id');
                if ($(this).val()) {
                    dijeloviKljuca[drugiAtribut] = $(this).val();
                }
                else {
                    if (!$(this).hasClass('autoinc')) {
                        neprazniKljucniElementi = false;
                        return;
                    }
                }
            });
            if (!neprazniKljucniElementi) {
                provjeri = false;
            }
        }
    }
    else if ($(this).hasClass('constraint-UNI')) {
        if ($(this).hasClass('nullable') && input.val()==='') {
            provjeri = false;
        }
        else if ($(this).val()==='') {
            provjeri = false;
            input.effect("highlight", { color: 'red'}, 3000);
        }
        else {
            dijeloviKljuca[atribut] = input.val();
        }
    }
    var input = $(this);
    if (provjeri) {
        var dataToSend = { potraznja : 'dostupne_vrijednosti_kljuca' , nove_vrijednosti : dijeloviKljuca , tablica : $('#odabirtablice').val() };
        $.ajax({
            url : "obrada_asinkronih_zahtjeva.php",
            type : "POST",
            data : dataToSend,
            dataType : "json",
            success: function(data) {
                if (data===false) {
                    input.parent().children(':input.constraint-PRI').effect("highlight", { color: 'red'}, 3000);
                }
            }
        });
    }
});

$('div#obrazac-za-unos').on('click', 'input#tipka-unesi', function(){
    var slog = {};
    $.each($('div#obrazac-za-unos :input:not([type=button])'), function(){
        var nazivAtributa = $(this).attr('id');
        if ($(this).attr('type')==='checkbox') {
            slog[nazivAtributa] = $(this).is(':checked') ? 1 : 0;
        }
        else {
            if ($(this).val() || (!$(this).val()) && ($(this).hasClass('autoinc') || $(this).hasClass('nullable'))) {
                slog[nazivAtributa] = $(this).val();
            }
        }
    });
    var tipka = $(this);
    var obrazac = tipka.parents('form');
    var dataToSend = {potraznja : 'pohrani_promjene' , akcija : 'dodaj' , tablica : $('#odabirtablice').val() , novi_podaci : JSON.stringify(slog)};
    $.ajax({
        url : "obrada_asinkronih_zahtjeva.php",
        type: "POST",
        data : dataToSend,
        dataType: "json",
        success: function(data) {
            if (data===true) {
                $(':input:not([type=button])', obrazac).each(function(){
                    if ($(this).is('select')) {
                        $(this).val($('option:first', $(this)).val());
                    }
                    else {
                        $(this).val('');
                    }
                });
                tipka.effect("highlight", { color: 'green'}, 3000);
            }
            else {
                alert("Došlo je do pogreške prilikom unosa zapisa! Jeste li ignorirali upozorenja prilikom unošenja vrijednosti primarnih ključeva?");
                tipka.effect("highlight", { color: 'red'}, 3000);
            }
        }
    });
});

function UploadCSV (nazivTablice) {
    var dataToSend = { potraznja : 'vrati_strukturu_i_sadrzaj_tablice' , tablica : nazivTablice , broj_stranice : 0 , vrijednost_filtriranja : '' , atribut_filtriranja : ''};
    $.ajax({
        url : "obrada_asinkronih_zahtjeva.php",
        type: "POST",
        data : dataToSend,
        dataType: "json",
        success: function(data) {
            $('div#csv-upload').html('<form></form>');
            var sadrzaj = $('div#csv-upload form');
            sadrzaj.append('<label for="upload-file">Odaberite CSV datoteku za upload</label><input type="file" id="upload-file"/><br/>');
            sadrzaj.append('<table><caption>Struktura Vaše CSV datoteke</caption><thead><th>Naziv atributa</th><th></th></thead><tbody></tbody></table>');
            var tijeloTablice = $('tbody', sadrzaj);
            var postojiAutoInkrementiranje = false;
            var atributi = [];
            for (var nazivAtributa in data['relacijska_shema']) {
                var definicijaReda = '<tr><td';
                if (data['relacijska_shema'][nazivAtributa].Extra==='auto_increment') {
                    definicijaReda += ' class="autoinc"';
                    postojiAutoInkrementiranje = true;
                }
                else {
                    atributi.push(nazivAtributa);
                }
                tijeloTablice.append(definicijaReda + '>' + nazivAtributa + '</td><td><img id="podigni" src="img/asc.gif" alt="podigni"/><img id="spusti" src="img/desc.gif" alt="spusti"/></td></tr>');
            }
            sadrzaj.append('<label for="unos-delimitera">Koji se delimiter koristi u Vašoj CSV datoteci?<input type="text" value=";" id="unos-delimitera" maxlength="1"/><br/>');
            if (postojiAutoInkrementiranje) {
                sadrzaj.append('<label for="sadrzi-sifru">Jesu li u datoteci definirane identiteske šifre?</label><input id="sadrzi-sifru" type="checkbox"/><br/>');
            }
            sadrzaj.append('<p>Traženi format CSV datoteke je sljedeći:</p>');
            sadrzaj.append('<p id="csv-format">' + atributi.join(';') + '</p>');
            sadrzaj.append('<label for="predaj-csv">Predaj CSV datoteku</label><input id="predaj-csv" type="button" value="Predaj"/><br/>');
            sadrzaj.append('<p id="status-uploadanja"></p>');
        }
    });
}

$('div#csv-upload').on('click', 'img#podigni', function(){
    var sadrzajTablice = $('div#csv-upload table tbody tr');
    var trenutnaPozicija = $(this).parents('tr').index();
    if (trenutnaPozicija === 0) {
        return;
    }
    else {
        $('tr:nth-child(' + trenutnaPozicija + ')', sadrzajTablice.parent()).before($(this).parents('tr'));
        var celijeSNazivima = $('input#sadrzi-sifru').is(':checked') ? $('td:first-child',sadrzajTablice) : $('td:first-child:not(.autoinc)',sadrzajTablice);
        $('p#csv-format').html(celijeSNazivima.map(function() {
            return this.innerText;
        }).get().join($('#unos-delimitera').val()));
    }
});

$('div#csv-upload').on('click', 'img#spusti', function(){
    var sadrzajTablice = $('div#csv-upload table tbody tr');
    var trenutnaPozicija = $(this).parents('tr').index();
    if (trenutnaPozicija === sadrzajTablice.length-1) {
        return;
    }
    else {
        $('tr:nth-child(' + (trenutnaPozicija+2) + ')', sadrzajTablice.parent()).after($(this).parents('tr'));
        var celijeSNazivima = $('input#sadrzi-sifru').is(':checked') ? $('td:first-child',sadrzajTablice) : $('td:first-child:not(.autoinc)',sadrzajTablice);
        $('p#csv-format').html(celijeSNazivima.map(function() {
            return this.innerText;
        }).get().join($('#unos-delimitera').val()));
    }
});

$('div#csv-upload').on('keyup', 'input#unos-delimitera', function(){
    var sadrzajTablice = $('div#csv-upload table tbody tr');
    var celijeSNazivima = $('input#sadrzi-sifru').is(':checked') ? $('td:first-child',sadrzajTablice) : $('td:first-child:not(.autoinc)',sadrzajTablice);
    $('p#csv-format').html(celijeSNazivima.map(function() {
        return this.innerText;
    }).get().join($(this).val()));
});

$('div#csv-upload').on('change', 'input#sadrzi-sifru', function(){
    var sadrzajTablice = $('div#csv-upload table tbody tr');
    var celijeSNazivima = $(this).is(':checked') ? $('td:first-child',sadrzajTablice) : $('td:first-child:not(.autoinc)',sadrzajTablice);
    $('p#csv-format').html(celijeSNazivima.map(function() {
        return this.innerText;
    }).get().join($('#unos-delimitera').val()));
});

$('div#csv-upload').on('click', 'input#predaj-csv', function(){
    var uploadPolje = $('#upload-file');
    var obrazac = uploadPolje.parents('form');
    if (uploadPolje.val()==="") {
        uploadPolje.effect("highlight", { color: 'red'}, 3000);
        return;
    }
    var data = new FormData();
    data.append('upload-file', uploadPolje[0].files[0]);
    data.append('naziv_tablice', $('#odabirtablice').val());
    data.append('delimiter', $('#unos-delimitera').val());
    var koristitiAutoInc = !$('#sadrzi-sifru').is(':checked');
    data.append('koristi_brojace', koristitiAutoInc);
    var sadrzajTablice = $('div#csv-upload table tbody tr');
    var celijeSNazivima = koristitiAutoInc ? $('td:first-child:not(.autoinc)',sadrzajTablice) : $('td:first-child',sadrzajTablice);
    data.append('redoslijed_atributa', celijeSNazivima.map(function() {
        return this.innerText;
    }).get());
    $.ajax({
        url: 'obrada_csv_datoteke.php',
        data: data,
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        type: 'POST',
        success: function(data){
            var statusPolje = $('#status-uploadanja', obrazac);
            var tekst = 'Poslano redaka: ' + data.primljeno + '<br/>Ispravno redaka: ' + data.ispravno + '<br/>Pohranjeno redaka: ' + data.pohranjeno;
            statusPolje.html(tekst);
            if (!data.pohranjeno) {
                statusPolje.css('background-color', 'red');
            }
            else if (data.primljeno !== data.pohranjeno) {
                statusPolje.css('background-color', 'yellow');
            }
            else {
                statusPolje.css('background-color', 'green');
            }
        }
    });
});

$('#tabs li').mousedown(function(){
    var dosadasnji = $("#tabs").tabs("option", "active");
    var novi = $(this).index('#tabs ul li');
    if (dosadasnji!==novi) {
        switch (novi) {
            case 0:
                DohvatiStrukturuISadrzajTablice($('#odabirtablice').val(), 1, true, '','');
                $('#filter-pojam').val('');
                break;
            case 1:
                GenerirajPoljaZaUnos($('#odabirtablice').val());
                break;
            case 2:
                UploadCSV($('#odabirtablice').val());
                break;
        }
    }
});
