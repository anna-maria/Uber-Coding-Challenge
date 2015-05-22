<?php
// database vars
$dbopts = parse_url(getenv('DATABASE_URL'));
if (empty($dbopts['path'])) {
    $dbopts = parse_url(require '../env.php');
}

// connect to database
try {
    $dbh = new PDO('pgsql:dbname='.ltrim($dbopts["path"],'/').';host='.$dbopts["host"], $dbopts["user"], $dbopts["pass"]);
} catch(PDOException $e) {
    echo $e->getMessage();
}

$movies = getData($dbh);

// get movie data from SF Data
function getData($dbh, $offset=1000) {
	$uniqueMovieNames = $uniqueLocations = [];
	$lastMovieId = $lastLocationId = 0;

	$reply = curlIt('https://data.sfgov.org/resource/yitu-d5am.json?$select=title,locations&$offset='.$offset, $error); 
	$decodedReply = json_decode($reply, true);
	if (empty($decodedReply)) {
	    // curl error or no more results
	    return $uniqueMovieNames;
	} else {
		foreach ($decodedReply as $r) {
			if (!isset($uniqueMovieNames[$r['title']])) {
				$uniqueMovieNames[$r['title']] = 1;
				
				// insert unique movie names into movie table
				$stmt = $dbh->prepare("INSERT INTO movie (name) VALUES (:name)");
				$stmt->bindParam(':name', $r['title']);
				$stmt->execute();
				$lastMovieId = $dbh->lastInsertId('movie_id_seq');
			}

			if (!isset($uniqueLocations[$r['locations']])) {
				$uniqueMovieNames[$r['locations']] = 1;
				
				$latLng = curlIt('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($r['locations']), $error); 
				$decodedLatLng = json_decode($latLng, true);
				if (!empty($decodedLatLng['results'])) {
					$lat = $decodedLatLng['results'][0]['geometry']['location']['lat'];
					$lng = $decodedLatLng['results'][0]['geometry']['location']['lng'];

					// insert unique movie names into movie table
					$stmt = $dbh->prepare("INSERT INTO location (place, lat, lng) VALUES (:location, :lat, :lng)");
					$stmt->bindParam(':location', $r['locations']);
					$stmt->bindParam(':lat', $lat);
					$stmt->bindParam(':lng', $lng);
					$stmt->execute();
					$lastLocationId = $dbh->lastInsertId('location_id_seq');
				}
			}

			// insert into movie_location table
			$stmt = $dbh->prepare("INSERT INTO movie_location (movie_id, location_id) VALUES (:movie, :location)");
			$stmt->bindParam(':movie', $lastMovieId);
			$stmt->bindParam(':location', $lastLocationId);
			$stmt->execute();
		}  

		$offset += 1000; 
		getData($dbh, $offset);
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