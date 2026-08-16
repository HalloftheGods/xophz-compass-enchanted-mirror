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
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_trends_data' ),
			'permission_callback' => function() {
				return current_user_can( 'read' );
			},
			'args'                => array(
				'keyword' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		register_rest_route( 'xophz-compass/v1', '/enchanted-mirror/metrics', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_domain_metrics' ),
			'permission_callback' => function() {
				return current_user_can( 'read' );
			},
			'args'                => array(
				'domain' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		register_rest_route( 'xophz-compass/v1', '/enchanted-mirror/site-metrics', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_site_metrics' ),
			'permission_callback' => function() {
				return current_user_can( 'read' );
			},
		) );
	}

	/**
	 * Fetch domain intelligence metrics
	 */
	public function get_domain_metrics( WP_REST_Request $request ) {
		$domain = $request->get_param( 'domain' );
		if ( empty( $domain ) ) {
			return new WP_REST_Response( array( 'error' => 'Missing domain parameter' ), 400 );
		}

		$metrics = $this->fetch_domain_metrics( $domain );
		return new WP_REST_Response( $metrics, 200 );
	}

	/**
	 * Fetch primary site domain metrics
	 */
	public function get_site_metrics( WP_REST_Request $request ) {
		$site_url = get_site_url();
		$host = wp_parse_url( $site_url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			$host = 'localhost';
		}

		$metrics = $this->fetch_domain_metrics( $host );
		return new WP_REST_Response( $metrics, 200 );
	}

	/**
	 * Internal helper to resolve and cache domain metrics
	 */
	private function fetch_domain_metrics( $raw_domain ) {
		$clean_domain = strtolower( trim( $raw_domain ) );
		$clean_domain = preg_replace( '#^https?://#i', '', $clean_domain );
		$clean_domain = preg_replace( '#/.*$#', '', $clean_domain );

		$cache_key = 'xophz_em_m_' . md5( $clean_domain );
		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}

		$result = array(
			'domain'          => $clean_domain,
			'globalRank'      => null,
			'countryRank'     => null,
			'categoryRank'    => null,
			'monthlyVisits'   => null,
			'bounceRate'      => null,
			'pagesPerVisit'   => null,
			'timeOnSite'      => null,
			'trafficSources'  => null,
			'pageRank'        => null,
			'source'          => 'none',
			'cached'          => false,
		);

		// Similarweb unauthenticated endpoint
		$sw_url = 'https://data.similarweb.com/api/v1/data?domain=' . rawurlencode( $clean_domain );
		$response = wp_remote_get( $sw_url, array(
			'timeout' => 12,
			'headers' => array(
				'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
				'Accept'          => 'application/json, text/plain, */*',
				'Accept-Language' => 'en-US,en;q=0.9',
			),
		) );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( is_array( $data ) ) {
				$result['source'] = 'similarweb';
				$result['globalRank'] = isset( $data['GlobalRank']['Rank'] ) && $data['GlobalRank']['Rank'] > 0 ? (int) $data['GlobalRank']['Rank'] : null;
				
				if ( ! empty( $data['CountryRank'] ) && ! empty( $data['CountryRank']['Rank'] ) ) {
					$result['countryRank'] = array(
						'rank'        => (int) $data['CountryRank']['Rank'],
						'countryCode' => isset( $data['CountryRank']['CountryCode'] ) ? (string) $data['CountryRank']['CountryCode'] : '',
					);
				}

				if ( ! empty( $data['CategoryRank'] ) && ! empty( $data['CategoryRank']['Rank'] ) ) {
					$result['categoryRank'] = array(
						'rank'     => (int) $data['CategoryRank']['Rank'],
						'category' => isset( $data['CategoryRank']['Category'] ) ? (string) $data['CategoryRank']['Category'] : '',
					);
				}

				if ( ! empty( $data['Engagements'] ) ) {
					$eng = $data['Engagements'];
					$result['monthlyVisits'] = isset( $eng['Visits'] ) ? (int) $eng['Visits'] : null;
					$result['bounceRate']    = isset( $eng['BounceRate'] ) ? round( (float) $eng['BounceRate'] * 100, 2 ) : null;
					$result['pagesPerVisit'] = isset( $eng['PagePerSubdomain'] ) ? round( (float) $eng['PagePerSubdomain'], 2 ) : ( isset( $eng['PagesPerVisit'] ) ? round( (float) $eng['PagesPerVisit'], 2 ) : null );
					$result['timeOnSite']    = isset( $eng['TimeOnSite'] ) ? round( (float) $eng['TimeOnSite'] ) : null;
				}

				if ( empty( $result['monthlyVisits'] ) && ! empty( $data['EstimatedMonthlyVisits'] ) && is_array( $data['EstimatedMonthlyVisits'] ) ) {
					$latest_visits = end( $data['EstimatedMonthlyVisits'] );
					if ( is_numeric( $latest_visits ) ) {
						$result['monthlyVisits'] = (int) $latest_visits;
					}
				}

				if ( ! empty( $data['TrafficSources'] ) && is_array( $data['TrafficSources'] ) ) {
					$ts = $data['TrafficSources'];
					$result['trafficSources'] = array(
						'direct'    => isset( $ts['Direct'] ) ? round( (float) $ts['Direct'] * 100, 1 ) : 0,
						'search'    => isset( $ts['Search'] ) ? round( (float) $ts['Search'] * 100, 1 ) : 0,
						'social'    => isset( $ts['Social'] ) ? round( (float) $ts['Social'] * 100, 1 ) : 0,
						'referrals' => isset( $ts['Referrals'] ) ? round( (float) $ts['Referrals'] * 100, 1 ) : 0,
						'mail'      => isset( $ts['Mail'] ) ? round( (float) $ts['Mail'] * 100, 1 ) : 0,
						'paid'      => isset( $ts['Paid Referrals'] ) ? round( (float) $ts['Paid Referrals'] * 100, 1 ) : 0,
					);
				}
			}
		}

		// Fallback / Supplementary Open PageRank lookup
		$opr_url = 'https://openpagerank.com/api/v1.0/getPageRank?domains%5B0%5D=' . rawurlencode( $clean_domain );
		$opr_response = wp_remote_get( $opr_url, array(
			'timeout' => 8,
			'headers' => array(
				'User-Agent' => 'Project-Compass-EnchantedMirror/1.0',
			),
		) );

		if ( ! is_wp_error( $opr_response ) && wp_remote_retrieve_response_code( $opr_response ) === 200 ) {
			$opr_body = wp_remote_retrieve_body( $opr_response );
			$opr_data = json_decode( $opr_body, true );
			if ( ! empty( $opr_data['response'][0]['page_rank_decimal'] ) ) {
				$result['pageRank'] = (float) $opr_data['response'][0]['page_rank_decimal'];
				if ( empty( $result['globalRank'] ) && ! empty( $opr_data['response'][0]['rank'] ) ) {
					$result['globalRank'] = (int) $opr_data['response'][0]['rank'];
				}
			}
		}

		// Cache valid results for 24 hours
		set_transient( $cache_key, $result, DAY_IN_SECONDS );

		return $result;
	}

	public function get_trends_data( WP_REST_Request $request ) {
		$keyword = sanitize_text_field( $request->get_param( 'keyword' ) );
		if ( empty( $keyword ) ) {
			return new WP_REST_Response( array( 'error' => 'Missing keyword' ), 400 );
		}

		$clean_keyword = strtolower( trim( $keyword ) );
		$cache_key = 'xophz_em_tr_' . md5( $clean_keyword );
		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return new WP_REST_Response( $cached, 200 );
		}

		$url = "https://trends.google.com/trends/api/explore?hl=en-US&tz=420&req=" . urlencode( '{"comparisonItem":[{"keyword":"' . addslashes( $clean_keyword ) . '","geo":"","time":"today 12-m"}],"category":0,"property":""}' ) . "&tz=420";
		
		$response = wp_remote_get( $url, array(
			'timeout' => 8,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
			),
		) );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$body = preg_replace( '/^\)\]\}\',\n/', '', $body );
			$data = json_decode( $body, true );

			$widgets = isset( $data['widgets'] ) ? $data['widgets'] : array();
			$timeseries_widget = null;
			foreach ( $widgets as $widget ) {
				if ( isset( $widget['id'] ) && $widget['id'] === 'TIMESERIES' ) {
					$timeseries_widget = $widget;
					break;
				}
			}

			if ( $timeseries_widget ) {
				$token = isset( $timeseries_widget['token'] ) ? $timeseries_widget['token'] : '';
				$req = json_encode( $timeseries_widget['request'] );
				$data_url = "https://trends.google.com/trends/api/widgetdata/multiline?hl=en-US&tz=420&req=" . urlencode( $req ) . "&token=" . $token . "&tz=420";

				$data_response = wp_remote_get( $data_url, array(
					'timeout' => 8,
					'headers' => array(
						'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
					),
				) );

				if ( ! is_wp_error( $data_response ) && wp_remote_retrieve_response_code( $data_response ) === 200 ) {
					$data_body = wp_remote_retrieve_body( $data_response );
					$data_body = preg_replace( '/^\)\]\}\',\n/', '', $data_body );
					$trends_data = json_decode( $data_body, true );

					if ( is_array( $trends_data ) && ! empty( $trends_data['default']['timelineData'] ) ) {
						set_transient( $cache_key, $trends_data, DAY_IN_SECONDS );
						return new WP_REST_Response( $trends_data, 200 );
					}
				}
			}
		}

		// Graceful Fallback: Generate normalized trend points when Google Trends blocks (429/403)
		$months = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
		$timeline_data = array();
		$seed = crc32( $clean_keyword );
		$base_val = 50 + ( abs( $seed ) % 35 );

		foreach ( $months as $idx => $m ) {
			$variance = ( ( abs( $seed + $idx * 17 ) % 31 ) - 15 );
			$val = max( 10, min( 100, $base_val + $variance ) );
			$timeline_data[] = array(
				'time'          => (string) ( time() - ( 12 - $idx ) * 30 * DAY_IN_SECONDS ),
				'formattedTime' => $m . ' 2026',
				'value'         => array( $val ),
			);
		}

		$fallback_trends = array(
			'default' => array(
				'timelineData' => $timeline_data,
			),
			'fallback' => true,
		);

		set_transient( $cache_key, $fallback_trends, HOUR_IN_SECONDS * 2 );
		return new WP_REST_Response( $fallback_trends, 200 );
	}

}
