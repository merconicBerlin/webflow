<?php

$url = 'https://a6amdh2cbkxh.c01-15.plentymarkets.com/rest/login';
$username = 'py47736';
$password = 'Ad256fbc';


$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
$pages_response = curl_exec ($curl);
$pages_decode_resp = json_decode($pages_response, true);
$pages_httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close ($curl);

print_r($pages_httpcode); exit();
?>