var glMap;

function initMap() {
    glMap = new google.maps.Map(document.getElementById('map'), {
        center: {lat: 46, lng: 16},
        zoom: 9
    });

    var lat = document.getElementById('lat');
    var lng = document.getElementById('lng');
    if (lat!==null && lng!==null) {
        var pos = { lat : parseFloat(lat.innerHTML) , lng : parseFloat(lng.innerHTML) };
        var infoWindow = new google.maps.InfoWindow({map: glMap});
        infoWindow.setPosition(pos);
        infoWindow.setContent('Vi se nalazite ovdje!');
        glMap.setCenter(pos);
    }
    else {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    var pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    var infoWindow = new google.maps.InfoWindow({map: glMap});
                    infoWindow.setPosition(pos);
                    infoWindow.setContent('Vi se nalazite ovdje!');
                    glMap.setCenter(pos);
                    var dataToSend = {potraznja: 'trenutna_geografska_pozicija', pozicija: pos};
                    $.ajax({
                        url : "obrada_asinkronih_zahtjeva.php",
                        type: "POST",
                        data : dataToSend,
                        dataType: "json"
                    });
                },
                function () {
                    handleLocationError(true, infoWindow, glMap.getCenter());
                }
            );
        }
        else {
            handleLocationError(false, infoWindow, glMap.getCenter());
        }
    }
}


function geocodeAddress(geocoder, address, nazivKnjiznice) {
    geocoder.geocode({'address': address}, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            glMap.setCenter(results[0].geometry.location);
            var marker = new google.maps.Marker({
                map: glMap,
                position: results[0].geometry.location,
                icon: "img/knjiznica_logo2.png"
            });
            var infowindow = new google.maps.InfoWindow({
                content: nazivKnjiznice + " (" + address + ")"
            });

            marker.addListener('mouseover', function() {
                infowindow.open(glMap, marker);
            });
            
            marker.addListener('mouseout', function() {
               infowindow.close();
            });
        }
        else if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
            console.log(nazivKnjiznice + " (" + address + ") nije označena zbog prevelikog broja poslanih upita za geolokacijom prema Google servisu.");
        }
        else if (status === google.maps.GeocoderStatus.ZERO_RESULTS) {
            console.log(nazivKnjiznice + " (" + address + ") nije označena jer na Google kartama nije pronađena geolokacija s traženom adresom.");
        }
    });
}

function handleLocationError(browserHasGeolocation, infoWindow, pos) {
    infoWindow.setPosition(pos);
    infoWindow.setContent(browserHasGeolocation ? 'Greška: Usluga geolociranja nije uspjela.' : 'Greška: Vaš web-preglednik ne podržava geolociranje.');
}