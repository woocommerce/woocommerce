<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Suggestions\Incentives;

/**
 * Abstract class for payment extension suggestion incentive provider classes.
 */
abstract class Incentive {
	const PREFIX = 'woocommerce_admin_pes_incentive_';

	/**
	 * The user meta name for storing dismissed incentives.
	 *
	 * @var string
	 */
	protected string $dismissed_meta_name = self::PREFIX . 'dismissed';

	/**
	 * The suggestion ID this incentive provider is for.
	 *
	 * @var string
	 */
	protected string $suggestion_id;

	/**
	 * Constructor.
	 *
	 * @param string $suggestion_id The suggestion ID this incentive provider is for.
	 */
	public function __construct( string $suggestion_id ) {
		$this->suggestion_id = $suggestion_id;
	}

	/**
	 * Get the details of all the incentives.
	 *
	 * The incentives are filtered based on the country code, incentive type, if provided, and their visibility.
	 *
	 * @param string $country_code   The business location country code to get incentives for.
	 * @param string $incentive_type Optional. The type of incentive to check for.
	 *
	 * @return array The incentives list with details for each incentive.
	 */
	public function get_all( string $country_code, string $incentive_type = '' ): array {
		$incentives = array_filter(
			$this->get_incentives( $country_code ),
			fn( $incentive ) => $this->validate_incentive( $incentive )
		);

		if ( ! empty( $incentive_type ) ) {
			$incentives = array_filter(
				$incentives,
				function ( $incentive ) use ( $incentive_type ) {
					return $incentive['type'] === $incentive_type;
				}
			);
		}

		return array_values( $incentives );
	}

	/**
	 * Get an incentive by promo ID.
	 *
	 * The incentives are filtered based on the country code, incentive type, if provided, and their visibility.
	 *
	 * @param string $promo_id       The incentive promo ID.
	 * @param string $country_code   The business location country code to get incentives for.
	 * @param string $incentive_type Optional. The type of incentive to search for.
	 *
	 * @return ?array The incentive details. Returns null if there is no incentive available.
	 */
	public function get_by_promo_id( string $promo_id, string $country_code, string $incentive_type = '' ): ?array {
		$incentives = array_filter(
			$this->get_all( $country_code, $incentive_type ),
			function ( $incentive ) use ( $promo_id ) {
				return $incentive['promo_id'] === $promo_id;
			}
		);

		if ( empty( $incentives ) ) {
			return null;
		}

		// Get the first found incentive, in the unlikely case there are multiple incentives with the same promo ID.
		return reset( $incentives );
	}

	/**
	 * Get an incentive by ID.
	 *
	 * The incentives are filtered based on the country code, incentive type, if provided, and their visibility.
	 *
	 * @param string $incentive_id The incentive ID.
	 * @param string $country_code The business location country code to get incentives for.
	 *
	 * @return ?array The incentive details. Returns null if there is no incentive available.
	 */
	public function get_by_id( string $incentive_id, string $country_code ): ?array {
		$incentives = array_filter(
			$this->get_all( $country_code ),
			function ( $incentive ) use ( $incentive_id ) {
				return $incentive['id'] === $incentive_id;
			}
		);

		if ( empty( $incentives ) ) {
			return null;
		}

		// Get the first found incentive, in the unlikely case there are multiple incentives with the same ID.
		return reset( $incentives );
	}

	/**
	 * Check if an incentive should be visible.
	 *
	 * @param string $id                          The incentive ID to check for visibility.
	 * @param string $country_code                The business location country code to get incentives for.
	 * @param bool   $skip_extension_active_check Whether to skip the check for the extension plugin being active.
	 *
	 * @return boolean Whether the incentive should be visible.
	 */
	public function is_visible( string $id, string $country_code, bool $skip_extension_active_check = false ): bool {
		// The extension plugin must not be active, unless we are asked to skip the check.
		if ( ! $skip_extension_active_check && $this->is_extension_active() ) {
			return false;
		}

		// The current WP user must have the required capabilities.
		if ( ! $this->user_has_caps() ) {
			return false;
		}

		// An incentive must be available.
		if ( empty( $this->get_by_id( $id, $country_code ) ) ) {
			return false;
		}

		// If the incentive has been dismissed in all contexts, don't show it.
		// We don't know the full list of contexts, so we can't assume anything beyond `all`.
		if ( $this->is_dismissed( $id, 'all' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Dismiss an incentive.
	 *
	 * @param string $id        The incentive ID to dismiss.
	 * @param string $context   Optional. The context ID in which the incentive is dismissed.
	 *                          This can be used to dismiss the same incentive in different contexts.
	 *                          If no context ID is provided, the incentive will be dismissed for all contexts.
	 * @param ?int   $timestamp Optional The timestamp when the incentive was dismissed.
	 *                          Defaults to the current time.
	 *
	 * @return bool True if the incentive was not previously dismissed and now it is.
	 *              False if the incentive was already dismissed, or we failed to persist the dismissal data.
	 */
	public function dismiss( string $id, string $context = 'all', ?int $timestamp = null ): bool {
		// If it is already dismissed, don't dismiss it again.
		if ( $this->is_dismissed( $id, $context ) ) {
			return false;
		}

		$all_dismissed_incentives = $this->get_all_dismissed_incentives();
		if ( empty( $all_dismissed_incentives[ $this->suggestion_id ] ) ) {
			$all_dismissed_incentives[ $this->suggestion_id ] = array();
			ksort( $all_dismissed_incentives );
		}

		$all_dismissed_incentives[ $this->suggestion_id ][] = array(
			'id'        => $id,
			'context'   => $context,
			'timestamp' => $timestamp ?? time(),
		);

		return $this->save_all_dismissed_incentives( $all_dismissed_incentives );
	}

	/**
	 * Check if an incentive has been manually dismissed.
	 *
	 * @param string $id      The incentive ID to check for dismissal.
	 * @param string $context Optional. The context ID in which to check for dismissal.
	 *                        If no context ID is provided, we check for dismissal in all contexts.
	 *
	 * @return boolean Whether the incentive has been manually dismissed.
	 */
	public function is_dismissed( string $id, string $context = '' ): bool {
		if ( empty( $id ) ) {
			return false;
		}

		$all_dismissed_incentives = $this->get_all_dismissed_incentives();

		// If there are no dismissed incentives for the suggestion, return early.
		$dismissed_incentives = $all_dismissed_incentives[ $this->suggestion_id ] ?? array();
		if ( empty( $dismissed_incentives ) ) {
			return false;
		}

		// Check if the incentive is dismissed in the given context.
		if ( in_array(
			$id,
			array_column(
				array_filter(
					$dismissed_incentives,
					// All context dismissals are always included.
					fn( $dismissed_incentive ) => 'all' === $dismissed_incentive['context'] || $context === $dismissed_incentive['context']
				),
				'id'
			),
			true
		) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the dismissals (contexts) for an incentive.
	 *
	 * @param string $id The incentive ID.
	 *
	 * @return array The contexts in which the incentive has been dismissed.
	 */
	public function get_dismissals( string $id ): array {
		$all_dismissed_incentives = $this->get_all_dismissed_incentives();

		// If there are no dismissed incentives for the suggestion, return early.
		$dismissed_incentives = $all_dismissed_incentives[ $this->suggestion_id ] ?? array();
		if ( empty( $dismissed_incentives ) ) {
			return array();
		}

		$dismissals = array_values(
			array_filter(
				$dismissed_incentives,
				fn( $dismissed_incentive ) => $id === $dismissed_incentive['id']
			)
		);

		return array_map(
			fn( $dismissed_incentive ) => array(
				'timestamp' => $dismissed_incentive['timestamp'],
				'context'   => $dismissed_incentive['context'],
			),
			$dismissals
		);
	}

	/**
	 * Get all the dismissed incentives grouped by suggestion.
	 *
	 * @return array The dismissed incentives grouped by suggestion.
	 */
	protected function get_all_dismissed_incentives(): array {
		$all_dismissed_incentives = get_user_meta( get_current_user_id(), $this->dismissed_meta_name, true );
		if ( empty( $all_dismissed_incentives ) ) {
			$all_dismissed_incentives = array();
		}

		return $all_dismissed_incentives;
	}

	/**
	 * Save all the dismissed incentives list.
	 *
	 * @param array $dismissed_incentives The dismissed incentives data.
	 *
	 * @return bool Whether the dismissed incentives were saved successfully.
	 */
	protected function save_all_dismissed_incentives( array $dismissed_incentives ): bool {
		return (bool) update_user_meta( get_current_user_id(), $this->dismissed_meta_name, $dismissed_incentives );
	}

	/**
	 * Check if the current user has the required capabilities to view incentives.
	 *
	 * @return bool Whether the current user has the required capabilities view incentives.
	 */
	protected function user_has_caps(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Validate an incentive details.
	 *
	 * It will check if the incentive details have the required keys.
	 *
	 * @param array $incentive The incentive details.
	 *
	 * @return bool Whether the incentive data is valid.
	 */
	protected function validate_incentive( array $incentive ): bool {
		// The incentive must have an ID, a promo ID, and a type.
		$required_keys = array( 'id', 'promo_id', 'type' );
		foreach ( $required_keys as $key ) {
			if ( empty( $incentive[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the corresponding extension suggestion plugin is active.
	 *
	 * @return boolean Whether the corresponding extension suggestion plugin is active.
	 */
	abstract protected function is_extension_active(): bool;

	/**
	 * Get eligible incentives.
	 *
	 * @param string $country_code The business location country code to get incentives for.
	 *
	 * @return array List of eligible incentives.
	 */
	abstract protected function get_incentives( string $country_code ): array;
}
