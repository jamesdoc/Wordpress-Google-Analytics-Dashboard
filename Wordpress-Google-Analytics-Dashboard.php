<?php

	/*
	Plugin Name: Top Posts Dashboard
	Description: (Provided by Google Analytics)
	Author: James Doc
	Author URI: http://jamesdoc.com
	Version: 0.2
	*/
	
	$load = new WpgaDash();
	include_once('wpga_dashboard_wdgt.php');
	
	
	class WpgaDash{
		
		// Name of the main var we are going to store in the options table
		protected $option_store = 'wpga_settings';
		protected $token_store = 'wpga_token';
		protected $profile_store = 'wpga_profile';
		protected $top_post_store = 'wpga_posts';
		
		// Declare some default settings
		protected $default_settings = array(
			'ga_api' => array(
					'google_client_id' 		=> '###',
					'google_client_secret' 	=> '###',
					'google_redirect_url' 	=> 'http://...',
					'google_page_url_prefix'=> 'http://...'
				),
			'ga_profile_id' => 'ga:######',
			'ga_filter' 	=> 'ga:pagePath=~/blog/.*/.*',
			'ga_dimension'	=> 'ga:pagePath,ga:pageTitle',
			'ga_metrics'	=> 'ga:pageviews',
			'ga_sort_by'	=> '-ga:pageviews',
			'ga_max_results'=> '50',
			'ga_root_url'	=> ''
		);
	
		
		public function __construct(){
			
			// Include Google API files
			require_once 'src/Google_Client.php';
			require_once 'src/contrib/Google_AnalyticsService.php';
			
			// Create or destroy settings in database on activation/deactivation
			register_activation_hook( __FILE__,  array( $this, 'wpga_activate'));
			register_deactivation_hook( __FILE__,  array( $this, 'wpga_deactivate'));
			
			add_action( 'admin_init', array( &$this, 'wpga_admin_init' ) );
			add_action( 'admin_menu', array( &$this, 'wpga_create_options_page' ) );
			
			// Authenticate
			add_action( 'admin_init', array( &$this, 'authenticate' ) );
			
			// Set up cronjob
			add_action( 'prefix_hourly_event_hook', array( &$this, 'wpga_get_top_posts') );
		}
		
		
		// Whitelist settings that will be created
		public function wpga_admin_init() {
			register_setting( 'wpga_settings', $this->option_store, array($this, 'wpga_validate') );
		}
		
		
		// Deal with all the authentication goodness that comes from Google
		public function authenticate() {
			if($_GET['auth'] == True){
				include_once('wpga_auth.php');
			}
			
			if($_GET['forget'] == True){
				update_option($this->token_store, '');
				update_option($this->profile_store, '');
				header('Location: ' . admin_url('options-general.php?page=wpga_settings'));
			}
			
			if($_GET['refresh'] == True){
				include_once('wpga_refresh_token.php');
			}
		}
		
		
		// Register options page
		public function wpga_create_options_page() {
			add_options_page(
				'Top Post Dashboard',
				'Top Post Dashboard',
				'manage_categories',
				'wpga_settings',
				array( $this, 'wpga_top_posts_options_form' )
			);
		}
		
		
		// Output options form
		public function wpga_top_posts_options_form() {
			
			$options = get_option($this->option_store);
			$token = get_option($this->token_store);
			$profiles = get_option($this->profile_store);
			$top_posts = get_option($this->top_post_store);
			
			// If profile in database doesn't match saved options then go and get new data
			if($options['ga_profile_id'] != $top_posts['profile']) {
				$this->wpga_get_top_posts();
			}
			
			?>
			<div class="wrap">
			
				<h2>Top Posts Dashboard Setup</h2>
				
				<form method="post" action="options.php">
					
					<?php settings_fields('wpga_settings'); ?>
					
					<h3>Google API Credentials</h3>
					
					<?php if( GOOGLE_CLIENT_ID != 'GOOGLE_CLIENT_ID' ): ?>
						<p>These have been defined already in your wp-config.php file.</p>
					<?php else : ?>
						<table class="form-table">
							<tr valign="top">
								<th scope="row">Client ID:</th>
								<td><input type="text" name="<?php echo $this->option_store?>[ga_api][google_client_id]" value="<?php echo $options['ga_api']['google_client_id']; ?>" /></td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Client Secret ID:</th>
								<td><input type="text" name="<?php echo $this->option_store?>[ga_api][google_client_secret]" value="<?php echo $options['ga_api']['google_client_secret']; ?>" /></td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Client Redirect URL:</th>
								<td><input type="text" name="<?php echo $this->option_store?>[ga_api][google_redirect_url]" value="<?php echo $options['ga_api']['google_redirect_url']; ?>" /></td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Client URL Prefix:</th>
								<td><input type="text" name="<?php echo $this->option_store?>[ga_api][google_page_url_prefix]" value="<?php echo $options['ga_api']['google_page_url_prefix']; ?>" /></td>
							</tr>
						</table>
						<p>
							To be more secure you can store this information in your wp-config.php file:<br />
							<code>
								define('GOOGLE_CLIENT_ID', '###.apps.googleusercontent.com');<br />
								define('GOOGLE_SECRET_ID', '###');<br />
								define('GOOGLE_REDIRECT_URL', 'http://...');<br />
								define('GOOGLE_URL_PREFIX', 'http://...');<br />
							</code>
						</p>
					<? endif; ?>
					
					
					
					<h3>Authenticate with Google</h3>
					<table class="form-table">
		                <tr valign="top">
		                	<?php if( $token == null): ?>
		                	<th scope="row">Grant access:</th>
		                    <td><a href="<?php echo admin_url('options-general.php?page=wpga_settings&auth=true')?>" class="button">Authorise access to Google Analytics data</a></td>
		                    <?php else: ?>
		                    <th scope="row">Forget access:</th>
		                    <td><a href="<?php echo admin_url('options-general.php?page=wpga_settings&forget=true')?>" class="button">Logout of Google Analytics</a></td>
		                    <?php endif; ?>
		                </tr>
		            </table>
					
					<?php if ($token != null): ?>
					
					<?php
						if (!$profiles) {
							// Go get a list of profiles that the user can access
							$this->get_profile_information();
							
							$profiles = get_option($this->profile_store);
						}
					?>
					
					<h3>Analytics Profile</h3>
					<table class="form-table">
		                <tr valign="top">
		                	<th scope="row">Analytics profile ID:</th>
		                    <td>		                    	
		                    	<select name="<?php echo $this->option_store?>[ga_profile_id]">
		                    		<?php foreach($profiles as $profile): ?>
		                    			<option value="<?php echo $profile['id']; ?>" <?php if($options['ga_profile_id'] == $profile['id']){ echo ' selected="selected"';} ?>><?php echo $profile['name']; ?></option>
		                    		<?php endforeach; ?>
		                    	</select>
		                    </td>
		                </tr>
		                
		                <tr valign="top">
		                	<th scope="row">Analytics filter:</th>	                    	
							<td><input type="text" name="<?php echo $this->option_store?>[ga_filter]" value="<?php echo $options['ga_filter']; ?>" /></td>
		                </tr>
		                
		                <tr valign="top">
		                	<th scope="row">Analytics dimension:</th>	                    	
							<td><input type="text" name="<?php echo $this->option_store?>[ga_dimension]" value="<?php echo $options['ga_dimension']; ?>" /></td>
		                </tr>
		                
		                <tr valign="top">
		                	<th scope="row">Analytics metric:</th>	                    	
							<td><input type="text" name="<?php echo $this->option_store?>[ga_metrics]" value="<?php echo $options['ga_metrics']; ?>" /></td>
		                </tr>
		                
		                <tr valign="top">
		                	<th scope="row">Analytics sort by:</th>	                    	
							<td><input type="text" name="<?php echo $this->option_store?>[ga_sort_by]" value="<?php echo $options['ga_sort_by']; ?>" /></td>
		                </tr>
		                
		                <tr valign="top">
		                	<th scope="row">Analytics max results:</th>	                    	
							<td><input type="text" name="<?php echo $this->option_store?>[ga_max_results]" value="<?php echo $options['ga_max_results']; ?>" /></td>
		                </tr>
		                
		                <tr valign="top">
		                	<th scope="row">Root URL:</th>	                    	
							<td>
								<input type="text" name="<?php echo $this->option_store?>[ga_root_url]" value="<?php echo $options['ga_root_url']; ?>" />
								<p class="description">If your blog is in a subfolder (eg: http://domain.com/blog), you will need to set this to just the root url (eg http://domain.com).</p>
							</td>
		                </tr>
		                
		            </table>
		            
		            <?php endif; ?>
					
		            <p class="submit">
		                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		            </p>
					
				</form>
				
				
			</div>
			<?php
		}
		
		
		// Never trust users: validate data entry
		public function wpga_validate($input) {
			
			$valid = array();
			
			$valid['ga_api']['google_client_id'] 		= sanitize_text_field($input['ga_api']['google_client_id']);
			$valid['ga_api']['google_client_secret'] 	= sanitize_text_field($input['ga_api']['google_client_secret']);
			$valid['ga_api']['google_redirect_url'] 	= sanitize_text_field($input['ga_api']['google_redirect_url']);
			$valid['ga_api']['google_page_url_prefix'] 	= sanitize_text_field($input['ga_api']['google_page_url_prefix']);
			
			$valid['ga_profile_id'] 					= sanitize_text_field($input['ga_profile_id']);
			$valid['ga_filter'] 						= sanitize_text_field($input['ga_filter']);
			$valid['ga_dimension'] 						= sanitize_text_field($input['ga_dimension']);
			$valid['ga_metrics'] 						= sanitize_text_field($input['ga_metrics']);
			$valid['ga_sort_by'] 						= sanitize_text_field($input['ga_sort_by']);
			$valid['ga_max_results'] 					= sanitize_text_field($input['ga_max_results']);
			$valid['ga_root_url'] 						= sanitize_text_field($input['ga_root_url']);
			
			// User hasn't entered a GA Profile ID: Throw error and reset field to default
			if (strlen($valid['ga_profile_id']) == 0) {
		        add_settings_error(
		                'ga_profile_id',                // Setting title
		                'ga_profile_id_error',          // Error ID
		                'Profile ID cannot be left empty',   // Error message
		                'error'                         // Type of message
		        );
		
		        // Set it to the default value
		        $valid['ga_profile_id'] = $this->default_settings['ga_profile_id'];
		    }
		    
		    if (strlen($valid['ga_filter']) == 0) {
		    	add_settings_error('ga_filter', 'ga_filter_error', 'Filter cannot be left empty', 'error');
		        $valid['ga_filter'] = $this->default_settings['ga_filter'];
		    }
		    
		    if (strlen($valid['ga_dimension']) == 0) {
		    	add_settings_error('ga_dimension', 'ga_dimension_error', 'Dimension cannot be left empty', 'error');
		        $valid['ga_dimension'] = $this->default_settings['ga_dimension'];
		    }
		    
		    if (strlen($valid['ga_sort_by']) == 0) {
		    	add_settings_error('ga_sort_by', 'ga_sort_by_error', 'Sort by cannot be left empty', 'error');
		        $valid['ga_sort_by'] = $this->default_settings['ga_sort_by'];
		    }
		    
		    if (strlen($valid['ga_max_results']) == 0 || $valid['ga_max_results'] > 100) {
		    	add_settings_error('ga_max_results', 'ga_max_results_error', 'Max result cannot be left empty and less than 100', 'error');
		        $valid['ga_max_results'] = $this->default_settings['ga_max_results'];
		    }
		    
		    if (strlen($valid['ga_root_url']) == 0 || !filter_var($valid['ga_root_url'], FILTER_VALIDATE_URL)) {
		    	add_settings_error('ga_root_url', 'ga_root_url_error', 'Root URL must be a valid URL', 'error');
		        $valid['ga_root_url'] = get_site_url();
		    }
		    
		    // If profile id set then refresh the stats in case of change
		    if ($valid['ga_profile_id'] != "") {
		    	$this->wpga_get_top_posts();
		    }
		    
		    return $valid;
		}
		
		
		// On activation create some default settings
		public function wpga_activate() {
			
			// Create some empty options
			update_option($this->option_store, $this->default_settings);
			update_option($this->token_store, '');
			update_option($this->profile_store, '');
			update_option($this->top_post_store, '');
			
			// Set up pseudo-cron
			wp_schedule_event( time(), 'hourly', 'prefix_hourly_event_hook' );
		}
		
		
		// On deactivation clear up in the database
		public function wpga_deactivate() {
		    
		    // Delete all database options
		    delete_option($this->option_store);
		    delete_option($this->token_store);
		    delete_option($this->profile_store);
		    delete_option($this->top_post_store);
		    
		    // Deregister pseudo cron
		    wp_clear_scheduled_hook( 'prefix_hourly_event_hook' );
		}		
		
		// Go to GA and add top posts to the database
		public function wpga_get_top_posts(){
			
			$options = get_option($this->option_store);
			$token = get_option($this->token_store);
			
			if (!$token || $options['ga_profile_id'] == 'ga:######') {
				return;
			}
		
			include_once("wpga_setup.php");
						
			$gClient->setAccessToken($token);
						
			//create analytics services object
			$analytics_service = new Google_AnalyticsService($gClient); 
			
			$start_date = date( "Y-m-d", strtotime("-30 day") ); 
			$end_date = date( "Y-m-d" );

			
			//analytics parameters (check configuration file)
			$params = array(
			    'dimensions' => $options['ga_dimension'],
			    'sort' => $options['ga_sort_by'],
			    'filters' => $options['ga_filter'],
			    'max-results' => $options['ga_max_results']
			);
			
			//get results from google analytics
			$top_posts = $analytics_service->data_ga->get($options['ga_profile_id'], $start_date, $end_date, $options['ga_metrics'], $params);
			
			$insert = array(
				'datetime' => date('U'),
				'profile' => $options['ga_profile_id'],
				'rows' => $top_posts->rows
			);
			
			// Add top posts to the options database			
			update_option($this->top_post_store, $insert); 
		}

		
		// Add a list of all the profiles that user has access to 
		public function get_profile_information(){
			
			$token = get_option($this->token_store);
			
			include_once("wpga_setup.php");
			
			// We're going to dump all the profile information into an array and then put them into the options table
			$profile_store = array();
			
			$gClient->setAccessToken($token);
					
			// Create analytics services object
			$analytics_service = new Google_AnalyticsService($gClient);
			
			try {
				// Get a list of top level accounts
				$accounts = $analytics_service->management_accounts->listManagementAccounts();
			} catch (Exception $e) {
				sleep(1);
				$accounts = $analytics_service->management_accounts->listManagementAccounts();
			}
			
			foreach ($accounts->getItems() as $account){
			
				$account_id = $account->getId();
				$account_name = $account->name;
				
				try {
					// Get a list of all the properties in each account
					$properties = $analytics_service->management_webproperties->listManagementWebproperties($account_id);
				} catch (Exception $e) {
					sleep(1);
					$properties = $analytics_service->management_webproperties->listManagementWebproperties($account_id);
				}
				
				foreach ($properties->getItems() as $property) {
					
					$property_id = $property->getId();
					$property_name = $property->name;
					$property_url = $property->websiteUrl;
					
					try {
						// Get the specific profile information for each property
						$profiles = $analytics_service->management_profiles->listManagementProfiles($account_id, $property_id);
					} catch (Exception $e) {
						sleep(1);
						$profiles = $analytics_service->management_profiles->listManagementProfiles($account_id, $property_id);
					}
			
					if (count($profiles->getItems()) > 0) {
						
						// We only care about the first profile
		                $items = $profiles->getItems();
		                $profileId = $items[0]->getId();
						
						$name = $account_name;
						
						if ($account_name != $property_name) {
							$name .= ' - ' . $property_name;
						}
						
						$profile_store[] = array(
							'id' 	=> 'ga:' . $profileId,
							'name'	=> $name,
							'url'	=> $property_url
						);
	                }
					
				} // End foreach property
				
			} // End foreach account
			
			// Store updates in the database
			update_option($this->profile_store, $profile_store); 
		}
		
	}