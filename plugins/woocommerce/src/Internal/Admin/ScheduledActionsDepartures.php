<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin;

use ActionScheduler;
use ActionScheduler_NullAction;
use ActionScheduler_Store;

/**
 * An optional, read-only departures board for scheduled actions.
 *
 * @internal
 */
final class ScheduledActionsDepartures {

	/**
	 * Register the board without changing Action Scheduler's admin callbacks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
		add_action( 'woocommerce_admin_status_content_action-scheduler', array( $this, 'render_link' ), 5 );
	}

	/**
	 * Register an admin page reachable only through its direct link.
	 *
	 * @internal
	 */
	public function register_page(): void {
		$hook = add_submenu_page(
			'',
			__( 'Scheduled action departures', 'woocommerce' ),
			__( 'Scheduled action departures', 'woocommerce' ),
			'manage_options',
			'wc-action-scheduler-departures',
			array( $this, 'render' )
		);
		if ( $hook ) {
			add_action( 'load-' . $hook, array( $this, 'enqueue_assets' ) );
		}
	}

	/**
	 * Load styles only on the departures page.
	 *
	 * @internal
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style( 'woocommerce-action-departures', WC()->plugin_url() . '/assets/css/action-departures.css', array(), WC_VERSION );
		add_filter( 'admin_title', array( $this, 'page_title' ) );
	}

	/**
	 * Name the hidden page in the browser tab.
	 *
	 * @internal
	 *
	 * @param mixed $admin_title Existing admin title.
	 * @return string
	 */
	public function page_title( $admin_title ): string {
		return __( 'Scheduled action departures', 'woocommerce' ) . ( is_string( $admin_title ) ? $admin_title : '' );
	}

	/**
	 * Offer a small detour from the standard Scheduled Actions screen.
	 *
	 * @internal
	 */
	public function render_link(): void {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wc-action-scheduler-departures' ) ) . '">' . esc_html__( 'Watch the departures ↗', 'woocommerce' ) . '</a></p>';
		}
	}

	/**
	 * Render a bounded snapshot; no action management or background refresh.
	 *
	 * @internal
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view scheduled actions.', 'woocommerce' ) );
		}

		if ( ! class_exists( ActionScheduler::class ) || ! ActionScheduler::is_initialized() ) {
			return;
		}

		$statuses = array(
			ActionScheduler_Store::STATUS_PENDING  => __( 'Upcoming', 'woocommerce' ),
			ActionScheduler_Store::STATUS_RUNNING  => __( 'In progress', 'woocommerce' ),
			ActionScheduler_Store::STATUS_FAILED   => __( 'Failed', 'woocommerce' ),
			ActionScheduler_Store::STATUS_COMPLETE => __( 'Complete', 'woocommerce' ),
			ActionScheduler_Store::STATUS_CANCELED => __( 'Canceled', 'woocommerce' ),
		);
		// This query parameter selects a read-only view, so no nonce is needed.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status   = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$status   = isset( $statuses[ $status ] ) ? $status : ActionScheduler_Store::STATUS_PENDING;
		$store    = ActionScheduler::store();
		$ids      = $store->query_actions(
			array(
				'status'   => $status,
				'per_page' => 17,
				'orderby'  => 'date',
				'order'    => in_array( $status, array( ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING ), true ) ? 'ASC' : 'DESC',
			)
		);
		$has_more = count( $ids ) > 16;
		$rows     = array();
		$now      = time();
		$labels   = $store->get_status_labels();
		foreach ( array_slice( $ids, 0, 16 ) as $id ) {
			$action = $store->fetch_action( $id );
			if ( $action instanceof ActionScheduler_NullAction ) {
				continue;
			}
			$date  = $action->get_schedule()->get_date();
			$label = $labels[ $status ];
			if ( ActionScheduler_Store::STATUS_PENDING === $status ) {
				$label = $date && $date->getTimestamp() > $now ? __( 'Scheduled', 'woocommerce' ) : __( 'Awaiting run', 'woocommerce' );
			}
			$rows[] = array(
				'id'    => $id,
				'hook'  => $action->get_hook(),
				'group' => $action->get_group(),
				'date'  => $date,
				'label' => $label,
			);
		}
		$board_url = admin_url( 'admin.php?page=wc-action-scheduler-departures' );
		$list_url  = admin_url( 'admin.php?page=wc-status&tab=action-scheduler' );
		include __DIR__ . '/views/html-admin-action-departures.php';
	}

	/**
	 * Render decorative split-flap tiles with one accessible text equivalent.
	 *
	 * @param string $text The text to display.
	 * @phpstan-ignore method.unused (Called from the included view in this class's scope.)
	 */
	private static function render_flaps( string $text ): void {
		echo '<span class="screen-reader-text">' . esc_html( $text ) . '</span><span class="wc-departures__flaps" aria-hidden="true" dir="auto">';
		// Keep joined scripts and combining characters together in translations.
		$characters = 1 === preg_match( '/^[\x20-\x7E]+$/D', $text ) ? str_split( $text ) : array( $text );
		foreach ( $characters as $character ) {
			echo '<span class="wc-departures__flap">' . esc_html( $character ) . '</span>';
		}
		echo '</span>';
	}
}
