<?php
/**
 * Woo AI Attribute Suggestion Request Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\ProductDataSuggestion;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute Suggestion Request class.
 */
class Product_Data_Suggestion_Request {
	const REQUESTED_DATA_NAME        = 'name';
	const REQUESTED_DATA_DESCRIPTION = 'description';
	const REQUESTED_DATA_TAGS        = 'tags';
	const REQUESTED_DATA_CATEGORIES  = 'categories';

	/**
	 * Name of the product data that is being requested (e.g. title, description, tags, etc.).
	 *
	 * @var string
	 */
	public $requested_data;

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
	 * @param string    $requested_data The key for the product data that suggestions are being requested for.
	 * @param string    $name           The name of the product.
	 * @param string    $description    The description of the product.
	 * @param string[]  $tags           The product tags.
	 * @param integer[] $categories     Category IDs of the product as an associative array.
	 * @param array[]   $attributes     Other attributes of the product as an associative array.
	 *
	 * @throws Product_Data_Suggestion_Exception If the requested attribute is invalid.
	 */
	public function __construct( string $requested_data, string $name, string $description, array $tags = array(), array $categories = array(), array $attributes = array() ) {
		$this->validate_requested_data( $requested_data );

		$this->requested_data = $requested_data;
		$this->name           = $name;
		$this->description    = $description;
		$this->tags           = $tags;
		$this->categories     = $categories;
		$this->attributes     = $attributes;
	}

	/**
	 * Validates the requested attribute.
	 *
	 * @param string $requested_data The attribute that suggestions are being requested for.
	 *
	 * @return void
	 *
	 * @throws Product_Data_Suggestion_Exception If the requested data is invalid.
	 */
	private function validate_requested_data( string $requested_data ): void {
		$valid_requested_data_keys = array(
			self::REQUESTED_DATA_NAME,
			self::REQUESTED_DATA_DESCRIPTION,
			self::REQUESTED_DATA_TAGS,
			self::REQUESTED_DATA_CATEGORIES,
		);

		if ( ! in_array( $requested_data, $valid_requested_data_keys, true ) ) {
			throw new Product_Data_Suggestion_Exception( 'Invalid requested data.', 400 );
		}
	}
}
