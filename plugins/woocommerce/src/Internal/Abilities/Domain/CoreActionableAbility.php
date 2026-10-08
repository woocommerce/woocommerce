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
	 * Definition classes keyed by ability name. A field change extends
	 * AbstractChangeAbility. An action describes its dry run with describe().
	 *
	 * @var array<string, class-string>
	 */
	public const DEFINITIONS = array(
		'woocommerce/order-add-note'      => OrderAddNote::class,
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
	 * The ability call that undoes the change.
	 *
	 * @param object $subject Object before the change.
	 * @param array  $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public function undo( $subject, array $input ): ?array {
		return $this->definition()::undo( $subject, $input );
	}

	/**
	 * What the save does beyond the object.
	 *
	 * @param object $subject Object before the change.
	 * @param array  $input   Ability input.
	 * @return string[]
	 */
	public function side_effects( $subject, array $input ): array {
		return $this->definition()::side_effects( $subject, $input );
	}

	/**
	 * The names of the values whose input fields have another name.
	 *
	 * @return array<string, string>
	 */
	public function get_value_names(): array {
		$definition = $this->definition();
		return method_exists( $definition, 'get_value_names' ) ? $definition::get_value_names() : array();
	}

	/**
	 * The dry run: the steps of a field change without the save, or the description of an action.
	 *
	 * @param array $input Valid ability input.
	 * @return array|\WP_Error
	 */
	protected function do_dry_run( array $input ) {
		$definition = $this->definition();
		return is_a( $definition, AbstractChangeAbility::class, true ) ? parent::do_dry_run( $input ) : $definition::describe( $input );
	}

	/**
	 * Definition class of the ability.
	 *
	 * @return class-string
	 */
	private function definition(): string {
		return self::DEFINITIONS[ $this->get_name() ];
	}
}
