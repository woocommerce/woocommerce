/* global wc_add_to_cart_params */
/*!
 * WooCommerce Add to Cart JS
 */
jQuery( function( $ ) {

	if ( typeof wc_add_to_cart_params === 'undefined' ) {
		return false;
	}

	// Ajax add to cart.
	$( document ).on( 'click', '.add_to_cart_button', function() {

		// AJAX add to cart request.
		var $thisbutton = $( this );

		if ( $thisbutton.is( '.ajax_add_to_cart' ) ) {

			if ( ! $thisbutton.attr( 'data-product_id' ) ) {
				return true;
			}

			$thisbutton.removeClass( 'added' );
			$thisbutton.addClass( 'loading' );

			var data = {};

			$.each( $thisbutton.data(), function( key, value ) {
				data[ key ] = value;
			});

			// Trigger event.
			$( document.body ).trigger( 'adding_to_cart', [ $thisbutton, data ] );

			// Ajax action.
			$.post( wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' ), data, function( response ) {

				if ( ! response ) {
					return;
				}

				if ( response.error && response.product_url ) {
					window.location = response.product_url;
					return;
				}

				// Redirect to cart option
				if ( wc_add_to_cart_params.cart_redirect_after_add === 'yes' ) {

					window.location = wc_add_to_cart_params.cart_url;
					return;

				} else {

					// Trigger event so themes can refresh other areas
					$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $thisbutton ] );

				}
			});

			return false;

		}

		return true;
	});

	// On "added_to_cart"
	$( document.body ).on( 'added_to_cart', function( event, fragments, cart_hash, $button ) {
		var page = window.location.toString().replace( 'add-to-cart', 'added-to-cart' );
		$button = typeof $button === 'undefined' ? false : $button;

		if ( $button ) {
			$button.removeClass( 'loading' );
		}

		// Block fragments class.
		if ( fragments ) {
			$.each( fragments, function( key ) {
				$( key ).addClass( 'updating' );
			});
		}

		// Block widgets and fragments.
		$( '.shop_table.cart, .updating, .cart_totals' )
			.fadeTo( '400', '0.6' )
			.block({
				message: null,
				overlayCSS: {
					opacity: 0.6
				}
			});

		if ( $button ) {
			// Changes button classes.
			$button.addClass( 'added' );

			// View cart text.
			if ( ! wc_add_to_cart_params.is_cart && $button.parent().find( '.added_to_cart' ).length === 0 ) {
				$button.after( ' <a href="' + wc_add_to_cart_params.cart_url + '" class="added_to_cart wc-forward" title="' +
					wc_add_to_cart_params.i18n_view_cart + '">' + wc_add_to_cart_params.i18n_view_cart + '</a>' );
			}
		}

		// Replace fragments.
		if ( fragments ) {
			$.each( fragments, function( key, value ) {
				$( key ).replaceWith( value );
			});

			$( document.body ).trigger( 'wc_fragments_loaded' );
		}

		// Unblock.
		$( '.widget_shopping_cart, .updating' ).stop( true ).css( 'opacity', '1' ).unblock();

		// Cart page elements.
		$( '.shop_table.cart' ).load( page + ' .shop_table.cart:eq(0) > *', function() {
			$( '.shop_table.cart' ).stop( true ).css( 'opacity', '1' ).unblock();

			$( document.body ).trigger( 'cart_page_refreshed' );
		});

		$( '.cart_totals' ).load( page + ' .cart_totals:eq(0) > *', function() {
			$( '.cart_totals' ).stop( true ).css( 'opacity', '1' ).unblock();
		});

	});

});
