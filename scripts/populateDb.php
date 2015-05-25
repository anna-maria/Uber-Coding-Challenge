<?php
// Get database credentials
$dbopts = parse_url(getenv('DATABASE_URL'));
if (empty($dbopts['path'])) {
    $dbopts = parse_url(require '../env.php');
}

// Connect to database
try {
    $db = new PDO('pgsql:dbname='.ltrim($dbopts["path"],'/').';host='.$dbopts["host"], $dbopts["user"], $dbopts["pass"]);
} catch(PDOException $e) {
    echo $e->getMessage();
}

$uniqueMovieNames = $uniqueLocations = [];
$movies = getData($db, 0, $uniqueMovieNames, $uniqueLocations);

// Get movie data from SF Data API - 1000 records at a time
function getData($db, $offset=1000, $uniqueMovieNames, $uniqueLocations) {
	$lastMovieId = $lastLocationId = 0;

	$reply = curlIt('https://data.sfgov.org/resource/yitu-d5am.json?$select=title,locations&$offset='.$offset, $error); 
	$decodedReply = json_decode($reply, true);
	if (empty($decodedReply)) {
	    // Curl error or no more results
	    return $uniqueMovieNames;
	} else {
		foreach ($decodedReply as $r) {
			if (!isset($uniqueMovieNames[$r['title']])) {
				// Insert unique movie names into movie table
				$stmt = $db->prepare("INSERT INTO movie (name) VALUES (:name)");
				$stmt->bindParam(':name', $r['title']);
				$stmt->execute();
				$lastMovieId = $db->lastInsertId('movie_id_seq');

				// Keep track of associative array with unique movie title as key and movie id as value
				$uniqueMovieNames[$r['title']] = $lastMovieId;
			}

			if (!isset($uniqueLocations[$r['locations']]) && $r['locations'] != '') {
				// Get latitude and longitude for a location using Google Maps Geocode API (include San Francisco, CA in the query for more accurate results) 				
				$latLng = curlIt('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($r['locations']).',+San+Francisco,+CA', $error); 
				$decodedLatLng = json_decode($latLng, true);

				if (!empty($decodedLatLng['results'])) {
					$lat = $decodedLatLng['results'][0]['geometry']['location']['lat'];
					$lng = $decodedLatLng['results'][0]['geometry']['location']['lng'];

					// Insert unique movie names into movie table
					$stmt = $db->prepare("INSERT INTO location (place, lat, lng) VALUES (:location, :lat, :lng)");
					$stmt->bindParam(':location', $r['locations']);
					$stmt->bindParam(':lat', $lat);
					$stmt->bindParam(':lng', $lng);
					$stmt->execute();
					$lastLocationId = $db->lastInsertId('location_id_seq');

					// Keep track of associative array with unique locations as key and location id as value
					$uniqueLocations[$r['locations']] = $lastLocationId;
				} else {
					// Location does not have lat/lng
					$uniqueLocations[$r['locations']] = null;
				}
			}

			// Insert into movie_location table
			$stmt = $db->prepare("INSERT INTO movie_location (movie_id, location_id) VALUES (:movie, :location)");
			$stmt->bindParam(':movie', $uniqueMovieNames[$r['title']]);
			$stmt->bindParam(':location', $uniqueLocations[$r['locations']]);
			$stmt->execute();
		}  

		// Increase offset for next batch and recursively call getData()
		$offset += 1000; 
		getData($db, $offset, $uniqueMovieNames, $uniqueLocations);
	}
}

// Function to send HTTP GET request
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