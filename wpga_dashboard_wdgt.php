<?php
	
	$load = new WpgaDashWdgt();
	
	class WpgaDashWdgt{
		
		public function __construct(){
			$posts = get_option('wpga_posts');
			
			if($posts) {
				add_action( 'wp_dashboard_setup', array($this, 'wpga_add_dashboard_widget') );
			}
		}
		
		
		public function wpga_add_dashboard_widget() {
			
			add_meta_box(
				'wdgt_section_stats',
				'Top posts in the last 30 days...',
				array($this, 'wpga_dashboard_wdgt'),
				'dashboard',
				'side',
				'high' 
			);
			
		}
		
		public function wpga_dashboard_wdgt(){
			$settings = get_option('wpga_settings');
			$posts = get_option('wpga_posts');
			$posts = array_slice($posts['rows'], 0, 10);
			?>
			
			<ul>
			
			<?php foreach($posts as $post): ?>
				<li>
					<a href="<?php echo $settings['ga_root_url'] . $post[0];?>" target="_blank">
						<?php echo str_replace(' | ' . get_bloginfo('name'), '', $post[1]); ?>
					</a><br />
					<small><?php echo $post[2]; ?> page views</small>
				</li>
			<?php endforeach; ?>
			
			</ul>
			
			<?php
		}
		
	}

	