<?php
	require '../vendor/autoload.php';

	$app = new \Slim\Slim();
	$app->get('/hello/:name', function ($name) {
	    echo "Hello, $name";
	});
	$app->run();

	/*getMovies();

	function getMovies() {
		$error = '';
		//$reply = curlIt('https://data.sfgov.org/resource/yitu-d5am.json', $error); 
		$reply = curlIt('https://data.sfgov.org/resource/wwmu-gmzc.json', $error);
		if ($reply == false) {
			// curl error
		} else {
			var_dump(json_decode($reply));
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

        /*$openTag = '<result>';
        $closeTag = '</result>';
        $pos1 = strpos($returned,$openTag);
        $pos2 = strpos($returned,$closeTag);
        if ($pos1 && $pos2) {
            $pos1+=strlen($openTag);
            $response = substr($returned,$pos1,$pos2-$pos1);
        }

        if ($returned=='Received' || (isset($response) && $response=='success') || $response=='queued') {
            return true;
        }
        else {
            $reason = 'unknown';
            
            $pos1 = strpos($returned,'<reason>');
            $pos2 = strpos($returned,'</reason>');
            if ($pos1 && $pos2) {
                $pos1+=strlen('<reason>');
                $reason = substr($returned,$pos1,$pos2-$pos1);
            }

            $error = $reason;
            return false;
        }*/
    //}