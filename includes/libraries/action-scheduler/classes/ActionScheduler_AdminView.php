<?php

/**
 * Class ActionScheduler_AdminView
 * @codeCoverageIgnore
 */
class ActionScheduler_AdminView extends ActionScheduler_AdminView_Deprecated {

	private static $admin_view = NULL;

	/**
	 * @return ActionScheduler_QueueRunner
	 * @codeCoverageIgnore
	 */
	public static function instance() {

		if ( empty( self::$admin_view ) ) {
			$class = apply_filters('action_scheduler_admin_view_class', 'ActionScheduler_AdminView');
			self::$admin_view = new $class();
		}

		return self::$admin_view;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function init() {
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || false == DOING_AJAX ) ) {

			if ( class_exists( 'WooCommerce' ) ) {
				add_action( 'woocommerce_admin_status_content_action-scheduler', array( $this, 'render_admin_ui' ) );
				add_filter( 'woocommerce_admin_status_tabs', array( $this, 'register_system_status_tab' ) );
			}

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
		}
	}


	/**
	 * Registers action-scheduler into WooCommerce > System status.
	 *
	 * @param array $tabs An associative array of tab key => label.
	 * @return array $tabs An associative array of tab key => label, including Action Scheduler's tabs
	 */
	public function register_system_status_tab( array $tabs ) {
		$tabs['action-scheduler'] = __( 'Scheduled Actions', 'action-scheduler' );

		return $tabs;
	}

	/**
	 * Include Action Scheduler's administration under the Tools menu.
	 *
	 * A menu under the Tools menu is important for backward compatibility (as that's
	 * where it started), and also provides more convenient access than the WooCommerce
	 * System Status page, and for sites where WooCommerce isn't active.
	 */
	public function register_menu() {
		add_submenu_page(
			'tools.php',
			__( 'Scheduled Actions', 'action-scheduler' ),
			__( 'Scheduled Actions', 'action-scheduler' ),
			'manage_options',
			'action-scheduler',
			array( $this, 'render_admin_ui' )
		);
	}

	/**
	 * Renders the Admin UI
	 */
	public function render_admin_ui() {
		$table = new ActionScheduler_ListTable( ActionScheduler::store(), ActionScheduler::logger(), ActionScheduler::runner() );
		$table->display_page();
	}
}
