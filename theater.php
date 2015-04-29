<?php
/*
	
	Plugin Name: Theater
	Plugin URI: http://wordpress.org/plugins/theatre/
	Description: Turn your Wordpress website into a theater website.
	Author: Jeroen Schmit, Slim & Dapper
	Version: 0.10.11
	Author URI: http://slimndap.com/
	Text Domain: wp_theatre
	Domain Path: /lang

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
$wpt_version = '0.10.11';

class WP_Theatre {
	function __construct() {

		// Set version
		global $wpt_version;
		$this->wpt_version = $wpt_version;
	
		// Includes
		$this->includes();
	
		// Setup
		$this->setup = new WPT_Setup();
		$this->admin = new WPT_Admin();
		$this->events = new WPT_Events();
		$this->productions = new WPT_Productions();
		$this->order = new WPT_Order();
		$this->feeds = new WPT_Feeds();
		$this->transient = new WPT_Transient();
		$this->listing_page = new WPT_Listing_Page();
		$this->calendar = new WPT_Calendar();
		$this->filter = new WPT_Filter();
		$this->cart = new WPT_Cart();
		if (is_admin()) {
		} else {
			$this->frontend = new WPT_Frontend();
		}
		
		// Options
		$this->wpt_language_options = get_option( 'wpt_language' );
		$this->wpt_listing_page_options = get_option( 'wpt_listing_page' );
		$this->wpt_style_options = get_option( 'wpt_style' );
		$this->wpt_tickets_options = get_option( 'wpt_tickets' );
		$this->deprecated_options();
		
		// Hooks
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this->setup, 'plugin_action_links' ) );

		// Plugin (de)activation hooks
		register_activation_hook( __FILE__, array($this, 'activate' ));		
		register_deactivation_hook( __FILE__, array($this, 'deactivate' ));	
		
		// Plugin update hooks
		if ($wpt_version!=get_option('wpt_version')) {
			update_option('wpt_version', $wpt_version);
			add_action('admin_init',array($this,'update'));
		}
		
		// Hook wpt_loaded action.
		add_action ('plugins_loaded', array($this,'wpt_loaded') );
	}
		
	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @access public
	 * @return void
	 */
	function includes() {
		require_once(dirname(__FILE__) . '/functions/wpt_listing.php');
		require_once(dirname(__FILE__) . '/functions/wpt_production.php');
		require_once(dirname(__FILE__) . '/functions/wpt_productions.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event.php');
		require_once(dirname(__FILE__) . '/functions/wpt_events.php');
		require_once(dirname(__FILE__) . '/functions/wpt_setup.php');
		require_once(dirname(__FILE__) . '/functions/wpt_season.php');
		require_once(dirname(__FILE__) . '/functions/wpt_widget.php');
		require_once(dirname(__FILE__) . '/functions/wpt_admin.php');
		require_once(dirname(__FILE__) . '/functions/wpt_order.php');
		require_once(dirname(__FILE__) . '/functions/wpt_feeds.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_transient.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_listing_page.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_calendar.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_filter.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_importer.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_cart.php');	
		if (is_admin()) {
		} else {
			require_once(dirname(__FILE__) . '/functions/wpt_frontend.php');
		}
		require_once(dirname(__FILE__) . '/integrations/wordpress-seo.php');
		require_once(dirname(__FILE__) . '/integrations/jetpack-featured-content.php');
		
	}
	
	public function seasons($PostClass = false) {
		return $this->get_seasons($PostClass);
	}		

	function activate() {
		wp_schedule_event( time(), 'wpt_schedule', 'wpt_cron');

		//defines the post types so the rules can be flushed.
		$this->setup->init();

		//and flush the rules.
		flush_rewrite_rules();		
	}
	
	function deactivate() {
		wp_clear_scheduled_hook('wpt_cron');
		delete_post_meta_by_key($this->order->meta_key);
		flush_rewrite_rules();		
	}

	function update() {
		$this->activate();
	}


 
 	/**
 	 * Fires the `wpt_loaded` action.
 	 * 
 	 * Use this to safely load plugins that depend on Theater.
 	 *
 	 * @access public
 	 * @return void
 	 */
 	function wpt_loaded() {
		do_action('wpt_loaded');
	}

	/*
	 * Private functions.
	 */
	 
	private function get_seasons($PostClass=false) {
		$args = array(
			'post_type'=>WPT_Season::post_type_name,
			'posts_per_page' => -1,
			'orderby' => 'title'
		);
		
		$posts = get_posts($args);
			
		$seasons = array();
		for ($i=0;$i<count($posts);$i++) {
			$seasons[] = new WPT_Season($posts[$i], $PostClass);
		}
		return $seasons;
	}


	/**
	 * Deprecated functions. 
	 *
	 * @deprecated 0.4.
	 */

	function compile_events($args=array()) {
		return $this->events->html($args);
	}
	
	private function get_events($PostClass = false) {
		return $this->events();
	}

	function render_events($args=array()) {
		echo $this->compile_events($args);
	}

	private function get_productions($PostClass = false) {
		return $this->productions();
	}

	function render_productions($args=array()) {
		return $this->productions->html_listing();
	}

 	/*
 	 * For backward compatibility purposes
 	 * Use old theatre options for style options and tickets options.
 	 * As of v0.8 style options and tickets options are stored seperately.
 	 */
 	
 	function deprecated_options() {
	 	if (empty($this->wpt_style_options)) {
		 	$this->wpt_style_options = get_option( 'wp_theatre' );
	 	}
	 	if (empty($this->wpt_tickets_options)) {
		 	$this->wpt_tickets_options = get_option( 'wp_theatre' );
	 	}
 	}
}

/**
 * Init WP_Theatre class
 */
$wp_theatre = new WP_Theatre();


?>
