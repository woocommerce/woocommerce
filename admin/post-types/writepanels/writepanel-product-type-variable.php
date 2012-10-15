<?php
/**
 * Variable Product Type
 *
 * Functions specific to variable products (for the write panels).
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/WritePanels
 * @version     1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Display the variation tab in product data.
 *
 * @access public
 * @return void
 */
function variable_product_type_options_tab() {
	?>
	<li class="variations_tab show_if_variable variation_options"><a href="#variable_product_options" title="<?php _e( 'Variations for variable products are defined here.', 'woocommerce' ); ?>"><?php _e( 'Variations', 'woocommerce' ); ?></a></li>
	<?php
}

add_action( 'woocommerce_product_write_panel_tabs', 'variable_product_type_options_tab' );


/**
 * Show the variable product options.
 *
 * @access public
 * @return void
 */
function variable_product_type_options() {
	global $post, $woocommerce;

	$attributes = maybe_unserialize( get_post_meta( $post->ID, '_product_attributes', true ) );

	// See if any are set
	$variation_attribute_found = false;
	if ( $attributes ) foreach( $attributes as $attribute ) {
		if ( isset( $attribute['is_variation'] ) ) {
			$variation_attribute_found = true;
			break;
		}
	}

	// Get tax classes
	$tax_classes = array_filter( array_map('trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
	$tax_class_options = array();
	$tax_class_options['parent'] = __( 'Same as parent', 'woocommerce' );
	$tax_class_options[''] = __( 'Standard', 'woocommerce' );
	if ( $tax_classes ) 
		foreach ( $tax_classes as $class )
			$tax_class_options[ sanitize_title( $class ) ] = esc_attr( $class );
	?>
	<div id="variable_product_options" class="panel wc-metaboxes-wrapper">

		<?php if ( ! $variation_attribute_found ) : ?>

			<div id="message" class="inline woocommerce-message">
				<div class="squeezer">
					<h4><?php _e( 'Before adding variations, add and save some attributes on the <strong>Attributes</strong> tab.', 'woocommerce' ); ?></h4>

					<p class="submit"><a class="button-primary" href="http://www.woothemes.com/woocommerce-docs/user-guide/product-variations/" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>
				</div>
			</div>

		<?php else : ?>

			<p class="toolbar">
				<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce' ); ?></a><a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce' ); ?></a>
				<strong><?php _e( 'Bulk edit:', 'woocommerce' ); ?></strong>
				<select id="field_to_edit">
					<option value="variable_price"><?php _e( 'Prices', 'woocommerce' ); ?></option>
					<option value="variable_sale_price"><?php _e( 'Sale prices', 'woocommerce' ); ?></option>
					<option value="variable_stock"><?php _e( 'Stock', 'woocommerce' ); ?></option>
					<option value="variable_weight"><?php _e( 'Weight', 'woocommerce' ); ?></option>
					<option value="variable_length"><?php _e( 'Length', 'woocommerce' ); ?></option>
					<option value="variable_width"><?php _e( 'Width', 'woocommerce' ); ?></option>
					<option value="variable_height"><?php _e( 'Height', 'woocommerce' ); ?></option>
					<option value="variable_file_paths" rel="textarea"><?php _e( 'File Path', 'woocommerce' ); ?></option>
					<option value="variable_download_limit"><?php _e( 'Download limit', 'woocommerce' ); ?></option>
					<option value="variable_download_expiry"><?php _e( 'Download Expiry', 'woocommerce' ); ?></option>
				</select>
				<a class="button bulk_edit plus"><?php _e( 'Edit', 'woocommerce' ); ?></a>
				<a class="button toggle toggle_downloadable" href="#"><?php _e( 'Downloadable', 'woocommerce' ); ?></a> <a class="button toggle toggle_virtual" href="#"><?php _e( 'Virtual', 'woocommerce' ); ?></a> <a class="button toggle toggle_enabled" href="#"><?php _e( 'Enabled', 'woocommerce' ); ?></a> <a href="#" class="button delete_variations"><?php _e( 'Delete all', 'woocommerce' ); ?></a>
			</p>

			<div class="woocommerce_variations wc-metaboxes">
				<?php
				// Get parent data
				$parent_data = array(
					'id'		=> $post->ID,
					'attributes' => $attributes,
					'tax_class_options' => $tax_class_options,
					'sku' 		=> get_post_meta( $post->ID, '_sku', true ),
					'weight' 	=> get_post_meta( $post->ID, '_weight', true ),
					'length' 	=> get_post_meta( $post->ID, '_length', true ),
					'width' 	=> get_post_meta( $post->ID, '_width', true ),
					'height' 	=> get_post_meta( $post->ID, '_height', true ),
					'tax_class' => get_post_meta( $post->ID, '_tax_class', true )
				);
	
				if ( ! $parent_data['weight'] )
					$parent_data['weight'] = '0.00';
					
				if ( ! $parent_data['length'] )
					$parent_data['length'] = '0';
					
				if ( ! $parent_data['width'] )
					$parent_data['width'] = '0';
					
				if ( ! $parent_data['height'] )
					$parent_data['height'] = '0';
		
				// Get variations
				$args = array(
					'post_type'		=> 'product_variation',
					'post_status' 	=> array( 'private', 'publish' ),
					'numberposts' 	=> -1,
					'orderby' 		=> 'menu_order',
					'order' 		=> 'asc',
					'post_parent' 	=> $post->ID
				);
				$variations = get_posts( $args );
				$loop = 0;
				if ( $variations ) foreach ( $variations as $variation ) {
					
					$variation_id 			= absint( $variation->ID );
					$variation_post_status 	= esc_attr( $variation->post_status );
					$variation_data 		= get_post_custom( $variation_id );
					$variation_data['variation_post_id'] = $variation_id;
					
					// Grab shipping classes
					$shipping_classes = get_the_terms( $variation_id, 'product_shipping_class' );
					$shipping_class = ( $shipping_classes && ! is_wp_error( $shipping_classes ) ) ? current( $shipping_classes )->term_id : '';
										
					$variation_fields = array( 
						'_sku',
						'_stock', 
						'_price',
						'_regular_price',
						'_sale_price',
						'_weight',
						'_length',
						'_width',
						'_height',
						'_tax_class',
						'_download_limit',
						'_download_expiry',
						'_file_paths',
						'_downloadable',
						'_virtual',
						'_thumbnail_id',
						'_sale_price_dates_from',
						'_sale_price_dates_to'
					);
					
					foreach ( $variation_fields as $field )
						$$field = isset( $variation_data[ $field ][0] ) ? $variation_data[ $field ][0] : '';
					
					// Price backwards compat
					if ( $_regular_price == '' && $_price )
						$_regular_price = $_price;
						
					// Get image
					$image = '';
					$image_id = absint( $_thumbnail_id );
					if ( $image_id )
						$image = wp_get_attachment_url( $image_id );
				
					// Format file paths
					$_file_paths = maybe_unserialize( $_file_paths );
					if ( is_array( $_file_paths ) ) 
						$_file_paths = implode( "\n", $_file_paths );
						
					include( 'variation-admin-html.php' );
					
					$loop++; 
				}
				?>
			</div>

			<p class="toolbar">

				<button type="button" class="button button-primary add_variation" <?php disabled( $variation_attribute_found, false ); ?>><?php _e( 'Add Variation', 'woocommerce' ); ?></button>

				<button type="button" class="button link_all_variations" <?php disabled( $variation_attribute_found, false ); ?>><?php _e( 'Link all variations', 'woocommerce' ); ?></button>

				<strong><?php _e( 'Default selections:', 'woocommerce' ); ?></strong>
				<?php
					$default_attributes = maybe_unserialize( get_post_meta( $post->ID, '_default_attributes', true ) );
					foreach ( $attributes as $attribute ) {

						// Only deal with attributes that are variations
						if ( ! $attribute['is_variation'] ) 
							continue;

						// Get current value for variation (if set)
						$variation_selected_value = isset( $default_attributes[ sanitize_title( $attribute['name'] ) ] ) ? $default_attributes[ sanitize_title( $attribute['name'] ) ] : '';

						// Name will be something like attribute_pa_color
						echo '<select name="default_attribute_' . sanitize_title( $attribute['name'] ) . '"><option value="">' . __( 'No default', 'woocommerce' ) . ' ' . esc_html( $woocommerce->attribute_label( $attribute['name'] ) ) . '&hellip;</option>';

						// Get terms for attribute taxonomy or value if its a custom attribute
						if ( $attribute['is_taxonomy'] ) {
							$post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );
							foreach ( $post_terms as $term )
								echo '<option ' . selected( $variation_selected_value, $term->slug, false ) . ' value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>';
						} else {
							$options = explode( '|', $attribute['value'] );
							foreach ( $options as $option )
								echo '<option ' . selected( $variation_selected_value, $option, false ) . ' value="' . esc_attr( $option ) . '">' . ucfirst( esc_html( $option ) ) . '</option>';
						}

						echo '</select>';
					}
				?>
			</p>

		<?php endif; ?>

		<div class="clear"></div>
	</div>
	<?php
	/**
	 * Product Type Javascript
	 */
	ob_start();
	?>
	jQuery(function(){

		<?php if ( ! $attributes || ( is_array( $attributes ) && sizeof( $attributes ) == 0 ) ) : ?>

			jQuery('#variable_product_options').on('click', 'button.link_all_variations, button.add_variation', function(){

				alert('<?php _e( 'You must add some attributes via the "Product Data" panel and save before adding a new variation.', 'woocommerce' ); ?>');

				return false;

			});

		<?php else : ?>

		// Add a variation
		jQuery('#variable_product_options').on('click', 'button.add_variation', function(){

			jQuery('.woocommerce_variations').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
			
			var loop = jQuery('.woocommerce_variation').size();
			
			var data = {
				action: 'woocommerce_add_variation',
				post_id: <?php echo $post->ID; ?>,
				loop: loop,
				security: '<?php echo wp_create_nonce("add-variation"); ?>'
			};

			jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {

				jQuery('.woocommerce_variations').append( response );
				
				jQuery(".tips").tipTip({
			    	'attribute' : 'data-tip',
			    	'fadeIn' : 50,
			    	'fadeOut' : 50
			    });

			    jQuery('input.variable_is_downloadable, input.variable_is_virtual').change();

				jQuery('.woocommerce_variations').unblock();

			});

			return false;

		});

		jQuery('#variable_product_options').on('click', 'button.link_all_variations', function(){

			var answer = confirm('<?php _e( 'Are you sure you want to link all variations? This will create a new variation for each and every possible combination of variation attributes (max 50 per run).', 'woocommerce' ); ?>');

			if (answer) {

				jQuery('#variable_product_options').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

				var data = {
					action: 'woocommerce_link_all_variations',
					post_id: <?php echo $post->ID; ?>,
					security: '<?php echo wp_create_nonce("link-variations"); ?>'
				};

				jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {

					var count = parseInt( response );

					if (count>0) {
						jQuery('.woocommerce_variations').load( window.location + ' .woocommerce_variations > *' );
					}

					if (count==1) {
						alert( count + ' <?php _e( "variation added", 'woocommerce' ); ?>');
					} else if (count==0 || count>1) {
						alert( count + ' <?php _e( "variations added", 'woocommerce' ); ?>');
					} else {
						alert('<?php _e( "No variations added", 'woocommerce' ); ?>');
					}

					jQuery('#variable_product_options').unblock();

				});
			}
			return false;
		});

		jQuery('#variable_product_options').on('click', 'button.remove_variation', function(e){
			e.preventDefault();
			var answer = confirm('<?php _e( 'Are you sure you want to remove this variation?', 'woocommerce' ); ?>');
			if (answer){

				var el = jQuery(this).parent().parent();

				var variation = jQuery(this).attr('rel');

				if (variation>0) {

					jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

					var data = {
						action: 'woocommerce_remove_variation',
						variation_id: variation,
						security: '<?php echo wp_create_nonce("delete-variation"); ?>'
					};

					jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
						// Success
						jQuery(el).fadeOut('300', function(){
							jQuery(el).remove();
						});
					});

				} else {
					jQuery(el).fadeOut('300', function(){
						jQuery(el).remove();
					});
				}

			}
			return false;
		});

		jQuery('#variable_product_options').on('click', 'a.delete_variations', function(){
			var answer = confirm('<?php _e( 'Are you sure you want to delete all variations? This cannot be undone.', 'woocommerce' ); ?>');
			if (answer){

				var answer = confirm('<?php _e( 'Last warning, are you sure?', 'woocommerce' ); ?>');

				if (answer) {

					var variation_ids = [];

					jQuery('.woocommerce_variations .woocommerce_variation').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

					jQuery('.woocommerce_variations .woocommerce_variation .remove_variation').each(function(){

						var variation = jQuery(this).attr('rel');
						if (variation>0) {
							variation_ids.push(variation);
						}
					});

					var data = {
						action: 'woocommerce_remove_variations',
						variation_ids: variation_ids,
						security: '<?php echo wp_create_nonce("delete-variations"); ?>'
					};

					jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
						jQuery('.woocommerce_variations .woocommerce_variation').fadeOut('300', function(){
							jQuery('.woocommerce_variations .woocommerce_variation').remove();
						});
					});

				}

			}
			return false;
		});

		jQuery('a.bulk_edit').click(function() {
			var field_to_edit = jQuery('select#field_to_edit').val();
			var input_tag = jQuery('select#field_to_edit :selected').attr('rel') ? jQuery('select#field_to_edit :selected').attr('rel') : 'input';
			
			var value = prompt("<?php _e( 'Enter a value', 'woocommerce' ); ?>");
			jQuery(input_tag + '[name^="' + field_to_edit + '"]').val( value );
			return false;
		});

		jQuery('a.toggle_virtual').click(function(){
			var checkbox = jQuery('input[name^="variable_is_virtual"]');
       		checkbox.attr('checked', !checkbox.attr('checked'));
       		jQuery('input.variable_is_virtual').change();
			return false;
		});

		jQuery('a.toggle_downloadable').click(function(){
			var checkbox = jQuery('input[name^="variable_is_downloadable"]');
       		checkbox.attr('checked', !checkbox.attr('checked'));
       		jQuery('input.variable_is_downloadable').change();
			return false;
		});

		jQuery('a.toggle_enabled').click(function(){
			var checkbox = jQuery('input[name^="variable_enabled"]');
       		checkbox.attr('checked', !checkbox.attr('checked'));
			return false;
		});

		jQuery('#variable_product_options').on('change', 'input.variable_is_downloadable', function(){

			jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_downloadable').hide();

			if (jQuery(this).is(':checked')) {
				jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_downloadable').show();
			}

		});

		jQuery('#variable_product_options').on('change', 'input.variable_is_virtual', function(){

			jQuery(this).closest('.woocommerce_variation').find('.hide_if_variation_virtual').show();

			if (jQuery(this).is(':checked')) {
				jQuery(this).closest('.woocommerce_variation').find('.hide_if_variation_virtual').hide();
			}

		});

		jQuery('input.variable_is_downloadable, input.variable_is_virtual').change();

		// Ordering
		$('.woocommerce_variations').sortable({
			items:'.woocommerce_variation',
			cursor:'move',
			axis:'y',
			handle: 'h3',
			scrollSensitivity:40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'wc-metabox-sortable-placeholder',
			start:function(event,ui){
				ui.item.css('background-color','#f6f6f6');
			},
			stop:function(event,ui){
				ui.item.removeAttr('style');
				variation_row_indexes();
			}
		});

		function variation_row_indexes() {
			$('.woocommerce_variations .woocommerce_variation').each(function(index, el){
				$('.variation_menu_order', el).val( parseInt( $(el).index('.woocommerce_variations .woocommerce_variation') ) );
			});
		};

		<?php endif; ?>

		var current_field_wrapper;

		window.send_to_editor_default = window.send_to_editor;

		jQuery('#variable_product_options').on('click', '.upload_image_button', function(){

			var post_id = jQuery(this).attr('rel');
			var parent = jQuery(this).parent();
			current_field_wrapper = parent;

			if (jQuery(this).is('.remove')) {

				jQuery('.upload_image_id', current_field_wrapper).val('');
				jQuery('img', current_field_wrapper).attr('src', '<?php echo woocommerce_placeholder_img_src(); ?>');
				jQuery(this).removeClass('remove');

			} else {

				window.send_to_editor = window.send_to_cproduct;
				formfield = jQuery('.upload_image_id', parent).attr('name');
				tb_show('', 'media-upload.php?post_id=' + post_id + '&amp;type=image&amp;TB_iframe=true');

			}

			return false;
		});

		window.send_to_cproduct = function(html) {

			jQuery('body').append('<div id="temp_image">' + html + '</div>');

			var img = jQuery('#temp_image').find('img');

			imgurl 		= img.attr('src');
			imgclass 	= img.attr('class');
			imgid		= parseInt(imgclass.replace(/\D/g, ''), 10);

			jQuery('.upload_image_id', current_field_wrapper).val(imgid);
			jQuery('.upload_image_button', current_field_wrapper).addClass('remove');

			jQuery('img', current_field_wrapper).attr('src', imgurl);
			tb_remove();
			jQuery('#temp_image').remove();

			window.send_to_editor = window.send_to_editor_default;

		}

	});
	<?php
	$javascript = ob_get_clean();
	$woocommerce->add_inline_js( $javascript );
}

add_action('woocommerce_product_write_panels', 'variable_product_type_options');


/**
 * Show the variable product selector in the dropdown in admin.
 *
 * @access public
 * @param mixed $types
 * @param mixed $product_type
 * @return array
 */
function variable_product_type_selector( $types, $product_type ) {
	$types['variable'] = __( 'Variable product', 'woocommerce' );
	return $types;
}

add_filter('product_type_selector', 'variable_product_type_selector', 1, 2);


/**
 * Save the variable product options.
 *
 * @access public
 * @param mixed $post_id
 * @return void
 */
function process_product_meta_variable( $post_id ) {
	global $woocommerce, $wpdb;

	if ( isset( $_POST['variable_sku'] ) ) {

		$variable_post_id 					= $_POST['variable_post_id'];
		$variable_sku 						= $_POST['variable_sku'];
		$variable_weight					= $_POST['variable_weight'];
		$variable_length					= $_POST['variable_length'];
		$variable_width						= $_POST['variable_width'];
		$variable_height					= $_POST['variable_height'];
		$variable_stock 					= $_POST['variable_stock'];
		$variable_regular_price 			= $_POST['variable_regular_price'];
		$variable_sale_price				= $_POST['variable_sale_price'];
		$upload_image_id					= $_POST['upload_image_id'];
		$variable_file_paths 				= $_POST['variable_file_paths'];
		$variable_download_limit 			= $_POST['variable_download_limit'];
		$variable_download_expiry   		= $_POST['variable_download_expiry'];
		$variable_shipping_class 			= $_POST['variable_shipping_class'];
		$variable_tax_class					= $_POST['variable_tax_class'];
		$variable_menu_order 				= $_POST['variation_menu_order'];
		$variable_sale_price_dates_from 	= $_POST['variable_sale_price_dates_from'];
		$variable_sale_price_dates_to 		= $_POST['variable_sale_price_dates_to'];

		if ( isset( $_POST['variable_enabled'] ) )
			$variable_enabled 				= $_POST['variable_enabled'];

		if ( isset( $_POST['variable_is_virtual'] ) )
			$variable_is_virtual			= $_POST['variable_is_virtual'];

		if ( isset( $_POST['variable_is_downloadable'] ) )
			$variable_is_downloadable 		= $_POST['variable_is_downloadable'];

		$attributes = (array) maybe_unserialize( get_post_meta( $post_id, '_product_attributes', true ) );

		$max_loop = max( array_keys( $_POST['variable_post_id'] ) );

		for ( $i = 0; $i <= $max_loop; $i ++ ) {

			if ( ! isset( $variable_post_id[ $i ] ) ) 
				continue;

			$variation_id = absint( $variable_post_id[ $i ] );

			// Virtal/Downloadable
			$is_virtual = isset( $variable_is_virtual[ $i ] ) ? 'yes' : 'no';
			$is_downloadable = isset( $variable_is_downloadable[ $i ] ) ? 'yes' : 'no';

			// Enabled or disabled
			$post_status = isset( $variable_enabled[ $i ] ) ? 'publish' : 'private';

			// Generate a useful post title
			$variation_post_title = sprintf( __( 'Variation #%s of %s', 'woocommerce' ), absint( $variation_id ), esc_html( get_the_title( $post_id ) ) );

			// Update or Add post
			if ( ! $variation_id ) {

				$variation = array(
					'post_title' 	=> $variation_post_title,
					'post_content' 	=> '',
					'post_status' 	=> $post_status,
					'post_author' 	=> get_current_user_id(),
					'post_parent' 	=> $post_id,
					'post_type' 	=> 'product_variation',
					'menu_order' 	=> $variable_menu_order[ $i ]
				);
				$variation_id = wp_insert_post( $variation );

			} else {

				$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status, 'post_title' => $variation_post_title, 'menu_order' => $variable_menu_order[ $i ] ), array( 'ID' => $variation_id ) );

			}

			// Update post meta
			update_post_meta( $variation_id, '_sku', woocommerce_clean( $variable_sku[ $i ] ) );
			update_post_meta( $variation_id, '_weight', woocommerce_clean( $variable_weight[ $i ] ) );

			update_post_meta( $variation_id, '_length', woocommerce_clean( $variable_length[ $i ] ) );
			update_post_meta( $variation_id, '_width', woocommerce_clean( $variable_width[ $i ] ) );
			update_post_meta( $variation_id, '_height', woocommerce_clean( $variable_height[ $i ] ) );

			update_post_meta( $variation_id, '_stock', woocommerce_clean( $variable_stock[ $i ] ) );
			update_post_meta( $variation_id, '_thumbnail_id', absint( $upload_image_id[ $i ] ) );

			update_post_meta( $variation_id, '_virtual', woocommerce_clean( $is_virtual ) );
			update_post_meta( $variation_id, '_downloadable', woocommerce_clean( $is_downloadable ) );
			
			// Price handling
			$regular_price 	= woocommerce_clean( $variable_regular_price[ $i ] );
			$sale_price 	= woocommerce_clean( $variable_sale_price[ $i ] );
			$date_from 		= woocommerce_clean( $variable_sale_price_dates_from[ $i ] );
			$date_to		= woocommerce_clean( $variable_sale_price_dates_to[ $i ] );
			
			update_post_meta( $variation_id, '_regular_price', $regular_price );
			update_post_meta( $variation_id, '_sale_price', $sale_price );
			
			// Save Dates
			if ( $date_from )
				update_post_meta( $variation_id, '_sale_price_dates_from', strtotime( $date_from ) );
			else
				update_post_meta( $variation_id, '_sale_price_dates_from', '' );
	
			if ( $date_to )
				update_post_meta( $variation_id, '_sale_price_dates_to', strtotime( $date_to ) );
			else
				update_post_meta( $variation_id, '_sale_price_dates_to', '' );

			if ( $date_to && ! $date_from )
				update_post_meta( $variation_id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );

			// Update price if on sale
			if ( $sale_price != '' && $date_to == '' && $date_from == '' )
				update_post_meta( $variation_id, '_price', $sale_price );
			else
				update_post_meta( $variation_id, '_price', $regular_price );

			if ( $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) )
				update_post_meta( $variation_id, '_price', $sale_price );
	
			if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $variation_id, '_price', $regular_price );
				update_post_meta( $variation_id, '_sale_price_dates_from', '' );
				update_post_meta( $variation_id, '_sale_price_dates_to', '' );
			}
					
			if ( $variable_tax_class[ $i ] !== 'parent' )
				update_post_meta( $variation_id, '_tax_class', woocommerce_clean( $variable_tax_class[ $i ] ) );
			else
				delete_post_meta( $variation_id, '_tax_class' );

			if ( $is_downloadable == 'yes' ) {
				update_post_meta( $variation_id, '_download_limit', woocommerce_clean( $variable_download_limit[ $i ] ) );
				update_post_meta( $variation_id, '_download_expiry', woocommerce_clean( $variable_download_expiry[ $i ] ) );
				
				$_file_paths = array();
				$file_paths = str_replace( "\r\n", "\n", $variable_file_paths[ $i ] );
				$file_paths = trim( preg_replace( "/\n+/", "\n", $file_paths ) );
				if ( $file_paths ) {
					$file_paths = explode( "\n", $file_paths );
	
					foreach ( $file_paths as $file_path ) {
						$file_path = woocommerce_clean( $file_path );
						$_file_paths[ md5( $file_path ) ] = $file_path;
					}
				}

				// grant permission to any newly added files on any existing orders for this product
				do_action( 'woocommerce_process_product_file_download_paths', $post_id, $variation_id, $_file_paths );

				update_post_meta( $variation_id, '_file_paths', $_file_paths );
			} else {
				update_post_meta( $variation_id, '_download_limit', '' );
				update_post_meta( $variation_id, '_download_expiry', '' );
				update_post_meta( $variation_id, '_file_paths', '' );
			}

			// Save shipping class
			$variable_shipping_class[ $i ] = ( $variable_shipping_class[ $i ] ) ? (int) $variable_shipping_class[ $i ] : '';
			wp_set_object_terms( $variation_id, $variable_shipping_class[ $i ], 'product_shipping_class');

			// Remove old taxonomies attributes so data is kept up to date
			if ( $variation_id ) 
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s AND post_id = %d;", 'attribute_%', $variation_id ) );

			// Update taxonomies
			foreach ( $attributes as $attribute ) {

				if ( $attribute['is_variation'] ) {

					$value = woocommerce_clean( $_POST[ 'attribute_' . sanitize_title( $attribute['name'] ) ][ $i ] );

					update_post_meta( $variation_id, 'attribute_' . sanitize_title( $attribute['name'] ), $value );
				}

			}

		 }

	}

	// Update parent if variable so price sorting works and stays in sync with the cheapest child
	$post_parent = $post_id;

	$children = get_posts( array(
		'post_parent' 	=> $post_parent,
		'posts_per_page'=> -1,
		'post_type' 	=> 'product_variation',
		'fields' 		=> 'ids',
		'post_status'	=> 'publish'
	) );

	$lowest_price = $lowest_regular_price = $lowest_sale_price = $highest_price = $highest_regular_price = $highest_sale_price = '';

	if ( $children ) {
		foreach ( $children as $child ) {

			$child_price 			= get_post_meta( $child, '_price', true );
			$child_regular_price 	= get_post_meta( $child, '_regular_price', true );
			$child_sale_price 		= get_post_meta( $child, '_sale_price', true );
			
			// Regular prices
			if ( ! is_numeric( $lowest_regular_price ) || $child_regular_price < $lowest_regular_price ) 
				$lowest_regular_price = $child_regular_price;
				
			if ( ! is_numeric( $highest_regular_price ) || $child_regular_price > $highest_regular_price ) 
				$highest_regular_price = $child_regular_price;
			
			// Sale prices
			if ( $child_price == $child_sale_price ) {
				if ( $child_sale_price !== '' && ( ! is_numeric( $lowest_sale_price ) || $child_sale_price < $lowest_sale_price ) ) 
					$lowest_sale_price = $child_sale_price;
				
				if ( $child_sale_price !== '' && ( ! is_numeric( $highest_sale_price ) || $child_sale_price > $highest_sale_price ) ) 
					$highest_sale_price = $child_sale_price;
			}
		}

    	$lowest_price 	= $lowest_sale_price === '' || $lowest_regular_price < $lowest_sale_price ? $lowest_regular_price : $lowest_sale_price;
		$highest_price 	= $highest_sale_price === '' || $highest_regular_price > $highest_sale_price ? $highest_regular_price : $highest_sale_price;
	}

	update_post_meta( $post_parent, '_price', $lowest_price );
	update_post_meta( $post_parent, '_min_variation_price', $lowest_price );
	update_post_meta( $post_parent, '_max_variation_price', $highest_price );
	update_post_meta( $post_parent, '_min_variation_regular_price', $lowest_regular_price );
	update_post_meta( $post_parent, '_max_variation_regular_price', $highest_regular_price );
	update_post_meta( $post_parent, '_min_variation_sale_price', $lowest_sale_price );
	update_post_meta( $post_parent, '_max_variation_sale_price', $highest_sale_price );

	// Update default attribute options setting
	$default_attributes = array();

	foreach ( $attributes as $attribute ) {
		if ( $attribute['is_variation'] ) {
			$value = woocommerce_clean( $_POST[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] );
			if ( $value )
				$default_attributes[ sanitize_title( $attribute['name'] ) ] = $value;
		}
	}

	update_post_meta( $post_parent, '_default_attributes', $default_attributes );
}

add_action( 'woocommerce_process_product_meta_variable', 'process_product_meta_variable' );