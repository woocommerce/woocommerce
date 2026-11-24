<?php
/**
 * Product term write helpers.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Saves the `WP_Term` of the product term create and update abilities, which extend ActionableAbility.
 */
trait ProductTermWriteTrait {

	/**
	 * The term fields that change() checked and save() writes.
	 *
	 * @var array{name?: string, slug?: string, parent?: int, description?: string}
	 */
	private array $term_args = array();

	/**
	 * Resource being changed.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'product_term';
	}

	/**
	 * The term ID, or zero for a term that is not created yet.
	 *
	 * @param object $subject Term.
	 * @return int
	 */
	protected function get_object_id( object $subject ): int {
		return $subject instanceof \WP_Term ? $subject->term_id : 0;
	}

	/**
	 * Keep the checked term fields for save(), and set them on the term in memory, so validators see the change.
	 *
	 * @param \WP_Term                                                                $term Term.
	 * @param array{name?: string, slug?: string, parent?: int, description?: string} $args Checked term fields, as wp_insert_term() and wp_update_term() take them.
	 */
	private function stage_term_args( \WP_Term $term, array $args ): void {
		$this->term_args = $args;
		foreach ( $args as $field => $value ) {
			$term->$field = $value;
		}
	}

	/**
	 * Create or update the term, then give the object its stored values, so syncs get the term ID and the final slug.
	 *
	 * @param \WP_Term $subject Term.
	 * @return null|\WP_Error
	 */
	protected function save( $subject ) {
		$code = $subject->term_id > 0 ? 'woocommerce_ability_product_term_update_failed' : 'woocommerce_ability_product_term_create_failed';
		$args = $this->term_args;
		if ( 0 === $subject->term_id ) {
			$name = $args['name'] ?? '';
			unset( $args['name'] );
			$result = wp_insert_term( wp_slash( $name ), $subject->taxonomy, wp_slash( $args ) );
		} elseif ( ! empty( $args ) ) {
			$result = wp_update_term( $subject->term_id, $subject->taxonomy, wp_slash( $args ) );
		} else {
			// The call only sent extension fields, which the syncs write.
			$result = array( 'term_id' => $subject->term_id );
		}
		if ( is_wp_error( $result ) ) {
			return new \WP_Error( $code, $result->get_error_message(), array( 'status' => 500 ) );
		}

		$stored = get_term( $result['term_id'], $subject->taxonomy );
		if ( ! $stored instanceof \WP_Term ) {
			return new \WP_Error( $code, __( 'The term was saved but could not be read back.', 'woocommerce' ), array( 'status' => 500 ) );
		}
		foreach ( get_object_vars( $stored ) as $field => $value ) {
			$subject->$field = $value;
		}
		return null;
	}

	/**
	 * Return the saved term.
	 *
	 * @param \WP_Term $subject Saved term.
	 * @return array|\WP_Error
	 */
	protected function prepare_response( $subject ) {
		$output = self::format_term( $subject );
		return is_wp_error( $output ) ? $output : array( 'term' => $output );
	}
}
