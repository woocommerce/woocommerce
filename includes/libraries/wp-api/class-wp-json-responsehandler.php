<?php

interface WP_JSON_ResponseHandler {
	/**
	 * Send a HTTP status code
	 *
	 * @param int $code HTTP status
	 */
	public function send_status( $code );

	/**
	 * Send a HTTP header
	 *
	 * @param string $key Header key
	 * @param string $value Header value
	 * @param boolean $replace Should we replace the existing header?
	 */
	public function header( $key, $value, $replace = true );

	/**
	 * Send a Link header
	 *
	 * @link http://tools.ietf.org/html/rfc5988
	 * @link http://www.iana.org/assignments/link-relations/link-relations.xml
	 *
	 * @param string $rel Link relation. Either a registered type, or an absolute URL
	 * @param string $link Target IRI for the link
	 * @param array $other Other parameters to send, as an assocative array
	 */
	public function link_header( $rel, $link, $other = array() );

	/**
	 * Send navigation-related headers for post collections
	 *
	 * @param WP_Query $query
	 */
	public function query_navigation_headers( $query );

	/**
	 * Retrieve the raw request entity (body)
	 *
	 * @return string
	 */
	public function get_raw_data();
}