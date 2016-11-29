<?php

namespace Mozilla;

/**
 * Aggregates content generators for a service worker.
 */
class WP_SW_Manager_Combinator {

	/**
	 * url
	 *
	 * @var mixed
	 * @access private
	 */
	private $url;

	/**
	 * writers
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access private
	 */
	private $writers = array();

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $url
	 * @return void
	 */
	public function __construct( $url ) {
		$this->url = $url;
	}

	/**
	 * Registers a callback to be called when generating the service worker
	 * to write a portion of the service worker functionality.
	 *
	 * @api
	 * @param callable $content_generator A callable object in charge of **write,
	 * not return** a portion of a service worker.
	 */
	public function add_content( $content_generator ) {
		$this->writers[] = $content_generator;
	}

	/**
	 * write_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function write_content() {
		foreach ( $this->writers as $content_generator ) {
			echo ';';
			call_user_func( $content_generator );
		}
	}

	/**
	 * get_url function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * has_content function.
	 *
	 * @access public
	 * @return void
	 */
	public function has_content() {
		return count( $this->writers ) != 0;
	}
}
