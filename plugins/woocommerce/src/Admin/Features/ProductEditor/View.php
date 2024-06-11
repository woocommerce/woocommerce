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
        ob_start();
        ?>
        <div data-wp-interactive="woocommerce/product-editor" data-wp-watch="actions.loadProduct">
            <div data-wp-bind--hidden="!state.loading">
                Loading...
            </div>
            <div data-wp-bind--hidden="state.loading">
                <?php self::render_tabs(); ?>
                <?php self::render_header(); ?>
                <?php self::render_tabs_content(); ?>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }

    /**
     * Render the header.
     */
    private static function render_header() {
        ob_start();
        ?>
        <button data-wp-on--click="actions.persistProduct">
            Save
        </button>
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
                <li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $tab['class'] ) ? implode( ' ', (array) $tab['class'] ) : '' ); ?>">
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
