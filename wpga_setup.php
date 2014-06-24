<?php
	
	if( GOOGLE_CLIENT_ID != 'GOOGLE_CLIENT_ID' ) {
		
		$google_client_id = GOOGLE_CLIENT_ID;
		$google_client_secret = GOOGLE_SECRET_ID;
		$google_redirect_url = GOOGLE_REDIRECT_URL;
		
	} else {
		
		$options = get_option($this->option_store);
		$google_client_id = $options['ga_api']['google_client_id'];
		$google_client_secret = $options['ga_api']['google_client_secret'];
		$google_redirect_url = $options['ga_api']['google_redirect_url'];
		
	}
		
	// Include Google API files
	require_once 'src/Google_Client.php';
	require_once 'src/contrib/Google_AnalyticsService.php';
	
	$gClient = new Google_Client();
	$gClient->setApplicationName('Google Analytics API  exploration');
	$gClient->setClientId($google_client_id);
	$gClient->setClientSecret($google_client_secret);
	$gClient->setRedirectUri($google_redirect_url);
	$gClient->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
	$gClient->setUseObjects(true);