<?php
/**
 * WooCommerce API
 *
 * Handles parsing XML request bodies and generating XML responses
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API_XML_Handler implements WC_API_Handler {

	/**
	 * Get the content type for the response
	 *
	 * @since 2.1
	 * @return string
	 */
	public function get_content_type() {

		return 'application/xml; charset=' . get_option( 'blog_charset' );
	}

	/**
	 * Parse the raw request body entity
	 *
	 * @since 2.1
	 * @param string $body the raw request body
	 * @return array
	 */
	public function parse_body( $data ) {

		// TODO: implement simpleXML parsing
	}

	/**
	 * Generate an XML response given an array of data
	 *
	 * @since 2.1
	 * @param array $data the response data
	 * @return string
	 */
	public function generate_response( $data ) {

		// TODO: implement array to XML
	}

}
