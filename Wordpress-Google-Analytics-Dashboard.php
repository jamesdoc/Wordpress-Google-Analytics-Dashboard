<?php

	/*
	Plugin Name: Top Posts Dashboard
	Description: (Provided by Google Analytics)
	Author: James Doc
	Author URI: http://jamesdoc.com
	Version: 0.1
	*/
	
	$load = new WpgaDash();
	
	class WpgaDash{
		
		// Name of the main var we are going to store in the options table
		protected $option_name = 'wpga_settings';
		
		// Declare some default settings
		protected $default_settings = array(
			'ga_api' => array(
					'google_client_id' 		=> '###',
					'google_client_secret' 	=> '###',
					'google_redirect_url' 	=> 'http://...',
					'google_page_url_prefix'=> 'http://...'
				),
			'ga_profile_id' => 'ga:######'
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
			
		}
		
		
		// Whitelist settings that will be created
		public function wpga_admin_init() {
			register_setting( 'wpga_settings', $this->option_name, array($this, 'wpga_validate') );
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
			
			$options = get_option($this->option_name);
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
								<td><input type="text" name="<?php echo $this->option_name?>[ga_api][google_client_id]" value="<?php echo $options['ga_api']['google_client_id']; ?>" /></td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Client Secret ID:</th>
								<td><input type="text" name="<?php echo $this->option_name?>[ga_api][google_client_secret]" value="<?php echo $options['ga_api']['google_client_secret']; ?>" /></td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Client Redirect URL:</th>
								<td><input type="text" name="<?php echo $this->option_name?>[ga_api][google_redirect_url]" value="<?php echo $options['ga_api']['google_redirect_url']; ?>" /></td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Client URL Prefix:</th>
								<td><input type="text" name="<?php echo $this->option_name?>[ga_api][google_page_url_prefix]" value="<?php echo $options['ga_api']['google_page_url_prefix']; ?>" /></td>
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
		                	<th scope="row">Grant access:</th>
		                    <td><a href="" class="button">Authorise access to Google Analytics data</a></td>
		                </tr>
		            </table>
					
					
					<h3>Analytics Profile</h3>
					<table class="form-table">
		                <tr valign="top">
		                	<th scope="row">Analytics profile ID:</th>
		                    <td><input type="text" name="<?php echo $this->option_name?>[ga_profile_id]" value="<?php echo $options['ga_profile_id']; ?>" /></td>
		                </tr>
		            </table>
		            
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
			
			
			// User hasn't entered a GA Profile ID: Throw error and reset field to default
			if (strlen($valid['ga_profile_id']) == 0) {
		        add_settings_error(
		                'ga_profile_id',                // Setting title
		                'ga_profile_id_error',          // Error ID
		                'Please select a profile ID',   // Error message
		                'error'                         // Type of message
		        );
		
		        // Set it to the default value
		        $valid['ga_profile_id'] = $this->default_settings['ga_profile_id'];
		    }
		    
		    
		    return $valid;
		}
		
		
		// On activation create some default settings
		public function wpga_activate() {
			update_option($this->option_name, $this->default_settings);
		}
		
		
		// On deactivation clear up in the database
		public function wpga_deactivate() {
		    delete_option($this->option_name);
		}
		
	}