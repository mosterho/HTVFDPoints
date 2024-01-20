// The following is custom Scriptcase code for app_Login.
// Keep this code in case  the app_Login script is somehow overwritten.
// Just remove the <?php and ?> lines.

<?php

// Copy from here down to just before the PHP end

[gbl_path] = '../home_esis_link';
[gbl_SAC_calendar_jsondecoded] = '';

try{
	// Find GeoLocation of client IP. If IP is not whitelisted, exit program.
	$filetoread = [gbl_path].'/GeoLocate/geolocate_API.php';
	if(file_exists($filetoread)){
		include $filetoread;
		$cls_geolocate = new cls_geolocateapi();
		$is_whitelisted = $cls_geolocate->fct_geolocate_comprehensive();
		if(is_bool($is_whitelisted)){
			if(!$is_whitelisted){
				// The following # commented lines are possible responses, but don't use them here, they're just examples.
				#echo "Is IP whitelisted?: ".$is_whitelisted?'True':'False'.'  response?------'.$cls_geolocate->response;
				#var_dump($cls_geolocate->response);
				#echo "<script>window.close();</script>";
				http_response_code(403);
				die();
			}
		}
		else{
			http_response_code(403);
			die();
		}
	}
	else {
		echo 'In app_Login, cannot open geolocate_API.PHP';
	}


    // include pointyear validation code, instantiate class, and retrieve
    // and update the cookie and, if needed, the options JSON file.
    $filetoread = [gbl_path].'./HTVFDPoints/Pointyear/pointyear_validation.php';
    include $filetoread;
    $wrk_cls_pointyear_validation = new cls_calendar_validation();


	// Retrieve type and value of point year. HTVFD uses December 1 to November 30.
	$filetoread = [gbl_path].'/dataonly/htvfd_init/options.json';
	if(file_exists($filetoread)){
		$wrk_options = file_get_contents($filetoread);
		$wrk1_options_jsondecoded = json_decode($wrk_options);
		[gbl_SAC_calendar_jsondecoded] = $wrk1_options_jsondecoded->{"calendar"};

	}
	else{
		echo 'In app_Login, calendar file not found';
		exit();
	}
}

catch(Exception $e){
	echo 'Error within onApplicationInit!! : '.$e;
}
// end of customized app_Login script
// If the geolocation check is valid, continue with normal script

?>
