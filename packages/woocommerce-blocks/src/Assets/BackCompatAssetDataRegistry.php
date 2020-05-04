<?php
/**
 * Backwards Compatibility file for plugins using wcSettings in prior versions
 *
 * @package WooCommerce/Blocks
 * @since 2.5.0
 */

namespace Automattic\WooCommerce\Blocks\Assets;

/**
 * Backwards Compatibility class for plugins using wcSettings in prior versions.
 *
 * Note: This will be removed at some point.
 *
 * @since 2.5.0
 */
class BackCompatAssetDataRegistry extends AssetDataRegistry {
	/**
	 * Overrides parent method.
	 *
	 * @see AssetDataRegistry::enqueue_asset_data
	 */
	public function enqueue_asset_data() {
		$this->initialize_core_data();
		$this->execute_lazy_data();
		/**
		 * Back-compat filter, developers, use 'woocommerce_shared_settings'
		 * filter, not this one.
		 *
		 * @deprecated 2.5.0
		 */
		$data = apply_filters(
			'woocommerce_components_settings',
			$this->get()
		);

		$data = rawurlencode( wp_json_encode( $data ) );
		// for back compat with wc-admin (or other plugins) that expects
		// wcSettings to be always available.
		// @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/932.
		echo '<script>';
		echo "var wcSettings = wcSettings || JSON.parse( decodeURIComponent( '" . esc_js( $data ) . "' ) );";
		echo '</script>';
	}

	/**
	 * Override parent method.
	 *
	 * @see AssetDataRegistry::get_core_data
	 *
	 * @return array  An array of data to output for enqueued script.
	 */
	protected function get_core_data() {
		global $wp_locale;
		$core_data = parent::get_core_data();
		return array_merge(
			$core_data,
			[
				'siteLocale'    => $core_data['locale']['siteLocale'],
				'stockStatuses' => $core_data['orderStatuses'],
				'dataEndpoints' => [],
				'l10n'          => [
					'userLocale'    => $core_data['locale']['userLocale'],
					'weekdaysShort' => $core_data['locale']['weekdaysShort'],
				],
				'currency'      => array_merge(
					$core_data['currency'],
					[
						'position'           => $core_data['currency']['symbolPosition'],
						'decimal_separator'  => $core_data['currency']['decimalSeparator'],
						'thousand_separator' => $core_data['currency']['thousandSeparator'],
						'price_format'       => $core_data['currency']['priceFormat'],
					]
				),
			]
		);
	}
}
