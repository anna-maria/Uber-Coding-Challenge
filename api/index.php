<?php
    require '../vendor/autoload.php';

    $app = new \Slim\Slim();

    $app->get('/map', function () {
        echo '<html>
              <head>
                <style type="text/css">
                  html, body, #map-canvas { height: 100%; margin: 0; padding: 0;}
                </style>
                <script type="text/javascript"
                  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCzZsdcUsJuf7DhjDhu4vhyOIiiQeAQ-2A">
                </script>
                <script type="text/javascript">
                  function initialize() {
                    var mapOptions = {
                      center: { lat: 37.783, lng: -122.417},
                      zoom: 12
                    };
                    var map = new google.maps.Map(document.getElementById(\'map-canvas\'),
                        mapOptions);
                    /*var myLatlng = new google.maps.LatLng(37.819,-122.479);
                    var marker = new google.maps.Marker({
                          position: myLatlng,
                          map: map,
                          title: \'Golden Gate Bridge!\'
                      });*/
                  }
                  google.maps.event.addDomListener(window, \'load\', initialize);
                </script>
              </head>
              <body>
                <div style="margin-top:10px;margin-bottom:10px;text-align:center;">To Start, Search By Movie Title: <input type="text" name="title" value=""></div>
                <div id="map-canvas"></div>
              </body>
            </html>';
    });

    $app->get('/movie', function () {
        echo getMovies();
    });
    $app->run();

    function getMovies() {
        $error = '';
        $reply = curlIt('https://data.sfgov.org/resource/yitu-d5am.json', $error); 
        //$reply = curlIt('https://data.sfgov.org/resource/wwmu-gmzc.json', $error);
        if ($reply == false) {
            // curl error
        } else {
            return $reply;
        }
    }
    
    function curlIt($url, &$error) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_setopt($ch, CURLOPT_URL, $url);
        $returned = curl_exec($ch);
        if($returned === false) {
            // most likely a timeout
            $error = curl_error($ch);
            return false;
        } else {
            return $returned;
        }
        curl_close ($ch);
    }