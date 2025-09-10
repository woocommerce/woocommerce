/* global woocommerce_admin_product_editor */
jQuery( function ( $ ) {
	$( function () {
		var editorWrapper = $( '#postdivrich' );

		/**
		 * In the Product Editor context, the footer needs to be hidden otherwise the computation of the postbox position is wrong.
		 * For more details, see https://github.com/woocommerce/woocommerce/pull/59212.
		 */
		$( '#wpfooter' ).css( { visibility: 'hidden', display: 'unset' } );

		if ( editorWrapper.length ) {
			editorWrapper.addClass( 'postbox woocommerce-product-description' );
			editorWrapper.prepend(
				'<h2 class="postbox-header"><label>' +
					woocommerce_admin_product_editor.i18n_description +
					'</label></h2>'
			);
		}
	} );
} );
