<?php

namespace dimadin\WIS;

class REST_API {
	use Singleton;

	/**
	 * Add main method to appropriate hook.
	 *
	 * @access public
	 */
	public function __construct() {
		// Initialize REST API extension
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ), 11 );
	}


	/**
	 * Add custom routes to REST API.
	 *
	 * @access public
	 */
	public function rest_api_init( $wp_rest_server ) {
		// Allow all origins
		$wp_rest_server->send_header( 'Access-Control-Allow-Origin', '*' );

		// Disable default CORS setting
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

		// Register route for reports
		register_rest_route( 'wis/v1', '/reports', array(
			array(
				'callback' => array( $this, 'reports' ),
				'methods'  => \WP_REST_Server::READABLE,
			),
			array(
				'callback' => array( $this, 'report' ),
				'methods'  => \WP_REST_Server::CREATABLE,
			),
		) );

		// Register route for radar maps
		register_rest_route( 'wis/v1', '/radar', array(
			'callback' => array( $this, 'radar' ),
			'methods'  => \WP_REST_Server::READABLE,
		) );

		// Register route for satellite maps
		register_rest_route( 'wis/v1', '/satellite', array(
			'callback' => array( $this, 'satellite' ),
			'methods'  => \WP_REST_Server::READABLE,
		) );

		// Register route for lightning maps
		register_rest_route( 'wis/v1', '/lightning', array(
			'callback' => array( $this, 'lightning' ),
			'methods'  => \WP_REST_Server::READABLE,
		) );

		// Register route for weather list
		register_rest_route( 'wis/v1', '/weather', array(
			'callback' => array( $this, 'weather' ),
			'methods'  => \WP_REST_Server::READABLE,
		) );

		// Register route for forecast
		register_rest_route( 'wis/v1', '/forecast', array(
			'callback' => array( $this, 'forecast' ),
			'methods'  => \WP_REST_Server::READABLE,
		) );
	}

	/**
	 * Get REST response for reports.
	 *
	 * @access public
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response $response Response for /reports endpoint.
	 */
	public function reports( \WP_REST_Request $request ) {
		// Prepare response with meta data and an array of radar maps items
		$data = array(
			'name'       => 'Последњи извештаји',
			'id'         => 'reports-list',
			'type'       => 'report',
			'reports'    => Data::reports(),
		);

		$response = new \WP_REST_Response( $data );

		return $response;
	}

	/**
	 * Create report from REST request and get response.
	 *
	 * @access public
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response $response Response for /reports endpoint.
	 */
	public function report( \WP_REST_Request $request ) {
		Reports::create( $request->get_body_params() );

		return self::reports( $request );
	}

	/**
	 * Get REST response for radar maps.
	 *
	 * @access public
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response $response Response for /radar endpoint.
	 */
	public function radar( \WP_REST_Request $request ) {
		// Prepare response with meta data and an array of radar maps items
		$data = array(
			'name'       => 'Радарске слике',
			'id'         => 'radar-maps',
			'type'       => 'thumbnails',
			'thumbnails' => Optimizer::do( Data::radar() ),
		);

		$response = new \WP_REST_Response( $data );

		return $response;
	}

	/**
	 * Get REST response for satellite maps.
	 *
	 * @access public
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response $response Response for /satellite endpoint.
	 */
	public function satellite( \WP_REST_Request $request ) {
		// Prepare response with meta data and an array of satellite maps items
		$data = array(
			'name'       => 'Сателитске слике',
			'id'         => 'satellite-maps',
			'type'       => 'thumbnails',
			'thumbnails' => Optimizer::do( Data::satellite() ),
		);

		$response = new \WP_REST_Response( $data );

		return $response;
	}

	/**
	 * Get REST response for lightning maps.
	 *
	 * @access public
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response $response Response for /satellite endpoint.
	 */
	public function lightning( \WP_REST_Request $request ) {
		// Prepare response with meta data and an array of lightning maps items
		$data = array(
			'name'       => 'Слике муња',
			'id'         => 'lightning-maps',
			'type'       => 'thumbnails',
			'thumbnails' => Optimizer::do( Data::lightning() ),
		);

		$response = new \WP_REST_Response( $data );

		return $response;
	}

	/**
	 * Get REST response for weather table.
	 *
	 * @access public
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response $response Response for /weather endpoint.
	 */
	public function weather( \WP_REST_Request $request ) {
		// Prepare response with meta data and an array of lightning maps items
		$data = array(
			'name'       => 'Тренутне температуре и време',
			'id'         => 'weather-table',
			'type'       => 'weather',
			'cities'     => Optimizer::do( Data::weather() ),
		);

		$response = new \WP_REST_Response( $data );

		return $response;
	}

	/**
	 * Get REST response for forecast.
	 *
	 * @access public
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response $response Response for /forecast endpoint.
	 */
	public function forecast( \WP_REST_Request $request ) {
		// Prepare response with meta data and an array of lightning maps items
		$data = array(
			'name'       => 'Прогноза',
			'id'         => 'forecast-info',
			'type'       => 'forecast',
			'forecasts'  => Data::forecast(),
		);

		$response = new \WP_REST_Response( $data );

		return $response;
	}
}
