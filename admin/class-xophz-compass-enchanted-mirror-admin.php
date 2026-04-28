<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Xophz_Compass_Enchanted_Mirror
 * @subpackage Xophz_Compass_Enchanted_Mirror/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Xophz_Compass_Enchanted_Mirror
 * @subpackage Xophz_Compass_Enchanted_Mirror/admin
 * @author     Your Name <email@example.com>
 */
class Xophz_Compass_Enchanted_Mirror_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xophz_Compass_Enchanted_Mirror_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xophz_Compass_Enchanted_Mirror_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/xophz-compass-enchanted-mirror-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xophz_Compass_Enchanted_Mirror_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xophz_Compass_Enchanted_Mirror_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/xophz-compass-enchanted-mirror-admin.js', array( 'jquery' ), $this->version, false );

	}


	/**
	 * Add menu item 
	 *
	 * @since    1.0.0
	 */
	public function addToMenu(){
        Xophz_Compass::add_submenu($this->plugin_name);
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_rest_endpoints() {
		register_rest_route( 'xophz-compass/v1', '/enchanted-mirror/trends', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_trends_data' ),
			'permission_callback' => function() {
				return current_user_can( 'read' );
			},
			'args' => array(
				'keyword' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field'
				)
			)
		) );
	}

	public function get_trends_data( WP_REST_Request $request ) {
		$keyword = $request->get_param( 'keyword' );
		if ( empty( $keyword ) ) {
			return new WP_REST_Response( [ 'error' => 'Missing keyword' ], 400 );
		}

		$url = "https://trends.google.com/trends/api/explore?hl=en-US&tz=420&req=" . urlencode('{"comparisonItem":[{"keyword":"' . addslashes($keyword) . '","geo":"","time":"today 12-m"}],"category":0,"property":""}') . "&tz=420";
		
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( [ 'error' => 'Failed to fetch explore data' ], 500 );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return new WP_REST_Response( [ 'error' => 'Google Trends API returned status ' . $status_code ], $status_code );
		}

		$body = wp_remote_retrieve_body( $response );
		// Strip leading garbage ")]}',\n"
		$body = preg_replace('/^\)\]\}\',\n/', '', $body);
		$data = json_decode( $body, true );

		$widgets = isset($data['widgets']) ? $data['widgets'] : [];
		$timeseries_widget = null;
		foreach ($widgets as $widget) {
			if (isset($widget['id']) && $widget['id'] === 'TIMESERIES') {
				$timeseries_widget = $widget;
				break;
			}
		}

		if (!$timeseries_widget) {
			return new WP_REST_Response( [ 'error' => 'No timeseries found' ], 500 );
		}

		$token = isset($timeseries_widget['token']) ? $timeseries_widget['token'] : '';
		$req = json_encode($timeseries_widget['request']);
		
		$data_url = "https://trends.google.com/trends/api/widgetdata/multiline?hl=en-US&tz=420&req=" . urlencode($req) . "&token=" . $token . "&tz=420";

		$data_response = wp_remote_get( $data_url );
		if ( is_wp_error( $data_response ) ) {
			return new WP_REST_Response( [ 'error' => 'Failed to fetch multiline data' ], 500 );
		}

		$data_body = wp_remote_retrieve_body( $data_response );
		$data_body = preg_replace('/^\)\]\}\',\n/', '', $data_body);
		$trends_data = json_decode( $data_body, true );

		return new WP_REST_Response( $trends_data, 200 );
	}

}
