<?php
/**
 * Order Notes
 *
 * Functions for displaying order comments in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/WritePanels
 * @version     1.6.4
 */


/**
 * Display the order notes meta box.
 *
 * @access public
 * @return void
 */
function woocommerce_order_notes_meta_box() {
	global $woocommerce, $post;

	$args = array(
		'post_id' => $post->ID,
		'approve' => 'approve',
		'type' => ''
	);

	$notes = get_comments( $args );

	echo '<ul class="order_notes">';

	if ($notes) :
		foreach($notes as $note) :

			$customer_note = get_comment_meta($note->comment_ID, 'is_customer_note', true);

			echo '<li rel="'.$note->comment_ID.'" class="note ';
			if ($customer_note) echo 'customer-note';
			echo '"><div class="note_content">';
			echo wpautop( wptexturize( $note->comment_content ) );
			echo '</div><p class="meta">'. sprintf(__('added %s ago', 'woocommerce'), human_time_diff(strtotime($note->comment_date_gmt), current_time('timestamp', 1))) .' - <a href="#" class="delete_note">'.__('Delete note', 'woocommerce').'</a></p>';
			echo '</li>';
		endforeach;
	else :
		echo '<li>' . __('There are no notes for this order yet.', 'woocommerce') . '</li>';
	endif;

	echo '</ul>';
	?>
	<div class="add_note">
		<h4><?php _e('Add note', 'woocommerce'); ?> <img class="help_tip" data-tip='<?php esc_attr_e('Add a note for your reference, or add a customer note (the user will be notified).', 'woocommerce'); ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" /></h4>
		<p>
			<textarea type="text" name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
		</p>
		<p>
			<select name="order_note_type" id="order_note_type">
				<option value="customer"><?php _e('Customer note', 'woocommerce'); ?></option>
				<option value=""><?php _e('Private note', 'woocommerce'); ?></option>
			</select>
			<a href="#" class="add_note button"><?php _e('Add', 'woocommerce'); ?></a>
		</p>
	</div>
	<script type="text/javascript">

		jQuery('a.add_note').click(function(){

			if (!jQuery('textarea#add_order_note').val()) return;

			jQuery('#woocommerce-order-notes').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				action: 		'woocommerce_add_order_note',
				post_id:		'<?php echo $post->ID; ?>',
				note: 			jQuery('textarea#add_order_note').val(),
				note_type:		jQuery('select#order_note_type').val(),
				security: 		'<?php echo wp_create_nonce("add-order-note"); ?>'
			};

			jQuery.post( '<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {

				jQuery('ul.order_notes').prepend( response );
				jQuery('#woocommerce-order-notes').unblock();
				jQuery('#add_order_note').val('');

			});

			return false;

		});

		jQuery('a.delete_note').live('click', function(){

			var note = jQuery(this).closest('li.note');

			jQuery(note).block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				action: 		'woocommerce_delete_order_note',
				note_id:		jQuery(note).attr('rel'),
				security: 		'<?php echo wp_create_nonce("delete-order-note"); ?>'
			};

			jQuery.post( '<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {

				jQuery(note).remove();

			});

			return false;

		});

	</script>
	<?php
}