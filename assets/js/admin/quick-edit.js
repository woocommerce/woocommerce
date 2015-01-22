/*global inlineEditPost */
jQuery(function( $ ) {
	$( '#the-list' ).on( 'click', '.editinline', function() {

		inlineEditPost.revert();

		var post_id = $( this ).closest( 'tr' ).attr( 'id' );

		post_id = post_id.replace( 'post-', '' );

		var $wc_inline_data = $( '#woocommerce_inline_' + post_id );

		var sku            = $wc_inline_data.find( '.sku' ).text(),
			regular_price  = $wc_inline_data.find( '.regular_price' ).text(),
			sale_price     = $wc_inline_data.find( '.sale_price ').text(),
			weight         = $wc_inline_data.find( '.weight' ).text(),
			length         = $wc_inline_data.find( '.length' ).text(),
			width          = $wc_inline_data.find( '.width' ).text(),
			height         = $wc_inline_data.find( '.height' ).text(),
			shipping_class = $wc_inline_data.find( '.shipping_class' ).text(),
			visibility     = $wc_inline_data.find( '.visibility' ).text(),
			stock_status   = $wc_inline_data.find( '.stock_status' ).text(),
			stock          = $wc_inline_data.find( '.stock' ).text(),
			featured       = $wc_inline_data.find( '.featured' ).text(),
			manage_stock   = $wc_inline_data.find( '.manage_stock' ).text(),
			menu_order     = $wc_inline_data.find( '.menu_order' ).text(),
			tax_status     = $wc_inline_data.find( '.tax_status' ).text(),
			tax_class      = $wc_inline_data.find( '.tax_class' ).text(),
			backorders     = $wc_inline_data.find( '.backorders' ).text();

		$( 'input[name="_sku"]', '.inline-edit-row' ).val( sku );
		$( 'input[name="_regular_price"]', '.inline-edit-row' ).val( regular_price );
		$( 'input[name="_sale_price"]', '.inline-edit-row' ).val( sale_price );
		$( 'input[name="_weight"]', '.inline-edit-row' ).val( weight );
		$( 'input[name="_length"]', '.inline-edit-row' ).val( length );
		$( 'input[name="_width"]', '.inline-edit-row' ).val( width );
		$( 'input[name="_height"]', '.inline-edit-row' ).val( height );

		$( 'select[name="_shipping_class"] option:selected', '.inline-edit-row' ).attr( 'selected', false ).change();
		$( 'select[name="_shipping_class"] option[value="' + shipping_class + '"]' ).attr( 'selected', 'selected' ).change();

		$( 'input[name="_stock"]', '.inline-edit-row' ).val( stock );
		$( 'input[name="menu_order"]', '.inline-edit-row' ).val( menu_order );

		$( 'select[name="_tax_status"] option[value="' + tax_status + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
		$( 'select[name="_tax_class"] option[value="' + tax_class + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );

		$( 'select[name="_visibility"] option, select[name="_stock_status"] option, select[name="_backorders"] option' ).removeAttr( 'selected' );

		$( 'select[name="_visibility"] option[value="' + visibility + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
		$( 'select[name="_stock_status"] option[value="' + stock_status + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
		$( 'select[name="_backorders"] option[value="' + backorders + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );

		if ( 'yes' === featured ) {
			$( 'input[name="_featured"]', '.inline-edit-row' ).attr( 'checked', 'checked' );
		} else {
			$( 'input[name="_featured"]', '.inline-edit-row' ).removeAttr( 'checked' );
		}

		if ( 'yes' === manage_stock ) {
			$( '.stock_qty_field', '.inline-edit-row' ).show().removeAttr( 'style' );
			$( 'input[name="_manage_stock"]', '.inline-edit-row' ).attr( 'checked', 'checked' );
		} else {
			$( '.stock_qty_field', '.inline-edit-row' ).hide();
			$( 'input[name="_manage_stock"]', '.inline-edit-row' ).removeAttr( 'checked' );
		}

		// Conditional display
		var product_type       = $wc_inline_data.find( '.product_type' ).text(),
			product_is_virtual = $wc_inline_data.find( '.product_is_virtual' ).text();

		if ( 'simple' === product_type || 'external' === product_type ) {
			$( '.price_fields', '.inline-edit-row' ).show().removeAttr( 'style' );
		} else {
			$( '.price_fields', '.inline-edit-row' ).hide();
		}

		if ( 'yes' === product_is_virtual ) {
			$( '.dimension_fields', '.inline-edit-row' ).hide();
		} else {
			$( '.dimension_fields', '.inline-edit-row' ).show().removeAttr( 'style' );
		}

		if ( 'grouped' === product_type ) {
			$( '.stock_fields', '.inline-edit-row' ).hide();
		} else {
			$( '.stock_fields', '.inline-edit-row' ).show().removeAttr( 'style' );
		}
	});

	$( '#the-list' ).on( 'change', '.inline-edit-row input[name="_manage_stock"]', function() {

		if ( $( this ).is( ':checked' ) ) {
			$( '.stock_qty_field', '.inline-edit-row' ).show().removeAttr( 'style' );
		} else {
			$( '.stock_qty_field', '.inline-edit-row' ).hide();
		}

	});

	$( '#wpbody' ).on( 'click', '#doaction, #doaction2', function() {
		$( 'input.text', '.inline-edit-row' ).val( '' );
		$( '#woocommerce-fields select' ).prop( 'selectedIndex', 0 );
		$( '#woocommerce-fields-bulk .inline-edit-group .change-input' ).hide();

		// Autosuggest product tags on bulk edit
		var tax = 'product_tag';
		$( 'tr.inline-editor textarea[name="tax_input[' + tax + ']"]' ).suggest( ajaxurl + '?action=ajax-tag-search&tax=' + tax, { delay: 500, minchars: 2, multiple: true, multipleSep: inlineEditL10n.comma } );
	});

	$( '#wpbody' ).on( 'change', '#woocommerce-fields-bulk .inline-edit-group .change_to', function() {

		if ( 0 < $( this ).val() ) {
			$( this ).closest( 'div' ).find( '.change-input' ).show();
		} else {
			$( this ).closest( 'div' ).find( '.change-input' ).hide();
		}

	});
});
