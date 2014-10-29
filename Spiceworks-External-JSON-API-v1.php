<?php

/*
	SPICEWORKS UNOFFICIAL EXTERNAL API
	This is a small script to authenticate a user to Spiceworks and then fetch some
	JSON API data from their Internal JSON API
	
	Caution: This may break in the future if Spiceworks changes the way they authenticate.
	
	Version: 1	
	
	Copyright (c) 2012, Media Realm http://mediarealm.com.au/
	All rights reserved.
	
	------------------------------------------------------------------------------------------
	
	Redistribution and use in source and binary forms, with or without modification,
	are permitted provided that the following conditions are met:
	
	* Redistributions of source code must retain the above copyright notice, this list
	  of conditions and the following disclaimer.
	
	* Redistributions in binary form must reproduce the above copyright notice, this list
	  of conditions and the following disclaimer in the documentation and/or other materials
	  provided with the distribution.
	
	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
	AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
	IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
	INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
	PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
	WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
	OF SUCH DAMAGE.
	
	------------------------------------------------------------------------------------------
   
*/



//----------------------
//Set the settings
$username = 'spiceworks_user@example.com'; //Spiceworks username / email
$password = 'PASSWORD-GOES-HERE'; //Spiceworks password
$url_root = 'http://spiceworks.example.com/'; //Include a trailing slash
$cookie_file = 'spicecookies.txt'; //cURL must be able to read and write to this file

$debugMode = false; //Set to true to get outputs of all of the HTTP requests

//Array of all the API calls to make. (no leading slash)
$api_call[] = 'api/alerts.json?filter=recent';
$api_call[] = 'api/hotfixes.json';



//We need to initiate a session and get the authenticity_token from the logon page before we can actually login.
$curl = curl_init($url_root . 'login');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
$loginPage = curl_exec($curl);
curl_close($curl);

if($debugMode) {
	echo "Login form page: (used for getting the authenticity token)\n";
	echo $loginPage;
	echo "\n\n\n";
}

//Using two explode functions to get the authenticity_token from the page:
$authToken = explode('<input name="authenticity_token" type="hidden" value="', $loginPage);
$authToken = explode('"', $authToken['1']);
$authToken = $authToken['0'];

if($debugMode) {
	echo "Authenticity Token: " . $authToken . "\n\n\n";
}

$loginFields = array(
		'authenticity_token' => urlencode($authToken),
		'_pickaxe' => urlencode('⸕'), //This was included in the original login form, so I'm including it here.
		// as of version 7.2.000519 the username and password fields have changed to pro_user 
		'pro_user[email]' => urlencode($username),
		'pro_user[password]' => urlencode($password),
		'btn' => urlencode('login')
	);

//Transform the fields, ready for POST-ing
foreach($loginFields as $key => $val) {
	$fields_string .= $key.'='.$val.'&';
}
$fields_string .= 'btn=login'; //Original form has two btn=login inputs.

if($debugMode) {
	echo "POST String: " . $fields_string . "\n\n\n";
}

//Initiate connection to Login page and send POST data
$curl = curl_init($url_root . 'login');
curl_setopt($curl, CURLOPT_POST, count($loginFields) + 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($curl, CURLOPT_REFERER, $url_root . 'login');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file); //These two options ensure the cookies are both read and written.
curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
$loginProcessPage = curl_exec($curl);
curl_close($curl);

if($debugMode) {
	echo "Login process page: (posts all of the login fields and saves the cookies)\n";
	echo $loginProcessPage;
	echo "\n\n\n";
}

//Stores each API request in an array
foreach($api_call as $api_url) {
	$curl = curl_init($url_root . $api_url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file); 
	$api_call_results[] = array(
		'url' => $api_url,
		'raw' => curl_exec($curl)
	);
	curl_close($curl);
}

//Loops through every stored API request, decodes the JSON and outputs it to the browser.
foreach($api_call_results as $key => $data) {
	$api_call_results[$key]['data'] = json_decode($data['raw'], true);
	print_r($api_call_results[$key]['data']);
}

?>
