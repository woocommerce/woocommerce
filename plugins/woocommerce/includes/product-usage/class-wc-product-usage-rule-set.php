<?php
/**
 * WooCommerce Product Usage Rule.
 *
 * This class defines the DTO for passing product feature restriction rules to WooCommerce extensions.
 *
 * @package WooCommerce\Admin\ProductUsage
 */

declare( strict_types = 1 );


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Product_Usage_Rule_Set.
 */
class WC_Product_Usage_Rule_Set {
	/**
	 * Set of product feature restriction rules.
	 *
	 * @var array|null $rules
	 */
	protected $rules;

	/**
	 * Constructor
	 *
	 * @param array $rules product feature restriction rules.
	 */
	public function __construct( $rules ) {
		$this->rules = $rules;
	}

	/**
	 * Retrieve the value of a rule by name
	 *
	 * @param string $rule_name name of the rule to retrieve value.
	 * @return mixed|null
	 */
	public function get_rule( string $rule_name ) {
		if ( ! isset( $this->rules[ $rule_name ] ) ) {
			return null;
		}

		return $this->rules[ $rule_name ];
	}
}
