<?php
/**
 * WooCommerce Customer effort score tracks
 *
 * @package WooCommerce\Admin\Features
 */

namespace Automattic\WooCommerce\Admin\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Triggers customer effort score on several different actions.
 */
class CustomerEffortScoreTracks {
	/**
	 * Option name for the CES Tracks queue.
	 */
	const CES_TRACKS_QUEUE_OPTION_NAME = 'woocommerce_ces_tracks_queue';

	/**
	 * Option name for the clear CES Tracks queue for page.
	 */
	const CLEAR_CES_TRACKS_QUEUE_FOR_PAGE_OPTION_NAME =
		'woocommerce_clear_ces_tracks_queue_for_page';

	/**
	 * Option name for the set of actions that have been shown.
	 */
	const SHOWN_FOR_ACTIONS_OPTION_NAME = 'woocommerce_ces_shown_for_actions';

	/**
	 * Action name for product add/publish.
	 */
	const PRODUCT_ADD_PUBLISH_ACTION_NAME = 'product_add_publish';

	/**
	 * Action name for product update.
	 */
	const PRODUCT_UPDATE_ACTION_NAME = 'product_update';

	/**
	 * Action name for shop order update.
	 */
	const SHOP_ORDER_UPDATE_ACTION_NAME = 'shop_order_update';

	/**
	 * Action name for settings change.
	 */
	const SETTINGS_CHANGE_ACTION_NAME = 'settings_change';

	/**
	 * Label for the snackbar that appears when a user submits the survey.
	 *
	 * @var string
	 */
	private $onsubmit_label;


	/**
	 * Constructor. Sets up filters to hook into WooCommerce.
	 */
	public function __construct() {
		$this->enable_survey_enqueing_if_tracking_is_enabled();
	}

	/**
	 * Add actions that require woocommerce_allow_tracking.
	 */
	private function enable_survey_enqueing_if_tracking_is_enabled() {
		// Only hook up the action handlers if in wp-admin.
		if ( ! is_admin() ) {
			return;
		}

		// Do not hook up the action handlers if a mobile device is used.
		if ( wp_is_mobile() ) {
			return;
		}

		// Only enqueue a survey if tracking is allowed.
		$allow_tracking = 'yes' === get_option( 'woocommerce_allow_tracking', 'no' );
		if ( ! $allow_tracking ) {
			return;
		}

		add_action(
			'admin_init',
			array(
				$this,
				'maybe_clear_ces_tracks_queue',
			)
		);

		// Only hook up the transition_post_status action handler
		// if on the edit page.
		global $pagenow;
		if ( 'post.php' === $pagenow ) {
			add_action(
				'transition_post_status',
				array(
					$this,
					'run_on_transition_post_status',
				),
				10,
				3
			);
		}

		add_action(
			'woocommerce_update_options',
			array(
				$this,
				'run_on_update_options',
			),
			10,
			3
		);

		$this->onsubmit_label = __( 'Thank you for your feedback!', 'woocommerce' );
	}

	/**
	 * Hook into the post status lifecycle, to detect relevant user actions
	 * that we want to survey about.
	 *
	 * @param string $new_status The new status.
	 * @param string $old_status The old status.
	 * @param Post   $post The post.
	 */
	public function run_on_transition_post_status(
		$new_status,
		$old_status,
		$post
	) {
		if ( 'product' === $post->post_type ) {
			$this->maybe_enqueue_ces_survey_for_product( $new_status, $old_status );
		} elseif ( 'shop_order' === $post->post_type ) {
			$this->enqueue_ces_survey_for_edited_shop_order();
		}
	}

	/**
	 * Maybe enqueue the CES survey, if product is being added or edited.
	 *
	 * @param string $new_status The new status.
	 * @param string $old_status The old status.
	 */
	private function maybe_enqueue_ces_survey_for_product(
		$new_status,
		$old_status
	) {
		if ( 'publish' !== $new_status ) {
			return;
		}

		if ( 'publish' !== $old_status ) {
			$this->enqueue_ces_survey_for_new_product();
		} else {
			$this->enqueue_ces_survey_for_edited_product();
		}
	}

	/**
	 * Get the current published product count.
	 *
	 * @return integer The current published product count.
	 */
	private function get_product_count() {
		$query         = new \WC_Product_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
				'status'   => array( 'publish' ),
			)
		);
		$products      = $query->get_products();
		$product_count = intval( $products->total );

		return $product_count;
	}

	/**
	 * Get the current shop order count.
	 *
	 * @return integer The current shop order count.
	 */
	private function get_shop_order_count() {
		$query            = new \WC_Order_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
			)
		);
		$shop_orders      = $query->get_orders();
		$shop_order_count = intval( $shop_orders->total );

		return $shop_order_count;
	}

	/**
	 * Return whether the action has already been shown.
	 *
	 * @param string $action The action to check.
	 *
	 * @return bool Whether the action has already been shown.
	 */
	private function has_been_shown( $action ) {
		$shown_for_features = get_option( self::SHOWN_FOR_ACTIONS_OPTION_NAME, array() );
		$has_been_shown     = in_array( $action, $shown_for_features, true );

		return $has_been_shown;
	}

	/**
	 * Enqueue the item to the CES tracks queue.
	 *
	 * @param array $item The item to enqueue.
	 */
	private function enqueue_to_ces_tracks( $item ) {
		$queue = get_option(
			self::CES_TRACKS_QUEUE_OPTION_NAME,
			array()
		);

		$has_duplicate = array_filter(
			$queue,
			function ( $queue_item ) use ( $item ) {
				return $queue_item['action'] === $item['action'];
			}
		);
		if ( $has_duplicate ) {
			return;
		}

		$queue[] = $item;

		update_option(
			self::CES_TRACKS_QUEUE_OPTION_NAME,
			$queue
		);
	}

	/**
	 * Enqueue the CES survey trigger for a new product.
	 */
	private function enqueue_ces_survey_for_new_product() {
		if ( $this->has_been_shown( self::PRODUCT_ADD_PUBLISH_ACTION_NAME ) ) {
			return;
		}

		$this->enqueue_to_ces_tracks(
			array(
				'action'         => self::PRODUCT_ADD_PUBLISH_ACTION_NAME,
				'label'          => __(
					'How easy was it to add a product?',
					'woocommerce'
				),
				'onsubmit_label' => $this->onsubmit_label,
				'pagenow'        => 'product',
				'adminpage'      => 'post-php',
				'props'          => array(
					'product_count' => $this->get_product_count(),
				),
			)
		);
	}

	/**
	 * Enqueue the CES survey trigger for an existing product.
	 */
	private function enqueue_ces_survey_for_edited_product() {
		if ( $this->has_been_shown( self::PRODUCT_UPDATE_ACTION_NAME ) ) {
			return;
		}

		$this->enqueue_to_ces_tracks(
			array(
				'action'         => self::PRODUCT_UPDATE_ACTION_NAME,
				'label'          => __(
					'How easy was it to edit your product?',
					'woocommerce'
				),
				'onsubmit_label' => $this->onsubmit_label,
				'pagenow'        => 'product',
				'adminpage'      => 'post-php',
				'props'          => array(
					'product_count' => $this->get_product_count(),
				),
			)
		);
	}

	/**
	 * Enqueue the CES survey trigger for an existing shop order.
	 */
	private function enqueue_ces_survey_for_edited_shop_order() {
		if ( $this->has_been_shown( self::SHOP_ORDER_UPDATE_ACTION_NAME ) ) {
			return;
		}

		$this->enqueue_to_ces_tracks(
			array(
				'action'         => self::SHOP_ORDER_UPDATE_ACTION_NAME,
				'label'          => __(
					'How easy was it to update an order?',
					'woocommerce'
				),
				'onsubmit_label' => $this->onsubmit_label,
				'pagenow'        => 'shop_order',
				'adminpage'      => 'post-php',
				'props'          => array(
					'order_count' => $this->get_shop_order_count(),
				),
			)
		);
	}


	/**
	 * Maybe clear the CES tracks queue, executed on every page load. If the
	 * clear option is set it clears the queue. In practice, this executes a
	 * page load after the queued CES tracks are displayed on the client, which
	 * sets the clear option.
	 */
	public function maybe_clear_ces_tracks_queue() {
		$clear_ces_tracks_queue_for_page = get_option(
			self::CLEAR_CES_TRACKS_QUEUE_FOR_PAGE_OPTION_NAME,
			false
		);

		if ( ! $clear_ces_tracks_queue_for_page ) {
			return;
		}

		$queue           = get_option(
			self::CES_TRACKS_QUEUE_OPTION_NAME,
			array()
		);
		$remaining_items = array_filter(
			$queue,
			function ( $item ) use ( $clear_ces_tracks_queue_for_page ) {
				return $clear_ces_tracks_queue_for_page['pagenow'] !== $item['pagenow']
				|| $clear_ces_tracks_queue_for_page['adminpage'] !== $item['adminpage'];
			}
		);

		update_option(
			self::CES_TRACKS_QUEUE_OPTION_NAME,
			array_values( $remaining_items )
		);
		update_option( self::CLEAR_CES_TRACKS_QUEUE_FOR_PAGE_OPTION_NAME, false );
	}

	/**
	 * Enqueue the CES survey trigger for setting changes.
	 */
	public function run_on_update_options() {
		if ( $this->has_been_shown( self::SETTINGS_CHANGE_ACTION_NAME ) ) {
			return;
		}

		$this->enqueue_to_ces_tracks(
			array(
				'action'         => self::SETTINGS_CHANGE_ACTION_NAME,
				'label'          => __(
					'How easy was it to update your settings?',
					'woocommerce'
				),
				'onsubmit_label' => $this->onsubmit_label,
				'pagenow'        => 'woocommerce_page_wc-settings',
				'adminpage'      => 'woocommerce_page_wc-settings',
				'props'          => (object) array(),
			)
		);
	}
}
