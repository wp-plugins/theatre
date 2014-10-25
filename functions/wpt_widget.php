<?php

	class WPT_Events_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_events_widget',
				__('Theatre Events','wp_theatre'), // Name
				array( 'description' => __( 'List of upcoming events', 'wp_theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;
			
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			
			echo $args['before_widget'];
			if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
				
			$filters = array(
				'limit'=>$instance['limit']
			);
			
			if (!empty($instance['template'])) {
				$filters['template'] = $instance['template'];
			}

			if ( ! ( $html = $wp_theatre->transient->get('e', array_merge($filters)) ) ) {
				$html = $wp_theatre->events->html($filters);
				$wp_theatre->transient->set('e', array_merge($filters), $html);
			}

			echo $html;

			echo $args['after_widget'];

		}

		public function form( $instance ) {
			$defaults = array(
				'title' => __( 'Upcoming events', 'wp_theatre' ),
				'limit' => 5,
				'template' => ''
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>
			<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of events to show', 'wp_theatre' ); ?>:</label> 
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="3" type="text" value="<?php echo esc_attr( $values['limit'] ); ?>">
			</p>
			<p class="wpt_widget_template">
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','wp_theatre' ); ?>:</label> 
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_attr( $values['template'] ); ?></textarea>
			<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template" target="_blank">documentation</a>.','wp_theatre');?></em>
			</p>
			<?php 
		}
	}

	/*
	 * Theater Production widget.
	 * Display a single production in a widget
	 * @since 0.8.2
	 */

	class WPT_Production_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_production_widget',
				__('Theater Production','wp_theatre'), // Name
				array( 'description' => __( 'Display a single production', 'wp_theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;
			
			
			if (!empty($instance['production'])) {
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
				
				echo $args['before_widget'];
				if ( ! empty( $title ) )
					echo $args['before_title'] . $title . $args['after_title'];
					
				$filters = array(
					'limit'=>$instance['limit']
				);
				
				if (!empty($instance['template'])) {
					$filters['template'] = $instance['template'];
				}
	
				$production = new WPT_Production($instance['production']);

				$production_args = array();
				if (!empty($instance['template'])) {
					$production_args['template'] = $instance['template'];
				}
				$html.= $production->html($production_args);

				echo $html;
	
				echo $args['after_widget'];
			}

		}

		public function form( $instance ) {
			global $wp_theatre;
			$defaults = array(
				'title' => __( 'Production', 'wp_theatre' ),
				'template' => ''
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>

			<p>
			<label for="<?php echo $this->get_field_id( 'production' ); ?>"><?php _e( 'Production','wp_theatre' ); ?>:</label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'production' ); ?>" name="<?php echo $this->get_field_name( 'production' ); ?>">
				<option value=""></option>
				<?php
					$productions = $wp_theatre->productions->load();
					
					foreach ($productions as $production) {
						echo '<option value="'.$production->ID.'"';
						if ($instance['production']==$production->ID) {
							echo ' selected="selected"';
						}
						echo '>';
						echo $production->title();
						echo '</option>';
					}
				?>
			</select>
			</p>


			<p class="wpt_widget_template">
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','wp_theatre' ); ?>:</label> 
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_attr( $values['template'] ); ?></textarea>
			<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template" target="_blank">documentation</a>.','wp_theatre');?></em>
			</p>
			<?php 
		}
	}

	/*
	 * Theater Production Events widget.
	 * Display all events for the current production.
	 * The widget is only visible on a production detail page: is_singular(WPT_Production::post_type_name)
	 * @since 0.8.3
	 */

	class WPT_Production_Events_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_production_events_widget',
				__('Theater Production Events','wp_theatre'), // Name
				array( 'description' => __( 'Display all events for the current production.', 'wp_theatre' ), )
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;
			global $post;
			
			if (is_singular(WPT_Production::post_type_name)) {
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
				
				echo $args['before_widget'];
				if ( ! empty( $title ) )
					echo $args['before_title'] . $title . $args['after_title'];
				
				$filters = array();
				if (!empty($instance['template'])) {
					$filters['template'] = $instance['template'];
				}
	
				$filters['production'] = $post->ID;
				
				if ( ! ( $html = $wp_theatre->transient->get('e', array_merge($filters)) ) ) {
					$html = $wp_theatre->events->html($filters);
					$wp_theatre->transient->set('e', array_merge($filters), $html);
				}
	
				echo $html;
	
				echo $args['after_widget'];
			}
			
			

		}

		public function form( $instance ) {
			$defaults = array(
				'title' => __( 'Upcoming events', 'wp_theatre' ),
				'limit' => 5,
				'template' => ''
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>
			<p class="wpt_widget_template">
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','wp_theatre' ); ?>:</label> 
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_attr( $values['template'] ); ?></textarea>
			<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template" target="_blank">documentation</a>.','wp_theatre');?></em>
			</p>
			<?php 
		}
	}


	class WPT_Productions_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_productions_widget',
				__('Theater Productions','wp_theatre'), // Name
				array( 'description' => __( 'List of upcoming productions', 'wp_theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;

			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

			echo $args['before_widget'];
			if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
				
			$filters = array(
				'limit' => $instance['limit'],
				'upcoming' => true
			);

			if (!empty($instance['template'])) {
				$filters['template'] = $instance['template'];
			}

			$transient_key = 'wpt_prods_'.md5(serialize($filters));
			if ( ! ( $html = get_transient($transient_key) ) ) {
				$html = $wp_theatre->productions->html($filters);
				set_transient($transient_key, $html, 4 * MINUTE_IN_SECONDS );
			}
			echo $html;
			echo $args['after_widget'];

		}

		public function form( $instance ) {
			$defaults = array(
				'title' => __( 'Upcoming productions', 'wp_theatre' ),
				'limit' => 5,
				'template' => ''
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>
			<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of productions to show', 'wp_theatre' ); ?>:</label> 
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="3" type="text" value="<?php echo esc_attr( $values['limit'] ); ?>">
			</p>
			<p class="wpt_widget_template">
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','wp_theatre' ); ?>:</label> 
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_attr( $values['template'] ); ?></textarea><br />
			<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template-2" target="_blank">documentation</a>.','wp_theatre');?></em>
			</p>
			<?php 
		}
	}
	
	class WPT_Cart_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_cart_widget',
				__('Theatre Cart','wp_theatre'), // Name
				array( 'description' => __( 'Contents of the shopping cart.', 'wp_theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;			
			if (!$wp_theatre->cart->is_empty()) {
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
				
				echo $args['before_widget'];
				if ( ! empty( $title ) )
					echo $args['before_title'] . $title . $args['after_title'];
				echo $wp_theatre->cart->render();
				echo $args['after_widget'];
			}


		}

		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			}
			else {
				$title = __( 'Cart', 'wp_theatre' );
			}
			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<?php 
		}

	
	}
?>