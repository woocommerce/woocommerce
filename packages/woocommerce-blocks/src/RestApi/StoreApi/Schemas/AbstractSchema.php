<?php
/**
 * Abstract Schema.
 *
 * Rest API schema class.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractBlock class.
 *
 * @since 2.5.0
 */
abstract class AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'Schema';

	/**
	 * Returns the full item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->title,
			'type'       => 'object',
			'properties' => $this->get_properties(),
		);
	}

	/**
	 * Returns schema properties.
	 *
	 * @return array
	 */
	abstract protected function get_properties();

	/**
	 * Force all schema properties to be readonly.
	 *
	 * @param array $properties Schema.
	 * @return array Updated schema.
	 */
	protected function force_schema_readonly( $properties ) {
		return array_map(
			function( $property ) {
				$property['readonly'] = true;
				if ( isset( $property['items']['properties'] ) ) {
					$property['items']['properties'] = $this->force_schema_readonly( $property['items']['properties'] );
				}
				return $property;
			},
			$properties
		);
	}

	/**
	 * Prepares HTML based content, such as post titles and content, for the API response.
	 *
	 * The wptexturize, convert_chars, and trim functions are also used in the `the_title` filter.
	 * The function wp_kses_post removes disallowed HTML tags.
	 *
	 * @param string|array $response Data to format.
	 * @return string|array Formatted data.
	 */
	protected function prepare_html_response( $response ) {
		if ( is_array( $response ) ) {
			return array_map( [ $this, 'prepare_html_response' ], $response );
		}
		return is_scalar( $response ) ? wp_kses_post( trim( convert_chars( wptexturize( $response ) ) ) ) : $response;
	}
}
