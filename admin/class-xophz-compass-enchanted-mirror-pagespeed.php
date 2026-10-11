<?php
/**
 * GET /xophz-compass/v1/enchanted-mirror/pagespeed?domain=<host[/path]>&strategy=mobile|desktop
 *
 * Calls the Google PageSpeed Insights API from the server. When a key is configured it is read
 * through the `google` WP connector and sent in the x-goog-api-key header, so it never reaches the
 * browser, a URL or a log. Without a key the call runs keyless on Google's shared quota.
 *
 * Response: 200 { "domain": string, "strategy": "mobile"|"desktop", "score": int 0-100 }
 * Errors:   400 rest_invalid_param, 502 enchanted_mirror_pagespeed_unreachable |
 *           enchanted_mirror_pagespeed_failed. A failure is reported, never replaced by a score.
 *
 * @package    Xophz_Compass_Enchanted_Mirror
 * @subpackage Xophz_Compass_Enchanted_Mirror/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xophz_Compass_Enchanted_Mirror_PageSpeed {

	const CONNECTOR_ID = 'google';
	const API_URL      = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
	const STRATEGIES   = array( 'mobile', 'desktop' );
	const CACHE_TTL    = 21600;
	const HOST_PATTERN = '/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i';

	public function register_routes() {
		register_rest_route( 'xophz-compass/v1', '/enchanted-mirror/pagespeed', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_pagespeed' ),
			'permission_callback' => function() {
				return current_user_can( 'read' );
			},
			'args'                => array(
				'domain'   => array(
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => array( __CLASS__, 'is_domain' ),
				),
				'strategy' => array(
					'required' => true,
					'type'     => 'string',
					'enum'     => self::STRATEGIES,
				),
			),
		) );
	}

	/**
	 * Runtime guard: a public host name, optionally followed by a path.
	 *
	 * @param mixed $value Raw request value.
	 */
	public static function is_domain( $value ): bool {
		$is_string = is_string( $value ) && '' !== $value && strlen( $value ) <= 2048;
		$host      = $is_string ? wp_parse_url( 'https://' . $value, PHP_URL_HOST ) : null;
		return is_string( $host ) && 1 === preg_match( self::HOST_PATTERN, $host );
	}

	public function get_pagespeed( WP_REST_Request $request ) {
		$domain    = strtolower( trim( (string) $request->get_param( 'domain' ) ) );
		$strategy  = (string) $request->get_param( 'strategy' );
		$cache_key = 'xophz_em_ps_' . md5( $strategy . '|' . $domain );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return rest_ensure_response( $cached );
		}

		$url = add_query_arg( array(
			'url'      => rawurlencode( 'https://' . $domain ),
			'strategy' => $strategy,
			'category' => 'performance',
		), self::API_URL );

		$response = wp_remote_get( $url, array(
			'timeout' => 60,
			'headers' => self::auth_headers(),
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'enchanted_mirror_pagespeed_unreachable', 'Could not reach Google PageSpeed Insights.', array( 'status' => 502 ) );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$score  = is_array( $data ) ? ( $data['lighthouseResult']['categories']['performance']['score'] ?? null ) : null;
		if ( 200 !== $status || ! is_numeric( $score ) ) {
			return new WP_Error(
				'enchanted_mirror_pagespeed_failed',
				sprintf( 'PageSpeed Insights returned no score (HTTP %d%s).', $status, self::google_reason( $data ) ),
				array( 'status' => 502 )
			);
		}

		$result = array(
			'domain'   => $domain,
			'strategy' => $strategy,
			'score'    => (int) round( (float) $score * 100 ),
		);
		set_transient( $cache_key, $result, self::CACHE_TTL );
		return rest_ensure_response( $result );
	}

	private static function auth_headers(): array {
		$key = class_exists( 'Xophz_Compass_Connector_Key' ) ? Xophz_Compass_Connector_Key::get( self::CONNECTOR_ID ) : '';
		return '' === $key ? array() : array( 'x-goog-api-key' => $key );
	}

	/** Google's enumerated error status (e.g. RESOURCE_EXHAUSTED), never its free-text message. */
	private static function google_reason( $data ): string {
		$reason = is_array( $data ) && is_string( $data['error']['status'] ?? null ) ? $data['error']['status'] : '';
		$reason = preg_replace( '/[^A-Z_]/', '', $reason );
		return '' === $reason ? '' : ', ' . $reason;
	}
}
