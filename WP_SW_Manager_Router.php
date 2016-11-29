<?php

namespace Mozilla;

/**
 * WP_SW_Manager_Router class.
 */
class WP_SW_Manager_Router {

	const TRIGGER = '_wpswmanager';

	const ACTION = 'wpswmgr_serve';

	/**
	 * instance
	 *
	 * @var mixed
	 * @access private
	 * @static
	 */
	private static $instance;

	/**
	 * get_router function.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function get_router() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * routes
	 *
	 * @var mixed
	 * @access private
	 */
	private $routes;

	/**
	 * __construct function.
	 *
	 * @access private
	 * @return void
	 */
	private function __construct() {
		$this->routes = array();
		add_action( 'wp_ajax_'. self::ACTION, array( $this, 'parse_request' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'parse_request' ) );
	}

	/**
	 * add_route function.
	 *
	 * @access public
	 * @param mixed $desired_url
	 * @param mixed $callback
	 * @return void
	 */
	public function add_route( $desired_url, $callback ) {
		$arguments = array_slice( func_get_args(), 2 );
		$route = $this->route( $desired_url );
		$this->routes[ $route ] = array( $callback, $arguments );
		return $this->route_url( $route );
	}

	/**
	 * route_url function.
	 *
	 * @access public
	 * @param mixed $route_or_desired_url
	 * @return void
	 */
	public function route_url( $route_or_desired_url ) {
		$route = $this->route( $route_or_desired_url );
		return admin_url( 'admin-ajax.php', 'relative' ) .
			   '?action=' . self::ACTION . '&' . self::TRIGGER . '=' . urlencode( $route );
	}

	/**
	 * parse_request function.
	 *
	 * @access public
	 * @return void
	 */
	public function parse_request() {
		$route_trigger = $this->identify_trigger( $_GET );
		if ( $route_trigger ) {
			list($handler, $args) = $this->routes[ $route_trigger ];
			call_user_func_array( $handler, $args );
		}
	}

	/**
	 * route function.
	 *
	 * @access private
	 * @param mixed $desired_url
	 * @return void
	 */
	private function route( $desired_url ) {
		$components = parse_url( $desired_url );
		$route = $components['path'];
		return $route;
	}

	/**
	 * identify_trigger function.
	 *
	 * @access private
	 * @param mixed $query_args
	 * @return void
	 */
	private function identify_trigger( $query_args ) {
		$handler = null;
		if ( array_key_exists( self::TRIGGER, $query_args ) ) {
			$handler = $query_args[ self::TRIGGER ];
		}
		return $handler;
	}
}
