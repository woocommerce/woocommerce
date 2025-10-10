<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName.
/**
 * Brands Admin Page
 *
 * Important: For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @package WooCommerce\Admin
 * @version x.x.x
 */

declare( strict_types = 1);

use Automattic\Jetpack\Constants;

/**
 * WC_Brands_Admin class.
 */
class WC_Brands_Admin {

	/**
	 * Settings array (Deprecated).
	 *
	 * @var array
	 */
	public $settings_tabs;

	/**
	 * Settings form fields (Deprecated).
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Admin fields.
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * __construct function.
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'styles' ) );
		add_action( 'product_brand_add_form_fields', array( $this, 'add_thumbnail_field' ) );
		add_action( 'product_brand_edit_form_fields', array( $this, 'edit_thumbnail_field' ), 10, 1 );
		add_action( 'created_term', array( $this, 'thumbnail_field_save' ), 10, 1 );
		add_action( 'edit_term', array( $this, 'thumbnail_field_save' ), 10, 1 );
		add_action( 'product_brand_pre_add_form', array( $this, 'taxonomy_description' ) );
		add_filter( 'woocommerce_sortable_taxonomies', array( $this, 'sort_brands' ) );
		add_filter( 'manage_edit-product_brand_columns', array( $this, 'columns' ) );
		add_filter( 'manage_product_brand_custom_column', array( $this, 'column' ), 10, 3 );
		add_filter( 'manage_product_posts_columns', array( $this, 'product_columns' ), 20, 1 );
		add_filter(
			'woocommerce_products_admin_list_table_filters',
			function ( $args ) {
				$args['product_brand'] = array( $this, 'render_product_brand_filter' );
				return $args;
			}
		);

		// Hiding setting for future deprecation. Only users who have touched these settings should see it.
		$setting_value = get_option( 'wc_brands_show_description' );
		if ( is_string( $setting_value ) ) {

			// Add the settings fields to each tab.
			add_action(
				'before_woocommerce_init',
				function () {
					$this->init_form_fields();
					$this->settings_tabs = array(
						'brands' => __( 'Brands', 'woocommerce' ),
					);
				}
			);
			add_action( 'woocommerce_get_sections_products', array( $this, 'add_settings_tab' ) );
			add_action( 'woocommerce_get_settings_products', array( $this, 'add_settings_section' ), null, 2 );
		}

		add_action( 'woocommerce_update_options_catalog', array( $this, 'save_admin_settings' ) );

		/* 2.1 */
		add_action( 'woocommerce_update_options_products', array( $this, 'save_admin_settings' ) );

		// Add brands filtering to the coupon creation screens.
		add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'add_coupon_brands_fields' ) );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_coupon_brands' ) );

		// Permalinks.
		add_filter( 'pre_update_option_woocommerce_permalinks', array( $this, 'validate_product_base' ) );

		add_action( 'current_screen', array( $this, 'add_brand_base_setting' ) );

		// CSV Import/Export Support.
		// https://github.com/woocommerce/woocommerce/wiki/Product-CSV-Importer-&-Exporter
		// Import.
		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'add_column_to_importer_exporter' ), 10 );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( $this, 'add_default_column_mapping' ), 10 );
		add_filter( 'woocommerce_product_importer_formatting_callbacks', array( $this, 'add_formatting_callback' ), 10, 2 );
		add_filter( 'woocommerce_product_import_inserted_product_object', array( $this, 'process_import' ), 10, 2 );

		// Export.
		add_filter( 'woocommerce_product_export_column_names', array( $this, 'add_column_to_importer_exporter' ), 10 );
		add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'add_column_to_importer_exporter' ), 10 );
		add_filter( 'woocommerce_product_export_product_column_brand_ids', array( $this, 'get_column_value_brand_ids' ), 10, 2 );
	}

	/**
	 * Add the settings for the new "Brands" subtab.
	 *
	 * @since  9.4.0
	 *
	 * @param array $settings Settings.
	 * @param array $current_section Current section.
	 */
	public function add_settings_section( $settings, $current_section ) {
		if ( 'brands' === $current_section ) {
			$settings = $this->settings;
		}
		return $settings;
	}

	/**
	 * Add a new "Brands" subtab to the "Products" tab.
	 *
	 * @since  9.4.0
	 * @param array $sections Sections.
	 */
	public function add_settings_tab( $sections ) {
		$sections = array_merge( $sections, $this->settings_tabs );
		return $sections;
	}

	/**
	 * Display coupon filter fields relating to brands.
	 *
	 * @since  9.4.0
	 * @return  void
	 */
	public function add_coupon_brands_fields() {
		global $post;
		// Brands.
		?>
		<div class="options_group"><div class="hr-section hr-section-coupon_restrictions"><?php echo esc_html__( 'And', 'woocommerce' ); ?></div>
		<p class="form-field"><label for="product_brands"><?php esc_html_e( 'Product brands', 'woocommerce' ); ?></label>
			<select id="product_brands" name="product_brands[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any brand', 'woocommerce' ); ?>">
				<?php
				$category_ids = (array) get_post_meta( $post->ID, 'product_brands', true );
				$categories   = get_terms(
					array(
						'taxonomy'   => 'product_brand',
						'orderby'    => 'name',
						'hide_empty' => false,
					)
				);

				if ( $categories ) {
					foreach ( $categories as $cat ) {
						echo '<option value="' . esc_attr( $cat->term_id ) . '"' . selected( in_array( $cat->term_id, $category_ids, true ), true, false ) . '>' . esc_html( $cat->name ) . '</option>';
					}
				}
				?>
			</select>
			<?php
				echo wc_help_tip( esc_html__( 'A product must be associated with this brand for the coupon to remain valid or, for "Product Discounts", products with these brands will be discounted.', 'woocommerce' ) );
				// Exclude Brands.
			?>
		<p class="form-field"><label for="exclude_product_brands"><?php esc_html_e( 'Exclude brands', 'woocommerce' ); ?></label>
			<select id="exclude_product_brands" name="exclude_product_brands[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'No brands', 'woocommerce' ); ?>">
				<?php
				$category_ids = (array) get_post_meta( $post->ID, 'exclude_product_brands', true );
				$categories   = get_terms(
					array(
						'taxonomy'   => 'product_brand',
						'orderby'    => 'name',
						'hide_empty' => false,
					)
				);

				if ( $categories ) {
					foreach ( $categories as $cat ) {
						echo '<option value="' . esc_attr( $cat->term_id ) . '"' . selected( in_array( $cat->term_id, $category_ids, true ), true, false ) . '>' . esc_html( $cat->name ) . '</option>';
					}
				}
				?>
			</select>
			<?php
				echo wc_help_tip( esc_html__( 'Product must not be associated with these brands for the coupon to remain valid or, for "Product Discounts", products associated with these brands will not be discounted.', 'woocommerce' ) );
			?>
		</div>
		<?php
	}

	/**
	 * Save coupon filter fields relating to brands.
	 *
	 * @since  9.4.0
	 * @param int $post_id Post ID.
	 * @return  void
	 */
	public function save_coupon_brands( $post_id ) {
		$product_brands         = isset( $_POST['product_brands'] ) ? array_map( 'intval', $_POST['product_brands'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$exclude_product_brands = isset( $_POST['exclude_product_brands'] ) ? array_map( 'intval', $_POST['exclude_product_brands'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Save.
		update_post_meta( $post_id, 'product_brands', $product_brands );
		update_post_meta( $post_id, 'exclude_product_brands', $exclude_product_brands );
	}

	/**
	 * Prepare form fields to be used in the various tabs.
	 */
	public function init_form_fields() {

		/**
		 * Filter Brands settings.
		 *
		 * @since 9.4.0
		 *
		 * @param array $settings Brands settings.
		 */
		$this->settings = apply_filters(
			'woocommerce_brands_settings_fields',
			array(
				array(
					'name' => __( 'Brands Archives', 'woocommerce' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'brands_archives',
				),
				array(
					'name' => __( 'Show description', 'woocommerce' ),
					'desc' => __( 'Choose to show the brand description on the archive page. Turn this off if you intend to use the description widget instead. Please note: this is only for themes that do not show the description.', 'woocommerce' ),
					'tip'  => '',
					'id'   => 'wc_brands_show_description',
					'css'  => '',
					'std'  => 'yes',
					'type' => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'brands_archives',
				),
			)
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function scripts() {
		$screen  = get_current_screen();
		$version = Constants::get_constant( 'WC_VERSION' );
		$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';

		if ( 'edit-product' === $screen->id ) {
			wp_register_script(
				'wc-brands-enhanced-select',
				WC()->plugin_url() . '/assets/js/admin/wc-brands-enhanced-select' . $suffix . '.js',
				array( 'jquery', 'selectWoo', 'wc-enhanced-select', 'wp-api' ),
				$version,
				true
			);
			wp_localize_script(
				'wc-brands-enhanced-select',
				'wc_brands_enhanced_select_params',
				array( 'ajax_url' => get_rest_url() . 'brands/search' )
			);
			wp_enqueue_script( 'wc-brands-enhanced-select' );
		}

		if ( in_array( $screen->id, array( 'edit-product_brand' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
	}

	/**
	 * Enqueue styles.
	 *
	 * @return void
	 */
	public function styles() {
		$version = Constants::get_constant( 'WC_VERSION' );
		wp_enqueue_style( 'brands-admin-styles', WC()->plugin_url() . '/assets/css/brands-admin.css', array(), $version );
	}

	/**
	 * Admin settings function.
	 */
	public function admin_settings() {
		woocommerce_admin_fields( $this->settings );
	}

	/**
	 * Save admin settings function.
	 */
	public function save_admin_settings() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['section'] ) && 'brands' === $_GET['section'] ) {
			woocommerce_update_options( $this->settings );
		}
	}

	/**
	 * Category thumbnails.
	 */
	public function add_thumbnail_field() {
		global $woocommerce;
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></label>
			<div id="product_cat_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" width="60px" height="60px" /></div>
			<div style="line-height:60px;">
				<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" />
				<button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
				<button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
			</div>
			<script type="text/javascript">

				jQuery(function(){
					// Only show the "remove image" button when needed
					if ( ! jQuery('#product_cat_thumbnail_id').val() ) {
						jQuery('.remove_image_button').hide();
					}

					// Uploading files
					var file_frame;

					jQuery(document).on( 'click', '.upload_image_button', function( event ){

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if ( file_frame ) {
							file_frame.open();
							return;
						}

						// Create the media frame.
						file_frame = wp.media.frames.downloadable_file = wp.media({
							title: '<?php echo esc_js( __( 'Choose an image', 'woocommerce' ) ); ?>',
							button: {
								text: '<?php echo esc_js( __( 'Use image', 'woocommerce' ) ); ?>',
							},
							multiple: false
						});

						// When an image is selected, run a callback.
						file_frame.on( 'select', function() {
							attachment = file_frame.state().get('selection').first().toJSON();

							jQuery('#product_cat_thumbnail_id').val( attachment.id );
							jQuery('#product_cat_thumbnail img').attr('src', attachment.url );
							jQuery('.remove_image_button').show();
						});

						// Finally, open the modal.
						file_frame.open();
					});

					jQuery(document).on( 'click', '.remove_image_button', function( event ){
						jQuery('#product_cat_thumbnail img').attr('src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>');
						jQuery('#product_cat_thumbnail_id').val('');
						jQuery('.remove_image_button').hide();
						return false;
					});
				});

			</script>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Edit thumbnail field row.
	 *
	 * @param WP_Term $term     Current taxonomy term object.
	 */
	public function edit_thumbnail_field( $term ) {
		global $woocommerce;

		$image        = '';
		$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
		if ( $thumbnail_id ) {
			$image = wp_get_attachment_url( $thumbnail_id );
		}
		if ( empty( $image ) ) {
			$image = wc_placeholder_img_src();
		}
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></label></th>
			<td>
				<div id="product_cat_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo esc_url( $image ); ?>" width="60px" height="60px" /></div>
				<div style="line-height:60px;">
					<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
					<button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
					<button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
				</div>
				<script type="text/javascript">

					jQuery(function(){

						// Only show the "remove image" button when needed
						if ( ! jQuery('#product_cat_thumbnail_id').val() )
							jQuery('.remove_image_button').hide();

						// Uploading files
						var file_frame;

						jQuery(document).on( 'click', '.upload_image_button', function( event ){

							event.preventDefault();

							// If the media frame already exists, reopen it.
							if ( file_frame ) {
								file_frame.open();
								return;
							}

							// Create the media frame.
							file_frame = wp.media.frames.downloadable_file = wp.media({
								title: '<?php echo esc_js( __( 'Choose an image', 'woocommerce' ) ); ?>',
								button: {
									text: '<?php echo esc_js( __( 'Use image', 'woocommerce' ) ); ?>',
								},
								multiple: false
							});

							// When an image is selected, run a callback.
							file_frame.on( 'select', function() {
								attachment = file_frame.state().get('selection').first().toJSON();

								jQuery('#product_cat_thumbnail_id').val( attachment.id );
								jQuery('#product_cat_thumbnail img').attr('src', attachment.url );
								jQuery('.remove_image_button').show();
							});

							// Finally, open the modal.
							file_frame.open();
						});

						jQuery(document).on( 'click', '.remove_image_button', function( event ){
							jQuery('#product_cat_thumbnail img').attr('src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>');
							jQuery('#product_cat_thumbnail_id').val('');
							jQuery('.remove_image_button').hide();
							return false;
						});
					});

				</script>
				<div class="clear"></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves thumbnail field.
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return void
	 */
	public function thumbnail_field_save( $term_id ) {
		if ( isset( $_POST['product_cat_thumbnail_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_term_meta( $term_id, 'thumbnail_id', absint( $_POST['product_cat_thumbnail_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	/**
	 * Brand taxonomy description.
	 */
	public function taxonomy_description() {
		echo wp_kses_post( wpautop( __( 'Brands can be added and managed from this screen. You can optionally upload a brand image to display in brand widgets and on brand archives', 'woocommerce' ) ) );
	}

	/**
	 * Sort brands function.
	 *
	 * @param array $sortable Sortable array.
	 */
	public function sort_brands( $sortable ) {
		$sortable[] = 'product_brand';
		return $sortable;
	}

	/**
	 * Add brands column in second-to-last position.
	 *
	 * @since 9.4.0
	 * @param mixed $columns Columns.
	 * @return array
	 */
	public function product_columns( $columns ) {
		if ( empty( $columns ) ) {
			return $columns;
		}

		$column_index  = 'taxonomy-product_brand';
		$brands_column = $columns[ $column_index ];
		unset( $columns[ $column_index ] );
		return array_merge(
			array_slice( $columns, 0, -2, true ),
			array( $column_index => $brands_column ),
			array_slice( $columns, -2, null, true )
		);
	}


	/**
	 * Columns function.
	 *
	 * @param mixed $columns Columns.
	 */
	public function columns( $columns ) {
		if ( empty( $columns ) ) {
			return $columns;
		}

		$new_columns          = array();
		$new_columns['cb']    = $columns['cb'];
		$new_columns['thumb'] = __( 'Image', 'woocommerce' );
		unset( $columns['cb'] );
		$columns = array_merge( $new_columns, $columns );
		return $columns;
	}

	/**
	 * Column function.
	 *
	 * @param mixed $columns Columns.
	 * @param mixed $column Column.
	 * @param mixed $id ID.
	 */
	public function column( $columns, $column, $id ) {
		if ( 'thumb' === $column ) {
			global $woocommerce;

			$image        = '';
			$thumbnail_id = get_term_meta( $id, 'thumbnail_id', true );

			if ( $thumbnail_id ) {
				$image = wp_get_attachment_url( $thumbnail_id );
			}
			if ( empty( $image ) ) {
				$image = wc_placeholder_img_src();
			}

			$columns .= '<img src="' . $image . '" alt="Thumbnail" class="wp-post-image" height="48" width="48" />';

		}
		return $columns;
	}

	/**
	 * Renders either dropdown or a search field for brands depending on the threshold value of
	 * woocommerce_product_brand_filter_threshold filter.
	 */
	public function render_product_brand_filter() {
		// phpcs:disable WordPress.Security.NonceVerification
		$brands_count       = (int) wp_count_terms( 'product_brand' );
		$current_brand_slug = wc_clean( wp_unslash( $_GET['product_brand'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		/**
		 * Filter the brands threshold count.
		 *
		 * @since 9.4.0
		 *
		 * @param int $value Threshold.
		 */
		if ( $brands_count <= apply_filters( 'woocommerce_product_brand_filter_threshold', 100 ) ) {
			wc_product_dropdown_categories(
				array(
					'pad_counts'        => true,
					'show_count'        => true,
					'orderby'           => 'name',
					'selected'          => $current_brand_slug,
					'show_option_none'  => __( 'Filter by brand', 'woocommerce' ),
					'option_none_value' => '',
					'value_field'       => 'slug',
					'taxonomy'          => 'product_brand',
					'name'              => 'product_brand',
					'class'             => 'dropdown_product_brand',
				)
			);
		} else {
			$current_brand   = $current_brand_slug ? get_term_by( 'slug', $current_brand_slug, 'product_brand' ) : '';
			$selected_option = '';
			if ( $current_brand_slug && $current_brand ) {
				$selected_option = '<option value="' . esc_attr( $current_brand_slug ) . '" selected="selected">' . esc_html( htmlspecialchars( wp_kses_post( $current_brand->name ) ) ) . '</option>';
			}
			$placeholder = esc_attr__( 'Filter by brand', 'woocommerce' );
			?>
			<select class="wc-brands-search" name="product_brand" data-placeholder="<?php echo $placeholder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" data-allow_clear="true">
				<?php echo $selected_option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</select>
			<?php
		}
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/**
	 * Add brand base permalink setting.
	 */
	public function add_brand_base_setting() {
		$screen = get_current_screen();
		if ( ! $screen || 'options-permalink' !== $screen->id ) {
			return;
		}

		add_settings_field(
			'woocommerce_product_brand_slug',
			__( 'Product brand base', 'woocommerce' ),
			array( $this, 'product_brand_slug_input' ),
			'permalink',
			'optional'
		);

		$this->save_permalink_settings();
	}

	/**
	 * Add a slug input box.
	 */
	public function product_brand_slug_input() {
		$permalink = get_option( 'woocommerce_brand_permalink', '' );
		?>
		<input name="woocommerce_product_brand_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $permalink ); ?>" placeholder="<?php echo esc_attr_x( 'brand', 'slug', 'woocommerce' ); ?>" />
		<?php
	}

	/**
	 * Save permalink settings.
	 *
	 * We need to save the options ourselves;
	 * settings api does not trigger save for the permalink page.
	 */
	public function save_permalink_settings() {
		if ( ! is_admin() ) {
			return;
		}

		if ( isset( $_POST['permalink_structure'], $_POST['wc-permalinks-nonce'], $_POST['woocommerce_product_brand_slug'] ) && wp_verify_nonce( wp_unslash( $_POST['wc-permalinks-nonce'] ), 'wc-permalinks' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			update_option( 'woocommerce_brand_permalink', wc_sanitize_permalink( trim( wc_clean( wp_unslash( $_POST['woocommerce_product_brand_slug'] ) ) ) ) );
		}
	}

	/**
	 * Validate the product base.
	 *
	 * Must have an additional slug, not just the brand as the base.
	 *
	 * @param array $value Value.
	 */
	public function validate_product_base( $value ) {
		if ( '/%product_brand%/' === trailingslashit( $value['product_base'] ) ) {
			$value['product_base'] = '/' . _x( 'product', 'slug', 'woocommerce' ) . $value['product_base'];
		}

		return $value;
	}

	/**
	 * Add csv column for importing/exporting.
	 *
	 * @param  array $options Mapping options.
	 * @return array $options
	 */
	public function add_column_to_importer_exporter( $options ) {
		$options['brand_ids'] = __( 'Brands', 'woocommerce' );
		return $options;
	}

	/**
	 * Add default column mapping.
	 *
	 * @param  array $mappings Mappings.
	 * @return array $mappings
	 */
	public function add_default_column_mapping( $mappings ) {
		$new_mapping = array( __( 'Brands', 'woocommerce' ) => 'brand_ids' );
		return array_merge( $mappings, $new_mapping );
	}

	/**
	 * Add formatting callback for brand_ids during CSV import.
	 *
	 * @param  array               $callbacks Formatting callbacks.
	 * @param  WC_Product_Importer $importer  Importer instance.
	 * @return array $callbacks
	 */
	public function add_formatting_callback( $callbacks, $importer ) {
		$mapped_keys = $importer->get_mapped_keys();

		// Find the index of brand_ids in the mapped keys.
		$brand_ids_index = array_search( 'brand_ids', $mapped_keys, true );

		// If brand_ids exists in the mapping, add our custom parser.
		if ( false !== $brand_ids_index ) {
			$callbacks[ $brand_ids_index ] = array( $this, 'parse_brands_field' );
		}

		return $callbacks;
	}

	/**
	 * Add brands to newly imported product.
	 *
	 * @param WC_Product $product Product being imported.
	 * @param array      $data    Raw CSV data.
	 */
	public function process_import( $product, $data ) {
		if ( empty( $data['brand_ids'] ) || ! is_array( $data['brand_ids'] ) ) {
			return;
		}

		$brand_ids = array_map( 'intval', $data['brand_ids'] );
		wp_set_object_terms( $product->get_id(), $brand_ids, 'product_brand' );
	}

	/**
	 * Parse brands field from a CSV during import.
	 *
	 * Based on WC_Product_CSV_Importer::parse_categories_field()
	 *
	 * @param string $value Field value.
	 * @return array
	 */
	public function parse_brands_field( $value ) {

		// Based on WC_Product_Importer::explode_values().
		$values    = str_replace( '\\,', '::separator::', explode( ',', $value ) );
		$row_terms = array();
		foreach ( $values as $row_value ) {
			$row_terms[] = trim( str_replace( '::separator::', ',', $row_value ) );
		}

		$brands = array();
		foreach ( $row_terms as $row_term ) {
			$parent = null;

			// WC Core uses '>', but for some reason it's already escaped at this point.
			$_terms = array_map( 'trim', explode( '&gt;', $row_term ) );
			$total  = count( $_terms );

			foreach ( $_terms as $index => $_term ) {
				$term = term_exists( $_term, 'product_brand', $parent );

				if ( is_array( $term ) ) {
					$term_id = $term['term_id'];
				} else {
					$term = wp_insert_term( $_term, 'product_brand', array( 'parent' => intval( $parent ) ) );

					if ( is_wp_error( $term ) ) {
						break; // We cannot continue if the term cannot be inserted.
					}

					$term_id = $term['term_id'];
				}

				// Only requires assign the last category.
				if ( ( 1 + $index ) === $total ) {
					$brands[] = $term_id;
				} else {
					// Store parent to be able to insert or query brands based in parent ID.
					$parent = $term_id;
				}
			}
		}

		return $brands;
	}

	/**
	 * Get brands column value for csv export.
	 *
	 * @param string     $value   What will be exported.
	 * @param WC_Product $product Product being exported.
	 * @return string    Brands separated by commas and child brands as "parent > child".
	 */
	public function get_column_value_brand_ids( $value, $product ) {
		$brand_ids = wp_parse_id_list( wp_get_post_terms( $product->get_id(), 'product_brand', array( 'fields' => 'ids' ) ) );

		if ( ! count( $brand_ids ) ) {
			return '';
		}

		// Based on WC_CSV_Exporter::format_term_ids().
		$formatted_brands = array();
		foreach ( $brand_ids as $brand_id ) {
			$formatted_term = array();
			$ancestor_ids   = array_reverse( get_ancestors( $brand_id, 'product_brand' ) );

			foreach ( $ancestor_ids as $ancestor_id ) {
				$term = get_term( $ancestor_id, 'product_brand' );
				if ( $term && ! is_wp_error( $term ) ) {
					$formatted_term[] = $term->name;
				}
			}

			$term = get_term( $brand_id, 'product_brand' );

			if ( $term && ! is_wp_error( $term ) ) {
				$formatted_term[] = $term->name;
			}

			$formatted_brands[] = implode( ' > ', $formatted_term );
		}

		// Based on WC_CSV_Exporter::implode_values().
		$values_to_implode = array();
		foreach ( $formatted_brands as $brand ) {
			$brand               = (string) is_scalar( $brand ) ? $brand : '';
			$values_to_implode[] = str_replace( ',', '\\,', $brand );
		}

		return implode( ', ', $values_to_implode );
	}
}

$GLOBALS['WC_Brands_Admin'] = new WC_Brands_Admin();
