<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\Fulfillments;

use WC_Tracks;

/**
 * FulfillmentsTracker class.
 */
class FulfillmentsTracker {
	/**
	 * Track the creation of a fulfillment.
	 *
	 * @param string $source The source of the fulfillment ( "fulfillment_modal", "bulk_action", or "api" ).
	 * @param string $initial_status The initial status of the fulfillment ( "draft", or "fulfilled" ).
	 * @param string $fulfillment_type The type of fulfillment ( "full", or "partial", based on all remaining items were included).
	 * @param int    $item_count The number of items in the fulfillment.
	 * @param int    $total_quantity The total quantity of items in the fulfillment.
	 * @param bool   $notification_sent Whether a notification was sent for the fulfillment.
	 *
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
	 * Track the update of a fulfillment.
	 *
	 * @param string $source The source of the fulfillment update ( "fulfillment_modal", or "api" ).
	 * @param int    $fulfillment_id The ID of the fulfillment being updated.
	 * @param string $original_status The original status of the fulfillment before the update.
	 * @param array  $changed_fields The fields that were changed in the fulfillment.
	 * @param bool   $notification_sent Whether a notification was sent for the update.
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
	 * Track the deletion of a fulfillment.
	 *
	 * @param string $source The source of the fulfillment deletion ( "fulfillment_modal", or "api" ).
	 * @param int    $fulfillment_id The ID of the fulfillment being deleted.
	 * @param string $status_at_deletion The status of the fulfillment at the time of deletion.
	 * @param bool   $notification_sent Whether a notification was sent for the deletion.
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

	/**
	 * Track the addition of tracking information to a fulfillment.
	 *
	 * @param int    $fulfillment_id The ID of the fulfillment to which tracking was added.
	 * @param string $entry_method The method by which the tracking was added ( "ui_auto_lookup", "ui_manual_select", "ui_manual_custom", or "api" ).
	 * @param string $provider_name The name of the shipping provider for the tracking.
	 * @param bool   $is_custom_provider Whether the provider is a custom provider.
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
	 * Track the lookup attempt for fulfillment tracking.
	 *
	 * @param string $lookup_status The status of the lookup attempt ( "success", "failure", or "not_found" ).
	 * @param string $provider_identified The provider identified during the lookup.
	 *
	 * @return void
	 */
	public static function track_fulfillment_tracking_lookup_attempt( string $lookup_status, string $provider_identified ) {
		WC_Tracks::record_event(
			'fulfillment_tracking_lookup_attempted',
			array(
				'lookup_status'       => $lookup_status,
				'provider_identified' => $provider_identified,
			)
		);
	}

	/**
	 * Track the usage of a bulk action on fulfillments.
	 *
	 * @param string $action The action performed ( "fulfill_orders", "unfulfill_orders" ).
	 * @param int    $order_count The number of orders affected by the bulk action.
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
	 * Track the usage of a filter in the fulfillment list.
	 *
	 * @param string $filter_by The field by which the fulfillment list is filtered ( "fulfillment_status", "shipping_provider" ).
	 * @param string $filter_value The value of the filter applied.
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

	/**
	 * Track the sending of a fulfillment notification.
	 *
	 * @param string $trigger_action The action that triggered the notification ( "fulfillment_created", "fulfillment_updated", "fulfillment_deleted" ).
	 * @param int    $fulfillment_id The ID of the fulfillment for which the notification was sent.
	 * @param int    $order_id The ID of the order associated with the fulfillment.
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
	 * Track the customization of a fulfillment email template.
	 *
	 * @param string $template_name The name of the email template that was customized.
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

	/**
	 * Track a validation error during fulfillment processing.
	 *
	 * @param string $action_attempted The action that was attempted ( "create", "update", "delete", "fulfill" ).
	 * @param string $error_code The error code associated with the validation error.
	 * @param string $source The source of the validation error ( "fulfillment_modal", "bulk_action", "api" ).
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
}
