<?php
/*
see http://mg.mkgarrison.com/2010/08/using-planningcenteronlinecoms-api-with.html for 
instructions on how to set up the PCO API.
*/

/* MAIN FUNCTIONS */

//get details of the next service as an array
function getNextServiceSummary($test_consumer,$access_consumer) {
	//get the list of future plans and retrieve the first plan ID
	$xml = simplexml_load_string(getPlans('220296',$test_consumer,$access_consumer));	//future plans
	$plans = $xml->xpath('//plan/id');	//array of future plan ids
	$firstPlanId = $plans[0];  //return the first plan id
	
	//get the first plan in detail
	$planxml = simplexml_load_string(getPlan($firstPlanId,$test_consumer,$access_consumer));
	
	$output[0]["date"] = getStartDate($planxml);
	$output[0]["time"] = getTime($planxml);
	$output[0]["leader"] = getPerson($planxml,'Worship Leader');
	$output[0]["speaker"] = getPerson($planxml,'Preacher');
	$output[0]["notes"] = getPlanNote($planxml,'General');

	return $output;
}

/* HELPER FUNCTIONS */

// get all service plans as xml
function getPlans($planType,$test_consumer,$access_consumer) {
	$request = OAuthRequest::from_consumer_and_token($test_consumer,
	$access_consumer,
	'GET',
	'https://www.planningcenteronline.com/service_types/'.$planType.'/plans.xml',
	NULL);
	$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(),$test_consumer,$access_consumer);
	// make request
	$plansResponse = run_curl($request, 'GET');
	return $plansResponse;
}

//get a single service plan as xml
function getPlan($planId,$test_consumer,$access_consumer) {
	$request = OAuthRequest::from_consumer_and_token($test_consumer,
	$access_consumer,
	'GET',
	'https://www.planningcenteronline.com/plans/'.$planId.'.xml',
	NULL);
	$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(),$test_consumer,$access_consumer);
	// make request
	$plansResponse = run_curl($request, 'GET');
	return $plansResponse;
}

//get a person name by role
function getPerson($xml,$role) {
	$persons = $xml->xpath("//plan-person[category-name='".$role."']/person-name");
	return $persons[0];
}

//get a note for a plan
function getPlanNote($xml,$category) {
	$persons = $xml->xpath("//plan-note[category-name='".$category."']/note");
	return $persons[0];
}

//get start date of the service
function getStartDate($xml) {
	$start = $xml->xpath("//service-time/starts-at-unformatted");
	$startdate = date('F j Y',strtotime($start[0]));
	return $startdate;
}

//get end time of the service
function getEndTime($xml) {
	$end = $xml->xpath("//service-time/ends-at");
	$endtime = date('H:i',strtotime($end[0]));
	return $endtime;
}

//get start time of the service
function getStartTime($xml) {
	$start = $xml->xpath("//service-time/starts-at-unformatted");
	$starttime = date('H:i',strtotime($start[0]));
	return $starttime;
}

//get time as a string
function getTime($xml) {
	$end = $xml->xpath("//service-time/ends-at");
	$endtime = date('H:i',strtotime($end[0]));
	
	$start = $xml->xpath("//service-time/starts-at");
	$starttime = date('H:i',strtotime($start[0]));
	
	return $starttime . "-" . $endtime;
}

?>