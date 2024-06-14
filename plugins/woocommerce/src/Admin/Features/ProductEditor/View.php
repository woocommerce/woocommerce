<?php
/**
 * WooCommerce Block Editor
 */

namespace Automattic\WooCommerce\Admin\Features\ProductEditor;

/**
 * Loads assets related to the block editor.
 */
class View {
    /**
     * Render the entire view.
     */
    public static function render() {
        self::hydrate_initial_state();
        ob_start();
        ?>
        <div data-wp-interactive="woocommerce/product-editor" data-wp-watch="actions.loadProduct">
            <div data-wp-bind--hidden="!state.loading">
                Loading...
            </div>
            <div data-wp-bind--hidden="state.loading">
                <?php self::render_header(); ?>
                <?php self::render_tabs_content(); ?>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }

    /**
     * Hydrate initial state.
     */
    public static function hydrate_initial_state() {
        global $product_object;
        global $post;

        if ( isset( $_GET['id'] ) ) {
            $product_object = wc_get_product( $_GET['id'] );
            $post = get_post( $_GET['id'] );
        }

        $products_controller = new \WC_REST_Products_Controller();

        wp_interactivity_state(
            'woocommerce/product-editor',
            array(
                'activeTab' => 'general',
                'product'   => $products_controller->prepare_object_for_response( $product_object, new \WP_REST_Request() )->get_data(),
                'loading'   => false,
            )
        );
    }

    /**
     * Render the header.
     */
    private static function render_header() {
        ob_start();
        ?>
        <div class="product-editor__header">
            <div class="product-editor__header-inner">
                <div></div>
                <div class="product-editor__header-title-bar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" d="M5 5.5h14a.5.5 0 01.5.5v1.5a.5.5 0 01-.5.5H5a.5.5 0 01-.5-.5V6a.5.5 0 01.5-.5zM4 9.232A2 2 0 013 7.5V6a2 2 0 012-2h14a2 2 0 012 2v1.5a2 2 0 01-1 1.732V18a2 2 0 01-2 2H6a2 2 0 01-2-2V9.232zm1.5.268V18a.5.5 0 00.5.5h12a.5.5 0 00.5-.5V9.5h-13z" clip-rule="evenodd"></path></svg>
                    <span class="product-editor__header-title-bar-text" data-wp-text="state.product.name"></span>
                </div>
                <div class="product-editor__header-actions">
                    <button data-wp-on--click="actions.persistProduct">
                        <?php _e( 'Update', 'woocommerce' ); ?>
                    </button>
                </div>
            </div>
            <?php self::render_tabs(); ?>
        </div>
        <?php
        echo ob_get_clean();
    }

    /**
     * Render the tabs.
     */
    private static function render_tabs() {
        ob_start();
        ?>
        <ul class="product_data_tabs wc-tabs">
            <?php foreach ( self::get_product_data_tabs() as $key => $tab ) : ?>
                <li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $tab['class'] ) ? implode( ' ', (array) $tab['class'] ) : '' ); ?>" data-wp-class--is-active="selectors.isTabActive" data-wp-context='{"id": "<?php echo $key; ?>"}'>
                    <a href="#<?php echo esc_attr( $tab['target'] ); ?>" data-wp-on--click="actions.setActiveTab" data-wp-context='{"id": "<?php echo $key; ?>"}'><span><?php echo esc_html( $tab['label'] ); ?></span></a>
                </li>
            <?php endforeach; ?>
            <?php do_action( 'woocommerce_product_write_panel_tabs' ); ?>
        </ul>
        <?php
        echo ob_get_clean();
    }

    private static function render_tabs_content() {
		include __DIR__ . '/Tabs/general.php';
    }

    /**
    * Return array of tabs to show.
    *
    * @return array
    */
   private static function get_product_data_tabs() {
       /* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */
       $tabs = apply_filters(
           'woocommerce_product_data_tabs',
           array(
               'general'        => array(
                   'label'    => __( 'General', 'woocommerce' ),
                   'target'   => 'general_product_data',
                   'class'    => array( 'hide_if_grouped' ),
                   'priority' => 10,
               ),
               'inventory'      => array(
                   'label'    => __( 'Inventory', 'woocommerce' ),
                   'target'   => 'inventory_product_data',
                   'class'    => array( 'show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external' ),
                   'priority' => 20,
               ),
               'shipping'       => array(
                   'label'    => __( 'Shipping', 'woocommerce' ),
                   'target'   => 'shipping_product_data',
                   'class'    => array( 'hide_if_virtual', 'hide_if_grouped', 'hide_if_external' ),
                   'priority' => 30,
               ),
               'linked_product' => array(
                   'label'    => __( 'Linked Products', 'woocommerce' ),
                   'target'   => 'linked_product_data',
                   'class'    => array(),
                   'priority' => 40,
               ),
               'attribute'      => array(
                   'label'    => __( 'Attributes', 'woocommerce' ),
                   'target'   => 'product_attributes',
                   'class'    => array(),
                   'priority' => 50,
               ),
               'variations'     => array(
                   'label'    => __( 'Variations', 'woocommerce' ),
                   'target'   => 'variable_product_options',
                   'class'    => array( 'show_if_variable' ),
                   'priority' => 60,
               ),
               'advanced'       => array(
                   'label'    => __( 'Advanced', 'woocommerce' ),
                   'target'   => 'advanced_product_data',
                   'class'    => array(),
                   'priority' => 70,
               ),
           )
       );
       /* phpcs: enable */

       // Sort tabs based on priority.
       uasort( $tabs, array( __CLASS__, 'product_data_tabs_sort' ) );

       return $tabs;
   }

   	/**
	 * Callback to sort product data tabs on priority.
	 *
	 * @since 3.1.0
	 * @param int $a First item.
	 * @param int $b Second item.
	 *
	 * @return bool
	 */
	private static function product_data_tabs_sort( $a, $b ) {
		if ( ! isset( $a['priority'], $b['priority'] ) ) {
			return -1;
		}

		if ( $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? -1 : 1;
	}
}
