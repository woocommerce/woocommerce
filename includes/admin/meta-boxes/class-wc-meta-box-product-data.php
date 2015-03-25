<?php
/**
 * Product Data
 *
 * Displays the product data box, tabbed, with several panels covering price, stock etc.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Product_Data Class
 */
class WC_Meta_Box_Product_Data {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $post, $thepostid;

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

		$thepostid = $post->ID;

		if ( $terms = wp_get_object_terms( $post->ID, 'product_type' ) ) {
			$product_type = sanitize_title( current( $terms )->name );
		} else {
			$product_type = apply_filters( 'default_product_type', 'simple' );
		}

		$product_type_selector = apply_filters( 'product_type_selector', array(
			'simple'   => __( 'Simple product', 'woocommerce' ),
			'grouped'  => __( 'Grouped product', 'woocommerce' ),
			'external' => __( 'External/Affiliate product', 'woocommerce' ),
			'variable' => __( 'Variable product', 'woocommerce' )
		), $product_type );

		$type_box = '<label for="product-type"><select id="product-type" name="product-type"><optgroup label="' . __( 'Product Type', 'woocommerce' ) . '">';

		foreach ( $product_type_selector as $value => $label ) {
			$type_box .= '<option value="' . esc_attr( $value ) . '" ' . selected( $product_type, $value, false ) .'>' . esc_html( $label ) . '</option>';
		}

		$type_box .= '</optgroup></select></label>';

		$product_type_options = apply_filters( 'product_type_options', array(
			'virtual' => array(
				'id'            => '_virtual',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Virtual', 'woocommerce' ),
				'description'   => __( 'Virtual products are intangible and aren\'t shipped.', 'woocommerce' ),
				'default'       => 'no'
			),
			'downloadable' => array(
				'id'            => '_downloadable',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Downloadable', 'woocommerce' ),
				'description'   => __( 'Downloadable products give access to a file upon purchase.', 'woocommerce' ),
				'default'       => 'no'
			)
		) );

		foreach ( $product_type_options as $key => $option ) {
			$selected_value = get_post_meta( $post->ID, '_' . $key, true );

			if ( '' == $selected_value && isset( $option['default'] ) ) {
				$selected_value = $option['default'];
			}

			$type_box .= '<label for="' . esc_attr( $option['id'] ) . '" class="'. esc_attr( $option['wrapper_class'] ) . ' tips" data-tip="' . esc_attr( $option['description'] ) . '">' . esc_html( $option['label'] ) . ': <input type="checkbox" name="' . esc_attr( $option['id'] ) . '" id="' . esc_attr( $option['id'] ) . '" ' . checked( $selected_value, 'yes', false ) .' /></label>';
		}

		?>
		<div class="panel-wrap product_data">

			<span class="type_box"> &mdash; <?php echo $type_box; ?></span>

			<ul class="product_data_tabs wc-tabs" style="display:none;">
				<?php
					$product_data_tabs = apply_filters( 'woocommerce_product_data_tabs', array(
						'general' => array(
							'label'  => __( 'General', 'woocommerce' ),
							'target' => 'general_product_data',
							'class'  => array( 'hide_if_grouped' ),
						),
						'inventory' => array(
							'label'  => __( 'Inventory', 'woocommerce' ),
							'target' => 'inventory_product_data',
							'class'  => array( 'show_if_simple', 'show_if_variable', 'show_if_grouped' ),
						),
						'shipping' => array(
							'label'  => __( 'Shipping', 'woocommerce' ),
							'target' => 'shipping_product_data',
							'class'  => array( 'hide_if_virtual', 'hide_if_grouped', 'hide_if_external' ),
						),
						'linked_product' => array(
							'label'  => __( 'Linked Products', 'woocommerce' ),
							'target' => 'linked_product_data',
							'class'  => array(),
						),
						'attribute' => array(
							'label'  => __( 'Attributes', 'woocommerce' ),
							'target' => 'product_attributes',
							'class'  => array(),
						),
						'variations' => array(
							'label'  => __( 'Variations', 'woocommerce' ),
							'target' => 'variable_product_options',
							'class'  => array( 'variations_tab', 'show_if_variable' ),
						),
						'advanced' => array(
							'label'  => __( 'Advanced', 'woocommerce' ),
							'target' => 'advanced_product_data',
							'class'  => array(),
						)
					) );

					foreach ( $product_data_tabs as $key => $tab ) {
						?><li class="<?php echo $key; ?>_options <?php echo $key; ?>_tab <?php echo implode( ' ' , $tab['class'] ); ?>">
							<a href="#<?php echo $tab['target']; ?>"><?php echo esc_html( $tab['label'] ); ?></a>
						</li><?php
					}

					do_action( 'woocommerce_product_write_panel_tabs' );
				?>
			</ul>
			<div id="general_product_data" class="panel woocommerce_options_panel"><?php

				echo '<div class="options_group hide_if_grouped">';

					// SKU
					if ( wc_product_sku_enabled() ) {
						woocommerce_wp_text_input( array( 'id' => '_sku', 'label' => '<abbr title="'. __( 'Stock Keeping Unit', 'woocommerce' ) .'">' . __( 'SKU', 'woocommerce' ) . '</abbr>', 'desc_tip' => 'true', 'description' => __( 'SKU refers to a Stock-keeping unit, a unique identifier for each distinct product and service that can be purchased.', 'woocommerce' ) ) );
					} else {
						echo '<input type="hidden" name="_sku" value="' . esc_attr( get_post_meta( $thepostid, '_sku', true ) ) . '" />';
					}

					do_action( 'woocommerce_product_options_sku' );

				echo '</div>';

				echo '<div class="options_group show_if_external">';

					// External URL
					woocommerce_wp_text_input( array( 'id' => '_product_url', 'label' => __( 'Product URL', 'woocommerce' ), 'placeholder' => 'http://', 'description' => __( 'Enter the external URL to the product.', 'woocommerce' ) ) );

					// Button text
					woocommerce_wp_text_input( array( 'id' => '_button_text', 'label' => __( 'Button text', 'woocommerce' ), 'placeholder' => _x('Buy product', 'placeholder', 'woocommerce'), 'description' => __( 'This text will be shown on the button linking to the external product.', 'woocommerce' ) ) );

				echo '</div>';

				echo '<div class="options_group pricing show_if_simple show_if_external">';

					// Price
					woocommerce_wp_text_input( array( 'id' => '_regular_price', 'label' => __( 'Regular Price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );

					// Special Price
					woocommerce_wp_text_input( array( 'id' => '_sale_price', 'data_type' => 'price', 'label' => __( 'Sale Price', 'woocommerce' ) . ' ('.get_woocommerce_currency_symbol().')', 'description' => '<a href="#" class="sale_schedule">' . __( 'Schedule', 'woocommerce' ) . '</a>' ) );

					// Special Price date range
					$sale_price_dates_from = ( $date = get_post_meta( $thepostid, '_sale_price_dates_from', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';
					$sale_price_dates_to   = ( $date = get_post_meta( $thepostid, '_sale_price_dates_to', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';

					echo '<p class="form-field sale_price_dates_fields">
								<label for="_sale_price_dates_from">' . __( 'Sale Price Dates', 'woocommerce' ) . '</label>
								<input type="text" class="short" name="_sale_price_dates_from" id="_sale_price_dates_from" value="' . esc_attr( $sale_price_dates_from ) . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
								<input type="text" class="short" name="_sale_price_dates_to" id="_sale_price_dates_to" value="' . esc_attr( $sale_price_dates_to ) . '" placeholder="' . _x( 'To&hellip;', 'placeholder', 'woocommerce' ) . '  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
								<a href="#" class="cancel_sale_schedule">'. __( 'Cancel', 'woocommerce' ) .'</a>
								<img class="help_tip" style="margin-top: 21px;" data-tip="' . __( 'The sale will end at the beginning of the set date.', 'woocommerce' ) . '" src="' . esc_url( WC()->plugin_url() ) . '/assets/images/help.png" height="16" width="16" />
							</p>';

					do_action( 'woocommerce_product_options_pricing' );

				echo '</div>';

				echo '<div class="options_group show_if_downloadable">';

					?>
					<div class="form-field downloadable_files">
						<label><?php _e( 'Downloadable Files', 'woocommerce' ); ?>:</label>
						<table class="widefat">
							<thead>
								<tr>
									<th class="sort">&nbsp;</th>
									<th><?php _e( 'Name', 'woocommerce' ); ?> <span class="tips" data-tip="<?php _e( 'This is the name of the download shown to the customer.', 'woocommerce' ); ?>">[?]</span></th>
									<th colspan="2"><?php _e( 'File URL', 'woocommerce' ); ?> <span class="tips" data-tip="<?php _e( 'This is the URL or absolute path to the file which customers will get access to.', 'woocommerce' ); ?>">[?]</span></th>
									<th>&nbsp;</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$downloadable_files = get_post_meta( $post->ID, '_downloadable_files', true );

								if ( $downloadable_files ) {
									foreach ( $downloadable_files as $key => $file ) {
										include( 'views/html-product-download.php' );
									}
								}
								?>
							</tbody>
							<tfoot>
								<tr>
									<th colspan="5">
										<a href="#" class="button insert" data-row="<?php
											$file = array(
												'file' => '',
												'name' => ''
											);
											ob_start();
											include( 'views/html-product-download.php' );
											echo esc_attr( ob_get_clean() );
										?>"><?php _e( 'Add File', 'woocommerce' ); ?></a>
									</th>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php

					// Download Limit
					woocommerce_wp_text_input( array( 'id' => '_download_limit', 'label' => __( 'Download Limit', 'woocommerce' ), 'placeholder' => __( 'Unlimited', 'woocommerce' ), 'description' => __( 'Leave blank for unlimited re-downloads.', 'woocommerce' ), 'type' => 'number', 'custom_attributes' => array(
						'step' 	=> '1',
						'min'	=> '0'
					) ) );

					// Expirey
					woocommerce_wp_text_input( array( 'id' => '_download_expiry', 'label' => __( 'Download Expiry', 'woocommerce' ), 'placeholder' => __( 'Never', 'woocommerce' ), 'description' => __( 'Enter the number of days before a download link expires, or leave blank.', 'woocommerce' ), 'type' => 'number', 'custom_attributes' => array(
						'step' 	=> '1',
						'min'	=> '0'
					) ) );

					 // Download Type
					woocommerce_wp_select( array( 'id' => '_download_type', 'label' => __( 'Download Type', 'woocommerce' ), 'description' => sprintf( __( 'Choose a download type - this controls the <a href="%s">schema</a>.', 'woocommerce' ), 'http://schema.org/' ), 'options' => array(
						''            => __( 'Standard Product', 'woocommerce' ),
						'application' => __( 'Application/Software', 'woocommerce' ),
						'music'       => __( 'Music', 'woocommerce' ),
					) ) );

					do_action( 'woocommerce_product_options_downloads' );

				echo '</div>';

				if ( wc_tax_enabled() ) {

					echo '<div class="options_group show_if_simple show_if_external show_if_variable">';

						// Tax
						woocommerce_wp_select( array( 'id' => '_tax_status', 'label' => __( 'Tax Status', 'woocommerce' ), 'options' => array(
							'taxable' 	=> __( 'Taxable', 'woocommerce' ),
							'shipping' 	=> __( 'Shipping only', 'woocommerce' ),
							'none' 		=> _x( 'None', 'Tax status', 'woocommerce' )
						) ) );

						$tax_classes         = WC_Tax::get_tax_classes();
						$classes_options     = array();
						$classes_options[''] = __( 'Standard', 'woocommerce' );

						if ( $tax_classes ) {

							foreach ( $tax_classes as $class ) {
								$classes_options[ sanitize_title( $class ) ] = esc_html( $class );
							}
						}

						woocommerce_wp_select( array( 'id' => '_tax_class', 'label' => __( 'Tax Class', 'woocommerce' ), 'options' => $classes_options ) );

						do_action( 'woocommerce_product_options_tax' );

					echo '</div>';

				}

				do_action( 'woocommerce_product_options_general_product_data' );
				?>
			</div>

			<div id="inventory_product_data" class="panel woocommerce_options_panel">

				<?php

				echo '<div class="options_group">';

				if ( 'yes' == get_option( 'woocommerce_manage_stock' ) ) {

					// manage stock
					woocommerce_wp_checkbox( array( 'id' => '_manage_stock', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __( 'Manage stock?', 'woocommerce' ), 'description' => __( 'Enable stock management at product level', 'woocommerce' ) ) );

					do_action( 'woocommerce_product_options_stock' );

					echo '<div class="stock_fields show_if_simple show_if_variable">';

					// Stock
					woocommerce_wp_text_input( array(
						'id'                => '_stock',
						'label'             => __( 'Stock Qty', 'woocommerce' ),
						'desc_tip'          => true,
						'description'       => __( 'Stock quantity. If this is a variable product this value will be used to control stock for all variations, unless you define stock at variation level.', 'woocommerce' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'step' => 'any'
						),
						'data_type'         => 'stock'
					) );

					// Backorders?
					woocommerce_wp_select( array( 'id' => '_backorders', 'label' => __( 'Allow Backorders?', 'woocommerce' ), 'options' => array(
						'no'     => __( 'Do not allow', 'woocommerce' ),
						'notify' => __( 'Allow, but notify customer', 'woocommerce' ),
						'yes'    => __( 'Allow', 'woocommerce' )
					), 'desc_tip' => true, 'description' => __( 'If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.', 'woocommerce' ) ) );

					do_action( 'woocommerce_product_options_stock_fields' );

					echo '</div>';

				}

				// Stock status
				woocommerce_wp_select( array( 'id' => '_stock_status', 'wrapper_class' => 'hide_if_variable', 'label' => __( 'Stock status', 'woocommerce' ), 'options' => array(
					'instock' => __( 'In stock', 'woocommerce' ),
					'outofstock' => __( 'Out of stock', 'woocommerce' )
				), 'desc_tip' => true, 'description' => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ) ) );

				do_action( 'woocommerce_product_options_stock_status' );

				echo '</div>';

				echo '<div class="options_group show_if_simple show_if_variable">';

				// Individual product
				woocommerce_wp_checkbox( array( 'id' => '_sold_individually', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __( 'Sold Individually', 'woocommerce' ), 'description' => __( 'Enable this to only allow one of this item to be bought in a single order', 'woocommerce' ) ) );

				do_action( 'woocommerce_product_options_sold_individually' );

				echo '</div>';

				do_action( 'woocommerce_product_options_inventory_product_data' );
				?>

			</div>

			<div id="shipping_product_data" class="panel woocommerce_options_panel">

				<?php

				echo '<div class="options_group">';

					// Weight
					if ( wc_product_weight_enabled() ) {
						woocommerce_wp_text_input( array( 'id' => '_weight', 'label' => __( 'Weight', 'woocommerce' ) . ' (' . get_option( 'woocommerce_weight_unit' ) . ')', 'placeholder' => wc_format_localized_decimal( 0 ), 'desc_tip' => 'true', 'description' => __( 'Weight in decimal form', 'woocommerce' ), 'type' => 'text', 'data_type' => 'decimal' ) );
					}

					// Size fields
					if ( wc_product_dimensions_enabled() ) {
						?><p class="form-field dimensions_field">
							<label for="product_length"><?php echo __( 'Dimensions', 'woocommerce' ) . ' (' . get_option( 'woocommerce_dimension_unit' ) . ')'; ?></label>
							<span class="wrap">
								<input id="product_length" placeholder="<?php _e( 'Length', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_length" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $thepostid, '_length', true ) ) ); ?>" />
								<input placeholder="<?php _e( 'Width', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_width" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $thepostid, '_width', true ) ) ); ?>" />
								<input placeholder="<?php _e( 'Height', 'woocommerce' ); ?>" class="input-text wc_input_decimal last" size="6" type="text" name="_height" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $thepostid, '_height', true ) ) ); ?>" />
							</span>
							<img class="help_tip" data-tip="<?php esc_attr_e( 'LxWxH in decimal form', 'woocommerce' ); ?>" src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" />
						</p><?php
					}

					do_action( 'woocommerce_product_options_dimensions' );

				echo '</div>';

				echo '<div class="options_group">';

					// Shipping Class
					$classes = get_the_terms( $thepostid, 'product_shipping_class' );
					if ( $classes && ! is_wp_error( $classes ) ) {
						$current_shipping_class = current( $classes )->term_id;
					} else {
						$current_shipping_class = '';
					}

					$args = array(
						'taxonomy'         => 'product_shipping_class',
						'hide_empty'       => 0,
						'show_option_none' => __( 'No shipping class', 'woocommerce' ),
						'name'             => 'product_shipping_class',
						'id'               => 'product_shipping_class',
						'selected'         => $current_shipping_class,
						'class'            => 'select short'
					);
					?><p class="form-field dimensions_field"><label for="product_shipping_class"><?php _e( 'Shipping class', 'woocommerce' ); ?></label> <?php wp_dropdown_categories( $args ); ?> <img class="help_tip" data-tip="<?php esc_attr_e( 'Shipping classes are used by certain shipping methods to group similar products.', 'woocommerce' ); ?>" src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" /></p><?php

					do_action( 'woocommerce_product_options_shipping' );

				echo '</div>';
				?>

			</div>

			<div id="product_attributes" class="panel wc-metaboxes-wrapper">

				<p class="toolbar">
					<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce' ); ?></a><a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce' ); ?></a>
				</p>

				<div class="product_attributes wc-metaboxes">

					<?php
						global $wc_product_attributes;

						// Array of defined attribute taxonomies
						$attribute_taxonomies = wc_get_attribute_taxonomies();

						// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set
						$attributes           = maybe_unserialize( get_post_meta( $thepostid, '_product_attributes', true ) );

						// Output All Set Attributes
						if ( ! empty( $attributes ) ) {
							$attribute_keys  = array_keys( $attributes );
							$attribute_total = sizeof( $attribute_keys );

							for ( $i = 0; $i < $attribute_total; $i ++ ) {
								$attribute     = $attributes[ $attribute_keys[ $i ] ];
								$position      = empty( $attribute['position'] ) ? 0 : absint( $attribute['position'] );
								$taxonomy      = '';
								$metabox_class = array();

								if ( $attribute['is_taxonomy'] ) {
									$taxonomy = $attribute['name'];

									if ( ! taxonomy_exists( $taxonomy ) ) {
										continue;
									}

									$attribute_taxonomy = $wc_product_attributes[ $taxonomy ];
									$metabox_class[]    = 'taxonomy';
									$metabox_class[]    = $taxonomy;
									$attribute_label    = wc_attribute_label( $taxonomy );
								} else {
									$attribute_label    = apply_filters( 'woocommerce_attribute_label', $attribute['name'], $attribute['name'] );
								}

								include( 'views/html-product-attribute.php' );
							}
						}
					?>
				</div>

				<p class="toolbar">
					<button type="button" class="button button-primary add_attribute"><?php _e( 'Add', 'woocommerce' ); ?></button>
					<select name="attribute_taxonomy" class="attribute_taxonomy">
						<option value=""><?php _e( 'Custom product attribute', 'woocommerce' ); ?></option>
						<?php
							if ( $attribute_taxonomies ) {
								foreach ( $attribute_taxonomies as $tax ) {
									$attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
									$label = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
									echo '<option value="' . esc_attr( $attribute_taxonomy_name ) . '">' . esc_html( $label ) . '</option>';
								}
							}
						?>
					</select>

					<button type="button" class="button save_attributes"><?php _e( 'Save attributes', 'woocommerce' ); ?></button>
				</p>
				<?php do_action( 'woocommerce_product_options_attributes' ); ?>
			</div>
			<div id="linked_product_data" class="panel woocommerce_options_panel">

				<div class="options_group">

				<p class="form-field"><label for="upsell_ids"><?php _e( 'Up-Sells', 'woocommerce' ); ?></label>
				<input type="hidden" class="wc-product-search" style="width: 50%;" id="upsell_ids" name="upsell_ids" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" data-selected="<?php
					$product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_upsell_ids', true ) ) );
					$json_ids    = array();

					foreach ( $product_ids as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( is_object( $product ) ) {
							$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name() ) );
						}
					}

					echo esc_attr( json_encode( $json_ids ) );
				?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <img class="help_tip" data-tip='<?php _e( 'Up-sells are products which you recommend instead of the currently viewed product, for example, products that are more profitable or better quality or more expensive.', 'woocommerce' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>

				<p class="form-field"><label for="crosssell_ids"><?php _e( 'Cross-Sells', 'woocommerce' ); ?></label>
				<input type="hidden" class="wc-product-search" style="width: 50%;" id="crosssell_ids" name="crosssell_ids" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" data-selected="<?php
					$product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_crosssell_ids', true ) ) );
					$json_ids    = array();

					foreach ( $product_ids as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( is_object( $product ) ) {
							$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name() ) );
						}
					}

					echo esc_attr( json_encode( $json_ids ) );
				?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <img class="help_tip" data-tip='<?php _e( 'Cross-sells are products which you promote in the cart, based on the current product.', 'woocommerce' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>

				</div>

				<?php

				echo '<div class="options_group grouping show_if_simple show_if_external">';

					// List Grouped products
					$post_parents = array();
					$post_parents[''] = __( 'Choose a grouped product&hellip;', 'woocommerce' );

					if ( $grouped_term = get_term_by( 'slug', 'grouped', 'product_type' ) ) {

						$posts_in = array_unique( (array) get_objects_in_term( $grouped_term->term_id, 'product_type' ) );

						if ( sizeof( $posts_in ) > 0 ) {

							$args = array(
								'post_type'        => 'product',
								'post_status'      => 'any',
								'numberposts'      => -1,
								'orderby'          => 'title',
								'order'            => 'asc',
								'post_parent'      => 0,
								'suppress_filters' => 0,
								'include'          => $posts_in,
							);

							$grouped_products = get_posts( $args );

							if ( $grouped_products ) {

								foreach ( $grouped_products as $product ) {

									if ( $product->ID == $post->ID ) {
										continue;
									}

									$post_parents[ $product->ID ] = $product->post_title;
								}
							}
						}

					}

					woocommerce_wp_select( array( 'id' => 'parent_id', 'label' => __( 'Grouping', 'woocommerce' ), 'value' => absint( $post->post_parent ), 'options' => $post_parents, 'desc_tip' => true, 'description' => __( 'Set this option to make this product part of a grouped product.', 'woocommerce' ) ) );

					woocommerce_wp_hidden_input( array( 'id' => 'previous_parent_id', 'value' => absint( $post->post_parent ) ) );

					do_action( 'woocommerce_product_options_grouping' );

				echo '</div>';
				?>

				<?php do_action( 'woocommerce_product_options_related' ); ?>

			</div>

			<div id="advanced_product_data" class="panel woocommerce_options_panel">

				<?php

				echo '<div class="options_group hide_if_external">';

					// Purchase note
					woocommerce_wp_textarea_input(  array( 'id' => '_purchase_note', 'label' => __( 'Purchase Note', 'woocommerce' ), 'desc_tip' => 'true', 'description' => __( 'Enter an optional note to send the customer after purchase.', 'woocommerce' ) ) );

				echo '</div>';

				echo '<div class="options_group">';

					// menu_order
					woocommerce_wp_text_input(  array( 'id' => 'menu_order', 'label' => __( 'Menu order', 'woocommerce' ), 'desc_tip' => 'true', 'description' => __( 'Custom ordering position.', 'woocommerce' ), 'value' => intval( $post->menu_order ), 'type' => 'number', 'custom_attributes' => array(
						'step' 	=> '1'
					)  ) );

				echo '</div>';

				echo '<div class="options_group reviews">';

					woocommerce_wp_checkbox( array( 'id' => 'comment_status', 'label' => __( 'Enable reviews', 'woocommerce' ), 'cbvalue' => 'open', 'value' => esc_attr( $post->comment_status ) ) );

					do_action( 'woocommerce_product_options_reviews' );

				echo '</div>';
				?>

				<?php do_action( 'woocommerce_product_options_advanced' ); ?>

			</div>

			<?php
				self::output_variations();

				do_action( 'woocommerce_product_data_panels' );
				do_action( 'woocommerce_product_write_panels' ); // _deprecated
			?>

			<div class="clear"></div>

		</div>
		<?php
	}

	/**
	 * Show options for the variable product type
	 */
	public static function output_variations() {
		global $post;

		$attributes = maybe_unserialize( get_post_meta( $post->ID, '_product_attributes', true ) );

		// See if any are set
		$variation_attribute_found = false;

		if ( $attributes ) {

			foreach ( $attributes as $attribute ) {

				if ( isset( $attribute['is_variation'] ) ) {
					$variation_attribute_found = true;
					break;
				}
			}
		}

		// Get tax classes
		$tax_classes           = WC_Tax::get_tax_classes();
		$tax_class_options     = array();
		$tax_class_options[''] = __( 'Standard', 'woocommerce' );

		if ( $tax_classes ) {

			foreach ( $tax_classes as $class ) {
				$tax_class_options[ sanitize_title( $class ) ] = esc_attr( $class );
			}
		}

		$backorder_options = array(
			'no'     => __( 'Do not allow', 'woocommerce' ),
			'notify' => __( 'Allow, but notify customer', 'woocommerce' ),
			'yes'    => __( 'Allow', 'woocommerce' )
		);

		$stock_status_options = array(
			'instock'    => __( 'In stock', 'woocommerce' ),
			'outofstock' => __( 'Out of stock', 'woocommerce' )
		);

		?>
		<div id="variable_product_options" class="panel wc-metaboxes-wrapper"><div id="variable_product_options_inner">

			<?php if ( ! $variation_attribute_found ) : ?>

				<div id="message" class="inline woocommerce-message">
					<p><?php _e( 'Before adding variations, add and save some attributes on the <strong>Attributes</strong> tab.', 'woocommerce' ); ?></p>

					<p class="submit"><a class="button-primary" href="<?php echo esc_url( apply_filters( 'woocommerce_docs_url', 'http://docs.woothemes.com/document/variable-product/', 'product-variations' ) ); ?>" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>
				</div>

			<?php else : ?>

				<p class="toolbar">
					<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce' ); ?></a><a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce' ); ?></a>
					<select id="field_to_edit">
						<option value=""><?php _e( 'Choose a field to bulk edit&hellip;', 'woocommerce' ); ?></option>
						<optgroup label="<?php esc_attr_e( 'Status', 'woocommerce' ); ?>">
							<option value="toggle_enabled"><?php _e( 'Toggle &quot;Enabled&quot;', 'woocommerce' ); ?></option>
							<option value="toggle_downloadable"><?php _e( 'Toggle &quot;Downloadable&quot;', 'woocommerce' ); ?></option>
							<option value="toggle_virtual"><?php _e( 'Toggle &quot;Virtual&quot;', 'woocommerce' ); ?></option>
							<option value="delete_all"><?php _e( 'Delete all variations', 'woocommerce' ); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e( 'Pricing', 'woocommerce' ); ?>">
							<option value="variable_regular_price"><?php _e( 'Prices', 'woocommerce' ); ?></option>
							<option value="variable_regular_price_increase"><?php _e( 'Prices increase by (fixed amount or %)', 'woocommerce' ); ?></option>
							<option value="variable_regular_price_decrease"><?php _e( 'Prices decrease by (fixed amount or %)', 'woocommerce' ); ?></option>
							<option value="variable_sale_price"><?php _e( 'Sale prices', 'woocommerce' ); ?></option>
							<option value="variable_sale_price_increase"><?php _e( 'Sale prices increase by (fixed amount or %)', 'woocommerce' ); ?></option>
							<option value="variable_sale_price_decrease"><?php _e( 'Sale prices decrease by (fixed amount or %)', 'woocommerce' ); ?></option>
							<option value="variable_sale_schedule"><?php _e( 'Scheduled sale dates', 'woocommerce' ); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e( 'Inventory', 'woocommerce' ); ?>">
							<option value="toggle_manage_stock"><?php _e( 'Toggle &quot;Manage stock&quot;', 'woocommerce' ); ?></option>
							<option value="variable_stock"><?php _e( 'Stock', 'woocommerce' ); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e( 'Shipping', 'woocommerce' ); ?>">
							<option value="variable_length"><?php _e( 'Length', 'woocommerce' ); ?></option>
							<option value="variable_width"><?php _e( 'Width', 'woocommerce' ); ?></option>
							<option value="variable_height"><?php _e( 'Height', 'woocommerce' ); ?></option>
							<option value="variable_weight"><?php _e( 'Weight', 'woocommerce' ); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e( 'Downloadable products', 'woocommerce' ); ?>">
							<option value="variable_download_limit"><?php _e( 'Download limit', 'woocommerce' ); ?></option>
							<option value="variable_download_expiry"><?php _e( 'Download Expiry', 'woocommerce' ); ?></option>
						</optgroup>
						<?php do_action( 'woocommerce_variable_product_bulk_edit_actions' ); ?>
					</select>
					<a class="button bulk_edit"><?php _e( 'Go', 'woocommerce' ); ?></a>
				</p>

				<div class="woocommerce_variations wc-metaboxes">
					<?php
					// Get parent data
					$parent_data = array(
						'id'                   => $post->ID,
						'attributes'           => $attributes,
						'tax_class_options'    => $tax_class_options,
						'sku'                  => get_post_meta( $post->ID, '_sku', true ),
						'weight'               => wc_format_localized_decimal( get_post_meta( $post->ID, '_weight', true ) ),
						'length'               => wc_format_localized_decimal( get_post_meta( $post->ID, '_length', true ) ),
						'width'                => wc_format_localized_decimal( get_post_meta( $post->ID, '_width', true ) ),
						'height'               => wc_format_localized_decimal( get_post_meta( $post->ID, '_height', true ) ),
						'tax_class'            => get_post_meta( $post->ID, '_tax_class', true ),
						'backorder_options'    => $backorder_options,
						'stock_status_options' => $stock_status_options
					);

					if ( ! $parent_data['weight'] ) {
						$parent_data['weight'] = wc_format_localized_decimal( 0 );
					}

					if ( ! $parent_data['length'] ) {
						$parent_data['length'] = wc_format_localized_decimal( 0 );
					}

					if ( ! $parent_data['width'] ) {
						$parent_data['width'] = wc_format_localized_decimal( 0 );
					}

					if ( ! $parent_data['height'] ) {
						$parent_data['height'] = wc_format_localized_decimal( 0 );
					}

					// Get variations
					$args = array(
						'post_type'   => 'product_variation',
						'post_status' => array( 'private', 'publish' ),
						'numberposts' => -1,
						'orderby'     => 'menu_order',
						'order'       => 'asc',
						'post_parent' => $post->ID
					);

					$variations = get_posts( $args );
					$loop = 0;

					if ( $variations ) {

						foreach ( $variations as $variation ) {
							$variation_id     = absint( $variation->ID );
							$variation_meta   = get_post_meta( $variation_id );
							$variation_data   = array();
							$shipping_classes = get_the_terms( $variation_id, 'product_shipping_class' );
							$variation_fields = array(
								'_sku'                   => '',
								'_stock'                 => '',
								'_regular_price'         => '',
								'_sale_price'            => '',
								'_weight'                => '',
								'_length'                => '',
								'_width'                 => '',
								'_height'                => '',
								'_download_limit'        => '',
								'_download_expiry'       => '',
								'_downloadable_files'    => '',
								'_downloadable'          => '',
								'_virtual'               => '',
								'_thumbnail_id'          => '',
								'_sale_price_dates_from' => '',
								'_sale_price_dates_to'   => '',
								'_manage_stock'          => '',
								'_stock_status'          => '',
								'_backorders'            => null,
								'_tax_class'             => null
							);

							foreach ( $variation_fields as $field => $value ) {
								$variation_data[ $field ] = isset( $variation_meta[ $field ][0] ) ? maybe_unserialize( $variation_meta[ $field ][0] ) : $value;
							}

							// Add the variation attributes
							foreach ( $variation_meta as $key => $value ) {
								if ( false !== strpos( $key, 'attribute_' ) ) {
									$variation_data[ $key ] = $value;
								}
							}

							// Formatting
							$variation_data['_regular_price'] = wc_format_localized_price( $variation_data['_regular_price'] );
							$variation_data['_sale_price']    = wc_format_localized_price( $variation_data['_sale_price'] );
							$variation_data['_weight']        = wc_format_localized_decimal( $variation_data['_weight'] );
							$variation_data['_length']        = wc_format_localized_decimal( $variation_data['_length'] );
							$variation_data['_width']         = wc_format_localized_decimal( $variation_data['_width'] );
							$variation_data['_height']        = wc_format_localized_decimal( $variation_data['_height'] );
							$variation_data['_thumbnail_id']  = absint( $variation_data['_thumbnail_id'] );
							$variation_data['image']          = $variation_data['_thumbnail_id'] ? wp_get_attachment_thumb_url( $variation_data['_thumbnail_id'] ) : '';
							$variation_data['shipping_class'] = $shipping_classes && ! is_wp_error( $shipping_classes ) ? current( $shipping_classes )->term_id : '';

							// Stock BW compat
							if ( '' !== $variation_data['_stock'] ) {
								$variation_data['_manage_stock'] = 'yes';
							}

							include( 'views/html-variation-admin.php' );

							$loop++;
						}
					}
					?>
				</div>

				<p class="toolbar">

					<button type="button" class="button button-primary add_variation" <?php disabled( $variation_attribute_found, false ); ?>><?php _e( 'Add Variation', 'woocommerce' ); ?></button>

					<button type="button" class="button link_all_variations" <?php disabled( $variation_attribute_found, false ); ?>><?php _e( 'Link all variations', 'woocommerce' ); ?></button>

					<strong><?php _e( 'Defaults', 'woocommerce' ); ?>: <span class="tips" data-tip="<?php _e( 'These are the attributes that will be pre-selected on the frontend.', 'woocommerce' ); ?>">[?]</span></strong>
					<?php
						$default_attributes = maybe_unserialize( get_post_meta( $post->ID, '_default_attributes', true ) );

						foreach ( $attributes as $attribute ) {

							// Only deal with attributes that are variations
							if ( ! $attribute['is_variation'] ) {
								continue;
							}

							// Get current value for variation (if set)
							$variation_selected_value = isset( $default_attributes[ sanitize_title( $attribute['name'] ) ] ) ? $default_attributes[ sanitize_title( $attribute['name'] ) ] : '';

							// Name will be something like attribute_pa_color
							echo '<select name="default_attribute_' . sanitize_title( $attribute['name'] ) . '"><option value="">' . __( 'No default', 'woocommerce' ) . ' ' . esc_html( wc_attribute_label( $attribute['name'] ) ) . '&hellip;</option>';

							// Get terms for attribute taxonomy or value if its a custom attribute
							if ( $attribute['is_taxonomy'] ) {

								$post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );

								foreach ( $post_terms as $term ) {
									echo '<option ' . selected( $variation_selected_value, $term->slug, false ) . ' value="' . esc_attr( $term->slug ) . '">' . apply_filters( 'woocommerce_variation_option_name', esc_html( $term->name ) ) . '</option>';
								}

							} else {

								$options = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );

								foreach ( $options as $option ) {
									echo '<option ' . selected( sanitize_title( $variation_selected_value ), sanitize_title( $option ), false ) . ' value="' . esc_attr( sanitize_title( $option ) ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) )  . '</option>';
								}

							}

							echo '</select>';
						}
					?>
				</p>

			<?php endif; ?>
		</div></div>
		<?php
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;

		// Add any default post meta
		add_post_meta( $post_id, 'total_sales', '0', true );

		// Get types
		$product_type    = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
		$is_downloadable = isset( $_POST['_downloadable'] ) ? 'yes' : 'no';
		$is_virtual      = isset( $_POST['_virtual'] ) ? 'yes' : 'no';

		// Product type + Downloadable/Virtual
		wp_set_object_terms( $post_id, $product_type, 'product_type' );
		update_post_meta( $post_id, '_downloadable', $is_downloadable );
		update_post_meta( $post_id, '_virtual', $is_virtual );

		// Update post meta
		if ( isset( $_POST['_regular_price'] ) ) {
			update_post_meta( $post_id, '_regular_price', ( $_POST['_regular_price'] === '' ) ? '' : wc_format_decimal( $_POST['_regular_price'] ) );
		}

		if ( isset( $_POST['_sale_price'] ) ) {
			update_post_meta( $post_id, '_sale_price', ( $_POST['_sale_price'] === '' ? '' : wc_format_decimal( $_POST['_sale_price'] ) ) );
		}

		if ( isset( $_POST['_tax_status'] ) ) {
			update_post_meta( $post_id, '_tax_status', wc_clean( $_POST['_tax_status'] ) );
		}

		if ( isset( $_POST['_tax_class'] ) ) {
			update_post_meta( $post_id, '_tax_class', wc_clean( $_POST['_tax_class'] ) );
		}

		if ( isset( $_POST['_purchase_note'] ) ) {
			update_post_meta( $post_id, '_purchase_note', wp_kses_post( stripslashes( $_POST['_purchase_note'] ) ) );
		}

		// Featured
		if ( update_post_meta( $post_id, '_featured', isset( $_POST['_featured'] ) ? 'yes' : 'no' ) ) {
			delete_transient( 'wc_featured_products' );
		}

		// Dimensions
		if ( 'no' == $is_virtual ) {

			if ( isset( $_POST['_weight'] ) ) {
				update_post_meta( $post_id, '_weight', ( '' === $_POST['_weight'] ) ? '' : wc_format_decimal( $_POST['_weight'] ) );
			}

			if ( isset( $_POST['_length'] ) ) {
				update_post_meta( $post_id, '_length', ( '' === $_POST['_length'] ) ? '' : wc_format_decimal( $_POST['_length'] ) );
			}

			if ( isset( $_POST['_width'] ) ) {
				update_post_meta( $post_id, '_width', ( '' === $_POST['_width'] ) ? '' : wc_format_decimal( $_POST['_width'] ) );
			}

			if ( isset( $_POST['_height'] ) ) {
				update_post_meta( $post_id, '_height', ( '' === $_POST['_height'] ) ? '' : wc_format_decimal( $_POST['_height'] ) );
			}

		} else {
			update_post_meta( $post_id, '_weight', '' );
			update_post_meta( $post_id, '_length', '' );
			update_post_meta( $post_id, '_width', '' );
			update_post_meta( $post_id, '_height', '' );
		}

		// Save shipping class
		$product_shipping_class = $_POST['product_shipping_class'] > 0 && $product_type != 'external' ? absint( $_POST['product_shipping_class'] ) : '';
		wp_set_object_terms( $post_id, $product_shipping_class, 'product_shipping_class');

		// Unique SKU
		$sku     = get_post_meta( $post_id, '_sku', true );
		$new_sku = wc_clean( stripslashes( $_POST['_sku'] ) );

		if ( '' == $new_sku ) {
			update_post_meta( $post_id, '_sku', '' );
		} elseif ( $new_sku !== $sku ) {

			if ( ! empty( $new_sku ) ) {

				$unique_sku = wc_product_has_unique_sku( $post_id, $new_sku );

				if ( ! $unique_sku ) {
					WC_Admin_Meta_Boxes::add_error( __( 'Product SKU must be unique.', 'woocommerce' ) );
				} else {
					update_post_meta( $post_id, '_sku', $new_sku );
				}
			} else {
				update_post_meta( $post_id, '_sku', '' );
			}
		}

		// Save Attributes
		$attributes = array();

		if ( isset( $_POST['attribute_names'] ) && isset( $_POST['attribute_values'] ) ) {

			$attribute_names  = $_POST['attribute_names'];
			$attribute_values = $_POST['attribute_values'];

			if ( isset( $_POST['attribute_visibility'] ) ) {
				$attribute_visibility = $_POST['attribute_visibility'];
			}

			if ( isset( $_POST['attribute_variation'] ) ) {
				$attribute_variation = $_POST['attribute_variation'];
			}

			$attribute_is_taxonomy = $_POST['attribute_is_taxonomy'];
			$attribute_position    = $_POST['attribute_position'];
			$attribute_names_count = sizeof( $attribute_names );

			for ( $i = 0; $i < $attribute_names_count; $i++ ) {

				if ( ! $attribute_names[ $i ] ) {
					continue;
				}

				$is_visible   = isset( $attribute_visibility[ $i ] ) ? 1 : 0;
				$is_variation = isset( $attribute_variation[ $i ] ) ? 1 : 0;
				$is_taxonomy  = $attribute_is_taxonomy[ $i ] ? 1 : 0;

				if ( $is_taxonomy ) {

					if ( isset( $attribute_values[ $i ] ) ) {

						// Select based attributes - Format values (posted values are slugs)
						if ( is_array( $attribute_values[ $i ] ) ) {
							$values = array_map( 'sanitize_title', $attribute_values[ $i ] );

						// Text based attributes - Posted values are term names - don't change to slugs
						} else {
							$values = array_map( 'stripslashes', array_map( 'strip_tags', explode( WC_DELIMITER, $attribute_values[ $i ] ) ) );
						}

						// Remove empty items in the array
						$values = array_filter( $values, 'strlen' );

					} else {
						$values = array();
					}

					// Update post terms
					if ( taxonomy_exists( $attribute_names[ $i ] ) ) {
						wp_set_object_terms( $post_id, $values, $attribute_names[ $i ] );
					}

					if ( $values ) {
						// Add attribute to array, but don't set values
						$attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
							'name'         => wc_clean( $attribute_names[ $i ] ),
							'value'        => '',
							'position'     => $attribute_position[ $i ],
							'is_visible'   => $is_visible,
							'is_variation' => $is_variation,
							'is_taxonomy'  => $is_taxonomy
						);
					}

				} elseif ( isset( $attribute_values[ $i ] ) ) {

					// Text based, separate by pipe
					$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'trim', array_map( 'wp_kses_post', array_map( 'stripslashes', explode( WC_DELIMITER, $attribute_values[ $i ] ) ) ) ) );

					// Custom attribute - Add attribute to array and set the values
					$attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
						'name'         => wc_clean( $attribute_names[ $i ] ),
						'value'        => $values,
						'position'     => $attribute_position[ $i ],
						'is_visible'   => $is_visible,
						'is_variation' => $is_variation,
						'is_taxonomy'  => $is_taxonomy
					);
				}

			 }
		}

		if ( ! function_exists( 'attributes_cmp' ) ) {
			function attributes_cmp( $a, $b ) {
				if ( $a['position'] == $b['position'] ) {
					return 0;
				}

				return ( $a['position'] < $b['position'] ) ? -1 : 1;
			}
		}
		uasort( $attributes, 'attributes_cmp' );

		update_post_meta( $post_id, '_product_attributes', $attributes );

		// Sales and prices
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) ) {

			// Variable and grouped products have no prices
			update_post_meta( $post_id, '_regular_price', '' );
			update_post_meta( $post_id, '_sale_price', '' );
			update_post_meta( $post_id, '_sale_price_dates_from', '' );
			update_post_meta( $post_id, '_sale_price_dates_to', '' );
			update_post_meta( $post_id, '_price', '' );

		} else {

			$date_from = isset( $_POST['_sale_price_dates_from'] ) ? wc_clean( $_POST['_sale_price_dates_from'] ) : '';
			$date_to   = isset( $_POST['_sale_price_dates_to'] ) ? wc_clean( $_POST['_sale_price_dates_to'] ) : '';

			// Dates
			if ( $date_from ) {
				update_post_meta( $post_id, '_sale_price_dates_from', strtotime( $date_from ) );
			} else {
				update_post_meta( $post_id, '_sale_price_dates_from', '' );
			}

			if ( $date_to ) {
				update_post_meta( $post_id, '_sale_price_dates_to', strtotime( $date_to ) );
			} else {
				update_post_meta( $post_id, '_sale_price_dates_to', '' );
			}

			if ( $date_to && ! $date_from ) {
				update_post_meta( $post_id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );
			}

			// Update price if on sale
			if ( '' !== $_POST['_sale_price'] && '' == $date_to && '' == $date_from ) {
				update_post_meta( $post_id, '_price', wc_format_decimal( $_POST['_sale_price'] ) );
			} else {
				update_post_meta( $post_id, '_price', ( $_POST['_regular_price'] === '' ) ? '' : wc_format_decimal( $_POST['_regular_price'] ) );
			}

			if ( '' !== $_POST['_sale_price'] && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $post_id, '_price', wc_format_decimal( $_POST['_sale_price'] ) );
			}

			if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $post_id, '_price', ( $_POST['_regular_price'] === '' ) ? '' : wc_format_decimal( $_POST['_regular_price'] ) );
				update_post_meta( $post_id, '_sale_price_dates_from', '' );
				update_post_meta( $post_id, '_sale_price_dates_to', '' );
			}
		}

		// Update parent if grouped so price sorting works and stays in sync with the cheapest child
		if ( $post->post_parent > 0 || 'grouped' == $product_type || $_POST['previous_parent_id'] > 0 ) {

			$clear_parent_ids = array();

			if ( $post->post_parent > 0 ) {
				$clear_parent_ids[] = $post->post_parent;
			}

			if ( 'grouped' == $product_type ) {
				$clear_parent_ids[] = $post_id;
			}

			if ( $_POST['previous_parent_id'] > 0 ) {
				$clear_parent_ids[] = absint( $_POST['previous_parent_id'] );
			}

			if ( $clear_parent_ids ) {
				foreach ( $clear_parent_ids as $clear_id ) {
					$children_by_price = get_posts( array(
						'post_parent'    => $clear_id,
						'orderby'        => 'meta_value_num',
						'order'          => 'asc',
						'meta_key'       => '_price',
						'posts_per_page' => 1,
						'post_type'      => 'product',
						'fields'         => 'ids'
					) );

					if ( $children_by_price ) {
						foreach ( $children_by_price as $child ) {
							$child_price = get_post_meta( $child, '_price', true );
							update_post_meta( $clear_id, '_price', $child_price );
						}
					}

					wc_delete_product_transients( $clear_id );
				}
			}
		}

		// Sold Individually
		if ( ! empty( $_POST['_sold_individually'] ) ) {
			update_post_meta( $post_id, '_sold_individually', 'yes' );
		} else {
			update_post_meta( $post_id, '_sold_individually', '' );
		}

		// Stock Data
		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {

			$manage_stock = 'no';
			$backorders   = 'no';
			$stock_status = wc_clean( $_POST['_stock_status'] );

			if ( 'external' === $product_type ) {

				$stock_status = 'instock';

			} elseif ( 'variable' === $product_type ) {

				// Stock status is always determined by children so sync later
				$stock_status = '';

				if ( ! empty( $_POST['_manage_stock'] ) ) {
					$manage_stock = 'yes';
					$backorders   = wc_clean( $_POST['_backorders'] );
				}

			} elseif ( 'grouped' !== $product_type && ! empty( $_POST['_manage_stock'] ) ) {
				$manage_stock = 'yes';
				$backorders   = wc_clean( $_POST['_backorders'] );
			}

			update_post_meta( $post_id, '_manage_stock', $manage_stock );
			update_post_meta( $post_id, '_backorders', $backorders );

			if ( $stock_status ) {
				wc_update_product_stock_status( $post_id, $stock_status );
			}

			if ( ! empty( $_POST['_manage_stock'] ) ) {
				wc_update_product_stock( $post_id, wc_stock_amount( $_POST['_stock'] ) );
			} else {
				update_post_meta( $post_id, '_stock', '' );
			}

		} else {
			wc_update_product_stock_status( $post_id, wc_clean( $_POST['_stock_status'] ) );
		}

		// Cross sells and upsells
		$upsells    = isset( $_POST['upsell_ids'] ) ? array_filter( array_map( 'intval', explode( ',', $_POST['upsell_ids'] ) ) ) : array();
		$crosssells = isset( $_POST['crosssell_ids'] ) ? array_filter( array_map( 'intval', explode( ',', $_POST['crosssell_ids'] ) ) ) : array();

		update_post_meta( $post_id, '_upsell_ids', $upsells );
		update_post_meta( $post_id, '_crosssell_ids', $crosssells );

		// Downloadable options
		if ( 'yes' == $is_downloadable ) {

			$_download_limit = absint( $_POST['_download_limit'] );
			if ( ! $_download_limit ) {
				$_download_limit = ''; // 0 or blank = unlimited
			}

			$_download_expiry = absint( $_POST['_download_expiry'] );
			if ( ! $_download_expiry ) {
				$_download_expiry = ''; // 0 or blank = unlimited
			}

			// file paths will be stored in an array keyed off md5(file path)
			$files = array();

			if ( isset( $_POST['_wc_file_urls'] ) ) {
				$file_names    = isset( $_POST['_wc_file_names'] ) ? $_POST['_wc_file_names'] : array();
				$file_urls     = isset( $_POST['_wc_file_urls'] )  ? array_map( 'trim', $_POST['_wc_file_urls'] ) : array();
				$file_url_size = sizeof( $file_urls );

				for ( $i = 0; $i < $file_url_size; $i ++ ) {
					if ( ! empty( $file_urls[ $i ] ) ) {
						$file_url            = ( 0 !== strpos( $file_urls[ $i ], 'http' ) ) ? wc_clean( $file_urls[ $i ] ) : esc_url_raw( $file_urls[ $i ] );
						$file_name           = wc_clean( $file_names[ $i ] );
						$file_hash           = md5( $file_url );
						$files[ $file_hash ] = array(
							'name' => $file_name,
							'file' => $file_url
						);
					}
				}
			}

			// grant permission to any newly added files on any existing orders for this product prior to saving
			do_action( 'woocommerce_process_product_file_download_paths', $post_id, 0, $files );

			update_post_meta( $post_id, '_downloadable_files', $files );
			update_post_meta( $post_id, '_download_limit', $_download_limit );
			update_post_meta( $post_id, '_download_expiry', $_download_expiry );

			if ( isset( $_POST['_download_type'] ) ) {
				update_post_meta( $post_id, '_download_type', wc_clean( $_POST['_download_type'] ) );
			}
		}

		// Product url
		if ( 'external' == $product_type ) {

			if ( isset( $_POST['_product_url'] ) ) {
				update_post_meta( $post_id, '_product_url', esc_url_raw( $_POST['_product_url'] ) );
			}

			if ( isset( $_POST['_button_text'] ) ) {
				update_post_meta( $post_id, '_button_text', wc_clean( $_POST['_button_text'] ) );
			}
		}

		// Save variations
		if ( 'variable' == $product_type ) {
			self::save_variations( $post_id, $post );
		}

		// Do action for product type
		do_action( 'woocommerce_process_product_meta_' . $product_type, $post_id );

		// Clear cache/transients
		wc_delete_product_transients( $post_id );
	}

	/**
	 * Save meta box data
	 */
	public static function save_variations( $post_id, $post ) {
		global $wpdb;

		$attributes = (array) maybe_unserialize( get_post_meta( $post_id, '_product_attributes', true ) );

		if ( isset( $_POST['variable_sku'] ) ) {

			$variable_post_id               = $_POST['variable_post_id'];
			$variable_sku                   = $_POST['variable_sku'];
			$variable_regular_price         = $_POST['variable_regular_price'];
			$variable_sale_price            = $_POST['variable_sale_price'];
			$upload_image_id                = $_POST['upload_image_id'];
			$variable_download_limit        = $_POST['variable_download_limit'];
			$variable_download_expiry       = $_POST['variable_download_expiry'];
			$variable_shipping_class        = $_POST['variable_shipping_class'];
			$variable_tax_class             = isset( $_POST['variable_tax_class'] ) ? $_POST['variable_tax_class'] : array();
			$variable_menu_order            = $_POST['variation_menu_order'];
			$variable_sale_price_dates_from = $_POST['variable_sale_price_dates_from'];
			$variable_sale_price_dates_to   = $_POST['variable_sale_price_dates_to'];

			$variable_weight                = isset( $_POST['variable_weight'] ) ? $_POST['variable_weight'] : array();
			$variable_length                = isset( $_POST['variable_length'] ) ? $_POST['variable_length'] : array();
			$variable_width                 = isset( $_POST['variable_width'] ) ? $_POST['variable_width'] : array();
			$variable_height                = isset( $_POST['variable_height'] ) ? $_POST['variable_height'] : array();
			$variable_enabled               = isset( $_POST['variable_enabled'] ) ? $_POST['variable_enabled'] : array();
			$variable_is_virtual            = isset( $_POST['variable_is_virtual'] ) ? $_POST['variable_is_virtual'] : array();
			$variable_is_downloadable       = isset( $_POST['variable_is_downloadable'] ) ? $_POST['variable_is_downloadable'] : array();

			$variable_manage_stock          = isset( $_POST['variable_manage_stock'] ) ? $_POST['variable_manage_stock'] : array();
			$variable_stock                 = isset( $_POST['variable_stock'] ) ? $_POST['variable_stock'] : array();
			$variable_backorders            = isset( $_POST['variable_backorders'] ) ? $_POST['variable_backorders'] : array();
			$variable_stock_status          = isset( $_POST['variable_stock_status'] ) ? $_POST['variable_stock_status'] : array();

			$max_loop = max( array_keys( $_POST['variable_post_id'] ) );

			for ( $i = 0; $i <= $max_loop; $i ++ ) {

				if ( ! isset( $variable_post_id[ $i ] ) ) {
					continue;
				}

				$variation_id = absint( $variable_post_id[ $i ] );

				// Checkboxes
				$is_virtual          = isset( $variable_is_virtual[ $i ] ) ? 'yes' : 'no';
				$is_downloadable     = isset( $variable_is_downloadable[ $i ] ) ? 'yes' : 'no';
				$post_status         = isset( $variable_enabled[ $i ] ) ? 'publish' : 'private';
				$manage_stock        = isset( $variable_manage_stock[ $i ] ) ? 'yes' : 'no';

				// Generate a useful post title
				$variation_post_title = sprintf( __( 'Variation #%s of %s', 'woocommerce' ), absint( $variation_id ), esc_html( get_the_title( $post_id ) ) );

				// Update or Add post
				if ( ! $variation_id ) {

					$variation = array(
						'post_title'   => $variation_post_title,
						'post_content' => '',
						'post_status'  => $post_status,
						'post_author'  => get_current_user_id(),
						'post_parent'  => $post_id,
						'post_type'    => 'product_variation',
						'menu_order'   => $variable_menu_order[ $i ]
					);

					$variation_id = wp_insert_post( $variation );

					do_action( 'woocommerce_create_product_variation', $variation_id );

				} else {

					$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status, 'post_title' => $variation_post_title, 'menu_order' => $variable_menu_order[ $i ] ), array( 'ID' => $variation_id ) );

					do_action( 'woocommerce_update_product_variation', $variation_id );

				}

				// Only continue if we have a variation ID
				if ( ! $variation_id ) {
					continue;
				}

				// Unique SKU
				$sku     = get_post_meta( $variation_id, '_sku', true );
				$new_sku = wc_clean( stripslashes( $variable_sku[ $i ] ) );

				if ( '' == $new_sku ) {
					update_post_meta( $variation_id, '_sku', '' );
				} elseif ( $new_sku !== $sku ) {

					if ( ! empty( $new_sku ) ) {
						$unique_sku = wc_product_has_unique_sku( $variation_id, $new_sku );

						if ( ! $unique_sku ) {
							WC_Admin_Meta_Boxes::add_error( __( 'Variation SKU must be unique.', 'woocommerce' ) );
						} else {
							update_post_meta( $variation_id, '_sku', $new_sku );
						}
					} else {
						update_post_meta( $variation_id, '_sku', '' );
					}
				}

				// Update post meta
				update_post_meta( $variation_id, '_thumbnail_id', absint( $upload_image_id[ $i ] ) );
				update_post_meta( $variation_id, '_virtual', wc_clean( $is_virtual ) );
				update_post_meta( $variation_id, '_downloadable', wc_clean( $is_downloadable ) );

				if ( isset( $variable_weight[ $i ] ) ) {
					update_post_meta( $variation_id, '_weight', ( '' === $variable_weight[ $i ] ) ? '' : wc_format_decimal( $variable_weight[ $i ] ) );
				}

				if ( isset( $variable_length[ $i ] ) ) {
					update_post_meta( $variation_id, '_length', ( '' === $variable_length[ $i ] ) ? '' : wc_format_decimal( $variable_length[ $i ] ) );
				}

				if ( isset( $variable_width[ $i ] ) ) {
					update_post_meta( $variation_id, '_width', ( '' === $variable_width[ $i ] ) ? '' : wc_format_decimal( $variable_width[ $i ] ) );
				}

				if ( isset( $variable_height[ $i ] ) ) {
					update_post_meta( $variation_id, '_height', ( '' === $variable_height[ $i ] ) ? '' : wc_format_decimal( $variable_height[ $i ] ) );
				}

				// Stock handling
				update_post_meta( $variation_id, '_manage_stock', $manage_stock );

				// Only update stock status to user setting if changed by the user, but do so before looking at stock levels at variation level
				if ( ! empty( $variable_stock_status[ $i ] ) ) {
					wc_update_product_stock_status( $variation_id, $variable_stock_status[ $i ] );
				}

				if ( 'yes' === $manage_stock ) {
					update_post_meta( $variation_id, '_backorders', wc_clean( $variable_backorders[ $i ] ) );
					wc_update_product_stock( $variation_id, wc_stock_amount( $variable_stock[ $i ] ) );
				} else {
					delete_post_meta( $variation_id, '_backorders' );
					delete_post_meta( $variation_id, '_stock' );
				}

				// Price handling
				$regular_price = wc_format_decimal( $variable_regular_price[ $i ] );
				$sale_price    = $variable_sale_price[ $i ] === '' ? '' : wc_format_decimal( $variable_sale_price[ $i ] );
				$date_from     = wc_clean( $variable_sale_price_dates_from[ $i ] );
				$date_to       = wc_clean( $variable_sale_price_dates_to[ $i ] );

				update_post_meta( $variation_id, '_regular_price', $regular_price );
				update_post_meta( $variation_id, '_sale_price', $sale_price );

				// Save Dates
				update_post_meta( $variation_id, '_sale_price_dates_from', $date_from ? strtotime( $date_from ) : '' );
				update_post_meta( $variation_id, '_sale_price_dates_to', $date_to ? strtotime( $date_to ) : '' );

				if ( $date_to && ! $date_from ) {
					update_post_meta( $variation_id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );
				}

				// Update price if on sale
				if ( '' !== $sale_price && '' === $date_to && '' === $date_from ) {
					update_post_meta( $variation_id, '_price', $sale_price );
				} else {
					update_post_meta( $variation_id, '_price', $regular_price );
				}

				if ( '' !== $sale_price && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
					update_post_meta( $variation_id, '_price', $sale_price );
				}

				if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
					update_post_meta( $variation_id, '_price', $regular_price );
					update_post_meta( $variation_id, '_sale_price_dates_from', '' );
					update_post_meta( $variation_id, '_sale_price_dates_to', '' );
				}

				if ( isset( $variable_tax_class[ $i ] ) && $variable_tax_class[ $i ] !== 'parent' ) {
					update_post_meta( $variation_id, '_tax_class', wc_clean( $variable_tax_class[ $i ] ) );
				} else {
					delete_post_meta( $variation_id, '_tax_class' );
				}

				if ( 'yes' == $is_downloadable ) {
					update_post_meta( $variation_id, '_download_limit', wc_clean( $variable_download_limit[ $i ] ) );
					update_post_meta( $variation_id, '_download_expiry', wc_clean( $variable_download_expiry[ $i ] ) );

					$files         = array();
					$file_names    = isset( $_POST['_wc_variation_file_names'][ $variation_id ] ) ? array_map( 'wc_clean', $_POST['_wc_variation_file_names'][ $variation_id ] ) : array();
					$file_urls     = isset( $_POST['_wc_variation_file_urls'][ $variation_id ] ) ? array_map( 'wc_clean', $_POST['_wc_variation_file_urls'][ $variation_id ] ) : array();
					$file_url_size = sizeof( $file_urls );

					for ( $ii = 0; $ii < $file_url_size; $ii ++ ) {

						if ( ! empty( $file_urls[ $ii ] ) ) {

							$files[ md5( $file_urls[ $ii ] ) ] = array(
								'name' => $file_names[ $ii ],
								'file' => $file_urls[ $ii ]
							);
						}
					}

					// grant permission to any newly added files on any existing orders for this product prior to saving
					do_action( 'woocommerce_process_product_file_download_paths', $post_id, $variation_id, $files );

					update_post_meta( $variation_id, '_downloadable_files', $files );
				} else {
					update_post_meta( $variation_id, '_download_limit', '' );
					update_post_meta( $variation_id, '_download_expiry', '' );
					update_post_meta( $variation_id, '_downloadable_files', '' );
				}

				// Save shipping class
				$variable_shipping_class[ $i ] = ! empty( $variable_shipping_class[ $i ] ) ? (int) $variable_shipping_class[ $i ] : '';
				wp_set_object_terms( $variation_id, $variable_shipping_class[ $i ], 'product_shipping_class');

				// Update taxonomies - don't use wc_clean as it destroys sanitized characters
				$updated_attribute_keys = array();
				foreach ( $attributes as $attribute ) {

					if ( $attribute['is_variation'] ) {
						$attribute_key = 'attribute_' . sanitize_title( $attribute['name'] );
						$value         = isset( $_POST[ $attribute_key ][ $i ] ) ? sanitize_title( stripslashes( $_POST[ $attribute_key ][ $i ] ) ) : '';
						$updated_attribute_keys[] = $attribute_key;
						update_post_meta( $variation_id, $attribute_key, $value );
					}
				}

				// Remove old taxonomies attributes so data is kept up to date - first get attribute key names
				$delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( '" . implode( "','", $updated_attribute_keys ) . "' ) AND post_id = %d;", $variation_id ) );

				foreach ( $delete_attribute_keys as $key ) {
					delete_post_meta( $variation_id, $key );
				}

				do_action( 'woocommerce_save_product_variation', $variation_id, $i );
			}
		}

		// Update parent if variable so price sorting works and stays in sync with the cheapest child
		WC_Product_Variable::sync( $post_id );

		// Update default attribute options setting
		$default_attributes = array();

		foreach ( $attributes as $attribute ) {

			if ( $attribute['is_variation'] ) {

				// Don't use wc_clean as it destroys sanitized characters
				if ( isset( $_POST[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] ) ) {
					$value = sanitize_title( trim( stripslashes( $_POST[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] ) ) );
				} else {
					$value = '';
				}

				if ( $value ) {
					$default_attributes[ sanitize_title( $attribute['name'] ) ] = $value;
				}
			}
		}

		update_post_meta( $post_id, '_default_attributes', $default_attributes );
	}
}
