<?php
/**
 * WC_Data subject class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\InMemorySubject;

defined( 'ABSPATH' ) || exit;

/**
 * Saves a WC_Data object an in-memory write changed.
 *
 * @since 11.3.0
 */
final class WCDataSubject implements InMemorySubject {

	/**
	 * Object.
	 *
	 * @var \WC_Data
	 */
	private $data;

	/**
	 * Wrap a WC_Data object.
	 *
	 * @param \WC_Data $data Object.
	 */
	public function __construct( \WC_Data $data ) {
		$this->data = $data;
	}

	/**
	 * The object's data with each meta entry as `id`, `key` and `value`.
	 *
	 * @return array
	 */
	public function snapshot(): array {
		$snapshot              = $this->data->get_data();
		$snapshot['meta_data'] = array_map(
			static function ( $meta ) {
				return array(
					'id'    => $meta->id,
					'key'   => $meta->key,
					'value' => $meta->value,
				);
			},
			$this->data->get_meta_data()
		);
		return $snapshot;
	}

	/**
	 * Save the object.
	 *
	 * @return true|\WP_Error
	 */
	public function save() {
		if ( $this->data->save() > 0 ) {
			return true;
		}
		return new \WP_Error( 'woocommerce_in_memory_write_save_failed', __( 'The change could not be saved.', 'woocommerce' ), array( 'status' => 500 ) );
	}
}
