<?php
/**
 * Handles taxonomies in admin
 *
 * @class    WC_Admin_Taxonomies
 * @version  2.3.10
 * @package  WooCommerce/Admin
 * @category Class
 * @author   WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin_Taxonomies class.
 */
class WC_Admin_Taxonomies {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Category/term ordering
		add_action( 'create_term', array( $this, 'create_term' ), 5, 3 );
		add_action( 'delete_term', array( $this, 'delete_term' ), 5 );

		// Add form
		add_action( 'product_cat_add_form_fields', array( $this, 'add_category_fields' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'edit_category_fields' ), 10 );
		add_action( 'created_term', array( $this, 'save_category_fields' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_category_fields' ), 10, 3 );

		// Add columns
		add_filter( 'manage_edit-product_cat_columns', array( $this, 'product_cat_columns' ) );
		add_filter( 'manage_product_cat_custom_column', array( $this, 'product_cat_column' ), 10, 3 );

		// Taxonomy page descriptions
		add_action( 'product_cat_pre_add_form', array( $this, 'product_cat_description' ) );

        //Contributed by Thewpexperts for color swatch
        add_action('woocommerce_product_attribute_field', array($this, 'woocommerce_term_custom_attribute_fields'), 10, 4);

        //Contributed by Thewpexperts for color swatch
        add_action('admin_footer', array($this, 'woocommerce_add_new_color_attribute_popup'));

		$attribute_taxonomies = wc_get_attribute_taxonomies();

		if ( ! empty( $attribute_taxonomies ) ) {
			foreach ( $attribute_taxonomies as $attribute ) {
                            add_action( 'pa_' . $attribute->attribute_name . '_pre_add_form', array( $this, 'product_attribute_description' ) );
                            //Contributed by Thewpexperts for color swatch
                            add_action('pa_' . $attribute->attribute_name . '_add_form_fields', array($this, 'woocommerce_add_attribute_fields'));
                            add_action('pa_' . $attribute->attribute_name . '_edit_form_fields', array($this, 'woocommerce_edit_attribute_fields'), 10, 2);
                            //Contributed by Thewpexperts for color swatch
                            add_filter('manage_edit-pa_' . $attribute->attribute_name . '_columns', array($this, 'woocommerce_add_attribute_columns'));
                            add_filter('manage_pa_' . $attribute->attribute_name . '_custom_column', array($this, 'woocommerce_add_attribute_column'), 10, 3);
                                
			}
		}

		// Maintain hierarchy of terms
		add_filter( 'wp_terms_checklist_args', array( $this, 'disable_checked_ontop' ) );
	}
 /**
  * Merged color swatch column with default columns. 
  * 
  * @param array $columns
  * @return array
  */
    public function woocommerce_add_attribute_columns($columns) {
        $attr = wc_get_tax_attribute($_REQUEST['taxonomy']);
        $new_columns = array();
        if ('color' === $attr->attribute_type) {
            $new_columns['cb'] = $columns['cb'];
            $new_columns['swatch'] = '';
            unset($columns['cb']);
        }
        return array_merge($new_columns, $columns);
    }

    /**
     * Adds a new column for color swatch
     * 
     * @param type $columns
     * @param type $column
     * @param type $term_id
     * Contributed by Thewpexperts for color swatch
     */
    public function woocommerce_add_attribute_column($columns, $column, $term_id) {
        $attr = wc_get_tax_attribute($_REQUEST['taxonomy']);
        switch ($attr->attribute_type) {
            case 'color':
                $width = 80;
                $height = 75;
                $color_combination = get_woocommerce_term_meta($term_id, 'term-color-comb', true);
                printf('<div class="multi-color-swatch-cloumn" style="border: 1px solid #e7e7e7;height:%s;width:%s;">', esc_attr($height . 'px'), esc_attr($width . 'px'));
                for ($i = 1; $i <= $color_combination; $i++) {
                    $value = get_woocommerce_term_meta($term_id, "color-$i", true);
                    printf('<div class="color-value" style="height:%s;width:%s;background-color:%s;float:left;"></div>', esc_attr($height . 'px'), esc_attr(round((100 / $color_combination), 2) . '%'), $value);
                }
                printf('</div>');
                break;
        }
    }
	/**
	 * Order term when created (put in position 0).
	 *
	 * @param mixed $term_id
	 * @param mixed $tt_id
	 * @param string $taxonomy
	 */
	public function create_term( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( 'product_cat' != $taxonomy && ! taxonomy_is_product_attribute( $taxonomy ) ) {
			return;
		}

		$meta_name = taxonomy_is_product_attribute( $taxonomy ) ? 'order_' . esc_attr( $taxonomy ) : 'order';

		update_woocommerce_term_meta( $term_id, $meta_name, 0 );
	}

	/**
	 * When a term is deleted, delete its meta.
	 *
	 * @param mixed $term_id
	 */
	public function delete_term( $term_id ) {
		global $wpdb;

		$term_id = absint( $term_id );

		if ( $term_id && get_option( 'db_version' ) < 34370 ) {
			$wpdb->delete( $wpdb->woocommerce_termmeta, array( 'woocommerce_term_id' => $term_id ), array( '%d' ) );
		}
	}

	/**
	 * Category thumbnail fields.
	 */
	public function add_category_fields() {
		?>
		<div class="form-field term-display-type-wrap">
			<label for="display_type"><?php _e( 'Display type', 'woocommerce' ); ?></label>
			<select id="display_type" name="display_type" class="postform">
				<option value=""><?php _e( 'Default', 'woocommerce' ); ?></option>
				<option value="products"><?php _e( 'Products', 'woocommerce' ); ?></option>
				<option value="subcategories"><?php _e( 'Subcategories', 'woocommerce' ); ?></option>
				<option value="both"><?php _e( 'Both', 'woocommerce' ); ?></option>
			</select>
		</div>
		<div class="form-field term-thumbnail-wrap">
			<label><?php _e( 'Thumbnail', 'woocommerce' ); ?></label>
			<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;"><img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" width="60px" height="60px" /></div>
			<div style="line-height: 60px;">
				<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" />
				<button type="button" class="upload_image_button button"><?php _e( 'Upload/Add image', 'woocommerce' ); ?></button>
				<button type="button" class="remove_image_button button"><?php _e( 'Remove image', 'woocommerce' ); ?></button>
			</div>
			<script type="text/javascript">

				// Only show the "remove image" button when needed
				if ( ! jQuery( '#product_cat_thumbnail_id' ).val() ) {
					jQuery( '.remove_image_button' ).hide();
				}

				// Uploading files
				var file_frame;

				jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

					event.preventDefault();

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.downloadable_file = wp.media({
						title: '<?php _e( "Choose an image", "woocommerce" ); ?>',
						button: {
							text: '<?php _e( "Use image", "woocommerce" ); ?>'
						},
						multiple: false
					});

					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {
						var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
						var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

						jQuery( '#product_cat_thumbnail_id' ).val( attachment.id );
						jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
						jQuery( '.remove_image_button' ).show();
					});

					// Finally, open the modal.
					file_frame.open();
				});

				jQuery( document ).on( 'click', '.remove_image_button', function() {
					jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
					jQuery( '#product_cat_thumbnail_id' ).val( '' );
					jQuery( '.remove_image_button' ).hide();
					return false;
				});

				jQuery( document ).ajaxComplete( function( event, request, options ) {
					if ( request && 4 === request.readyState && 200 === request.status
						&& options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {

						var res = wpAjax.parseAjaxResponse( request.responseXML, 'ajax-response' );
						if ( ! res || res.errors ) {
							return;
						}
						// Clear Thumbnail fields on submit
						jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
						jQuery( '#product_cat_thumbnail_id' ).val( '' );
						jQuery( '.remove_image_button' ).hide();
						// Clear Display type field on submit
						jQuery( '#display_type' ).val( '' );
						return;
					}
				} );

			</script>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Edit category thumbnail field.
	 *
	 * @param mixed $term Term (category) being edited
	 */
	public function edit_category_fields( $term ) {

		$display_type = get_woocommerce_term_meta( $term->term_id, 'display_type', true );
		$thumbnail_id = absint( get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true ) );

		if ( $thumbnail_id ) {
			$image = wp_get_attachment_thumb_url( $thumbnail_id );
		} else {
			$image = wc_placeholder_img_src();
		}
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Display type', 'woocommerce' ); ?></label></th>
			<td>
				<select id="display_type" name="display_type" class="postform">
					<option value="" <?php selected( '', $display_type ); ?>><?php _e( 'Default', 'woocommerce' ); ?></option>
					<option value="products" <?php selected( 'products', $display_type ); ?>><?php _e( 'Products', 'woocommerce' ); ?></option>
					<option value="subcategories" <?php selected( 'subcategories', $display_type ); ?>><?php _e( 'Subcategories', 'woocommerce' ); ?></option>
					<option value="both" <?php selected( 'both', $display_type ); ?>><?php _e( 'Both', 'woocommerce' ); ?></option>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Thumbnail', 'woocommerce' ); ?></label></th>
			<td>
				<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;"><img src="<?php echo esc_url( $image ); ?>" width="60px" height="60px" /></div>
				<div style="line-height: 60px;">
					<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" value="<?php echo $thumbnail_id; ?>" />
					<button type="button" class="upload_image_button button"><?php _e( 'Upload/Add image', 'woocommerce' ); ?></button>
					<button type="button" class="remove_image_button button"><?php _e( 'Remove image', 'woocommerce' ); ?></button>
				</div>
				<script type="text/javascript">

					// Only show the "remove image" button when needed
					if ( '0' === jQuery( '#product_cat_thumbnail_id' ).val() ) {
						jQuery( '.remove_image_button' ).hide();
					}

					// Uploading files
					var file_frame;

					jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if ( file_frame ) {
							file_frame.open();
							return;
						}

						// Create the media frame.
						file_frame = wp.media.frames.downloadable_file = wp.media({
							title: '<?php _e( "Choose an image", "woocommerce" ); ?>',
							button: {
								text: '<?php _e( "Use image", "woocommerce" ); ?>'
							},
							multiple: false
						});

						// When an image is selected, run a callback.
						file_frame.on( 'select', function() {
							var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
							var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

							jQuery( '#product_cat_thumbnail_id' ).val( attachment.id );
							jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
							jQuery( '.remove_image_button' ).show();
						});

						// Finally, open the modal.
						file_frame.open();
					});

					jQuery( document ).on( 'click', '.remove_image_button', function() {
						jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
						jQuery( '#product_cat_thumbnail_id' ).val( '' );
						jQuery( '.remove_image_button' ).hide();
						return false;
					});

				</script>
				<div class="clear"></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * save_category_fields function.
	 *
	 * @param mixed $term_id Term ID being saved
	 * @param mixed $tt_id
	 * @param string $taxonomy
	 */
	public function save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( isset( $_POST['display_type'] ) && 'product_cat' === $taxonomy ) {
			update_woocommerce_term_meta( $term_id, 'display_type', esc_attr( $_POST['display_type'] ) );
		}
		if ( isset( $_POST['product_cat_thumbnail_id'] ) && 'product_cat' === $taxonomy ) {
			update_woocommerce_term_meta( $term_id, 'thumbnail_id', absint( $_POST['product_cat_thumbnail_id'] ) );
		}
                //Contributed by Thewpexperts for color swatch
                if ( isset( $_POST[ 'term-color-comb' ] ) ) {
                    update_woocommerce_term_meta( $term_id, 'term-color-comb', absint( $_POST[ 'term-color-comb' ] ) );
                    update_woocommerce_term_meta( $term_id, 'color-1', esc_attr( $_POST[ 'color-1' ] ) );
                    update_woocommerce_term_meta( $term_id, 'color-2', esc_attr( $_POST[ 'color-2' ] ) );
                    update_woocommerce_term_meta( $term_id, 'color-3', esc_attr( $_POST[ 'color-3' ] ) );
                }
    }

	/**
	 * Description for product_cat page to aid users.
	 */
	public function product_cat_description() {
		echo wpautop( __( 'Product categories for your store can be managed here. To change the order of categories on the front-end you can drag and drop to sort them. To see more categories listed click the "screen options" link at the top-right of this page.', 'woocommerce' ) );
	}

	/**
	 * Description for shipping class page to aid users.
	 */
	public function product_attribute_description() {
		echo wpautop( __( 'Attribute terms can be assigned to products and variations.<br/><br/><b>Note</b>: Deleting a term will remove it from all products and variations to which it has been assigned. Recreating a term will not automatically assign it back to products.', 'woocommerce' ) );
	}

	/**
	 * Thumbnail column added to category admin.
	 *
	 * @param mixed $columns
	 * @return array
	 */
	public function product_cat_columns( $columns ) {
		$new_columns = array();

		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
			unset( $columns['cb'] );
		}

		$new_columns['thumb'] = __( 'Image', 'woocommerce' );

		return array_merge( $new_columns, $columns );
	}

	/**
	 * Thumbnail column value added to category admin.
	 *
	 * @param string $columns
	 * @param string $column
	 * @param int $id
	 *
	 * @return string
	 */
	public function product_cat_column( $columns, $column, $id ) {

		if ( 'thumb' == $column ) {

			$thumbnail_id = get_woocommerce_term_meta( $id, 'thumbnail_id', true );

			if ( $thumbnail_id ) {
				$image = wp_get_attachment_thumb_url( $thumbnail_id );
			} else {
				$image = wc_placeholder_img_src();
			}

			// Prevent esc_url from breaking spaces in urls for image embeds
			// Ref: https://core.trac.wordpress.org/ticket/23605
			$image = str_replace( ' ', '%20', $image );

			$columns .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr__( 'Thumbnail', 'woocommerce' ) . '" class="wp-post-image" height="48" width="48" />';

		}

		return $columns;
	}

	/**
	 * Maintain term hierarchy when editing a product.
	 *
	 * @param  array $args
	 * @return array
	 */
	public function disable_checked_ontop( $args ) {
		if ( ! empty( $args['taxonomy'] ) && 'product_cat' === $args['taxonomy'] ) {
			$args['checked_ontop'] = false;
		}
		return $args;
	}
        
    /** 
     * Print HTML for color swatch on attribute term screens
     *
     * @param $term 
     * @param $type
     * @param $value
     * @param $form
     * Contributed by Thewpexperts for color swatch
     */
    public function woocommerce_term_custom_attribute_fields($term, $type, $value, $form) {

        if (in_array($type, array('select', 'text'))) {
            return;
        }
        printf(
                '<%s class="form-field">%s<label for="term-%s">%s</label>%s', 'edit' == $form ? 'tr' : 'div', 'edit' == $form ? '<th>' : '', esc_attr($type), 'Select Color Combinations', 'edit' == $form ? '</th><td>' : ''
        );
        switch ($type) {
            case 'color':
                $color_comb = get_woocommerce_term_meta($term->term_id, 'term-color-comb', true);
                ?>
                <div style="line-height:60px;">
                    <?php
                    for ($numberOfColor = 1; $numberOfColor <= 3; $numberOfColor++) {
                        ?>
                        <input type="radio"  name="term-<?php echo esc_attr($type) ?>-comb" id="term-<?php echo esc_attr($type) . "-comb_$numberOfColor" ?>" value="<?php echo $numberOfColor; ?>" class="select_comb_radio" <?php echo ($color_comb == $numberOfColor) ? 'checked="checked"' : ''; ?>  />
                        <?php
                        esc_html_e(($numberOfColor == 1 ? 'One Color ' : ($numberOfColor == 2 ? 'Two Color ' : 'Three Color ')), 'woocommerce');
                    }
                    ?>
                </div>
                <?php
                for ($color = 1; $color <= 3; $color++) {
                    $colorVal = get_woocommerce_term_meta($term->term_id, "color-$color", true);
                    ?>
                    <input type="text" id="term-<?php echo esc_attr($type) . "-picker-$color" ?>" name="<?php echo esc_attr($type) . "-$color" ?>" value="<?php echo esc_attr($colorVal) ?>" />
                    <?php
                }
                break;

            default:

                break;
        }
        echo 'edit' == $form ? '</td></tr>' : '</div>';
    }

    /**
     * Show a Popup for color swatch items.
     * 
     * Contributed by Thewpexperts for color swatch
     */
    public function woocommerce_add_new_color_attribute_popup() {

        global $post_type;

        if ($post_type != 'product') {
            return;
        }
        ?>
        <div id="wc-modal-add-new-color-attribute-container" class="wc-swatch-modal-container hidden">
            <div class="wc-swatch-modal">
                <button type="button" class="wc-swatch-popup-close button-link media-modal-close">
                    <span class="media-modal-icon"></span></button>
                <div class="wc-swatch-modal-header">
                    <h2><?php esc_html_e('Add New Value', 'woocommerce') ?></h2>
                </div>
                <div class="wc-swatch-modal-content">
                    <div class="form-field">
                        <label class="field-label" for="name"><?php _e('Name', 'woocommerce'); ?></label>
                        <input type="text" name="term" class="swatch-input term_name">
                        <p><?php _e('The name is how it appears on your site.', 'woocommerce'); ?></p>
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="slug"><?php _e('Slug', 'woocommerce'); ?></label>
                        <input type="text" name="slug" class="swatch-input term_slug">
                        <p><?php _e('The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'woocommerce.'); ?></p>
                    </div>
                    <div class="form-field">
                        <label class="field-label">Select Color Combinations</label>
                        <br/>
                        <?php
                        $type = 'color';
                        for ($numberOfColor = 1; $numberOfColor <= 3; $numberOfColor++) {
                            ?>
                            <input type="radio"  name="term-<?php echo esc_attr($type) ?>-comb" id="term-<?php echo esc_attr($type) . "-comb_$numberOfColor" ?>" value="<?php echo $numberOfColor; ?>" class="select_comb_radio swatch-input"/>
                            <label for="<?php echo 'term-' . esc_attr($type) . '-comb_' . $numberOfColor . ''; ?>">
                                <?php
                                esc_html_e(($numberOfColor == 1 ? 'One Color ' : ($numberOfColor == 2 ? 'Two Color ' : 'Three Color ')), 'woocommerce');
                            }
                            ?>
                        </label>
                    </div>
                    <br/>
                    <div class="form-field">
                        <?php
                        for ($color = 1; $color <= 3; $color++) {
                            ?>
                            <input type="text" id="term-<?php echo esc_attr($type) . "-picker-$color" ?>" name="<?php echo esc_attr($type) . "-$color" ?>"  class="swatch-input"/>
                            <?php
                        }
                        ?>
                    </div>
                    <input type="text" name="taxonomytype" class="swatch-input hidden" value="<?php echo $type; ?>">
                </div>
                <div class="hidden wc-swatch-taxonomy"> </div>
                <div class="wc-swatch-modal-footer">
                    <button class="wc-swatch-popup-close button button-secondary">
                        <?php esc_html_e('Cancel', 'woocommerce') ?></button>
                    <button class="button button-primary wc-modal-new-attribute-submit">
                        <?php esc_html_e('Add New', 'woocommerce') ?></button>
                </div>
            </div>

            <script type="text/template" id="tmpl-swatch-input-taxonomy">
                <input type="hidden" name="taxonomy" value="{{data.taxonomy}}" class="swatch-input">
            </script>
            <div class="wc-swatch-modal-main"></div>
        </div>
        <?php
    }

    /** 
     * 
     * A hook to add fields to "Add attribute" term screen.
     *
     * @param string $taxonomy
     * Contributed by Thewpexperts for color swatch
     */
    public function woocommerce_add_attribute_fields($taxonomy) {
        $attr = wc_get_tax_attribute($taxonomy);
        do_action('woocommerce_product_attribute_field', $taxonomy, $attr->attribute_type, '', 'add');
    }

    /** 
     * A hook to add fields to "Edit attribute" term screen
     *
     * @param string $taxonomy
     * Contributed by Thewpexperts for color swatch
     */
    public function woocommerce_edit_attribute_fields($term, $taxonomy) {
        $attr = wc_get_tax_attribute($taxonomy);
        $value = get_term_meta($term->term_id, $attr->attribute_type, true);
        do_action('woocommerce_product_attribute_field', $term, $attr->attribute_type, $value, 'edit');
    }        
}

new WC_Admin_Taxonomies();
