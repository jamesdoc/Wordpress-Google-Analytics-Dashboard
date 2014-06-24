<?php
	
	include_once("wpga_setup.php");
	
	// If we have an access token we really don't need to be here
	if (isset($_SESSION["token"])) {
		header('Location: ' . $base_url);
	}
	
	// Has Google sent a auth code via the query string?
	if (isset($_GET['code'])) {
	
		$gClient->authenticate();
		$token = $gClient->getAccessToken();
		update_option($this->token_store, $token);
		header('Location: ' . admin_url('options-general.php?page=wpga_settings'));
	
	// Nothing from Google, lets go log in...
	} else {
		$gClient->authenticate();
	}
