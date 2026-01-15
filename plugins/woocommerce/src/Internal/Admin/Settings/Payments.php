<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions as ExtensionSuggestions;
use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Exception;

defined( 'ABSPATH' ) || exit;
/**
 * Payments settings service class.
 *
 * @internal
 */
class Payments {

	const PAYMENTS_NOX_PROFILE_KEY              = 'woocommerce_payments_nox_profile';
	const PAYMENTS_PROVIDER_STATE_SNAPSHOTS_KEY = 'woocommerce_payments_provider_state_snapshots';

	const SUGGESTIONS_CONTEXT = 'wc_settings_payments';

	const EVENT_PREFIX = 'settings_payments_';

	const FROM_PAYMENTS_SETTINGS        = 'WCADMIN_PAYMENT_SETTINGS';
	const FROM_PAYMENTS_MENU_ITEM       = 'PAYMENTS_MENU_ITEM';
	const FROM_PAYMENTS_TASK            = 'WCADMIN_PAYMENT_TASK';
	const FROM_ADDITIONAL_PAYMENTS_TASK = 'WCADMIN_ADDITIONAL_PAYMENT_TASK';
	const FROM_PROVIDER_ONBOARDING      = 'PROVIDER_ONBOARDING';

	/**
	 * The payment providers service.
	 *
	 * @var PaymentsProviders
	 */
	private PaymentsProviders $providers;

	/**
	 * The payment extension suggestions service.
	 *
	 * @var ExtensionSuggestions
	 */
	private ExtensionSuggestions $extension_suggestions;

	/**
	 * Initialize the class instance.
	 *
	 * @param PaymentsProviders    $payment_providers             The payment providers service.
	 * @param ExtensionSuggestions $payment_extension_suggestions The payment extension suggestions service.
	 *
	 * @internal
	 */
	final public function init( PaymentsProviders $payment_providers, ExtensionSuggestions $payment_extension_suggestions ): void {
		$this->providers             = $payment_providers;
		$this->extension_suggestions = $payment_extension_suggestions;
	}

	/**
	 * Get the payment provider details list for the settings page.
	 *
	 * @param string $location    The location for which the providers are being determined.
	 *                            This is an ISO 3166-1 alpha-2 country code.
	 * @param bool   $for_display Optional. Whether the payment providers list is intended for display purposes or
	 *                            it is meant to be used for internal business logic.
	 *                            Primarily, this means that when it is not for display, we will use the raw
	 *                            payment gateways list (all the registered gateways), not just the ones that
	 *                            should be shown to the user on the Payments Settings page.
	 *                            This complication is for backward compatibility as it relates to legacy settings hooks
	 *                            being fired or not.
	 * @param bool   $remove_shells Optional. Whether to remove the payment providers shells from the list.
	 *                              If the $for_display is true, this will be ignored since the display logic will
	 *                              handle the shells itself.
	 *
	 * @return array The payment providers details list.
	 * @throws Exception If there are malformed or invalid suggestions.
	 */
	public function get_payment_providers( string $location, bool $for_display = true, bool $remove_shells = false ): array {
		$payment_gateways = $this->providers->get_payment_gateways( $for_display );
		if ( ! $for_display && $remove_shells ) {
			$payment_gateways = $this->providers->remove_shell_payment_gateways( $payment_gateways, $location );
		}

		$providers_order_map = $this->providers->get_order_map();

		$payment_providers = array();

		// Only include suggestions if the requesting user can install plugins.
		$suggestions = array();
		if ( current_user_can( 'install_plugins' ) ) {
			$suggestions = $this->providers->get_extension_suggestions( $location, self::SUGGESTIONS_CONTEXT );
		}
		// If we have preferred suggestions, add them to the providers list.
		if ( ! empty( $suggestions['preferred'] ) ) {
			// Sort them by priority, ASC.
			usort(
				$suggestions['preferred'],
				function ( $a, $b ) {
					return $a['_priority'] <=> $b['_priority'];
				}
			);

			// By default, we will add the preferred suggestions at the top of the list.
			$last_preferred_order = -1;
			// If WooPayments is already present, we add the preferred suggestions after it.
			// This way we ensure default installed WooPayments is at the same place as its suggestion would be.
			if ( isset( $providers_order_map[ WooPaymentsService::GATEWAY_ID ] ) ) {
				$last_preferred_order = $providers_order_map[ WooPaymentsService::GATEWAY_ID ];
			}

			foreach ( $suggestions['preferred'] as $suggestion ) {
				$suggestion_order_map_id = $this->providers->get_suggestion_order_map_id( $suggestion['id'] );
				// Determine the suggestion's order value.
				// If we don't have an order for it, add it to the top but keep the relative order:
				// PSP first, APM after PSP, offline PSP after PSP and APM.
				if ( ! isset( $providers_order_map[ $suggestion_order_map_id ] ) ) {
					$providers_order_map = Utils::order_map_add_at_order( $providers_order_map, $suggestion_order_map_id, $last_preferred_order + 1 );
				}

				// Save the preferred provider's order to know where we should be inserting next.
				// But only if the last preferred order is less than the current one.
				if ( $last_preferred_order < $providers_order_map[ $suggestion_order_map_id ] ) {
					$last_preferred_order = $providers_order_map[ $suggestion_order_map_id ];
				}

				// Change suggestion details to align it with a regular payment gateway.
				$suggestion['_suggestion_id'] = $suggestion['id'];
				$suggestion['id']             = $suggestion_order_map_id;
				$suggestion['_type']          = PaymentsProviders::TYPE_SUGGESTION;
				$suggestion['_order']         = $providers_order_map[ $suggestion_order_map_id ];
				unset( $suggestion['_priority'] );

				$payment_providers[] = $suggestion;
			}
		}

		foreach ( $payment_gateways as $payment_gateway ) {
			// Determine the gateway's order value.
			// If we don't have an order for it, add it to the end.
			if ( ! isset( $providers_order_map[ $payment_gateway->id ] ) ) {
				$providers_order_map = Utils::order_map_add_at_order( $providers_order_map, $payment_gateway->id, count( $payment_providers ) );
			}

			$payment_providers[] = $this->providers->get_payment_gateway_details(
				$payment_gateway,
				$providers_order_map[ $payment_gateway->id ],
				$location
			);
		}

		// Add offline payment methods group entry if we have offline payment methods.
		if ( in_array( PaymentsProviders::TYPE_OFFLINE_PM, array_column( $payment_providers, '_type' ), true ) ) {
			// Determine the item's order value.
			// If we don't have an order for it, add it to the end.
			if ( ! isset( $providers_order_map[ PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP ] ) ) {
				$providers_order_map = Utils::order_map_add_at_order( $providers_order_map, PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP, count( $payment_providers ) );
			}

			$payment_providers[] = array(
				'id'          => PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP,
				'_type'       => PaymentsProviders::TYPE_OFFLINE_PMS_GROUP,
				'_order'      => $providers_order_map[ PaymentsProviders::OFFLINE_METHODS_ORDERING_GROUP ],
				'title'       => esc_html__( 'Take offline payments', 'woocommerce' ),
				'description' => esc_html__( 'Accept payments offline using multiple different methods. These can also be used to test purchases.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/payment_methods/cod.svg', WC_PLUGIN_FILE ),
				// The offline PMs (and their group) are obviously from WooCommerce, and WC is always active.
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => '', // This pseudo-provider should have no use for the plugin file.
					'status' => PaymentsProviders::EXTENSION_ACTIVE,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => Utils::wc_payments_settings_url( '/' . ( class_exists( '\WC_Settings_Payment_Gateways' ) ? \WC_Settings_Payment_Gateways::OFFLINE_SECTION_NAME : 'offline' ) ),
						),
					),
				),
			);
		}

		// Determine the final, standardized providers order map.
		$providers_order_map = $this->providers->enhance_order_map( $providers_order_map );
		// Enforce the order map on all providers, just in case.
		foreach ( $payment_providers as $key => $provider ) {
			$payment_providers[ $key ]['_order'] = $providers_order_map[ $provider['id'] ];
		}
		// NOTE: For now, save it back to the DB. This is temporary until we have a better way to handle this!
		$this->providers->save_order_map( $providers_order_map );

		// Sort the payment providers by order, ASC.
		usort(
			$payment_providers,
			function ( $a, $b ) {
				return $a['_order'] <=> $b['_order'];
			}
		);

		// Only process payment provider states if we are displaying the providers.
		// This is to ensure we don't introduce any performance issues outside the Payments settings page.
		if ( $for_display ) {
			$this->process_payment_provider_states( $payment_providers );
		}

		return $payment_providers;
	}

	/**
	 * Get the payment extension suggestions for the given location.
	 *
	 * @param string $location The location for which the suggestions are being fetched.
	 *
	 * @return array[] The payment extension suggestions for the given location, split into preferred and other.
	 * @throws Exception If there are malformed or invalid suggestions.
	 */
	public function get_payment_extension_suggestions( string $location ): array {
		return $this->providers->get_extension_suggestions( $location, self::SUGGESTIONS_CONTEXT );
	}

	/**
	 * Get the payment extension suggestions categories details.
	 *
	 * @return array The payment extension suggestions categories.
	 */
	public function get_payment_extension_suggestion_categories(): array {
		return $this->providers->get_extension_suggestion_categories();
	}

	/**
	 * Get the business location country code for the Payments settings.
	 *
	 * @return string The ISO 3166-1 alpha-2 country code to use for the overall business location.
	 *                If the user didn't set a location, the WC base location country code is used.
	 */
	public function get_country(): string {
		$user_nox_meta = get_user_meta( get_current_user_id(), self::PAYMENTS_NOX_PROFILE_KEY, true );
		if ( ! empty( $user_nox_meta['business_country_code'] ) ) {
			return $user_nox_meta['business_country_code'];
		}

		return WC()->countries->get_base_country();
	}

	/**
	 * Set the business location country for the Payments settings.
	 *
	 * @param string $location The country code. This should be an ISO 3166-1 alpha-2 country code.
	 */
	public function set_country( string $location ): bool {
		$previous_country = $this->get_country();

		$user_payments_nox_profile = get_user_meta( get_current_user_id(), self::PAYMENTS_NOX_PROFILE_KEY, true );

		if ( empty( $user_payments_nox_profile ) ) {
			$user_payments_nox_profile = array();
		} else {
			$user_payments_nox_profile = maybe_unserialize( $user_payments_nox_profile );
		}
		$user_payments_nox_profile['business_country_code'] = $location;

		$result = false !== update_user_meta( get_current_user_id(), self::PAYMENTS_NOX_PROFILE_KEY, $user_payments_nox_profile );

		if ( $result && $previous_country !== $location ) {
			// Record an event that the business location (registration country code) was changed.
			$this->record_event(
				'business_location_update',
				array(
					'business_country'          => $location,
					'previous_business_country' => $previous_country,
				)
			);
		}

		return $result;
	}

	/**
	 * Update the payment providers order map.
	 *
	 * @param array $order_map The new order for payment providers.
	 *
	 * @return bool True if the payment providers ordering was successfully updated, false otherwise.
	 */
	public function update_payment_providers_order_map( array $order_map ): bool {
		$result = $this->providers->update_payment_providers_order_map( $order_map );

		if ( $result ) {
			// Record an event that the payment providers order map was updated.
			$this->record_event(
				'payment_providers_order_map_updated',
				array(
					'order_map' => implode( ', ', array_keys( $this->providers->get_order_map() ) ),
				)
			);
		}

		return $result;
	}

	/**
	 * Attach a payment extension suggestion.
	 *
	 * This is only an internal recording of attachment. No actual extension installation or activation happens.
	 *
	 * @param string $id The ID of the payment extension suggestion to attach.
	 *
	 * @return bool True if the suggestion was successfully marked as attached, false otherwise.
	 * @throws Exception If the suggestion ID is invalid.
	 */
	public function attach_payment_extension_suggestion( string $id ): bool {
		$result = $this->providers->attach_extension_suggestion( $id );

		if ( $result ) {
			// Record an event that the suggestion was attached.
			$this->record_event(
				'extension_suggestion_attached',
				array(
					'suggestion_id' => $id,
				)
			);
		}

		return $result;
	}

	/**
	 * Hide a payment extension suggestion.
	 *
	 * @param string $id The ID of the payment extension suggestion to hide.
	 *
	 * @return bool True if the suggestion was successfully hidden, false otherwise.
	 * @throws Exception If the suggestion ID is invalid.
	 */
	public function hide_payment_extension_suggestion( string $id ): bool {
		$result = $this->providers->hide_extension_suggestion( $id );

		if ( $result ) {
			// Record an event that the suggestion was hidden.
			$this->record_event(
				'extension_suggestion_hidden',
				array(
					'suggestion_id' => $id,
				)
			);
		}

		return $result;
	}

	/**
	 * Dismiss a payment extension suggestion incentive.
	 *
	 * @param string $suggestion_id The suggestion ID.
	 * @param string $incentive_id  The incentive ID.
	 * @param string $context       Optional. The context in which the incentive should be dismissed.
	 *                              Default is to dismiss the incentive in all contexts.
	 * @param bool   $do_not_track  Optional. If true, the incentive dismissal will not be tracked.
	 *
	 * @return bool True if the incentive was not previously dismissed and now it is.
	 *              False if the incentive was already dismissed or could not be dismissed.
	 * @throws Exception If the incentive could not be dismissed due to an error.
	 */
	public function dismiss_extension_suggestion_incentive( string $suggestion_id, string $incentive_id, string $context = 'all', bool $do_not_track = false ): bool {
		$result = $this->extension_suggestions->dismiss_incentive( $incentive_id, $suggestion_id, $context );

		if ( ! $do_not_track && $result ) {
			// Record an event that the incentive was dismissed.
			$this->record_event(
				'incentive_dismiss',
				array(
					'suggestion_id'   => $suggestion_id,
					'incentive_id'    => $incentive_id,
					'display_context' => $context,
				)
			);
		}

		return $result;
	}

	/**
	 * Send a Tracks event.
	 *
	 * By default, Woo adds `url`, `blog_lang`, `blog_id`, `store_id`, `products_count`, and `wc_version`
	 * properties to every event.
	 *
	 * @param string $name The event name.
	 *                     If it is not prefixed with self::EVENT_PREFIX, it will be prefixed with it.
	 * @param array  $properties Optional. The event custom properties.
	 *                           These properties will be merged with the default properties.
	 *                           Default properties values take precedence over the provided ones.
	 *
	 * @return void
	 */
	private function record_event( string $name, array $properties = array() ) {
		if ( ! function_exists( 'wc_admin_record_tracks_event' ) ) {
			return;
		}

		// If the event name is empty, we don't record it.
		if ( empty( $name ) ) {
			return;
		}

		// If the event name is not prefixed with `settings_payments_`, we prefix it.
		if ( ! str_starts_with( $name, self::EVENT_PREFIX ) ) {
			$name = self::EVENT_PREFIX . $name;
		}

		// Add default properties to every event and overwrite custom properties with the same keys.
		$properties = array_merge(
			$properties,
			array(
				'business_country' => $this->get_country(),
			),
		);

		wc_admin_record_tracks_event( $name, $properties );
	}

	/**
	 * Process the payment providers states and update the snapshots in the DB.
	 *
	 * @param array $payment_providers The payment providers details list.
	 */
	private function process_payment_provider_states( array $payment_providers ): void {
		// Read the current state snapshots from the DB.
		$snapshots = get_option( self::PAYMENTS_PROVIDER_STATE_SNAPSHOTS_KEY, array() );
		if ( ! is_array( $snapshots ) ) {
			$snapshots = array();
		}

		$default_snapshot = array(
			'extension_active'  => false,
			'account_connected' => false,
			'account_test_mode' => false,
			'needs_setup'       => false,
			'test_mode'         => false,
		);

		// Iterate through the payment providers and generate their updated snapshots.
		// We will use the provider's plugin slug as the key for the snapshot to ensure uniqueness.
		// For now, we will only focus on the provider state for official extensions, not all the gateways.
		$new_snapshots = array();
		foreach ( $payment_providers as $provider ) {
			if ( empty( $provider['plugin']['slug'] ) ||
				empty( $provider['id'] ) ||
				empty( $provider['state'] ) || ! is_array( $provider['state'] ) ||
				empty( $provider['onboarding']['state'] ) || ! is_array( $provider['onboarding']['state'] ) ||
				empty( $provider['_type'] ) ||
				PaymentsProviders::TYPE_GATEWAY !== $provider['_type'] ||
				empty( $provider['_suggestion_id'] )
			) {
				continue;
			}

			$snapshot_key = $provider['plugin']['slug'];

			// Since we are going after the provider general state, not that of the specific gateway,
			// we only need to look at the first found gateway from a given provider.
			if ( isset( $new_snapshots[ $snapshot_key ] ) ) {
				continue;
			}

			// If we don't have an already existing snapshot for this provider, we create one with default values.
			// This way we can track changes even for the first time we see a provider.
			if ( ! isset( $snapshots[ $snapshot_key ] ) ) {
				$snapshots[ $snapshot_key ] = $default_snapshot;
			} else {
				// Make sure the old snapshot has the same keys as the default one.
				$snapshots[ $snapshot_key ] = array_merge( $default_snapshot, $snapshots[ $snapshot_key ] );
				// Remove any keys that are not in the default snapshot.
				$snapshot_keys = array_keys( $default_snapshot );
				foreach ( $snapshots[ $snapshot_key ] as $key => $v ) {
					if ( ! in_array( $key, $snapshot_keys, true ) ) {
						unset( $snapshots[ $snapshot_key ][ $key ] );
					}
				}

				// Always sort the old snapshot by keys to ensure consistency.
				ksort( $snapshots[ $snapshot_key ] );
			}

			// Generate the new snapshot for the provider.
			$new_snapshots[ $snapshot_key ] = array(
				'extension_active'  => true, // The extension is definitely active since we have a gateway from it.
				'account_connected' => $provider['state']['account_connected'] ?? $default_snapshot['account_connected'],
				'account_test_mode' => $provider['onboarding']['state']['test_mode'] ?? $default_snapshot['account_test_mode'],
				'needs_setup'       => $provider['state']['needs_setup'] ?? $default_snapshot['needs_setup'],
				'test_mode'         => $provider['state']['test_mode'] ?? $default_snapshot['test_mode'],
			);

			// Always sort the new snapshot by keys to ensure consistency.
			ksort( $new_snapshots[ $snapshot_key ] );
		}

		// Provider snapshots that are not in the new snapshots but were in the old ones should be kept but marked as inactive.
		foreach ( $snapshots as $snapshot_key => $old_snapshot ) {
			if ( ! isset( $new_snapshots[ $snapshot_key ] ) ) {
				$new_snapshots[ $snapshot_key ]                     = $old_snapshot;
				$new_snapshots[ $snapshot_key ]['extension_active'] = false;
			}
		}

		// Always order the new snapshots by keys to ensure DB updates happen only when the data changes.
		ksort( $new_snapshots );

		// Save the new snapshots back to the DB, as soon as we have them ready to avoid concurrent state change tracking.
		// No need to autoload this option since it will be used only in the Payments Settings area.
		$result = update_option( self::PAYMENTS_PROVIDER_STATE_SNAPSHOTS_KEY, $new_snapshots, false );
		if ( ! $result ) {
			// If we didn't update the option, we don't need to track any changes.
			return;
		}

		try {
			$this->maybe_track_providers_state_change( $payment_providers, $snapshots, $new_snapshots );
		} catch ( \Throwable $exception ) {
			// If we failed to track the changes, we log the error but don't throw it.
			// This is to avoid breaking the Payments Settings page.
			SafeGlobalFunctionProxy::wc_get_logger()->error(
				'Failed to track payment providers state change: ' . $exception->getMessage(),
				array(
					'source' => 'settings-payments',
				)
			);
		}
	}

	/**
	 * Maybe track the payment providers state change.
	 *
	 * This method will iterate through the new snapshots and compare them with the old ones.
	 * If there are any changes, it will track them.
	 *
	 * @param array $providers      The list of payment provider details.
	 * @param array $old_snapshots  The old snapshots of the providers' states.
	 * @param array $new_snapshots  The new snapshots of the providers' states.
	 */
	private function maybe_track_providers_state_change( array $providers, array $old_snapshots, array $new_snapshots ): void {
		foreach ( $new_snapshots as $provider_extension_slug => $new_snapshot ) {
			if ( ! isset( $old_snapshots[ $provider_extension_slug ] ) ) {
				// If we don't have an old snapshot for this provider, we can't track the change.
				continue;
			}

			// If there are no changes, we don't need to track anything.
			if ( maybe_serialize( $old_snapshots[ $provider_extension_slug ] ) === maybe_serialize( $new_snapshot ) ) {
				continue;
			}

			// Search for the provider by its plugin slug.
			$provider = null;
			foreach ( $providers as $p ) {
				if ( isset( $p['plugin']['slug'] ) && $p['plugin']['slug'] === $provider_extension_slug ) {
					$provider = $p;
					break;
				}
			}
			if ( ! $provider ) {
				// If we couldn't find the provider in the list it means the extension was deactivated.
				// Get the matching suggestion by its slug.
				$provider = $this->providers->get_extension_suggestion_by_plugin_slug( $provider_extension_slug );
				if ( ! empty( $provider['id'] ) ) {
					// If we found the suggestion, we can use it as a replacement provider.
					// We need to set the `_suggestion_id` so we can handle the date more uniformly.
					$provider['_suggestion_id'] = $provider['id'];
				}
			}
			if ( ! $provider ) {
				continue;
			}

			$this->maybe_track_provider_state_change( $provider, $old_snapshots[ $provider_extension_slug ], $new_snapshot );
		}
	}

	/**
	 * Track the payment provider state change.
	 *
	 * @param array $provider       The payment provider details.
	 * @param array $old_snapshot   The old snapshot of the provider's state.
	 * @param array $new_snapshot   The new snapshot of the provider's state.
	 */
	private function maybe_track_provider_state_change( array $provider, array $old_snapshot, array $new_snapshot ): void {
		// Note: Keep the order of the events in a way that makes sense for the onboarding flow.

		// Track extension_active change.
		if ( $old_snapshot['extension_active'] && ! $new_snapshot['extension_active'] ) {
			$this->record_event(
				'provider_extension_deactivated',
				array(
					'provider_id'             => $provider['id'],
					'suggestion_id'           => $provider['_suggestion_id'],
					'provider_extension_slug' => $provider['plugin']['slug'],
				)
			);

			// If the extension was also uninstalled, we can track that as well.
			if ( ! empty( $provider['plugin']['status'] ) && PaymentsProviders::EXTENSION_NOT_INSTALLED === $provider['plugin']['status'] ) {
				$this->record_event(
					'provider_extension_uninstalled',
					array(
						'provider_id'             => $provider['id'],
						'suggestion_id'           => $provider['_suggestion_id'],
						'provider_extension_slug' => $provider['plugin']['slug'],
					)
				);
			}
		} elseif ( ! $old_snapshot['extension_active'] && $new_snapshot['extension_active'] ) {
			$this->record_event(
				'provider_extension_activated',
				array(
					'provider_id'             => $provider['id'],
					'suggestion_id'           => $provider['_suggestion_id'],
					'provider_extension_slug' => $provider['plugin']['slug'],
				)
			);
		}

		// Track account_connected change.
		if ( $old_snapshot['account_connected'] && ! $new_snapshot['account_connected'] ) {
			$this->record_event(
				'provider_account_disconnected',
				array(
					'provider_id'                => $provider['id'],
					'suggestion_id'              => $provider['_suggestion_id'],
					'provider_extension_slug'    => $provider['plugin']['slug'],
					'provider_account_test_mode' => $old_snapshot['account_test_mode'] ? 'yes' : 'no',
				)
			);
		} elseif ( ! $old_snapshot['account_connected'] && $new_snapshot['account_connected'] ) {
			$this->record_event(
				'provider_account_connected',
				array(
					'provider_id'                => $provider['id'],
					'suggestion_id'              => $provider['_suggestion_id'],
					'provider_extension_slug'    => $provider['plugin']['slug'],
					'provider_account_test_mode' => $new_snapshot['account_test_mode'] ? 'yes' : 'no',
				)
			);
		}

		// Track needs_setup change.
		if ( $old_snapshot['needs_setup'] && ! $new_snapshot['needs_setup'] ) {
			$this->record_event(
				'provider_setup_completed',
				array(
					'provider_id'             => $provider['id'],
					'suggestion_id'           => $provider['_suggestion_id'],
					'provider_extension_slug' => $provider['plugin']['slug'],
				)
			);
		} elseif ( ! $old_snapshot['needs_setup'] && $new_snapshot['needs_setup'] ) {
			$this->record_event(
				'provider_setup_required',
				array(
					'provider_id'             => $provider['id'],
					'suggestion_id'           => $provider['_suggestion_id'],
					'provider_extension_slug' => $provider['plugin']['slug'],
				)
			);
		}

		// Track payments test_mode change, but only if an account is connected.
		if ( $new_snapshot['account_connected'] ) {
			if ( $old_snapshot['test_mode'] && ! $new_snapshot['test_mode'] ) {
				$this->record_event(
					'provider_live_payments_enabled',
					array(
						'provider_id'             => $provider['id'],
						'suggestion_id'           => $provider['_suggestion_id'],
						'provider_extension_slug' => $provider['plugin']['slug'],
					)
				);
			} elseif ( ! $old_snapshot['test_mode'] && $new_snapshot['test_mode'] ) {
				$this->record_event(
					'provider_test_payments_enabled',
					array(
						'provider_id'             => $provider['id'],
						'suggestion_id'           => $provider['_suggestion_id'],
						'provider_extension_slug' => $provider['plugin']['slug'],
					)
				);
			}
		}

		// Track account_test_mode change, but only if the account is connected.
		if ( $new_snapshot['account_connected'] ) {
			if ( $old_snapshot['account_test_mode'] && ! $new_snapshot['account_test_mode'] ) {
				$this->record_event(
					'provider_account_live_mode_enabled',
					array(
						'provider_id'             => $provider['id'],
						'suggestion_id'           => $provider['_suggestion_id'],
						'provider_extension_slug' => $provider['plugin']['slug'],
					)
				);
			} elseif ( ! $old_snapshot['account_test_mode'] && $new_snapshot['account_test_mode'] ) {
				$this->record_event(
					'provider_account_test_mode_enabled',
					array(
						'provider_id'             => $provider['id'],
						'suggestion_id'           => $provider['_suggestion_id'],
						'provider_extension_slug' => $provider['plugin']['slug'],
					)
				);
			}
		}
	}
}
