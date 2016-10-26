<?php
/**
 * Product Data
 *
 * Displays the product data box, tabbed, with several panels covering price, stock etc.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Meta Boxes
 * @version  2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Meta_Box_Product_Data Class.
 */
class WC_Meta_Box_Product_Data {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $post, $thepostid;

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

		$thepostid      = $post->ID;
		$product_object = wc_get_product( $thepostid );

		if ( $terms = wp_get_object_terms( $post->ID, 'product_type' ) ) {
			$product_type = sanitize_title( current( $terms )->name );
		} else {
			$product_type = apply_filters( 'default_product_type', 'simple' );
		}

		$type_box = '<label for="product-type"><select id="product-type" name="product-type"><optgroup label="' . esc_attr__( 'Product Type', 'woocommerce' ) . '">';

		foreach ( wc_get_product_types() as $value => $label ) {
			$type_box .= '<option value="' . esc_attr( $value ) . '" ' . selected( $product_type, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}

		$type_box .= '</optgroup></select></label>';

		$product_type_options = apply_filters( 'product_type_options', array(
			'virtual' => array(
				'id'            => '_virtual',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Virtual', 'woocommerce' ),
				'description'   => __( 'Virtual products are intangible and aren\'t shipped.', 'woocommerce' ),
				'default'       => 'no',
			),
			'downloadable' => array(
				'id'            => '_downloadable',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Downloadable', 'woocommerce' ),
				'description'   => __( 'Downloadable products give access to a file upon purchase.', 'woocommerce' ),
				'default'       => 'no',
			),
		) );

		foreach ( $product_type_options as $key => $option ) {
			$selected_value = get_post_meta( $post->ID, '_' . $key, true );

			if ( '' == $selected_value && isset( $option['default'] ) ) {
				$selected_value = $option['default'];
			}

			$type_box .= '<label for="' . esc_attr( $option['id'] ) . '" class="' . esc_attr( $option['wrapper_class'] ) . ' tips" data-tip="' . esc_attr( $option['description'] ) . '">' . esc_html( $option['label'] ) . ': <input type="checkbox" name="' . esc_attr( $option['id'] ) . '" id="' . esc_attr( $option['id'] ) . '" ' . checked( $selected_value, 'yes', false ) . ' /></label>';
		}

		?>
		<div class="panel-wrap product_data">

			<span class="type_box hidden"> &mdash; <?php echo $type_box; ?></span>

			<ul class="product_data_tabs wc-tabs">
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
							'class'  => array( 'show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external' ),
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
						),
					) );

					foreach ( $product_data_tabs as $key => $tab ) {
						?><li class="<?php echo $key; ?>_options <?php echo $key; ?>_tab <?php echo implode( ' ' , (array) $tab['class'] ); ?>">
							<a href="#<?php echo $tab['target']; ?>"><?php echo esc_html( $tab['label'] ); ?></a>
						</li><?php
					}

					do_action( 'woocommerce_product_write_panel_tabs' );
				?>
			</ul>
			<div id="general_product_data" class="panel woocommerce_options_panel"><?php

				echo '<div class="options_group show_if_external">';

					// External URL
					woocommerce_wp_text_input( array( 'id' => '_product_url', 'label' => __( 'Product URL', 'woocommerce' ), 'placeholder' => 'http://', 'description' => __( 'Enter the external URL to the product.', 'woocommerce' ) ) );

					// Button text
					woocommerce_wp_text_input( array( 'id' => '_button_text', 'label' => __( 'Button text', 'woocommerce' ), 'placeholder' => _x( 'Buy product', 'placeholder', 'woocommerce' ), 'description' => __( 'This text will be shown on the button linking to the external product.', 'woocommerce' ) ) );

				echo '</div>';

				echo '<div class="options_group pricing show_if_simple show_if_external hidden">';

					// Price
					woocommerce_wp_text_input( array( 'id' => '_regular_price', 'label' => __( 'Regular price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );

					// Special Price
					woocommerce_wp_text_input( array( 'id' => '_sale_price', 'data_type' => 'price', 'label' => __( 'Sale price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'description' => '<a href="#" class="sale_schedule">' . __( 'Schedule', 'woocommerce' ) . '</a>' ) );

					// Special Price date range
					$sale_price_dates_from = ( $date = get_post_meta( $thepostid, '_sale_price_dates_from', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';
					$sale_price_dates_to   = ( $date = get_post_meta( $thepostid, '_sale_price_dates_to', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';

					echo '<p class="form-field sale_price_dates_fields">
								<label for="_sale_price_dates_from">' . __( 'Sale price dates', 'woocommerce' ) . '</label>
								<input type="text" class="short" name="_sale_price_dates_from" id="_sale_price_dates_from" value="' . esc_attr( $sale_price_dates_from ) . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
								<input type="text" class="short" name="_sale_price_dates_to" id="_sale_price_dates_to" value="' . esc_attr( $sale_price_dates_to ) . '" placeholder="' . _x( 'To&hellip;', 'placeholder', 'woocommerce' ) . '  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
								<a href="#" class="cancel_sale_schedule">' . __( 'Cancel', 'woocommerce' ) . '</a>' . wc_help_tip( __( 'The sale will end at the beginning of the set date.', 'woocommerce' ) ) . '
							</p>';

					do_action( 'woocommerce_product_options_pricing' );

				echo '</div>';

				echo '<div class="options_group show_if_downloadable hidden">';

					?>
					<div class="form-field downloadable_files">
						<label><?php _e( 'Downloadable files', 'woocommerce' ); ?></label>
						<table class="widefat">
							<thead>
								<tr>
									<th class="sort">&nbsp;</th>
									<th><?php _e( 'Name', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the name of the download shown to the customer.', 'woocommerce' ) ); ?></th>
									<th colspan="2"><?php _e( 'File URL', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the URL or absolute path to the file which customers will get access to. URLs entered here should already be encoded.', 'woocommerce' ) ); ?></th>
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
												'name' => '',
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
					woocommerce_wp_text_input( array(
						'id'                => '_download_limit',
						'label'             => __( 'Download limit', 'woocommerce' ),
						'placeholder'       => __( 'Unlimited', 'woocommerce' ),
						'description'       => __( 'Leave blank for unlimited re-downloads.', 'woocommerce' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'step' 	=> '1',
							'min'	=> '0',
						),
					) );

					// Expirey
					woocommerce_wp_text_input( array(
						'id'                => '_download_expiry',
						'label'             => __( 'Download expiry', 'woocommerce' ),
						'placeholder'       => __( 'Never', 'woocommerce' ),
						'description'       => __( 'Enter the number of days before a download link expires, or leave blank.', 'woocommerce' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'step' 	=> '1',
							'min'	=> '0',
						),
					) );

					 // Download Type
					woocommerce_wp_select( array(
						'id'          => '_download_type',
						'label'       => __( 'Download type', 'woocommerce' ),
						'description' => sprintf( __( 'Choose a download type - this controls the <a href="%s">schema</a>.', 'woocommerce' ), 'http://schema.org/' ),
						'options'     => array(
							''            => __( 'Standard Product', 'woocommerce' ),
							'application' => __( 'Application/Software', 'woocommerce' ),
							'music'       => __( 'Music', 'woocommerce' ),
						),
					) );

					do_action( 'woocommerce_product_options_downloads' );

				echo '</div>';

				if ( wc_tax_enabled() ) {

					echo '<div class="options_group show_if_simple show_if_external show_if_variable">';

						// Tax
						woocommerce_wp_select( array(
							'id'      => '_tax_status',
							'label'   => __( 'Tax status', 'woocommerce' ),
							'options' => array(
								'taxable' 	=> __( 'Taxable', 'woocommerce' ),
								'shipping' 	=> __( 'Shipping only', 'woocommerce' ),
								'none' 		=> _x( 'None', 'Tax status', 'woocommerce' ),
							),
							'desc_tip'    => 'true',
							'description' => __( 'Define whether or not the entire product is taxable, or just the cost of shipping it.', 'woocommerce' ),
						) );

						$tax_classes         = WC_Tax::get_tax_classes();
						$classes_options     = array();
						$classes_options[''] = __( 'Standard', 'woocommerce' );

						if ( ! empty( $tax_classes ) ) {
							foreach ( $tax_classes as $class ) {
								$classes_options[ sanitize_title( $class ) ] = esc_html( $class );
							}
						}

						woocommerce_wp_select( array(
							'id'          => '_tax_class',
							'label'       => __( 'Tax class', 'woocommerce' ),
							'options'     => $classes_options,
							'desc_tip'    => 'true',
							'description' => __( 'Choose a tax class for this product. Tax classes are used to apply different tax rates specific to certain types of product.', 'woocommerce' ),
						) );

						do_action( 'woocommerce_product_options_tax' );

					echo '</div>';

				}

				do_action( 'woocommerce_product_options_general_product_data' );
				?>
			</div>

			<div id="inventory_product_data" class="panel woocommerce_options_panel hidden">

				<?php

				echo '<div class="options_group">';

				// SKU
				if ( wc_product_sku_enabled() ) {
					woocommerce_wp_text_input( array( 'id' => '_sku', 'label' => '<abbr title="' . __( 'Stock Keeping Unit', 'woocommerce' ) . '">' . __( 'SKU', 'woocommerce' ) . '</abbr>', 'desc_tip' => 'true', 'description' => __( 'SKU refers to a Stock-keeping unit, a unique identifier for each distinct product and service that can be purchased.', 'woocommerce' ) ) );
				} else {
					echo '<input type="hidden" name="_sku" value="' . esc_attr( get_post_meta( $thepostid, '_sku', true ) ) . '" />';
				}

				do_action( 'woocommerce_product_options_sku' );

				if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {

					// manage stock
					woocommerce_wp_checkbox( array( 'id' => '_manage_stock', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __( 'Manage stock?', 'woocommerce' ), 'description' => __( 'Enable stock management at product level', 'woocommerce' ) ) );

					do_action( 'woocommerce_product_options_stock' );

					echo '<div class="stock_fields show_if_simple show_if_variable">';

					// Stock
					woocommerce_wp_text_input( array(
						'id'                => '_stock',
						'label'             => __( 'Stock quantity', 'woocommerce' ),
						'desc_tip'          => true,
						'description'       => __( 'Stock quantity. If this is a variable product this value will be used to control stock for all variations, unless you define stock at variation level.', 'woocommerce' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'step' => 'any',
						),
						'data_type'         => 'stock',
					) );

					// Backorders?
					woocommerce_wp_select( array(
						'id'          => '_backorders',
						'label'       => __( 'Allow backorders?', 'woocommerce' ),
						'options'     => array(
							'no'      => __( 'Do not allow', 'woocommerce' ),
							'notify'  => __( 'Allow, but notify customer', 'woocommerce' ),
							'yes'     => __( 'Allow', 'woocommerce' ),
						),
						'desc_tip'    => true,
						'description' => __( 'If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.', 'woocommerce' ),
					) );

					do_action( 'woocommerce_product_options_stock_fields' );

					echo '</div>';

				}

				// Stock status
				woocommerce_wp_select( array(
					'id'             => '_stock_status',
					'wrapper_class'  => 'hide_if_variable hide_if_external',
					'label'          => __( 'Stock status', 'woocommerce' ),
					'options'        => array(
						'instock'    => __( 'In stock', 'woocommerce' ),
						'outofstock' => __( 'Out of stock', 'woocommerce' ),
					),
					'desc_tip'       => true,
					'description'    => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', 'woocommerce' ),
				) );

				do_action( 'woocommerce_product_options_stock_status' );

				echo '</div>';

				echo '<div class="options_group show_if_simple show_if_variable">';

				// Individual product
				woocommerce_wp_checkbox( array( 'id' => '_sold_individually', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __( 'Sold individually', 'woocommerce' ), 'description' => __( 'Enable this to only allow one of this item to be bought in a single order', 'woocommerce' ) ) );

				do_action( 'woocommerce_product_options_sold_individually' );

				echo '</div>';

				do_action( 'woocommerce_product_options_inventory_product_data' );
				?>

			</div>

			<div id="shipping_product_data" class="panel woocommerce_options_panel hidden">

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
								<input id="product_length" placeholder="<?php esc_attr_e( 'Length', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_length" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $thepostid, '_length', true ) ) ); ?>" />
								<input placeholder="<?php esc_attr_e( 'Width', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_width" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $thepostid, '_width', true ) ) ); ?>" />
								<input placeholder="<?php esc_attr_e( 'Height', 'woocommerce' ); ?>" class="input-text wc_input_decimal last" size="6" type="text" name="_height" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $thepostid, '_height', true ) ) ); ?>" />
							</span>
							<?php echo wc_help_tip( __( 'LxWxH in decimal form', 'woocommerce' ) ); ?>
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
						'class'            => 'select short',
					);
					?><p class="form-field dimensions_field"><label for="product_shipping_class"><?php _e( 'Shipping class', 'woocommerce' ); ?></label> <?php wp_dropdown_categories( $args ); ?> <?php echo wc_help_tip( __( 'Shipping classes are used by certain shipping methods to group similar products.', 'woocommerce' ) ); ?></p><?php

					do_action( 'woocommerce_product_options_shipping' );

				echo '</div>';
				?>

			</div>

			<div id="product_attributes" class="panel wc-metaboxes-wrapper hidden">
				<div class="toolbar toolbar-top">
					<span class="expand-close">
						<a href="#" class="expand_all"><?php _e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php _e( 'Close', 'woocommerce' ); ?></a>
					</span>
					<select name="attribute_taxonomy" class="attribute_taxonomy">
						<option value=""><?php _e( 'Custom product attribute', 'woocommerce' ); ?></option>
						<?php
							global $wc_product_attributes;

							// Array of defined attribute taxonomies
							$attribute_taxonomies = wc_get_attribute_taxonomies();

							if ( ! empty( $attribute_taxonomies ) ) {
								foreach ( $attribute_taxonomies as $tax ) {
									$attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
									$label = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
									echo '<option value="' . esc_attr( $attribute_taxonomy_name ) . '">' . esc_html( $label ) . '</option>';
								}
							}
						?>
					</select>
					<button type="button" class="button add_attribute"><?php _e( 'Add', 'woocommerce' ); ?></button>
				</div>
				<div class="product_attributes wc-metaboxes">
					<?php
						// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set
						$attributes = $product_object->get_attributes();
						$i          = -1;

						foreach ( $attributes as $attribute ) {
							$i++;
							$metabox_class = array();

							if ( $attribute->is_taxonomy() ) {
								$metabox_class[] = 'taxonomy';
								$metabox_class[] = $attribute->get_name();
							}

							include( 'views/html-product-attribute.php' );
						}
					?>
				</div>
				<div class="toolbar">
					<span class="expand-close">
						<a href="#" class="expand_all"><?php _e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php _e( 'Close', 'woocommerce' ); ?></a>
					</span>
					<button type="button" class="button save_attributes button-primary"><?php _e( 'Save attributes', 'woocommerce' ); ?></button>
				</div>
				<?php do_action( 'woocommerce_product_options_attributes' ); ?>
			</div>
			<div id="linked_product_data" class="panel woocommerce_options_panel hidden">

				<div class="options_group">
					<p class="form-field">
						<label for="upsell_ids"><?php _e( 'Up-sells', 'woocommerce' ); ?></label>
						<input type="hidden" class="wc-product-search" style="width: 50%;" id="upsell_ids" name="upsell_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" data-exclude="<?php echo intval( $post->ID ); ?>" data-selected="<?php
							$product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_upsell_ids', true ) ) );
							$json_ids    = array();

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( is_object( $product ) ) {
									$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
								}
							}

							echo esc_attr( json_encode( $json_ids ) );
						?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <?php echo wc_help_tip( __( 'Up-sells are products which you recommend instead of the currently viewed product, for example, products that are more profitable or better quality or more expensive.', 'woocommerce' ) ); ?>
					</p>

					<p class="form-field">
						<label for="crosssell_ids"><?php _e( 'Cross-sells', 'woocommerce' ); ?></label>
						<input type="hidden" class="wc-product-search" style="width: 50%;" id="crosssell_ids" name="crosssell_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" data-exclude="<?php echo intval( $post->ID ); ?>" data-selected="<?php
							$product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_crosssell_ids', true ) ) );
							$json_ids    = array();

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( is_object( $product ) ) {
									$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
								}
							}

							echo esc_attr( json_encode( $json_ids ) );
						?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <?php echo wc_help_tip( __( 'Cross-sells are products which you promote in the cart, based on the current product.', 'woocommerce' ) ); ?>
					</p>

					<p class="form-field show_if_grouped">
						<label for="grouped_products"><?php _e( 'Grouped products', 'woocommerce' ); ?></label>
						<input type="hidden" class="wc-product-search" style="width: 50%;" id="grouped_products" name="grouped_products" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" data-exclude="<?php echo intval( $post->ID ); ?>" data-selected="<?php
						$product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_children', true ) ) );
						$json_ids    = array();

						foreach ( $product_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( is_object( $product ) ) {
								$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
							}
						}

						echo esc_attr( json_encode( $json_ids ) );
						?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <?php echo wc_help_tip( __( 'This lets you choose which products are part of this group.', 'woocommerce' ) ); ?>
					</p>
				</div>

				<?php do_action( 'woocommerce_product_options_related' ); ?>
			</div>

			<div id="advanced_product_data" class="panel woocommerce_options_panel hidden">

				<div class="options_group hide_if_external">
					<?php
						// Purchase note
						woocommerce_wp_textarea_input( array( 'id' => '_purchase_note', 'label' => __( 'Purchase note', 'woocommerce' ), 'desc_tip' => 'true', 'description' => __( 'Enter an optional note to send the customer after purchase.', 'woocommerce' ) ) );
					?>
				</div>

				<div class="options_group">
					<?php
						// menu_order
						woocommerce_wp_text_input( array(
							'id'          => 'menu_order',
							'label'       => __( 'Menu order', 'woocommerce' ),
							'desc_tip'    => 'true',
							'description' => __( 'Custom ordering position.', 'woocommerce' ),
							'value'       => intval( $post->menu_order ),
							'type'        => 'number',
							'custom_attributes' => array(
								'step' 	=> '1',
							),
						) );
					?>
				</div>

				<div class="options_group reviews">
					<?php
						woocommerce_wp_checkbox( array( 'id' => 'comment_status', 'label' => __( 'Enable reviews', 'woocommerce' ), 'cbvalue' => 'open', 'value' => esc_attr( $post->comment_status ) ) );

						do_action( 'woocommerce_product_options_reviews' );
					?>
				</div>

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
	 * Show options for the variable product type.
	 */
	public static function output_variations() {
		global $post, $wpdb;

		// Get attributes
		$attributes = maybe_unserialize( get_post_meta( $post->ID, '_product_attributes', true ) );

		// See if any are set
		$variation_attribute_found = false;

		if ( $attributes ) {
			foreach ( $attributes as $attribute ) {
				if ( ! empty( $attribute['is_variation'] ) ) {
					$variation_attribute_found = true;
					break;
				}
			}
		}

		$variations_count       = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation' AND post_status IN ('publish', 'private')", $post->ID ) ) );
		$variations_per_page    = absint( apply_filters( 'woocommerce_admin_meta_boxes_variations_per_page', 15 ) );
		$variations_total_pages = ceil( $variations_count / $variations_per_page );
		?>
		<div id="variable_product_options" class="panel wc-metaboxes-wrapper hidden"><div id="variable_product_options_inner">

			<?php if ( ! $variation_attribute_found ) : ?>

				<div id="message" class="inline notice woocommerce-message">
					<p><?php _e( 'Before you can add a variation you need to add some variation attributes on the <strong>Attributes</strong> tab.', 'woocommerce' ); ?></p>
					<p>
						<a class="button-primary" href="<?php echo esc_url( apply_filters( 'woocommerce_docs_url', 'https://docs.woocommerce.com/document/variable-product/', 'product-variations' ) ); ?>" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a>
					</p>
				</div>

			<?php else : ?>

				<div class="toolbar toolbar-variations-defaults">
					<div class="variations-defaults">
						<strong><?php _e( 'Default Form Values', 'woocommerce' ); ?>: <?php echo wc_help_tip( __( 'These are the attributes that will be pre-selected on the frontend.', 'woocommerce' ) ); ?></strong>
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
								echo '<select name="default_attribute_' . sanitize_title( $attribute['name'] ) . '" data-current="' . esc_attr( $variation_selected_value ) . '"><option value="">' . __( 'No default', 'woocommerce' ) . ' ' . esc_html( wc_attribute_label( $attribute['name'] ) ) . '&hellip;</option>';

								// Get terms for attribute taxonomy or value if its a custom attribute
								if ( $attribute['is_taxonomy'] ) {
									$post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );

									foreach ( $post_terms as $term ) {
										echo '<option ' . selected( $variation_selected_value, $term->slug, false ) . ' value="' . esc_attr( $term->slug ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</option>';
									}
								} else {
									$options = wc_get_text_attributes( $attribute['value'] );

									foreach ( $options as $option ) {
										$selected = sanitize_title( $variation_selected_value ) === $variation_selected_value ? selected( $variation_selected_value, sanitize_title( $option ), false ) : selected( $variation_selected_value, $option, false );
										echo '<option ' . $selected . ' value="' . esc_attr( $option ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
									}
								}

								echo '</select>';
							}
						?>
					</div>
					<div class="clear"></div>
				</div>

				<div class="toolbar toolbar-top">
					<select id="field_to_edit" class="variation_actions">
						<option data-global="true" value="add_variation"><?php _e( 'Add variation', 'woocommerce' ); ?></option>
						<option data-global="true" value="link_all_variations"><?php _e( 'Create variations from all attributes', 'woocommerce' ); ?></option>
						<option value="delete_all"><?php _e( 'Delete all variations', 'woocommerce' ); ?></option>
						<optgroup label="<?php esc_attr_e( 'Status', 'woocommerce' ); ?>">
							<option value="toggle_enabled"><?php _e( 'Toggle &quot;Enabled&quot;', 'woocommerce' ); ?></option>
							<option value="toggle_downloadable"><?php _e( 'Toggle &quot;Downloadable&quot;', 'woocommerce' ); ?></option>
							<option value="toggle_virtual"><?php _e( 'Toggle &quot;Virtual&quot;', 'woocommerce' ); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e( 'Pricing', 'woocommerce' ); ?>">
							<option value="variable_regular_price"><?php _e( 'Set regular prices', 'woocommerce' ); ?></option>
							<option value="variable_regular_price_increase"><?php _e( 'Increase regular prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
							<option value="variable_regular_price_decrease"><?php _e( 'Decrease regular prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
							<option value="variable_sale_price"><?php _e( 'Set sale prices', 'woocommerce' ); ?></option>
							<option value="variable_sale_price_increase"><?php _e( 'Increase sale prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
							<option value="variable_sale_price_decrease"><?php _e( 'Decrease sale prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
							<option value="variable_sale_schedule"><?php _e( 'Set scheduled sale dates', 'woocommerce' ); ?></option>
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
							<option value="variable_download_expiry"><?php _e( 'Download expiry', 'woocommerce' ); ?></option>
						</optgroup>
						<?php do_action( 'woocommerce_variable_product_bulk_edit_actions' ); ?>
					</select>
					<a class="button bulk_edit do_variation_action"><?php _e( 'Go', 'woocommerce' ); ?></a>

					<div class="variations-pagenav">
						<span class="displaying-num"><?php printf( _n( '%s item', '%s items', $variations_count, 'woocommerce' ), $variations_count ); ?></span>
						<span class="expand-close">
							(<a href="#" class="expand_all"><?php _e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php _e( 'Close', 'woocommerce' ); ?></a>)
						</span>
						<span class="pagination-links">
							<a class="first-page disabled" title="<?php esc_attr_e( 'Go to the first page', 'woocommerce' ); ?>" href="#">&laquo;</a>
							<a class="prev-page disabled" title="<?php esc_attr_e( 'Go to the previous page', 'woocommerce' ); ?>" href="#">&lsaquo;</a>
							<span class="paging-select">
								<label for="current-page-selector-1" class="screen-reader-text"><?php _e( 'Select Page', 'woocommerce' ); ?></label>
								<select class="page-selector" id="current-page-selector-1" title="<?php esc_attr_e( 'Current page', 'woocommerce' ); ?>">
									<?php for ( $i = 1; $i <= $variations_total_pages; $i++ ) : ?>
										<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
									<?php endfor; ?>
								</select>
								 <?php _ex( 'of', 'number of pages', 'woocommerce' ); ?> <span class="total-pages"><?php echo $variations_total_pages; ?></span>
							</span>
							<a class="next-page" title="<?php esc_attr_e( 'Go to the next page', 'woocommerce' ); ?>" href="#">&rsaquo;</a>
							<a class="last-page" title="<?php esc_attr_e( 'Go to the last page', 'woocommerce' ); ?>" href="#">&raquo;</a>
						</span>
					</div>
					<div class="clear"></div>
				</div>

				<div class="woocommerce_variations wc-metaboxes" data-attributes="<?php
					// esc_attr does not double encode - htmlspecialchars does
					echo htmlspecialchars( json_encode( $attributes ) );
				?>" data-total="<?php echo $variations_count; ?>" data-total_pages="<?php echo $variations_total_pages; ?>" data-page="1" data-edited="false">
				</div>

				<div class="toolbar">
					<button type="button" class="button-primary save-variation-changes" disabled="disabled"><?php _e( 'Save changes', 'woocommerce' ); ?></button>
					<button type="button" class="button cancel-variation-changes" disabled="disabled"><?php _e( 'Cancel', 'woocommerce' ); ?></button>

					<div class="variations-pagenav">
						<span class="displaying-num"><?php printf( _n( '%s item', '%s items', $variations_count, 'woocommerce' ), $variations_count ); ?></span>
						<span class="expand-close">
							(<a href="#" class="expand_all"><?php _e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php _e( 'Close', 'woocommerce' ); ?></a>)
						</span>
						<span class="pagination-links">
							<a class="first-page disabled" title="<?php esc_attr_e( 'Go to the first page', 'woocommerce' ); ?>" href="#">&laquo;</a>
							<a class="prev-page disabled" title="<?php esc_attr_e( 'Go to the previous page', 'woocommerce' ); ?>" href="#">&lsaquo;</a>
							<span class="paging-select">
								<label for="current-page-selector-1" class="screen-reader-text"><?php _e( 'Select Page', 'woocommerce' ); ?></label>
								<select class="page-selector" id="current-page-selector-1" title="<?php esc_attr_e( 'Current page', 'woocommerce' ); ?>">
									<?php for ( $i = 1; $i <= $variations_total_pages; $i++ ) : ?>
										<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
									<?php endfor; ?>
								</select>
								 <?php _ex( 'of', 'number of pages', 'woocommerce' ); ?> <span class="total-pages"><?php echo $variations_total_pages; ?></span>
							</span>
							<a class="next-page" title="<?php esc_attr_e( 'Go to the next page', 'woocommerce' ); ?>" href="#">&rsaquo;</a>
							<a class="last-page" title="<?php esc_attr_e( 'Go to the last page', 'woocommerce' ); ?>" href="#">&raquo;</a>
						</span>
					</div>

					<div class="clear"></div>
				</div>

			<?php endif; ?>
		</div></div>
		<?php
	}

	/**
	 * Prepare downloads for save.
	 * @return array
	 */
	private static function prepare_downloads() {
		$downloads = array();

		if ( isset( $_POST['_wc_file_urls'] ) ) {
			$file_names    = isset( $_POST['_wc_file_names'] ) ? $_POST['_wc_file_names']                                  : array();
			$file_urls     = isset( $_POST['_wc_file_urls'] ) ? wp_unslash( array_map( 'trim', $_POST['_wc_file_urls'] ) ) : array();
			$file_url_size = sizeof( $file_urls );

			for ( $i = 0; $i < $file_url_size; $i ++ ) {
				if ( ! empty( $file_urls[ $i ] ) ) {
					$downloads[] = array(
						'name' => wc_clean( $file_names[ $i ] ),
						'file' => $file_urls[ $i ],
					);
				}
			}
		}

		return $downloads;
	}

	/**
	 * Prepare children for save.
	 * @return array
	 */
	private static function prepare_children() {
		return isset( $_POST['grouped_products'] ) ? array_filter( array_map( 'intval', explode( ',', $_POST['grouped_products'] ) ) ) : array();
	}

	/**
	 * Prepare attributes for save.
	 * @return array
	 */
	public static function prepare_attributes( $data = false ) {
		$attributes = array();

		if ( ! $data ) {
			$data = $_POST;
		}

		if ( isset( $data['attribute_names'], $data['attribute_values'] ) ) {
			$attribute_names         = $data['attribute_names'];
			$attribute_values        = $data['attribute_values'];
			$attribute_visibility    = isset( $data['attribute_visibility'] ) ? $data['attribute_visibility'] : array();
			$attribute_variation     = isset( $data['attribute_variation'] ) ? $data['attribute_variation'] : array();
			$attribute_position      = $data['attribute_position'];
			$attribute_names_max_key = max( array_keys( $attribute_names ) );

			for ( $i = 0; $i <= $attribute_names_max_key; $i++ ) {
				if ( empty( $attribute_names[ $i ] ) || ! isset( $attribute_values[ $i ] ) ) {
					continue;
				}
				$attribute_name = wc_clean( $attribute_names[ $i ] );
				$attribute_id   = wc_attribute_taxonomy_id_by_name( $attribute_name );
				$options        = isset( $attribute_values[ $i ] ) ? $attribute_values[ $i ] : '';

				if ( is_array( $options ) ) {
					// Term ids sent as array.
					$options = wp_parse_id_list( $options );
				} else {
					// Terms or text sent in textarea.
					$options = 0 < $attribute_id ? wc_sanitize_textarea( wc_sanitize_term_text_based( $options ) ) : wc_sanitize_textarea( $options );
					$options = wc_get_text_attributes( $options );
				}

				$attribute = new WC_Product_Attribute();
				$attribute->set_id( $attribute_id );
				$attribute->set_name( $attribute_name );
				$attribute->set_options( $options );
				$attribute->set_position( $attribute_position[ $i ] );
				$attribute->set_visible( isset( $attribute_visibility[ $i ] ) );
				$attribute->set_variation( isset( $attribute_variation[ $i ] ) );
				$attributes[] = $attribute;
			}
		}
		return $attributes;
	}

	/**
	 * Save meta box data.
	 */
	public static function save( $post_id, $post ) {
		// Process product type first so we have the correct class to run setters.
		$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
		$classname    = WC_Product_Factory::get_classname_from_product_type( $product_type );

		if ( ! class_exists( $classname ) ) {
			$classname = 'WC_Product_Simple';
		}

		$product = new $classname( $post_id );
		$errors  = $product->set_props( array(
			'sku'               => wc_clean( $_POST['_sku'] ),
			'purchase_note'     => wp_kses_post( stripslashes( $_POST['_purchase_note'] ) ),
			'downloadable'      => isset( $_POST['_downloadable'] ) ,
			'virtual'           => isset( $_POST['_virtual'] ),
			'tax_status'        => wc_clean( $_POST['_tax_status'] ),
			'tax_class'         => wc_clean( $_POST['_tax_class'] ),
			'weight'            => wc_clean( $_POST['_weight'] ),
			'length'            => wc_clean( $_POST['_length'] ),
			'width'             => wc_clean( $_POST['_width'] ),
			'height'            => wc_clean( $_POST['_height'] ),
			'shipping_class_id' => absint( $_POST['product_shipping_class'] ),
			'sold_individually' => ! empty( $_POST['_sold_individually'] ),
			'upsell_ids'        => array_map( 'intval', explode( ',', $_POST['upsell_ids'] ) ),
			'crosssell_ids'     => array_map( 'intval', explode( ',', $_POST['crosssell_ids'] ) ),
			'regular_price'     => wc_clean( $_POST['_regular_price'] ),
			'sale_price'        => wc_clean( $_POST['_sale_price'] ),
			'date_on_sale_from' => wc_clean( $_POST['_sale_price_dates_from'] ),
			'date_on_sale_to'   => wc_clean( $_POST['_sale_price_dates_to'] ),
			'manage_stock'      => ! empty( $_POST['_manage_stock'] ),
			'backorders'        => wc_clean( $_POST['_backorders'] ),
			'stock_status'      => wc_clean( $_POST['_stock_status'] ),
			'stock'             => wc_stock_amount( $_POST['_stock'] ),
			'attributes'        => self::prepare_attributes(),
			'download_limit'    => '' === $_POST['_download_limit'] ? '' : absint( $_POST['_download_limit'] ),
			'download_expiry'   => '' === $_POST['_download_expiry'] ? '' : absint( $_POST['_download_expiry'] ),
			'download_type'     => wc_clean( $_POST['_download_type'] ),
			'downloads'         => self::prepare_downloads(),
			'product_url'       => esc_url_raw( $_POST['_product_url'] ),
			'button_text'       => wc_clean( $_POST['_button_text'] ),
			'children'          => 'grouped' === $product_type ? self::prepare_children() : null,
		) );

		if ( is_wp_error( $errors ) ) {
			WC_Admin_Meta_Boxes::add_error( $errors->get_error_message() );
		}

		$product->save();

		// Do action for product type
		do_action( 'woocommerce_process_product_meta_' . $product_type, $post_id );
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
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

			$variable_description           = isset( $_POST['variable_description'] ) ? $_POST['variable_description'] : array();

			$max_loop = max( array_keys( $_POST['variable_post_id'] ) );

			for ( $i = 0; $i <= $max_loop; $i ++ ) {

				if ( ! isset( $variable_post_id[ $i ] ) ) {
					continue;
				}

				$variation_id = absint( $variable_post_id[ $i ] );

				// Checkboxes
				$is_virtual      = isset( $variable_is_virtual[ $i ] ) ? 'yes' : 'no';
				$is_downloadable = isset( $variable_is_downloadable[ $i ] ) ? 'yes' : 'no';
				$post_status     = isset( $variable_enabled[ $i ] ) ? 'publish' : 'private';
				$manage_stock    = isset( $variable_manage_stock[ $i ] ) ? 'yes' : 'no';

				// Generate a useful post title
				$variation_post_title = sprintf( __( 'Variation #%1$s of %2$s', 'woocommerce' ), absint( $variation_id ), esc_html( get_the_title( $post_id ) ) );

				// Update or Add post
				if ( ! $variation_id ) {

					$variation = array(
						'post_title'   => $variation_post_title,
						'post_content' => '',
						'post_status'  => $post_status,
						'post_author'  => get_current_user_id(),
						'post_parent'  => $post_id,
						'post_type'    => 'product_variation',
						'menu_order'   => $variable_menu_order[ $i ],
					);

					$variation_id = wp_insert_post( $variation );

					do_action( 'woocommerce_create_product_variation', $variation_id );

				} else {

					$modified_date = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) );

					$wpdb->update( $wpdb->posts, array(
							'post_status'       => $post_status,
							'post_title'        => $variation_post_title,
							'menu_order'        => $variable_menu_order[ $i ],
							'post_modified'     => $modified_date,
							'post_modified_gmt' => get_gmt_from_date( $modified_date ),
					), array( 'ID' => $variation_id ) );

					clean_post_cache( $variation_id );

					do_action( 'woocommerce_update_product_variation', $variation_id );

				}

				// Only continue if we have a variation ID
				if ( ! $variation_id ) {
					continue;
				}

				// Unique SKU
				$sku     = get_post_meta( $variation_id, '_sku', true );
				$new_sku = wc_clean( $variable_sku[ $i ] );

				if ( '' == $new_sku ) {
					update_post_meta( $variation_id, '_sku', '' );
				} elseif ( $new_sku !== $sku ) {
					if ( ! empty( $new_sku ) ) {
						$unique_sku = wc_product_has_unique_sku( $variation_id, $new_sku );

						if ( ! $unique_sku ) {
							WC_Admin_Meta_Boxes::add_error( sprintf( __( '#%s &ndash; Variation SKU must be unique.', 'woocommerce' ), $variation_id ) );
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

				if ( 'yes' === $manage_stock ) {
					update_post_meta( $variation_id, '_backorders', wc_clean( $variable_backorders[ $i ] ) );
					wc_update_product_stock( $variation_id, wc_stock_amount( $variable_stock[ $i ] ) );
				} else {
					delete_post_meta( $variation_id, '_backorders' );
					delete_post_meta( $variation_id, '_stock' );
				}

				// Only update stock status to user setting if changed by the user, but do so before looking at stock levels at variation level
				if ( ! empty( $variable_stock_status[ $i ] ) ) {
					wc_update_product_stock_status( $variation_id, $variable_stock_status[ $i ] );
				}

				// Price handling
				_wc_save_product_price( $variation_id, $variable_regular_price[ $i ], $variable_sale_price[ $i ], $variable_sale_price_dates_from[ $i ], $variable_sale_price_dates_to[ $i ] );

				if ( isset( $variable_tax_class[ $i ] ) && 'parent' !== $variable_tax_class[ $i ] ) {
					update_post_meta( $variation_id, '_tax_class', wc_clean( $variable_tax_class[ $i ] ) );
				} else {
					delete_post_meta( $variation_id, '_tax_class' );
				}

				if ( 'yes' == $is_downloadable ) {
					update_post_meta( $variation_id, '_download_limit', wc_clean( $variable_download_limit[ $i ] ) );
					update_post_meta( $variation_id, '_download_expiry', wc_clean( $variable_download_expiry[ $i ] ) );

					$files              = array();
					$file_names         = isset( $_POST['_wc_variation_file_names'][ $variation_id ] ) ? array_map( 'wc_clean', $_POST['_wc_variation_file_names'][ $variation_id ] ) : array();
					$file_urls          = isset( $_POST['_wc_variation_file_urls'][ $variation_id ] ) ? array_map( 'wc_clean', $_POST['_wc_variation_file_urls'][ $variation_id ] ) : array();
					$file_url_size      = sizeof( $file_urls );
					$allowed_file_types = get_allowed_mime_types();

					for ( $ii = 0; $ii < $file_url_size; $ii ++ ) {
						if ( ! empty( $file_urls[ $ii ] ) ) {
							// Find type and file URL
							if ( 0 === strpos( $file_urls[ $ii ], 'http' ) ) {
								$file_is  = 'absolute';
								$file_url = esc_url_raw( $file_urls[ $ii ] );
							} elseif ( '[' === substr( $file_urls[ $ii ], 0, 1 ) && ']' === substr( $file_urls[ $ii ], -1 ) ) {
								$file_is  = 'shortcode';
								$file_url = wc_clean( $file_urls[ $ii ] );
							} else {
								$file_is = 'relative';
								$file_url = wc_clean( $file_urls[ $ii ] );
							}

							$file_name = wc_clean( $file_names[ $ii ] );
							$file_hash = md5( $file_url );

							// Validate the file extension
							if ( in_array( $file_is, array( 'absolute', 'relative' ) ) ) {
								$file_type  = wp_check_filetype( strtok( $file_url, '?' ), $allowed_file_types );
								$parsed_url = parse_url( $file_url, PHP_URL_PATH );
								$extension  = pathinfo( $parsed_url, PATHINFO_EXTENSION );

								if ( ! empty( $extension ) && ! in_array( $file_type['type'], $allowed_file_types ) ) {
									WC_Admin_Meta_Boxes::add_error( sprintf( __( '#%1$s &ndash; The downloadable file %2$s cannot be used as it does not have an allowed file type. Allowed types include: %3$s', 'woocommerce' ), $variation_id, '<code>' . basename( $file_url ) . '</code>', '<code>' . implode( ', ', array_keys( $allowed_file_types ) ) . '</code>' ) );
									continue;
								}
							}

							// Validate the file exists
							if ( 'relative' === $file_is && ! apply_filters( 'woocommerce_downloadable_file_exists', file_exists( $file_url ), $file_url ) ) {
								WC_Admin_Meta_Boxes::add_error( sprintf( __( '#%1$s &ndash; The downloadable file %2$s cannot be used as it does not exist on the server.', 'woocommerce' ), $variation_id, '<code>' . $file_url . '</code>' ) );
								continue;
							}

							$files[ $file_hash ] = array(
								'name' => $file_name,
								'file' => $file_url,
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

				update_post_meta( $variation_id, '_variation_description', wp_kses_post( $variable_description[ $i ] ) );

				// Save shipping class
				$variable_shipping_class[ $i ] = ! empty( $variable_shipping_class[ $i ] ) ? (int) $variable_shipping_class[ $i ] : '';
				wp_set_object_terms( $variation_id, $variable_shipping_class[ $i ], 'product_shipping_class' );

				// Update Attributes
				$updated_attribute_keys = array();
				foreach ( $attributes as $attribute ) {
					if ( $attribute['is_variation'] ) {
						$attribute_key            = 'attribute_' . sanitize_title( $attribute['name'] );
						$updated_attribute_keys[] = $attribute_key;

						if ( $attribute['is_taxonomy'] ) {
							// Don't use wc_clean as it destroys sanitized characters
							$value = isset( $_POST[ $attribute_key ][ $i ] ) ? sanitize_title( stripslashes( $_POST[ $attribute_key ][ $i ] ) ) : '';
						} else {
							$value = isset( $_POST[ $attribute_key ][ $i ] ) ? wc_clean( stripslashes( $_POST[ $attribute_key ][ $i ] ) ) : '';
						}

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
				$value = '';

				if ( isset( $_POST[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] ) ) {
					if ( $attribute['is_taxonomy'] ) {
						// Don't use wc_clean as it destroys sanitized characters
						$value = sanitize_title( trim( stripslashes( $_POST[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] ) ) );
					} else {
						$value = wc_clean( trim( stripslashes( $_POST[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] ) ) );
					}
				}

				if ( $value ) {
					$default_attributes[ sanitize_title( $attribute['name'] ) ] = $value;
				}
			}
		}

		update_post_meta( $post_id, '_default_attributes', $default_attributes );
	}
}
