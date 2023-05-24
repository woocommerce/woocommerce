<?php
/**
 * Woo AI Attribute Suggestion Request Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\AttributeSuggestion;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute Suggestion Request class.
 */
class Attribute_Suggestion_Request {
	const REQUESTED_ATTRIBUTE_NAME        = 'name';
	const REQUESTED_ATTRIBUTE_DESCRIPTION = 'description';
	const REQUESTED_ATTRIBUTE_TAGS        = 'tags';
	const REQUESTED_ATTRIBUTE_CATEGORIES  = 'categories';

	/**
	 * Name of the attribute that is being requested (e.g. title, description, tags, etc.).
	 *
	 * @var string
	 */
	public $requested_attribute;

	/**
	 * The name of the product.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The description of the product.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The product tags.
	 *
	 * @var string[]
	 */
	public $tags;

	/**
	 * Categories of the product as an associative array.
	 *
	 * @var string[]
	 */
	public $categories;

	/**
	 * Other attributes of the product as an associative array.
	 *
	 * @var array[] Associative array of attributes. Each attribute is an associative array with the following keys:
	 *              - name: The name of the attribute.
	 *              - value: The value of the attribute.
	 */
	public $attributes;

	/**
	 * Constructor
	 *
	 * @param string    $requested_attribute The attribute that suggestions are being requested for.
	 * @param string    $name                The name of the product.
	 * @param string    $description         The description of the product.
	 * @param string[]  $tags                The product tags.
	 * @param integer[] $categories          Category IDs of the product as an associative array.
	 * @param array[]   $attributes          Other attributes of the product as an associative array.
	 *
	 * @throws Exception If the requested attribute is invalid.
	 */
	public function __construct( string $requested_attribute, string $name, string $description, array $tags = array(), array $categories = array(), array $attributes = array() ) {
		$this->validate_requested_attribute( $requested_attribute );

		$this->requested_attribute = $requested_attribute;
		$this->name                = $name;
		$this->description         = $description;
		$this->tags                = $tags;
		$this->categories          = $categories;
		$this->attributes          = $attributes;
	}

	/**
	 * Validates the requested attribute.
	 *
	 * @param string $requested_attribute The attribute that suggestions are being requested for.
	 *
	 * @return void
	 *
	 * @throws Exception If the requested attribute is invalid.
	 */
	private function validate_requested_attribute( string $requested_attribute ): void {
		$valid_requested_attributes = array(
			self::REQUESTED_ATTRIBUTE_NAME,
			self::REQUESTED_ATTRIBUTE_DESCRIPTION,
			self::REQUESTED_ATTRIBUTE_TAGS,
			self::REQUESTED_ATTRIBUTE_CATEGORIES,
		);

		if ( ! in_array( $requested_attribute, $valid_requested_attributes, true ) ) {
			throw new Exception( 'Invalid requested attribute.' );
		}
	}
}
