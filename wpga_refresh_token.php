<?php
	
	$token = get_option($this->token_store);
	if(!$token) { return; }
	
	$token = json_decode($token);
		
	include_once("wpga_setup.php");

	$gClient->setAccessType('offline');
	$gClient->refreshToken($token->refresh_token);
	$newtoken=$gClient->getAccessToken();
	
    $authObj = json_decode($newtoken);
	
	$authObj->refresh_token = $token->refresh_token;
	$authObj->token_type = 'Bearer';
	
	update_option($this->token_store, json_encode($authObj));