<?php
/**
 * WP Postman API.
 *
 * @package WP-API-Libraries\WP Postman API
 */
/*
* Plugin Name: WP Postman API.
* Plugin URI: https://github.com/wp-api-libraries/wp-postman-api
* Description: Perform API requests to Postman in WordPress.
* Author: WP API Libraries
* Version: 1.0.0
* Author URI: https://wp-api-libraries.com
* GitHub Plugin URI: https://github.com/wp-api-libraries/wp-postman-api
* GitHub Branch: master
* Text Domain: wp-postman-api
*/

/* Exit if accessed directly. */
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'PostmanAPI' ) ) {

	/**
	 * PostmanAPI
	 */
	class PostmanAPI {

		/**
		 * HTTP request arguments.
		 *
		 * (default value: array())
		 *
		 * @var array
		 * @access protected
		 */
		protected $args = array();
		/**
		 * api_key
		 *
		 * @var mixed
		 * @access protected
		 * @static
		 */
		protected static $api_key;

		/**
		 * BaseAPI Endpoint
		 *
		 * @var string
		 * @access protected
		 */
		protected $base_uri = 'https://api.getpostman.com';

		/**
		 * Route being called.
		 *
		 * @var string
		 */
		protected $route = '';
		/**
		 * __construct function.
		 *
		 * @access public
		 * @param mixed $api_key
		 * @return void
		 */
		function __construct( $api_key = null ) {
			static::$api_key = $api_key;


		}

	/**
		 * Prepares API request.
		 *
		 * @param  string $route   API route to make the call to.
		 * @param  array  $args    Arguments to pass into the API call.
		 * @param  array  $method  HTTP Method to use for request.
		 * @return self            Returns an instance of itself so it can be chained to the fetch method.
		 */
		protected function build_request( $route, $args = array(), $method = 'GET' ) {
			// Headers get added first.
			$this->set_headers();
			// Add Method and Route.
			$this->args['method'] = $method;
			$this->route          = $route;
			// Generate query string for GET requests.
			if ( 'GET' === $method ) {
				$this->route = add_query_arg( array_filter( $args ), $route );
			} // Add to body for all other requests. (Json encode if content-type is json).
			elseif ( 'application/json' === $this->args['headers']['Content-Type'] ) {
				$this->args['body'] = wp_json_encode( $args );
			} else {
				$this->args['body'] = $args;
			}
			return $this;
		}
		/**
		 * Fetch the request from the API.
		 *
		 * @access private
		 * @return array|WP_Error Request results or WP_Error on request failure.
		 */
		protected function fetch() {

			// Make the request.
			$response = wp_remote_request( $this->base_uri . $this->route, $this->args );



			// Retrieve Status code & body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );
			$this->set_links( $response );
			$this->clear();
			// Return WP_Error if request is not successful.
			if ( ! $this->is_status_ok( $code ) ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: %d', 'wp-taxjar-api' ), $code ), $body );
			}
			return $body;
		}
		/**
		 * set_links function.
		 *
		 * @access protected
		 * @param mixed $response
		 * @return void
		 */
		protected function set_links( $response ) {
			$this->links = array();
			// Get links from response header.
			$links = wp_remote_retrieve_header( $response, 'link' );
			// Parse the string into a convenient array.
			$links = explode( ',', $links );
			if ( ! empty( $links ) ) {
				foreach ( $links as $link ) {
					$tmp = explode( ';', $link );
					$res = preg_match( '~<(.*?)>~', $tmp[0], $match );
					if ( ! empty( $res ) ) {
						// Some string magic to set array key. Changes 'rel="next"' => 'next'.
						$key                 = str_replace( array( 'rel=', '"' ), '', trim( $tmp[1] ) );
						$this->links[ $key ] = $match[1];
					}
				}
			}
		}
		/**
		 * Set request headers.
		 */
		protected function set_headers() {
			// Set request headers.
			$this->args['headers'] = array(
				'Content-Type'  => 'application/json',
				'X-Api-Key' => static::$api_key,
			);
		}
		/**
		 * Clear query data.
		 */
		protected function clear() {
			$this->args = array();
		}
		/**
		 * Check if HTTP status code is a success.
		 *
		 * @param  int $code HTTP status code.
		 * @return boolean       True if status is within valid range.
		 */
		protected function is_status_ok( $code ) {
			return ( 200 <= $code && 300 > $code );
		}

		// Collections.

		public function get_collections( $args = array() ) {
			return $this->build_request( '/collections', $args )->fetch();
		}

	}
}
