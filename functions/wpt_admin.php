<?php
class WPT_Admin {
	function __construct() {
		add_action( 'admin_init', function() {
			wp_enqueue_script( 'wp_theatre_js', plugins_url( '../main.js', __FILE__ ), array('jquery') );
			wp_enqueue_style( 'wp_theatre_css', plugins_url( '../style.css', __FILE__ ) );
			wp_enqueue_script( 'jquery-ui-timepicker', plugins_url( '../js/jquery-ui-timepicker-addon.js', __FILE__ ), array('jquery-ui-datepicker','jquery-ui-slider')  );
			wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		});
		add_action( 'admin_menu', array($this, 'admin_menu' ));
		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action( 'edit_post', array( $this, 'edit_post' ));
		add_action( 'delete_post',array( $this, 'delete_post' ));
		add_action( 'save_post', array( $this, 'save_post' ) );
	}	

	function admin_menu() {
		add_menu_page( __('Theatre'), __('Theatre'), 'edit_posts', 'theatre', array($this, 'hallo'), '', 30);
	}
	
	function hallo() {
		echo 'hallo';
	}

	function add_meta_boxes() {
		add_meta_box(
            'wp_theatre_events',
            WPT_Event::post_type()->labels->name,
            array($this,'meta_box_events'),
            WPT_Production::post_type()->name,
            'side'
        ); 		
		add_meta_box(
            'wp_theatre_event_data',
            __('Event data','wp_theatre'),
            array($this,'meta_box_event_data'),
            WPT_Event::post_type()->name,
            'normal'
        ); 		
		add_meta_box(
            'wp_theatre_tickets',
            __('Tickets','wp_theatre'),
            array($this,'meta_box_tickets'),
            WPT_Event::post_type()->name,
            'normal'
        ); 	
		add_meta_box(
            'wp_theatre_seasons',
            WPT_Season::post_type()->labels->name,
            array($this,'meta_box_seasons'),
            WPT_Production::post_type()->name,
            'side'
        ); 	
	}
	
	function meta_box_events($production) {
		

		$production = get_the_id();
		
		if (get_post_status($production) == 'auto-draft') {
			echo __('You need to save this production before you can add events.');
		} else {
			
			$args = array(
				'post_type'=>WPT_Event::post_type_name,
				'meta_key' => 'event_date',
				'order_by' => 'meta_value_num',
				'order' => 'ASC',
				'meta_query' => array(
					array(
						'key' => WPT_Production::post_type_name,
						'value' => get_the_ID(),
						'compare' => '=',
					),
					array(
						'key' => 'event_date', // Check the start date field
						'value' => date("Y-m-d"), // Set today's date (note the similar format)
						'compare' => '>=', // Return the ones greater than today's date
						'type' => 'NUMERIC,' // Let WordPress know we're working with numbers
					)
				),
			);
	
			$events = get_posts($args);
			if (count($events)>0) {
				echo '<ul>';
				foreach ($events as $event) {
					echo '<li>';
					edit_post_link( 
						strftime('%x %X',strtotime(get_post_meta($event->ID,'event_date',true))), 
						'','',
						$event->ID
					);
					echo '<br />';
					echo get_post_meta($event->ID,'venue',true).', '.get_post_meta($event->ID,'city',true);
					echo '</li>';
					
				}
				echo '</ul>';
				
			}
			
			echo '<p><a href="'.get_bloginfo('url').'/wp-admin/post-new.php?post_type='.WPT_Event::post_type_name.'&'.WPT_Production::post_type_name.'='.$production.'" class="button button-primary">'.WPT_Event::post_type()->labels->new_item.'</a></p>';	
		}		
	}

	function meta_box_event_data($event) {
		global $wp_theatre;
		
		wp_nonce_field(WPT_Event::post_type()->name, WPT_Event::post_type()->name.'_nonce' );

		echo '<table class="form-table">';
		echo '<tbody>';

		echo '<tr class="form-field">';		
		echo '<th>';
		echo '<label>';
		echo WPT_Production::post_type()->labels->singular_name;
		echo '</label> ';
		echo '</th>';
		
		echo '<td>';

		if (isset($_GET[WPT_Production::post_type_name])) {
			$current_production = (int) $_GET[WPT_Production::post_type_name];
		} else {
			$current_production = get_post_meta($event->ID,WPT_Production::post_type_name,true);
		}

		$args = array(
			'post_type'=>WPT_Production::post_type_name,
			'posts_per_page' => -1
		);
		if (is_numeric($current_production)) {
			$args['p'] = $current_production;
			$the_query = new WP_Query($args);
			if ( $the_query->have_posts() ) {
				$the_query->the_post();
				echo '<input type="hidden" name="'.WPT_Production::post_type_name.'" value="'.$current_production.'" />';
				echo '<a href="'.get_bloginfo('url').'/wp-admin/post.php?post='.$current_production.'&action=edit">';
				the_title();
				echo '</a>';
			}
		} else {
			$the_query = new WP_Query($args);
			if ( $the_query->have_posts() ) {
				echo '<select name="'.WPT_Production::post_type_name.'">';
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					echo '<option value="'.get_the_ID().'"';
					if ($current_production==get_the_ID()) {
						echo ' selected="selected"';
					}
					echo '>';
					the_title();
					echo '</option>';
				}
				echo '</select>';
			}
			
		}	
		wp_reset_postdata();	
		
		echo '</td>';
		echo '</tr>';
		
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Event date','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" class="wp_theatre_datepicker" name="event_date"';
        echo ' value="' . get_post_meta($event->ID,'event_date',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Venue','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" name="venue"';
        echo ' value="' . get_post_meta($event->ID,'venue',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
		echo '<tr class="form-field">';
		echo '<th><label>'.__('City','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" name="city"';
        echo ' value="' . get_post_meta($event->ID,'city',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
        echo '</tbody>';
        echo '</table>';		
	}
	
	function meta_box_tickets($event) {
		echo '<table class="form-table">';
		echo '<tbody>';
		
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Tickets URL','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="url" name="tickets_url"';
        echo ' value="' . get_post_meta($event->ID,'tickets_url',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
        echo '</tbody>';
        echo '</table>';		
	}

	function meta_box_seasons($production) {
		global $wp_theatre;
		
		wp_nonce_field(WPT_Production::post_type_name, WPT_Production::post_type_name.'_nonce' );

		$args = array(
			'post_type'=>WPT_Season::post_type_name,
			'posts_per_page' => -1
		);

		$seasons = get_posts($args);
		if (count($seasons)>0) {
			echo '<select name="'.WPT_Season::post_type_name.'">';
			echo '<option></option>';
			foreach ($seasons as $season) {
				echo '<option value="'.$season->ID.'"';
				if (get_post_meta($production->ID,WPT_Season::post_type_name,true)==$season->ID) {
					echo ' selected="selected"';
				}
				echo '>';
				echo $season->post_title;
				echo '</option>';
			}
			echo '</select>';

		}
	
	}
	
	function edit_post( $post_id ) {
		$this->flush_cache();
	}
	
	function delete_post( $post_id ) {
		$this->flush_cache();	
	}
	
	function save_post( $post_id ) {
		global $wp_theatre;
	
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST[WPT_Event::post_type_name.'_nonce'] ) )
			return $post_id;

		$nonce = $_POST[WPT_Event::post_type_name.'_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, WPT_Event::post_type_name ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$production = sanitize_text_field( $_POST[WPT_Production::post_type_name] );
		$event_date = sanitize_text_field( $_POST['event_date'] );
		$venue = sanitize_text_field( $_POST['venue'] );
		$city = sanitize_text_field( $_POST['city'] );
		$tickets_url = sanitize_text_field( $_POST['tickets_url'] );

		// Update the meta field.
		update_post_meta( $post_id, WPT_Production::post_type_name, $production );
		update_post_meta( $post_id, 'event_date', $event_date );
		update_post_meta( $post_id, 'venue', $venue );
		update_post_meta( $post_id, 'city', $city );
		update_post_meta( $post_id, 'tickets_url', $tickets_url );

	
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST[WPT_Production::post_type_name.'_nonce'] ) )
			return $post_id;

		$nonce = $_POST[WPT_Production::post_type_name.'_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, WPT_Production::post_type_name ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$season = sanitize_text_field( $_POST[WPT_Season::post_type_name] );

		// Update the meta field.
		update_post_meta( $post_id, WPT_Season::post_type_name, $season );
		
		$this->flush_cache();
	}
	
	function flush_cache() {
		if(!class_exists('W3_Plugin_TotalCacheAdmin'))		
			return;	
		if (
			!in_array(
				get_post_type($post_id),
				array(WPT_Production::post_type_name,WPT_Event::post_type_name,WPT_Season::post_type_name)
			)
		) return;   
			
		if (function_exists('w3tc_pgcache_flush')) { w3tc_pgcache_flush(); }		
	}
}

if (is_admin()) {
	new WPT_Admin();
}

?>