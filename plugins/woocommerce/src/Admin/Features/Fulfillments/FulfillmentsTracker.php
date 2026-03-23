<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\Fulfillments;

use WC_Tracks;

/**
 * FulfillmentsTracker class.
 *
 * Centralizes all telemetry for the Fulfillments feature. Every tracked event is recorded via
 * WC_Tracks::record_event() which sends it to the analytics pipeline with a "wcadmin_" prefix.
 *
 * Tracked events (WOOPLUG-5197):
 *
 * 1. Core Funnel (Adoption):
 *    - fulfillment_modal_opened   : Merchant opens the fulfillment editor drawer.         (Frontend only)
 *    - fulfillment_created        : A new fulfillment is saved.                            (REST controller)
 *    - fulfillment_updated        : An existing fulfillment is modified.                   (REST controller)
 *    - fulfillment_deleted        : A fulfillment is deleted.                              (REST controller)
 *
 * 2. Tracking Information (Usage Patterns):
 *    - fulfillment_tracking_added            : Tracking info is attached to a fulfillment. (REST controller)
 *    - fulfillment_tracking_lookup_attempted : Tracking number auto-lookup is attempted.   (FulfillmentsManager)
 *
 * 3. Efficiency / Power-User:
 *    - fulfillment_bulk_action_used : Bulk fulfill/unfulfill from the orders list.         (FulfillmentsRenderer)
 *    - fulfillment_filter_used      : Orders list filtered by fulfillment status/provider. (FulfillmentsRenderer)
 *
 * 4. Customer Communication:
 *    - fulfillment_notification_sent          : A fulfillment email is queued to the customer.    (REST controller)
 *    - fulfillment_email_template_customized  : Merchant saves fulfillment email template settings. (FulfillmentsManager)
 *
 * 5. Friction / Errors:
 *    - fulfillment_validation_error : A create/update/delete action fails validation.      (REST controller)
 *
 * @since 10.7.0
 */
class FulfillmentsTracker {

	// ──────────────────────────────────────────────
	// 1. Core Funnel: Fulfillment Creation & Management
	// ──────────────────────────────────────────────

	/**
	 * Track when a merchant opens the fulfillment editor modal/sidebar.
	 *
	 * Tracked from: Frontend (JS recordEvent).
	 * Measures: Feature discoverability and adoption.
	 *
	 * @since 10.7.0
	 *
	 * @param string $source  Where the modal was opened from ("orders_list" or "order_detail_page").
	 * @param int    $order_id The ID of the order being viewed.
	 *
	 * @return void
	 */
	public static function track_fulfillment_modal_opened( string $source, int $order_id ): void {
		WC_Tracks::record_event(
			'fulfillment_modal_opened',
			array(
				'source'   => $source,
				'order_id' => $order_id,
			)
		);
	}

	/**
	 * Track when a new fulfillment is successfully saved.
	 *
	 * Tracked from: OrderFulfillmentsRestController::create_fulfillment().
	 * Measures: Core adoption; whether merchants create full vs. partial shipments.
	 *
	 * @since 10.7.0
	 *
	 * @param string $source           The source of the fulfillment ("fulfillments_modal", "bulk_action", or "api").
	 * @param string $initial_status   The initial status of the fulfillment ("draft" or "fulfilled").
	 * @param string $fulfillment_type Whether all remaining items were included ("full" or "partial").
	 * @param int    $item_count       Total quantity of items in the fulfillment (sum of item quantities).
	 * @param int    $total_quantity   Total quantity of all items in the order.
	 * @param bool   $notification_sent Whether the customer notification was requested.
	 * @return void
	 */
	public static function track_fulfillment_creation( string $source, string $initial_status, string $fulfillment_type, int $item_count, int $total_quantity, bool $notification_sent ): void {
		WC_Tracks::record_event(
			'fulfillment_created',
			array(
				'source'            => $source,
				'initial_status'    => $initial_status,
				'fulfillment_type'  => $fulfillment_type,
				'item_count'        => $item_count,
				'total_quantity'    => $total_quantity,
				'notification_sent' => $notification_sent,
			)
		);
	}

	/**
	 * Track when an existing fulfillment is successfully updated.
	 *
	 * Tracked from: OrderFulfillmentsRestController::update_fulfillment().
	 * Measures: How often merchants modify fulfillments and which fields change most.
	 *
	 * @since 10.7.0
	 *
	 * @param string $source            The source of the update ("fulfillments_modal" or "api").
	 * @param int    $fulfillment_id    The ID of the fulfillment being updated.
	 * @param string $original_status   The status before the update ("draft" or "fulfilled").
	 * @param array  $changed_fields    The changes as returned by Fulfillment::get_changes(). Core data
	 *                                  props (e.g. 'status') at top level, meta changes under 'meta_data'.
	 *                                  Serialized as JSON by WC_Tracks.
	 * @param bool   $notification_sent Whether a customer re-notification was requested.
	 *
	 * @return void
	 */
	public static function track_fulfillment_update( string $source, int $fulfillment_id, string $original_status, array $changed_fields, bool $notification_sent ): void {
		WC_Tracks::record_event(
			'fulfillment_updated',
			array(
				'source'            => $source,
				'fulfillment_id'    => $fulfillment_id,
				'original_status'   => $original_status,
				'changed_fields'    => $changed_fields,
				'notification_sent' => $notification_sent,
			)
		);
	}

	/**
	 * Track when a fulfillment is successfully deleted.
	 *
	 * Tracked from: OrderFulfillmentsRestController::delete_fulfillment().
	 * Measures: How often merchants remove fulfillments and at what stage.
	 *
	 * @since 10.7.0
	 *
	 * @param string $source              The source of the deletion ("fulfillments_modal" or "api").
	 * @param int    $fulfillment_id      The ID of the fulfillment being deleted.
	 * @param string $status_at_deletion  The status at the time of deletion ("draft" or "fulfilled").
	 * @param bool   $notification_sent   Whether a deletion notification was requested.
	 *
	 * @return void
	 */
	public static function track_fulfillment_deletion( string $source, int $fulfillment_id, string $status_at_deletion, bool $notification_sent ): void {
		WC_Tracks::record_event(
			'fulfillment_deleted',
			array(
				'source'             => $source,
				'fulfillment_id'     => $fulfillment_id,
				'status_at_deletion' => $status_at_deletion,
				'notification_sent'  => $notification_sent,
			)
		);
	}

	// ──────────────────────────────────────────────
	// 2. Tracking Information Workflow
	// ──────────────────────────────────────────────

	/**
	 * Track when tracking information is successfully added to a fulfillment.
	 *
	 * Tracked from: OrderFulfillmentsRestController (create and update flows).
	 * Measures: How merchants add tracking info (auto-lookup vs. manual vs. API) and which
	 *           carriers are used. The provider_name property for custom providers is used to
	 *           identify the most frequently added custom carriers, informing the roadmap for
	 *           expanding native carrier support.
	 *
	 * @since 10.7.0
	 *
	 * @param int    $fulfillment_id    The ID of the fulfillment to which tracking was added.
	 * @param string $entry_method      How the tracking was added ("ui_auto_lookup", "ui_manual_select", "ui_manual_custom", or "api").
	 * @param string $provider_name     The name/key of the shipping provider (e.g., "usps", "fedex").
	 * @param bool   $is_custom_provider Whether the provider is a custom (non-native) provider.
	 *
	 * @return void
	 */
	public static function track_fulfillment_tracking_added( int $fulfillment_id, string $entry_method, string $provider_name, bool $is_custom_provider ): void {
		WC_Tracks::record_event(
			'fulfillment_tracking_added',
			array(
				'fulfillment_id'     => $fulfillment_id,
				'entry_method'       => $entry_method,
				'provider_name'      => $provider_name,
				'is_custom_provider' => $is_custom_provider,
			)
		);
	}

	/**
	 * Track when a tracking number auto-lookup is attempted.
	 *
	 * Tracked from: FulfillmentsManager::try_parse_tracking_number().
	 * Measures: Effectiveness of auto-detection. A high failure rate indicates the need to improve
	 *           carrier detection logic. The url_generated flag checks if a functional tracking URL
	 *           was constructed (a success requires both provider identification AND URL generation).
	 *
	 * @since 10.7.0
	 *
	 * @param string $lookup_status       The lookup result ("success" or "not_found").
	 * @param string $provider_identified The standardized carrier name identified (e.g., "usps"). Empty if not found.
	 * @param bool   $url_generated       Whether the system successfully constructed a tracking URL.
	 *
	 * @return void
	 */
	public static function track_fulfillment_tracking_lookup_attempt( string $lookup_status, string $provider_identified, bool $url_generated = false ): void {
		WC_Tracks::record_event(
			'fulfillment_tracking_lookup_attempted',
			array(
				'lookup_status'       => $lookup_status,
				'provider_identified' => $provider_identified,
				'url_generated'       => $url_generated,
			)
		);
	}

	// ──────────────────────────────────────────────
	// 3. Efficiency & Power-User Features
	// ──────────────────────────────────────────────

	/**
	 * Track when a merchant applies a fulfillment-related bulk action from the orders list.
	 *
	 * Tracked from: FulfillmentsRenderer::handle_fulfillment_bulk_actions().
	 * Measures: Whether merchants use the time-saving bulk-fulfill feature.
	 *
	 * @since 10.7.0
	 *
	 * @param string $action      The action performed ("fulfill_orders" or "unfulfill_orders").
	 * @param int    $order_count The number of orders selected for the bulk action.
	 *
	 * @return void
	 */
	public static function track_fulfillment_bulk_action_used( string $action, int $order_count ): void {
		WC_Tracks::record_event(
			'fulfillment_bulk_action_used',
			array(
				'action'      => $action,
				'order_count' => $order_count,
			)
		);
	}

	/**
	 * Track when the orders list is filtered using a fulfillment-related filter.
	 *
	 * Tracked from: FulfillmentsRenderer::filter_orders_list_table_query().
	 * Measures: Whether merchants use fulfillment filters and which values they filter by most.
	 *
	 * @since 10.7.0
	 *
	 * @param string $filter_by    The filter field ("fulfillment_status" or "shipping_provider").
	 * @param string $filter_value The specific value selected (e.g., "partially_fulfilled", "usps").
	 *
	 * @return void
	 */
	public static function track_fulfillment_filter_used( string $filter_by, string $filter_value ): void {
		WC_Tracks::record_event(
			'fulfillment_filter_used',
			array(
				'filter_by'    => $filter_by,
				'filter_value' => $filter_value,
			)
		);
	}

	// ──────────────────────────────────────────────
	// 4. Customer Communication
	// ──────────────────────────────────────────────

	/**
	 * Track when a fulfillment notification email is successfully queued to a customer.
	 *
	 * Tracked from: OrderFulfillmentsRestController (create, update, and delete flows).
	 * Measures: Whether the communication loop is being closed; how often merchants notify customers.
	 *
	 * @since 10.7.0
	 *
	 * @param string $trigger_action The action that triggered the notification ("fulfillment_created", "fulfillment_updated", or "fulfillment_deleted").
	 * @param int    $fulfillment_id The ID of the fulfillment.
	 * @param int    $order_id       The ID of the associated order.
	 *
	 * @return void
	 */
	public static function track_fulfillment_notification_sent( string $trigger_action, int $fulfillment_id, int $order_id ): void {
		WC_Tracks::record_event(
			'fulfillment_notification_sent',
			array(
				'trigger_action' => $trigger_action,
				'fulfillment_id' => $fulfillment_id,
				'order_id'       => $order_id,
			)
		);
	}

	/**
	 * Track when a merchant saves changes to a fulfillment email template in settings.
	 *
	 * Tracked from: FulfillmentsManager (hooked to woocommerce_update_options_email_{id}).
	 * Measures: Whether merchants customize fulfillment email templates.
	 *
	 * @since 10.7.0
	 *
	 * @param string $template_name The email template ID that was customized (e.g., "customer_fulfillment_created").
	 *
	 * @return void
	 */
	public static function track_fulfillment_email_template_customized( string $template_name ): void {
		WC_Tracks::record_event(
			'fulfillment_email_template_customized',
			array(
				'template_name' => $template_name,
			)
		);
	}

	// ──────────────────────────────────────────────
	// 5. Friction & Error Tracking
	// ──────────────────────────────────────────────

	/**
	 * Track when a fulfillment action fails due to a validation error.
	 *
	 * Tracked from: OrderFulfillmentsRestController (create, update, and delete flows).
	 * Measures: Where users encounter errors; helps proactively identify bugs and UX problems.
	 *
	 * @since 10.7.0
	 *
	 * @param string $action_attempted The action that was attempted ("create", "update", "delete", or "fulfill").
	 * @param string $error_code       The error code from the exception.
	 * @param string $source           The source of the error ("fulfillments_modal", "bulk_action", or "api").
	 *
	 * @return void
	 */
	public static function track_fulfillment_validation_error( string $action_attempted, string $error_code, string $source ): void {
		WC_Tracks::record_event(
			'fulfillment_validation_error',
			array(
				'action_attempted' => $action_attempted,
				'error_code'       => $error_code,
				'source'           => $source,
			)
		);
	}

	// ──────────────────────────────────────────────
	// Helpers
	// ──────────────────────────────────────────────

	/**
	 * Determine the tracking entry method from the request source and fulfillment meta data.
	 *
	 * Maps the shipping option meta value to the standardized entry_method values expected
	 * by the fulfillment_tracking_added event. Whether the provider is custom or native is
	 * tracked separately via the is_custom_provider property on the event.
	 *
	 *   - "ui_auto_lookup" : Tracking number was auto-detected via the lookup API.
	 *   - "ui_manual"      : Merchant manually selected or entered a provider.
	 *   - "api"            : Tracking was added via the REST API (not through the UI).
	 *
	 * @since 10.7.0
	 *
	 * @param string $source          The request source ("fulfillments_modal" or "api").
	 * @param string $shipping_option The shipping option meta value ("tracking-number", "manual-entry", or "no-info").
	 *
	 * @return string The entry method identifier.
	 */
	public static function determine_tracking_entry_method( string $source, string $shipping_option ): string {
		if ( 'fulfillments_modal' !== $source ) {
			return 'api';
		}

		if ( 'tracking-number' === $shipping_option ) {
			return 'ui_auto_lookup';
		}

		if ( 'manual-entry' === $shipping_option ) {
			return 'ui_manual';
		}

		return 'api';
	}
}
