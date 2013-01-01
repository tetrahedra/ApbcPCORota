<?php
/*
Functions to deal with authorisation of PCO.
Based on sample PHP code from PCO.
*/

$callback_url = site_url('/') . "wp-admin/options-general.php?page=ApbcPCORota/pco_options.php";
//$debug = true;
$request_token_endpoint = "https://www.planningcenteronline.com/oauth/request_token";
$authorize_endpoint = "https://www.planningcenteronline.com/oauth/authorize";
$oauth_access_token_endpoint = "https://www.planningcenteronline.com/oauth/access_token";

/***************************************************************************
 * Function: Run CURL
 * Description: Executes a CURL request
 * Parameters: url (string) - URL to make request to
 *             method (string) - HTTP transfer method
 *             headers - HTTP transfer headers
 *             postvals - post values
 **************************************************************************/
function run_curl($url, $method = 'GET', $headers = null, $postvals = null){
    $ch = curl_init($url);

    if ($method == 'GET'){
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    } else {
        $options = array(
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postvals,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 3
        );
        curl_setopt_array($ch, $options);
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// ********** Helper functions for authorization

/*
 * Looks up the endpoint and retrieves a Request Token (key & secret) 
 */
function getAndSetRequestToken($options) {
	require_once "OAuth.php";       //oauth library
	global $request_token_endpoint, $callback_url, $authorize_endpoint;
	
	$consumer = new OAuthConsumer($options['pco_key'], $options['pco_secret'], NULL);

	//prepare to get request token
	$sig_method = new OAuthSignatureMethod_HMAC_SHA1();
	$parsed = parse_url($request_token_endpoint);
	$params = array('oauth_callback' => $callback_url);
	
	//sign request and get request token
	$req_req = OAuthRequest::from_consumer_and_token($consumer, NULL, "GET", $request_token_endpoint, $params);
	$req_req->sign_request($sig_method, $consumer, NULL);
	$req_token = run_curl($req_req->to_url(), 'GET');
	
	//if fetching request token was successful we should have oauth_token and oauth_token_secret
	parse_str($req_token, $tokens);
	$oauth_token = $tokens['oauth_token'];
	$oauth_token_secret = $tokens['oauth_token_secret'];
	
	//store key and token details in the WordPress options table
	update_option('pco_request', array('pco_oauth_token' => $oauth_token, 'pco_oauth_token_secret' => $oauth_token_secret));
	
	//build authentication url following sign-in and redirect user
	$auth_url = $authorize_endpoint . "?oauth_token=$oauth_token";
	return $auth_url;
}

/*
 * Looks up the endpoint and retrieves an Access Token (key & secret) 
 */
function getAndSetAccessToken($options,$request_options) {
	require_once "OAuth.php";       //oauth library
	global $oauth_access_token_endpoint;

	$oauth_verifier = $_GET['oauth_verifier'];
	echo "Oauth_verifier = " . $oauth_verifier;
	
	//create required consumer variables
	$test_consumer  = new OAuthConsumer($options['pco_key'], $options['pco_secret'], NULL);
	$req_token      = new OAuthConsumer($request_options['pco_oauth_token'], $request_options['pco_oauth_token_secret'], NULL);
	$sig_method     = new OAuthSignatureMethod_HMAC_SHA1();
	//echo "<p>REQUEST TOKEN============================================</p>"; 
	//echo $req_token;
	//echo "<p>============================================</p>";
	
	//exchange authenticated request token for access token
	$params         = array('oauth_verifier' => $oauth_verifier);
	$acc_req        = OAuthRequest::from_consumer_and_token($test_consumer, $req_token, "GET", $oauth_access_token_endpoint, $params);
	$acc_req->sign_request($sig_method, $test_consumer, $req_token);
	$access_ret     = run_curl($acc_req->to_url(), 'GET');
	
	//if access token fetch succeeded, we should have oauth_token and oauth_token_secret parse and generate access consumer from values
	parse_str($access_ret, $access_token);
	//echo "<p>ACCESS TOKEN============================================</p>";
	//echo $access_ret;
	//echo print_r($access_token);
	//echo "<p>============================================</p>";
	
	//update the options
	if ($access_token)
		echo "Access token retrieved";
		
	update_option('pco_plugin_options',
		array(
			'pco_key' => $options['pco_key'], 
			'pco_secret' => $options['pco_secret'],
			'pco_tokenkey' => $access_token['oauth_token'], 
			'pco_tokensecret' => $access_token['oauth_token_secret']
			)
		);	
}







?>
