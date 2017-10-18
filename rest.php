<?php

/*



Allowed parameters

	state (*): state code of the city (Ex: CA)
	city (*): name of the city (Ex: Irvine)
    since: date since the weather should be retrivied (If no date is specified it returns the last 2 weeks weather information), If date is specified it returns since that date to 2 weeks after that date

(*) Required parameters


Response format

	results:
		date: YYYYMMDD
		temp: average temp in Farenheit
		humidity: 
		condition: 

*/

// Configuration
date_default_timezone_set('America/Los_Angeles');
const API_KEY = 'a81937e6b81c2625';
$URL_API = 'http://api.wunderground.com/api/'.API_KEY.'/history_%s/q/%s/%s.json';

// Parameters
$state = @$_GET['state'];
$city = @$_GET['city'];
$since = @$_GET['since'];



function error_response($errorMsg) {
	$response = new StdClass();
	$response->ok = false;
	$response->error = $errorMsg;
	http_response_code(422);
	header('Content-Type: application/json');
	echo json_encode($response);
}

/**
 * retrieve_weather_by_date
 * Retrieves the weather for a specific date
 */
function retrieve_weather_by_date($date, $state, $city) {
	global $URL_API;
	$url_pet = sprintf($URL_API, $date, $state, $city);
	$server_response = json_decode(file_get_contents($url_pet));
	//print_r($server_response);
	$response = new StdClass();
	$response->date = $date;
	$response->temp = $server_response->history->observations[0]->tempi;
	$response->humidity = $server_response->history->observations[0]->hum.'%';
	$response->condition = $server_response->history->observations[0]->conds;
	return $response;
}


/** 
 * retrieve_weather
 * Retrieves the weather for 2 weeks since the since date (if since date is less than 14 days in the past, it returns only the valid dates)
 */
function retrieve_weather($since, $state, $city) {
	$start = DateTime::createFromFormat('Ymd',$since);
	
	$toDate = new DateTime(); // Init today
	if ($since<date('Ymd', strtotime('-2 weeks'))) {
		$toDate = clone $start;
		$toDate->add(new DateInterval('P2W'));
	}
	$toDate->modify('+1 day'); // This is because DatePeriod excludes the end date in the results
	
	$interval = new DateInterval('P1D');

	$period = new DatePeriod($start, $interval, $toDate);
	
	$response = new StdClass();
	$response->ok = true;
	$response->results = array();
	foreach($period as $date){
		array_push($response->results, retrieve_weather_by_date($date->format('Ymd'), $state, $city));
	}
	return $response;
}


// Validation
if (!$state) { // The city is required
	error_response('State is a required field');
	die();
}
if (!$city) { // The city is required
	error_response('City is a required field');
	die();
}
if($since) {
	$dateOk = true;
	if (strlen($since)!=8) 
		$dateOk = false;
	else {
		$year = substr($since, 0, 4);
		$month = substr($since, 4,2);
		$day = substr($since, 6,2);
		if(!checkdate($month, $day, $year))
			$dateOk = false;
	}
	if(!$dateOk) {
		error_response('Date is not valid (Format required YYYYMMDD');
		die();
	}

}

// If the app get here is because the city is entered

if (!$since) {
	$since = date('Ymd', strtotime('-2 weeks'));
}


header('Content-Type: application/json');
echo json_encode(retrieve_weather($since, $state, $city));

?>