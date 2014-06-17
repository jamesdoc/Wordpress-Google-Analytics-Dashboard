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
			'ga_profile_id' => 'ga:######'
		);
		
		
		public function __construct(){
			
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
					
					<table class="form-table">
		                <tr valign="top">
		                	<th scope="row">Analytics profile id:</th>
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
			$valid['ga_profile_id'] = sanitize_text_field($input['ga_profile_id']);
			
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