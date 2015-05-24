<?php
// Restrict testing to running from the command line
(PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die('cli only');

// Test sequence: POST, GET, GETID, PUT, DELETE
$postResult = testPost(['name' => 'Test Movie']);
if ($postResult['status'] == false) {
	echo "POST ERROR: " . $postResult['err'] . "\n";
	exit;
}

$result = testGet();
if ($result['status'] == false) {
	echo "GET ERROR: " . $result['err'] . "\n";
	exit;
}

$result = testGetId($postResult['id']);
if ($result['status'] == false) {
	echo "GET BY SPECIFIC ID ERROR: " . $result['err'] . "\n";
	exit;
}

$result = testPut($postResult['id'], ['name' => 'Test Movie']);
if ($result['status'] == false) {
	echo "PUT ERROR: " . $result['err'] . "\n";
	exit;
}

$result = testDelete($postResult['id']);
if ($result['status'] == false) {
	echo "DELETE ERROR: " . $result['err'] . "\n";
	exit;
}

echo "ALL TESTS PASSED!";

/*
 * FUNCTIONS FOR TESTING ENDPOINTS
 */
 
// Test GET /movies
function testGet() {
	$error = '';
	$response = curlIt('http://localhost:8000/movies', $error);

	return checkResponse($response, '200', $error, false);
}

// Test GET /movies/:id
function testGetId($id) {
	$error = '';
	$response = curlIt('http://localhost:8000/movies/' . $id, $error);
	$jsonCheck = json_decode($response[0], true);

	return checkResponse($response, '200', $error, false);
}

// Test POST /movies
function testPost($data) {
	$error = '';
	$jsonData = json_encode($data);
	$response = curlItCustom('http://localhost:8000/movies', $jsonData, $error, 'POST');
	$jsonCheck = json_decode($response[0], true);

	return checkResponse($response, '201', $error, true);
}

// Test PUT /movies/:id
function testPut($id, $data) {
	$error = '';
	$jsonData = json_encode($data);
	$response = curlItCustom('http://localhost:8000/movies/' . $id, $jsonData, $error, 'PUT');
	$jsonCheck = json_decode($response[0], true);
	
	if ($response == false) {
		return ['status' => false, 'err' => $error];
	} 

	if ($response[1]['http_code'] == '200') {
		return ['status' => true];
	} else {
		return ['status' => false, 'err' => $error];
	}
}

// Test DELETE /movies/:id
function testDelete($id) {
	$error = '';
	$response = curlItCustom('http://localhost:8000/movies/' . $id, null, $error, 'DELETE');

	if ($response == false) {
		return ['status' => false, 'err' => $error];
	} 

	if ($response[1]['http_code'] == '204') {
		return ['status' => true];
	} else {
		return ['status' => false, 'err' => $error];
	}
}

/*
 * EXTRA FUNCTIONS
 */

// Function to check for appropriate response codes and valid response body
function checkResponse($response, $responseCode, $error, $returnId) {
	$jsonCheck = json_decode($response[0], true);

	if ($response == false) {
		// Curl error
		return ['status' => false, 'err' => $error];
	} 

	if ($response[1]['http_code'] == $responseCode && $jsonCheck != null && count($jsonCheck) > 0 && $error == '') {
		if ($returnId == true) {
			return ['status' => true, 'id' => $jsonCheck['id']];
		}

		return ['status' => true];
	} else {
		// Response Error
		return ['status' => false, 'err' => 'Response HTTP Code is incorrect or response body is empty'];
	}
}

// Function to send HTTP GET request
function curlIt($url, &$error) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FAILONERROR, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    curl_setopt($ch, CURLOPT_URL, $url);
    $returned = curl_exec($ch);
    if($returned === false) {
        // Most likely a timeout
        $error = curl_error($ch);
        return false;
    } else {
    	$info = curl_getinfo($ch);
        return [$returned, $info];
    }
    curl_close ($ch);
}

// Function to send HTTP POST, PUT, DELETE request
function curlItCustom($url,$jsonData,&$error,$requestType) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
    	'Content-Type: application/json',
    	'Content-Length: ' . strlen($jsonData)
	]);

    curl_setopt($ch, CURLOPT_URL, $url);
    $returned = curl_exec($ch);

    if($returned === false) {
        $error = curl_error($ch);
        return false;
    } else {
    	$info = curl_getinfo($ch);
        return [$returned, $info];
    }

    curl_close ($ch);    
}