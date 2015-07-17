<?php

class WPT_Event_Template extends WPT_Template {

	/**
	 * Gets the default template for events.
	 * 
	 * @since	0.12.1
	 * @access 	protected
	 * @return 	string		The default template.
	 */
	protected function get_default() {
		$default = '{{thumbnail|permalink}} {{title|permalink}} {{remark}} {{datetime}} {{location}} {{tickets}}';
		
		/**
		 * Filter the default template for events.
		 * 
		 * @param 	string	$default	The default template.
		 */
		$default = apply_filters('wpt_event_template_default', $default);
		$default = apply_filters('wpt/event/template/default', $default);
		
		return $default;
	}

	/**
	 * Gets the value for a field from an event.
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @param 	string	$field		The field.
	 * @param 	array 	$args		Arguments for the field (optional).
	 * 								Eg. the 'thumbnail'-field can have an optional 'size' argument:
	 *								{{thumbnail('full')}}
	 * @param 	array 	$filters 	Array of WPT_Template_Filter objects.
	 *								Filters to apply to the value (optional).
	 *
	 * @return 	string				The value.
	 */
	protected function get_field_value($field, $args = array(), $filters = array()) {

		$value = '';

		$value_args = array(
			'html' => true,
			'filters' => $filters,
		);

		switch ( $field ) {
			case 'datetime':
			case 'duration':
			case 'venue':
			case 'location':
			case 'remark':
			case 'title':
				$value = $this->object->{$field}($value_args);
				break;
			case 'thumbnail':
				if ( ! empty($args[0]) ) {
					$replacement_args['size'] = $args[0];
				}
			case 'categories':
			case 'content':
			case 'excerpt':
				$value = $this->object->production()->{$field}($value_args);
				break;
			case 'startdate':
			case 'date':
				$value = $this->object->startdate_html( $filters );
				break;
			case 'starttime':
			case 'time':
				$value = $this->object->starttime_html( $filters );
				break;
			case 'enddate':
			case 'endtime':
			case 'prices':
			case 'tickets':
			case 'tickets_url':
				$value = $this->object->{$field.'_html'}($filters);
				break;
			default:
				$value = $this->object->custom( $field,$value_args );
		}

		/**
		 * Filter the value for an event field.
		 *
		 * @since	0.12.1
		 * @param	string		$value		The value.
		 * @param	string		$field		The field.
		 * @param	array		$args		Arguments for the field (optional).
		 * 									Eg. the 'thumbnail'-field can have an optional 'size' argument:
		 *									{{thumbnail('full')}}
		 * @param	array		$filters	Array of WPT_Template_Filter objects.
		 *									Filters to apply to the value (optional).
		 * @param	WPT_Event	$event		The event.
		 */
		$value = apply_filters( 'wpt/event/template/field/value', $value, $field, $args, $filters );

		return $value;
	}
}