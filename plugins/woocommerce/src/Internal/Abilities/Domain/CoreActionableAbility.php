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
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return $this->definition()::get_resource();
	}

	/**
	 * Load the object to change.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Data|\WP_Error
	 */
	public function load( array $input ) {
		return $this->definition()::load( $input );
	}

	/**
	 * Change the object in memory.
	 *
	 * @param \WC_Data $subject Object to change.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error
	 */
	public function change( $subject, array $input ) {
		return $this->definition()::change( $subject, $input );
	}

	/**
	 * Save the changed object.
	 *
	 * @param \WC_Data $subject Changed object.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		return $this->definition()::save( $subject );
	}

	/**
	 * The ability output for the saved object.
	 *
	 * @param \WC_Data $subject Saved object.
	 * @return array
	 */
	public function prepare_response( $subject ) {
		return $this->definition()::prepare_response( $subject );
	}

	/**
	 * Run the definition the way it runs with the feature off.
	 *
	 * @param array $input Ability input.
	 * @return mixed
	 */
	protected function execute_unstaged( array $input ) {
		return $this->definition()::execute( $input );
	}

	/**
	 * Ask the definition whether the object takes staged changes.
	 *
	 * @param \WC_Data $subject Loaded object.
	 * @return bool
	 */
	protected function supports_staged_changes( $subject ): bool {
		return $this->definition()::supports_staged_changes( $subject );
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
