<?php
/**
 * The event editor.
 *
 * Adds an event editor to the production admin page.
 *
 * @since 0.11
 *
 */
class WPT_Event_Editor {

	function __construct() {

		add_action( 'admin_init', array( $this, 'enqueue_scripts' ) );

		add_action( 'add_meta_boxes_'.WPT_Production::post_type_name, array( $this, 'add_events_meta_box' ) );

		/*
		 * Process the form by hooking into the `save_post`-action.
		 * We can't use the `save_post_wpt_event'-action because this causes problems
		 * with ACF's `save_post`-hooks.
		 * See: https://github.com/slimndap/wp-theatre/issues/76
		 */
		add_action( 'save_post', array( $this, 'save_event' ) );

		add_action( 'wp_ajax_wpt_event_editor_delete_event', array( $this, 'delete_event_over_ajax' ) );

	}

	/**
	 * Adds the event editor meta box to the production admin page.
	 *
	 * @since 0.11
	 */
	public function add_events_meta_box() {
		add_meta_box(
			'wpt_event_editor',
			__( 'Events', 'wp_theatre' ),
			array( $this, 'events_meta_box' ),
			WPT_Production::post_type_name,
			'normal',
			'high'
		);
	}

	/**
	 * Sets the javascript variables.
	 *
	 * @since 0.11
	 */
	public function enqueue_scripts() {
		wp_localize_script( 'wp_theatre_admin', 'wpt_event_editor_defaults', $this->get_defaults() );

		wp_localize_script(
			'wp_theatre_admin',
			'wpt_event_editor_security',
			array(
				'nonce' => wp_create_nonce( 'wpt_event_editor_nonce' ),
			)
		);
	}

	/**
	 * Handles the event delete AJAX requests.
	 *
	 * @since 0.11
	 */
	public function delete_event_over_ajax() {

		check_ajax_referer( 'wpt_event_editor_nonce', 'nonce' , true );

		$event_id = $_POST['event_id'];

		// Check if this is a real event.
		if ( is_null( get_post( $event_id ) ) ) {
			wp_die();
		}

		$event = new WPT_Event( $event_id );
		$production = $event->production();

		wp_delete_post( $event_id, true );
		echo $this->get_listing_html( $production->ID );

		wp_die();
	}

	/**
	 * Gets the defaults for use in the event editor.
	 *
	 * You can use the 'wpt/event_editor/defaults'-filter to alter the settings.
	 *
	 * @since 0.11
	 * @access private
	 * @return array {
	 * 		int		$duration			Default duration of an event.
	 *									This is used to automatically set the end time of an event when
	 *									you create a new event.
	 *		string	$datetime_format	Format of the date and time in the event editor.
	 *									See PHP's date() for possible values.
	 *		string	$event_date			Default date and time of a new event.
	 *									Can be anything that strtotime() understands.
	 *		string  $tickets_button     Default text on ticket buttons.
	 *		string	$tickets_status		Default status of a new event.
	 * }
	 */
	private function get_defaults() {

		$defaults = array(
			'duration' => 2 * HOUR_IN_SECONDS,
			'datetime_format' => 'Y-m-d H:i',
			'event_date' => date( 'Y-m-d H:i', strtotime( 'Today 8 PM' ) ),
			'tickets_button' => __( 'Tickets', 'wp_theatre' ),
			'tickets_status' => WPT_Event::tickets_status_onsale,
			'confirm_delete_message' => __( 'Are you sure that you want to delete this event?', 'wp_theatre' ),
		);

		/**
		 * Filter the event editor defaults.
		 *
		 * Use this filter to alter existing defaults.
		 *
		 * @since 0.11
		 *
		 * @param array $defaults	The current defaults.
		 *							See the definition of this function for all possible values.
		 */
		return apply_filters( 'wpt/event_editor/defaults', $defaults );
	}

	/**
	 * Gets the event editor fields for an event.
	 *
	 * @since 0.11
	 * @access public
	 * @see WPT_Event_Editor::get_defaults()
	 * @param int $event_id (default: false)
	 * @return array {
	 * 		array $field {
	 *			string	$id		Unique identifier for this field.
	 *							Should be equal to the meta key for this field.
	 *			string 	$title	Title of this field, visible to the user.
	 *			array	$edit {
	 *				Optional. Form input of this field.
	 *
	 *				string		$placeholder	Optional. Value for the 'placeholder' attribute of the input.
	 *											Default ''.
	 *				string		$description	Optional. Description to display under the label for the input.
	 *											Default ''.
	 *				bool		$disabled		Optional. Activate the 'disabled' attribute of the input.
	 *											Default <false>.
	 *				callback	$callback		Optional. Function that return the HTML for the input.
	 *											Default array( $this, 'get_control' ).
	 *			}
	 *			array	$save {
	 *				Optional. Save handler for this field.
	 *
	 *				callback	$callback		Optional. Function that saves the $_POST value of the field when you
	 *											submit the event editor form.
	 *											Default array( $this, 'get_field' ).
	 *			}
	 *	 	}
	 * }
	 */
	public function get_fields( $event_id = false ) {
		$defaults = $this->get_defaults();

		$fields = array(
			array(
				'id' => 'event_date',
				'title' => __( 'Start time', 'wp_theatre' ),
			),
			array(
				'id' => 'enddate',
				'title' => __( 'End time', 'wp_theatre' ),
				'save' => array(
					'callback' => array( $this, 'save_enddate' ),
				),
			),
			array(
				'id' => 'venue',
				'title' => __( 'Venue', 'wp_theatre' ),
			),
			array(
				'id' => 'city',
				'title' => __( 'City', 'wp_theatre' ),
			),
			array(
				'id' => 'remark',
				'title' => __( 'Remark', 'wp_theatre' ),
				'edit' => array(
					'placeholder' => __( 'e.g. Premiere or Try-out', 'wp_theatre' ),
				),
			),
			array(
				'id' => 'tickets_status',
				'title' => __( 'Tickets status', 'wp_theatre' ),
				'edit' => array(
					'callback' => array( $this, 'get_control_tickets_status_html' ),
				),
				'save' => array(
					'callback' => array( $this, 'save_tickets_status' ),
				),
			),
			array(
				'id' => 'tickets_url',
				'title' => __( 'Tickets URL', 'wp_theatre' ),
				'edit' => array(
					'placeholder' => 'http://',
				),
			),
			array(
				'id' => 'tickets_button',
				'title' => __( 'Text for tickets link', 'wp_theatre' ),
				'edit' => array(
					'description' => sprintf( __( 'Leave blank for \'%s\'', 'wp_theatre' ), $defaults['tickets_button'] ),
				),
			),
			array(
				'id' => '_wpt_event_tickets_price',
				'title' => __( 'Prices', 'wp_theatre' ),
				'edit' => array(
					'callback' => array( $this, 'get_control_prices_html' ),
					'description' => __( 'Place extra prices on a new line.', 'wp_theatre' ),
				),
				'save' => array(
					'callback' => array( $this, 'save_prices' ),
				),
			),
		);

		/**
		 * Filter the event editor fields for an event.
		 *
		 * Use this filter to add your own fields or to remove or alter existing fields.
		 *
		 * @since 0.11
		 *
		 * @param array $fields		The current fields.
		 *							See the definition of this function for all possible values.
		 * @paramt int	$event_id	The ID of the event.
		 */
		$fields = apply_filters( 'wpt/event_editor/fields', $fields, $event_id );

		return $fields;
	}

	/**
	 * Renders the content of the event editor meta box.
	 *
	 * The metabox contains:
	 * - a nonce field,
	 * - a listing with existing events for the current production and
	 * - a form to create a new event.
	 *
	 * @since 0.11
	 * @param object $post	The post object of the current production.
	 * @return void
	 */
	public function events_meta_box( $post ) {

		wp_nonce_field( 'wpt_event_editor', 'wpt_event_editor_nonce' );

		echo '<div class="wpt_event_editor_event_listing">';
		echo $this->get_listing_html( $post->ID );
		echo '</div>';

		echo '<h4>'.__( 'Add a new event', 'wp_theatre' ).'</h4>';
		echo $this->get_form_html( $post->ID );

	}

	/**
	 * Gets all actions for an event in a listing.
	 *
	 * @since 0.11
	 * @access 	private
	 * @param 	int 	$event_id	The event that is the subject for the actions.
	 * @return 	array {
	 *		A key => value array of the actions.
	 *
	 * 		array {
	 *			string	$title	Title of this action, visible to the user.
	 *			string 	$link	The URL of this action.
	 * 		}
	 * }
	 */
	private function get_listing_actions( $event_id ) {

		$actions = array(
			'edit' => array(
				'title' => __( 'Edit' ),
				'link' => get_edit_post_link( $event_id ),
			),
			'delete' => array(
				'title' => __( 'Delete' ),
				'link' => get_delete_post_link( $event_id, '', true ),
			),
		);

		/**
		 * Filter the actions for an event in a listing.
		 *
		 * Use this filter to add your own actions or to remove or alter existing actions.
		 *
		 * @since 0.11
		 * @param array $actions	The current actions.
		 *							See the definition of this function for all possible values.
		 * @param int	$event_id	The ID of the event.
		 */
		$actions = apply_filters( 'wpt/event_editor/listing/actions', $actions, $event_id );

		return $actions;

	}

	/**
	 * Get the HTML for actions of an event in a listing.
	 *
	 * @since 	0.11
	 * @access 	private
	 * @param 	int 	$event_id	The event that is the subject for the actions.
	 * @return 	string				The HTML.
	 */
	private function get_listing_actions_html($event_id) {

		$html = '';

		$html .= '<td class="wpt_event_editor_listing_actions">';
		foreach ( $this->get_listing_actions( $event_id ) as $action => $action_args ) {
			$html .= '<a class="wpt_event_editor_listing_action_'.$action.'" href="'.$action_args['link'].'">'.$action_args['title'].'</a> ';
		}
		$html .= '</td>';

		$html = apply_filters( 'wpt/event_editor/listing/actions/html', $html, $event_id, $actions );

		return $html;

	}

	/**
	 * Gets the HTML for a default field input control of an event.
	 *
	 * @sinc 0.11
	 * @param 	array 	$field		The field.
	 * @param 	int     $event_id   The event that is being edited.
	 * 								Leave blank (or <false>) if you want to create a new event.
	 *								Default <false>.
	 * @return 	string				The HTML.
	 */
	public function get_control_html( $field, $event_id = false ) {
		$html = '';

		if ( ! empty( $field['edit']['callback'] ) ) {
			$html .= call_user_func_array( $field['edit']['callback'], array( $field, $event_id ) );
		} else {
			$html .= '<input type="text" id="wpt_event_editor_'.$field['id'].'" name="wpt_event_editor_'.$field['id'].'"';

			if ( ! empty( $field['edit']['placeholder'] ) ) {
				$html .= ' placeholder="'.$field['edit']['placeholder'].'"';
			}

			if ( is_numeric( $event_id ) ) {
				$value = get_post_meta( $event_id, $field['id'], true );

				$html .= ' value="'.esc_attr( $value ).'"';
			}

			if ( ! empty( $field['disabled'] ) ) {
				$html .= ' disabled';
			}

			$html .= ' />';
		}

		/**
		 * Filter the HTML for a default field input control of an event.
		 *
		 * @since 0.11
		 * @param 	string 	$html		The current HTML.
		 * @param	array	$field		The field.
		 * @param 	int		$event_id	The ID of the event.
		 */
		$html = apply_filters( 'wpt/event_editor/control/html/field='.$field['id'], $html, $field, $event_id );
		$html = apply_filters( 'wpt/event_editor/control/html', $html, $field, $event_id );

		return $html;
	}

	/**
	 * Gets the HTML for a field input label.
	 *
	 * @since 0.11
	 * @param 	array 	$field		The field.
	 * @return 	string				The HTML.
	 */
	public function get_control_label_html( $field ) {
		$html = '';

		$label = $field['title'];

		/**
		 * Filter the text for a field input label.
		 *
		 * @since 0.11
		 * @param 	string 	$label		The current label, as plain text.
		 * @param	string	$field		The unique identifier of the field.
		 */
		$label = apply_filters( 'wpt/event_editor/control/label/field='.$field['id'], $label, $field );
		$label = apply_filters( 'wpt/event_editor/control/label/', $label, $field );

		$html .= '<label for="wpt_event_editor_'.$field['id'].'">'.$label.'</label>';

		if ( ! empty( $field['edit']['description'] ) ) {
			$description = $field['edit']['description'];
			$html .= '<p class="description">'.esc_html( $description ).'</p>';
		}

		/**
		 * Filter the HTML for a field input label.
		 *
		 * @since 0.11
		 * @since 0.11.3	Removed the descriptions from the filter params.
		 * 					You can still extract this from the $field.
		 * @param 	string 	$html			The current label, as HTML.
		 * @param	array	$field			The field.
		 * @param	string	$label			The current label, as plain text.
		 */
		$label = apply_filters( 'wpt/event_editor/control/label/html/field='.$field['id'], $html, $field, $label );
		$label = apply_filters( 'wpt/event_editor/control/label/html', $html, $field, $label );

		return $html;
	}

	/**
	 * Gets the HTML for a prices input control of an event.
	 *
	 * @since 	0.11
	 * @param 	array 	$field		The field.
	 * @param 	int     $event_id   The event that is being edited.
	 * 								Leave blank (or <false>) if you want to create a new event.
	 *								Default <false>.
	 * @return 	string				The HTML.
	 */
	public function get_control_prices_html( $field, $event_id = false ) {
		$html = '';
		$html .= '<textarea id="wpt_event_editor_'.$field['id'].'" name="wpt_event_editor_'.$field['id'].'"';

		if ( ! empty( $field['disabled'] ) ) {
			$html .= ' disabled';
		}

		$html .= '>';

		if ( is_numeric( $event_id ) ) {
			$values = get_post_meta( $event_id, $field['id'], false );
			$html .= implode( "\n", $values );
		}

		$html .= '</textarea>';

		/**
		 * Filter the HTML for a prices input control of an event.
		 *
		 * @since 0.11
		 * @param 	string 	$html		The current HTML.
		 * @param	array	$field		The field.
		 * @param 	int		$event_id	The ID of the event.
		 */
		$html = apply_filters( 'wpt/event_editor/control/html/field='.$field['id'], $html, $field, $event_id );
		$html = apply_filters( 'wpt/event_editor/control/html', $html, $field, $event_id );

		return $html;
	}

	/**
	 * Gets the HTML for a tickets statis input control of an event.
	 *
	 * @sinc 0.11
	 * @param 	array 	$field		The field.
	 * @param 	int     $event_id   The event that is being edited.
	 * 								Leave blank (or <false>) if you want to create a new event.
	 *								Default <false>.
	 * @return 	string				The HTML.
	 */
	public function get_control_tickets_status_html( $field, $event_id = false ) {

		$defaults = $this->get_defaults();

		$html = '';

		$tickets_status_options = array(
			WPT_Event::tickets_status_onsale => __( 'On sale', 'wp_theatre' ),
			WPT_Event::tickets_status_soldout => __( 'Sold Out', 'wp_theatre' ),
			WPT_Event::tickets_status_cancelled => __( 'Cancelled', 'wp_theatre' ),
			WPT_Event::tickets_status_hidden => __( 'Hidden', 'wp_theatre' ),
		);
		$tickets_status_options = apply_filters( 'wpt_event_editor_tickets_status_options', $tickets_status_options );

		if ( is_numeric( $event_id ) ) {
			$value = get_post_meta( $event_id, $field['id'], true );
		}

		if ( empty( $value ) ) {
			$value = $defaults['tickets_status'];
		}

		foreach ( $tickets_status_options as $status => $name ) {
			$html .= '<label>';
			$html .= '<input type="radio" name="wpt_event_editor_'.$field['id'].'" value="'.$status.'"';
			$html .= checked( $value, $status, false );
			$html .= ' />';
			$html .= '<span>'.$name.'</span>';
			$html .= '</label><br />	';
		}

		$html .= '<label>';
		$html .= '<input type="radio" name="wpt_event_editor_'.$field['id'].'" value="'.WPT_Event::tickets_status_other.'"';
		$html .= checked(
			in_array(
				$value,
				array_keys( $tickets_status_options )
			),
			false,
			false
		);
		$html .= '/>';
		$html .= '<span>'.__( 'Other', 'wp_theatre' ).': </span>';
		$html .= '</label>';
		$html .= '<input type="text" name="wpt_event_editor_'.$field['id'].'_other"';
		if ( ! in_array( $value, array_keys( $tickets_status_options ) ) ) {
			$html .= ' value="'.esc_attr( $value ).'"';
		}
		$html .= ' />';

		/**
		 * Filter the HTML for a prices input control of an event.
		 *
		 * @since 0.11
		 * @param 	string 	$html		The current HTML.
		 * @param	string	$field		The unique identifier of the field.
		 * @param 	int		$event_id	The ID of the event.
		 */
		$html = apply_filters( 'wpt/event_editor/control/html/field='.$field['id'], $html, $field, $event_id );
		$html = apply_filters( 'wpt/event_editor/control/html', $html, $field, $event_id );

		return $html;
	}

	/**
	 * Gets the HTML for a list of existing events for a production.
	 *
	 * @since 	0.11
	 * @access 	private
	 * @param 	int 	$production_id	The production.
	 * @return 	string	The HTML.
	 */
	private function get_listing_html( $production_id ) {

		global $wp_theatre;

		$html = '';

		$args = array(
			'status' => array( 'publish', 'draft' ),
			'production' => $production_id,
		);
		$events = $wp_theatre->events->get( $args );

		if ( ! empty( $events ) ) {
			$html .= '<table>';
			for ( $i = 0;$i < count( $events );$i++ ) {
				$event = $events[ $i ];

				if ( 1 == $i % 2 ) {
					$html .= '<tr class="alternate" data-event_id="'.$event->ID.'">';
				} else {
					$html .= '<tr data-event_id="'.$event->ID.'">';
				}

				$html .= $this->get_listing_event_html( $event );

				$html .= $this->get_listing_source_html( $event->ID );

				$html .= $this->get_listing_actions_html( $event->ID );

				$html .= '</tr>';
			}
			$html .= '</table>';
		}

		return $html;
	}

	/**
	 * Gets the HTML for a single event in a listing.
	 *
	 * @since 	0.11
	 * @access 	private
	 * @param 	WPT_Event 	$event	The event.
	 * @return 	string				The HTML.
	 */
	private function get_listing_event_html( $event ) {

		$html = '';

		$args = array(
			'html' => true,
		);

		$html .= '<td>';
		$html .= $event->date( $args );
		$html .= $event->time( $args );
		$html .= '</td>';

		$html .= '<td>';
		$html .= $event->venue( $args );
		$html .= $event->city( $args );
		$html .= $event->remark( $args );
		$html .= '</td>';

		$html .= '<td>';
		$html .= $event->tickets( $args );
		$html .= '</td>';

		/**
		 * Filter the HTML for a single event in a listing.
		 *
		 * @since 	0.11
		 * @param 	string 		$html		The current HTML.
		 * @param	WPT_Event	$event		The event.
		 */
		return apply_filters( 'wpt/event_editor/listing/event/html', $html, $event );
	}

	/**
	 * Gets the HTML for the event editor form of an event.
	 *
	 * @since 	0.11
	 * @param 	int 	$production_id	The production that the event belongs to.
	 * @param 	int     $event_id       The event that is being edited.
	 * 									Leave blank (or <false>) if you want to create a new event.
	 *									Default <false>.
	 * @return 	string					The HTML.
	 */
	public function get_form_html( $production_id, $event_id = false ) {

		$html = '';

		$html .= '<table class="wpt_event_editor_event_form">';

		foreach ( $this->get_fields( $event_id ) as $field ) {
			$html .= '<tr>';
			$html .= '<th>'.$this->get_control_label_html( $field ).'</th>';
			$html .= '<td>'.$this->get_control_html( $field, $event_id ).'</td>';
			$html .= '</tr>';
		}

		$html .= '</table>';

		/**
		 * Filter the HTML for the event editor form of an event.
		 *
		 * @since 	0.11
		 * @param 	string 	$html		The current HTML.
		 * @param 	int 	$production_id	The production that the event belongs to.
		 * @param 	int     $event_id       The event that is being edited.
		 */
		return apply_filters( 'wpt/event_editor/form/html', $html, $production_id, $event_id );
	}

	/**
	 * Gets the HTML for the source of an event in a listing.
	 *
	 * @since 0.11
	 * @param 	int 	$event_id	The event.
	 * @return 	string				The HTML.
	 */
	public function get_listing_source_html($event_id) {

		$html = '';

		$source = get_post_meta( $event_id, '_wpt_source', true );

		/**
		 * Filter the source of an event in a listing
		 *
		 * @since 0.11
		 * @param 	string 	$source		The source, as plain text.
		 * @param	int		$event_id	The event.
		 */
		$source = apply_filters( 'wpt/event_editor/listing/source', $source, $event_id );

		$html .= '<td class="wpt_event_editor_source">'.$source.'</td>';

		/**
		 * Filter HTML for the source of an event in a listing
		 *
		 * @since 0.11
		 * @param 	string 	$html		The source, as HTML.
		 * @param	int		$event_id	The event.
		 * @param 	string 	$source		The source, as plain text.
		 */
		$html = apply_filters( 'wpt/event_editor/listing/source/html', $html, $event_id, $source );

		return $html;

	}

	/**
	 * Saves a new event.
	 *
	 * 1. Creates a new event that is entered in the event editor on
	 * the production admin page.
	 * 2. Saves all fields of the event.
	 *
	 * @since 	0.11
	 * @see		WPT_Event_Editor::save_field();
	 * @param 	int 	$post_id	The ID of the production.
	 * @return 	int					The ID of the production.
	 */
	public function save_event($post_id) {

		if ( ! isset( $_POST['wpt_event_editor_nonce'] ) ) {
			return $post_id; }

		if ( ! wp_verify_nonce( $_POST['wpt_event_editor_nonce'], 'wpt_event_editor' ) ) {
			return $post_id; }

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id; }

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id; }

		/*
		 * Event needs at least at start time.
		 */
		if ( empty($_POST['wpt_event_editor_event_date']) ) {
			return $post_id;
		}

		// Unhook to avoid loops
		remove_action( 'save_post', array( $this, 'save_event' ) );

		$post = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => 'publish',
		);

		if ( $event_id = wp_insert_post( $post ) ) {

			add_post_meta( $event_id, WPT_Production::post_type_name, $post_id, true );

			foreach ( $this->get_fields() as $field ) {
				$this->save_field( $field, $event_id );
			}
		}

		// Rehook
		add_action( 'save_post', array( $this, 'save_event' ) );

		return $post_id;

	}

	/**
	 * Saves a field of an event.
	 *
	 * Saves the value of a field to the database, unless the field
	 * has a 'save'-callback defined.
	 *
	 * @since	0.11
	 * @since 	0.11.1	Leave disabled fields alone.
	 * @param 	array 	$field		The field.
	 * @param 	int 	$event_id	The event.
	 * @return 	void
	 */
	public function save_field($field, $event_id) {

		if ( ! empty( $field['disabled'] ) ) {
			return;
		}

		if ( ! empty($field['save']['callback']) ) {
			call_user_func_array( $field['save']['callback'], array( $field, $event_id ) );
		} else {
			$key = 'wpt_event_editor_'.$field['id'];
			if ( ! empty($_POST[ $key ]) ) {
				$value = $_POST[ $key ];
				$this->save_value( $value, $field, $event_id );
			}
		}

	}

	/**
	 * Saves the value for a field to the database.
	 *
	 * @since 	0.11
	 * @access 	protected
	 * @param 	mixed	$value		The value.
	 * @param 	array 	$field		The field.
	 * @param 	int 	$event_id	The event.
	 * @param 	bool 	$update 	Whether of not to update an existing value (default: true).
	 * @return	int					The ID of the inserted/updated row
	 */
	protected function save_value($value, $field, $event_id, $update = true) {

		/**
		 * Filter the value of an event field, before it is saved
		 * to the database.
		 *
		 * @since 0.11
		 * @param 	mixed 	$value		The value.
		 * @param	array	$field		The field.
		 * @param	int		$event_id	The event.
		 */
		$value = apply_filters( 'wpt/event_editor/save/value/field='.$field['id'], $value, $field, $event_id );
		$value = apply_filters( 'wpt/event_editor/save/value', $value, $field, $event_id );

		if ( $update ) {
			$field_id = update_post_meta( $event_id, $field['id'], $value );
		} else {
			$field_id = add_post_meta( $event_id, $field['id'], $value );
		}

		return $field_id;
	}

	/**
	 * Saves the enddate field of an event.
	 *
	 * @since	0.11
	 * @since	0.11.1	Get the event_date from the database if the event_date field is disabled.
	 * @param 	array 	$field		The field.
	 * @param 	int 	$event_id	The event.
	 * @return 	void
	 */
	public function save_enddate($field, $event_id) {

		$defaults = $this->get_defaults();

		$key = 'wpt_event_editor_'.$field['id'];

		if ( empty($_POST[ $key ]) ) {
			return;
		}

		$value = $_POST[ $key ];

		if ( isset ( $_POST['wpt_event_editor_event_date'] ) ) {
			$event_date = strtotime( $_POST['wpt_event_editor_event_date'] );
		} else {
			$event_date = strtotime( get_post_meta( $event_id, 'event_date', true ) );
		}

		$enddate = strtotime( $value );
		if ( $enddate < $event_date ) {
			$value = date( 'Y-m-d H:i', $event_date + $defaults['duration'] );
		}

		$this->save_value( $value, $field, $event_id );

	}

	/**
	 * Saves the prices field of an event.
	 *
	 * @since	0.11
	 * @param 	array 	$field		The field.
	 * @param 	int 	$event_id	The event.
	 * @return 	void
	 */
	public function save_prices($field, $event_id) {

		delete_post_meta( $event_id, $field['id'] );

		$key = 'wpt_event_editor_'.$field['id'];

		if ( empty($_POST[ $key ]) ) {
			return;
		}

		$values = explode( "\r\n",$_POST[ $key ] );

		foreach ( $values as $value ) {
			if ( '' != $value ) {
				$this->save_value( $value, $field, $event_id, false );
			}
		}

	}

	/**
	 * Saves the tickets status field of an event.
	 *
	 * @since	0.11
	 * @param 	array 	$field		The field.
	 * @param 	int 	$event_id	The event.
	 * @return 	void
	 */
	public function save_tickets_status($field, $event_id) {

		$key = 'wpt_event_editor_'.$field['id'];

		if ( empty($_POST[ $key ]) ) {
			return;
		}

		$value = $_POST[ $key ];

		if ( $value == WPT_Event::tickets_status_other ) {
			$value = $_POST[ 'wpt_event_editor_'.$field['id'].'_other' ];
		}

		$this->save_value( $value, $field, $event_id );

	}

}