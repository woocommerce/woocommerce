<?php
/**
 * Core actionable ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;

defined( 'ABSPATH' ) || exit;

/**
 * Runs Core's write abilities as actionable abilities, with the steps of their definitions.
 */
final class CoreActionableAbility extends ActionableAbility {

	/**
	 * Definition classes keyed by ability name.
	 *
	 * @var array<string, class-string<AbstractChangeAbility>>
	 */
	public const DEFINITIONS = array(
		'woocommerce/order-update-status' => OrderUpdateStatus::class,
		'woocommerce/product-create'      => ProductCreate::class,
		'woocommerce/product-update'      => ProductUpdate::class,
	);

	/**
	 * Object type that the ability changes.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return $this->definition()::get_object_type();
	}

	/**
	 * Load the object to change.
	 *
	 * @param array $input Ability input.
	 * @return object|\WP_Error
	 */
	public function load( array $input ) {
		return $this->definition()::load( $input );
	}

	/**
	 * Change the object in memory.
	 *
	 * @param object $subject Object to change.
	 * @param array  $input   Ability input.
	 * @return null|\WP_Error
	 */
	public function change( $subject, array $input ) {
		return $this->definition()::change( $subject, $input );
	}

	/**
	 * Save the changed object.
	 *
	 * @param object $subject Changed object.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		return $this->definition()::save( $subject );
	}

	/**
	 * The ability output for the saved object.
	 *
	 * @param object $subject Saved object.
	 * @return array
	 */
	public function prepare_response( $subject ) {
		return $this->definition()::prepare_response( $subject );
	}

	/**
	 * Definition class of the ability.
	 *
	 * @return class-string<AbstractChangeAbility>
	 */
	private function definition(): string {
		return self::DEFINITIONS[ $this->get_name() ];
	}
}
