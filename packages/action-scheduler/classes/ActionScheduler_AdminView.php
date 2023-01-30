<?php

/**
 * Class ActionScheduler_AdminView
 * @codeCoverageIgnore
 */
class ActionScheduler_AdminView extends ActionScheduler_AdminView_Deprecated {

	private static $admin_view = NULL;

	private static $screen_id = 'tools_page_action-scheduler';

	/** @var ActionScheduler_ListTable */
	protected $list_table;

	/**
	 * @return ActionScheduler_AdminView
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
				add_action( 'woocommerce_system_status_report', array( $this, 'system_status_report' ) );
				add_filter( 'woocommerce_admin_status_tabs', array( $this, 'register_system_status_tab' ) );
			}

			add_action( 'admin_menu', array( $this, 'register_menu' ) );

			add_action( 'current_screen', array( $this, 'add_help_tabs' ) );
		}
	}

	public function system_status_report() {
		$table = new ActionScheduler_wcSystemStatus( ActionScheduler::store() );
		$table->render();
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
		$hook_suffix = add_submenu_page(
			'tools.php',
			__( 'Scheduled Actions', 'action-scheduler' ),
			__( 'Scheduled Actions', 'action-scheduler' ),
			'manage_options',
			'action-scheduler',
			array( $this, 'render_admin_ui' )
		);
		add_action( 'load-' . $hook_suffix , array( $this, 'process_admin_ui' ) );
	}

	/**
	 * Triggers processing of any pending actions.
	 */
	public function process_admin_ui() {
		$this->get_list_table();
	}

	/**
	 * Renders the Admin UI
	 */
	public function render_admin_ui() {
		$table = $this->get_list_table();
		$table->display_page();
	}

	/**
	 * Get the admin UI object and process any requested actions.
	 *
	 * @return ActionScheduler_ListTable
	 */
	protected function get_list_table() {
		if ( null === $this->list_table ) {
			$this->list_table = new ActionScheduler_ListTable( ActionScheduler::store(), ActionScheduler::logger(), ActionScheduler::runner() );
			$this->list_table->process_actions();
		}

		return $this->list_table;
	}

	/**
	 * Provide more information about the screen and its data in the help tab.
	 */
	public function add_help_tabs() {
		$screen = get_current_screen();

		if ( ! $screen || self::$screen_id != $screen->id ) {
			return;
		}

		$as_version = ActionScheduler_Versions::instance()->latest_version();
		$screen->add_help_tab(
			array(
				'id'      => 'action_scheduler_about',
				'title'   => __( 'About', 'action-scheduler' ),
				'content' =>
					'<h2>' . sprintf( __( 'About Action Scheduler %s', 'action-scheduler' ), $as_version ) . '</h2>' .
					'<p>' .
						__( 'Action Scheduler is a scalable, traceable job queue for background processing large sets of actions. Action Scheduler works by triggering an action hook to run at some time in the future. Scheduled actions can also be scheduled to run on a recurring schedule.', 'action-scheduler' ) .
					'</p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'action_scheduler_columns',
				'title'   => __( 'Columns', 'action-scheduler' ),
				'content' =>
					'<h2>' . __( 'Scheduled Action Columns', 'action-scheduler' ) . '</h2>' .
					'<ul>' .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Hook', 'action-scheduler' ), __( 'Name of the action hook that will be triggered.', 'action-scheduler' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Status', 'action-scheduler' ), __( 'Action statuses are Pending, Complete, Canceled, Failed', 'action-scheduler' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Arguments', 'action-scheduler' ), __( 'Optional data array passed to the action hook.', 'action-scheduler' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Group', 'action-scheduler' ), __( 'Optional action group.', 'action-scheduler' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Recurrence', 'action-scheduler' ), __( 'The action\'s schedule frequency.', 'action-scheduler' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Scheduled', 'action-scheduler' ), __( 'The date/time the action is/was scheduled to run.', 'action-scheduler' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Log', 'action-scheduler' ), __( 'Activity log for the action.', 'action-scheduler' ) ) .
					'</ul>',
			)
		);
	}
}
