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

		// COLLECTIONS.

		/**
		 * [get_collections description]
		 * @param  array  $args [description]
		 * @return [type]       [description]
		 */
		public function get_collections( $args = array() ) {
			return $this->build_request( '/collections', $args )->fetch();
		}

		/**
		 * [get_collection description]
		 * @param  [type] $collection_uid [description]
		 * @param  array  $args           [description]
		 * @return [type]                 [description]
		 */
		public function get_collection( $collection_uid, $args = array() ) {
			return $this->build_request( '/collections/' . $collection_uid, $args )->fetch();
		}

		/**
		 * Create Collection (https://schema.getpostman.com/json/collection/v2.0.0/docs/index.html)
		 * @param  array  $args [description]
		 * @return [type]       [description]
		 */
		public function create_collection( $args = array() ) {
			return $this->build_request( '/collections', $args, 'POST' )->fetch();
		}

		/**
		 * [update_collection description]
		 * @param  [type] $collection_uid [description]
		 * @param  array  $args           [description]
		 * @return [type]                 [description]
		 */
		public function update_collection( $collection_uid, $args = array() ) {
			return $this->build_request( '/collections/' . $collection_uid, $args, 'PUT' )->fetch();
		}

		/**
		 * [delete_collection description]
		 * @param  [type] $collection_uid [description]
		 * @return [type]                 [description]
		 */
		public function delete_collection( $collection_uid ) {
			return $this->build_request( '/collections/' . $collection_uid, null, 'DELETE' )->fetch();
		}

		// ENVIRONMENTS.

		public function get_environments( $args = array() ) {
			return $this->build_request( '/environments', $args )->fetch();
		}

		public function get_environment( $environment_uid, $args = array() ) {
			return $this->build_request( '/environments/' . $environment_uid, $args )->fetch();
		}

				/**
				 * [create_workspace description]
				 * @param  array  $args [description]
				 * @return [type]       [description]
				 */
				public function create_environment( $args = array() ) {
					return $this->build_request( '/environments', $args, 'POST' )->fetch();
				}

				/**
				 * [update_workspace description]
				 * @param  [type] $workspace_id [description]
				 * @param  array  $args         [description]
				 * @return [type]               [description]
				 */
				public function update_environment( $environment_uid, $args = array() ) {
					return $this->build_request( '/environments/'. $environment_uid, $args, 'PUT' )->fetch();
				}

				/**
				 * [delete_workspace description]
				 * @param  [type] $workspace_id [description]
				 * @return [type]               [description]
				 */
				public function delete_environment( $environment_uid ) {
					return $this->build_request( '/environments/'. $environment_uid, null, 'DELETE' )->fetch();
				}

		// MOCKS.

		// MONITORS.

		/**
		 * [get_monitors description]
		 * @param  array  $args [description]
		 * @return [type]       [description]
		 */
		public function get_monitors( $args = array() ) {
			return $this->build_request( '/monitors', $args )->fetch();
		}

		// WORKSPACES.

		/**
		 * [get_workspaces description]
		 * @param  array  $args [description]
		 * @return [type]       [description]
		 */
		public function get_workspaces( $args = array() ) {
			return $this->build_request( '/workspaces', $args )->fetch();
		}

		/**
		 * [get_workspace description]
		 * @param  [type] $workspace_id [description]
		 * @param  array  $args         [description]
		 * @return [type]               [description]
		 */
		public function get_workspace( $workspace_id, $args = array() ) {
			return $this->build_request( '/workspaces/' . $workspace_id, $args )->fetch();
		}

		/**
		 * [create_workspace description]
		 * @param  array  $args [description]
		 * @return [type]       [description]
		 */
		public function create_workspace( $args = array() ) {
			return $this->build_request( '/workspaces/', $args, 'POST' )->fetch();
		}

		/**
		 * [update_workspace description]
		 * @param  [type] $workspace_id [description]
		 * @param  array  $args         [description]
		 * @return [type]               [description]
		 */
		public function update_workspace( $workspace_id, $args = array() ) {
			return $this->build_request( '/workspaces/'. $workspace_id, $args, 'PUT' )->fetch();
		}

		/**
		 * [delete_workspace description]
		 * @param  [type] $workspace_id [description]
		 * @return [type]               [description]
		 */
		public function delete_workspace( $workspace_id ) {
			return $this->build_request( '/workspaces/'. $workspace_id, null, 'DELETE' )->fetch();
		}

		// USER.

		/**
		 * Get Me (Myself).
		 * @return object Results from Me.
		 */
		public function get_me() {
			return $this->build_request( '/me' )->fetch();
		}

		// IMPORT.

		/**
		 * [import_external_api description]
		 * @param  [type] $import_type [description]
		 * @param  array  $args        [description]
		 * @return [type]              [description]
		 */
		public function import_external_api( $import_type, $args = array() ) {
			return $this->build_request( '/import/' . $import_type, $args, 'POST' )->fetch();
		}

		/**
		 * [import_exported_data description]
		 * @param  array  $args [description]
		 * @return [type]       [description]
		 */
		public function import_exported_data( $args = array() ) {
			return $this->build_request( '/import/exported', $args, 'POST' )->fetch();
		}

		// API.

	}
}
