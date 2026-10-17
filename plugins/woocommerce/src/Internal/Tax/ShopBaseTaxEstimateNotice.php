<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\Enums\DefaultCustomerAddress;
use Automattic\WooCommerce\Enums\TaxBasedOn;
use WC_Cache_Helper;
use WC_Tax;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the read-only tax estimate shown on the Tax settings screen: what an un-addressed shopper
 * is quoted given the Default customer location setting, the per-tax-class rates at the store
 * address, and warnings when no rate matches. This is display only; it never changes how tax is
 * calculated (see BaseLocationTaxEstimate for the lookup behavior).
 *
 * @since 11.3.0
 */
class ShopBaseTaxEstimateNotice {

	/**
	 * Build the estimate help text and warning for the Tax settings screen.
	 *
	 * @return array{estimate: string, warning: string} HTML strings, empty when there is nothing to show.
	 */
	public function get_notice(): array {
		$empty = array(
			'estimate' => '',
			'warning'  => '',
		);

		if ( ! $this->is_shown() ) {
			return $empty;
		}

		$location = get_option( 'woocommerce_default_customer_address', DefaultCustomerAddress::BASE );

		if ( DefaultCustomerAddress::BASE === $location ) {
			return $this->shop_base_notice();
		}

		if ( DefaultCustomerAddress::NO_DEFAULT === $location ) {
			return array(
				'estimate' => $this->wrap(
					sprintf(
						/* translators: %s: link to the General settings. */
						__( 'No tax is shown until the customer enters their address. Change this with Default customer location in %s.', 'woocommerce' ),
						$this->general_link()
					)
				),
				'warning'  => '',
			);
		}

		if ( in_array( $location, array( DefaultCustomerAddress::GEOLOCATION, DefaultCustomerAddress::GEOLOCATION_AJAX ), true ) ) {
			return array(
				'estimate' => $this->wrap(
					sprintf(
						/* translators: %s: link to the General settings. */
						__( 'Before a customer enters their address, tax is estimated from their detected country and state. Change this with Default customer location in %s.', 'woocommerce' ),
						$this->general_link()
					)
				),
				'warning'  => '',
			);
		}

		return $empty;
	}

	/**
	 * Whether the estimate should appear at all.
	 *
	 * @return bool
	 */
	private function is_shown(): bool {
		if ( ! wc_tax_enabled() ) {
			return false;
		}

		$automated_taxes_enabled = in_array( get_option( 'wc_connect_taxes_enabled' ), array( 'yes', true ), true );

		/**
		 * Filters whether the shop-base tax estimate help text and warning show on the Tax settings screen.
		 *
		 * Automated tax extensions that calculate tax themselves can return false to hide the manual-rate estimate.
		 *
		 * @since 11.3.0
		 * @param bool $show Whether to show the estimate. Defaults to false when automated taxes are enabled.
		 */
		return (bool) apply_filters( 'woocommerce_show_shop_base_tax_estimate', ! $automated_taxes_enabled );
	}

	/**
	 * Build the notice for the "Shop base address" default customer location.
	 *
	 * @return array{estimate: string, warning: string}
	 */
	private function shop_base_notice(): array {
		// WC()->countries is not available in every context this view loads in (e.g. during install).
		$countries = WC()->countries;
		if ( ! $countries || ! class_exists( 'WC_Tax' ) ) {
			return array(
				'estimate' => '',
				'warning'  => '',
			);
		}

		$country        = $countries->get_base_country();
		$state          = $countries->get_base_state();
		$postcode       = $countries->get_base_postcode();
		$city           = $countries->get_base_city();
		$has_address    = '' !== $postcode || '' !== $city;
		$tax_based_on   = TaxBasedOn::BASE === get_option( 'woocommerce_tax_based_on', TaxBasedOn::SHIPPING );
		$base_args      = array(
			'country'  => $country,
			'state'    => $state,
			'postcode' => $postcode,
			'city'     => $city,
		);
		$standard_rates = WC_Tax::find_rates( array_merge( $base_args, array( 'tax_class' => '' ) ) );

		// Lead line: what is taxed, and where.
		if ( $has_address ) {
			$address_html = '<strong>' . esc_html( implode( ', ', array_filter( array( $city, $postcode ) ) ) ) . '</strong>';
			$lead         = $tax_based_on
				? sprintf( /* translators: %s: store base address (bold). */ __( 'All orders are taxed at your store base address: %s.', 'woocommerce' ), $address_html )
				: sprintf( /* translators: %s: store base address (bold). */ __( 'Until a customer enters their address, tax is estimated at your store base address: %s.', 'woocommerce' ), $address_html );
		} else {
			$lead = $tax_based_on
				? __( 'All orders are taxed at your store\'s country and state only.', 'woocommerce' )
				: __( 'Until a customer enters their address, tax is estimated at your store\'s country and state only.', 'woocommerce' );
		}

		$lines = array( $lead, $this->rate_summary( $base_args ) );

		// Footer: how to change it, and a link to the rates. Base tax mode is not driven by the default location.
		if ( $tax_based_on ) {
			$lines[] = $this->standard_rates_link();
		} else {
			$lines[] = sprintf(
				/* translators: 1: link to the General settings, 2: link to the standard tax rates. */
				__( 'Change this with Default customer location in %1$s. %2$s', 'woocommerce' ),
				$this->general_link(),
				$this->standard_rates_link()
			);
		}

		return array(
			'estimate' => $this->wrap( implode( '<br/>', array_filter( $lines ) ) ),
			'warning'  => $this->warning( $standard_rates, $country, $state, $has_address, $tax_based_on ),
		);
	}

	/**
	 * Summarize the estimated rate for each tax class at the store address.
	 *
	 * @param array $base_args find_rates() args for the store address (without tax_class).
	 * @return string
	 */
	private function rate_summary( array $base_args ): string {
		$classes = array( '' => __( 'Standard rate', 'woocommerce' ) );
		foreach ( WC_Tax::get_tax_classes() as $class_name ) {
			$classes[ sanitize_title( $class_name ) ] = $class_name;
		}

		$parts = array();
		foreach ( $classes as $slug => $label ) {
			$rates = WC_Tax::find_rates( array_merge( $base_args, array( 'tax_class' => $slug ) ) );

			if ( empty( $rates ) ) {
				$parts[] = esc_html( $label ) . ': &ndash;';
				continue;
			}

			// Effective rate on a 100 base, so compound rates are reflected correctly.
			$pct     = array_sum( WC_Tax::calc_tax( 100, $rates, false ) );
			$parts[] = esc_html( $label ) . ': ' . esc_html( rtrim( rtrim( sprintf( '%.4f', $pct ), '0' ), '.' ) . '%' );
		}

		return sprintf(
			/* translators: %s: list of estimated tax rates per tax class. */
			__( 'Estimated rates &mdash; %s', 'woocommerce' ),
			implode( ' &middot; ', $parts )
		);
	}

	/**
	 * Build the no-rate warning, scoped to the store's standard-rate situation.
	 *
	 * @param array  $standard_rates Rates matched at the store address for the standard class.
	 * @param string $country        Store base country.
	 * @param string $state          Store base state.
	 * @param bool   $has_address    Whether the store has a postcode or city.
	 * @param bool   $tax_based_on   Whether tax is calculated on the shop base address.
	 * @return string
	 */
	private function warning( array $standard_rates, string $country, string $state, bool $has_address, bool $tax_based_on ): string {
		if ( ! empty( $standard_rates ) ) {
			return '';
		}

		if ( ! $has_address && $this->state_has_scoped_standard_rates( $country, $state ) ) {
			return sprintf(
				/* translators: 1: link to the General settings, 2: link to the standard tax rates. */
				__( 'Your store has postcode- or city-scoped standard rates, but no store postcode or city is set, so they cannot be estimated. Add them in %1$s, or see %2$s.', 'woocommerce' ),
				$this->general_link(),
				$this->standard_rates_link()
			);
		}

		if ( $tax_based_on ) {
			return sprintf(
				/* translators: %s: link to the standard tax rates. */
				__( 'No standard tax rate matches your store address, so standard-rate products are quoted no tax. Add a matching rate in %s.', 'woocommerce' ),
				$this->standard_rates_link()
			);
		}

		return sprintf(
			/* translators: %s: link to the standard tax rates. */
			__( 'No standard tax rate matches your store address, so standard-rate products are quoted no tax for a customer who has not entered an address. Add a matching rate in %s.', 'woocommerce' ),
			$this->standard_rates_link()
		);
	}

	/**
	 * Whether the store's state has any postcode- or city-scoped standard rate.
	 *
	 * @param string $country Store base country.
	 * @param string $state   Store base state.
	 * @return bool
	 */
	private function state_has_scoped_standard_rates( string $country, string $state ): bool {
		if ( '' === $country ) {
			return false;
		}

		global $wpdb;

		$cache_key = WC_Cache_Helper::get_cache_prefix( 'taxes' ) . 'shop_base_scoped_' . md5( $country . '|' . $state );
		$cached    = wp_cache_get( $cache_key, 'taxes' );
		if ( false !== $cached ) {
			return (bool) $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- No core API returns a rate's location scope; cached above.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rate_locations locations
				INNER JOIN {$wpdb->prefix}woocommerce_tax_rates rates ON locations.tax_rate_id = rates.tax_rate_id
				WHERE rates.tax_rate_country = %s AND rates.tax_rate_state = %s AND rates.tax_rate_class = '' AND locations.location_type IN ( 'postcode', 'city' )",
				$country,
				$state
			)
		);

		$result = absint( $count ) > 0;
		wp_cache_set( $cache_key, $result ? 1 : 0, 'taxes' );

		return $result;
	}

	/**
	 * Wrap the estimate text in a targetable span.
	 *
	 * @param string $html Estimate HTML.
	 * @return string
	 */
	private function wrap( string $html ): string {
		return '' === $html ? '' : '<span class="wc-tax-base-location-estimate">' . $html . '</span>';
	}

	/**
	 * Link to the General settings screen.
	 *
	 * @return string
	 */
	private function general_link(): string {
		return '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">' . esc_html__( 'Settings → General', 'woocommerce' ) . '</a>';
	}

	/**
	 * Link to the standard tax rates screen.
	 *
	 * @return string
	 */
	private function standard_rates_link(): string {
		return '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax&section=standard' ) ) . '">' . esc_html__( 'View standard rates', 'woocommerce' ) . '</a>';
	}
}
