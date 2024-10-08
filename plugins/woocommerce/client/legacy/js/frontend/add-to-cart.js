/* global wc_add_to_cart_params */
jQuery( function( $ ) {

	if ( typeof wc_add_to_cart_params === 'undefined' ) {
		return false;
	}

	/**
	 * AddToCartHandler class.
	 */
	var AddToCartHandler = function() {
		this.requests    = [];
		this.addRequest  = this.addRequest.bind( this );
		this.run         = this.run.bind( this );
		this.$liveRegion = this.createLiveRegion();

		$( document.body )
			.on( 'click', '.add_to_cart_button:not(.wc-interactive)', { addToCartHandler: this }, this.onAddToCart )
			.on( 'click', '.remove_from_cart_button', { addToCartHandler: this }, this.onRemoveFromCart )
			.on( 'added_to_cart', { addToCartHandler: this }, this.onAddedToCart )
			.on( 'removed_from_cart', { addToCartHandler: this }, this.onRemovedFromCart )
			.on( 'ajax_request_not_sent.adding_to_cart', this.updateButton );
	};

	/**
	 * Add add to cart event.
	 */
	AddToCartHandler.prototype.addRequest = function( request ) {
		this.requests.push( request );

		if ( 1 === this.requests.length ) {
			this.run();
		}
	};

	/**
	 * Run add to cart events.
	 */
	AddToCartHandler.prototype.run = function() {
		var requestManager = this,
			originalCallback = requestManager.requests[0].complete;

		requestManager.requests[0].complete = function() {
			if ( typeof originalCallback === 'function' ) {
				originalCallback();
			}

			requestManager.requests.shift();

			if ( requestManager.requests.length > 0 ) {
				requestManager.run();
			}
		};

		$.ajax( this.requests[0] );
	};

	/**
	 * Handle the add to cart event.
	 */
	AddToCartHandler.prototype.onAddToCart = function( e ) {
		var $thisbutton = $( this );

		if ( $thisbutton.is( '.ajax_add_to_cart' ) ) {
			if ( ! $thisbutton.attr( 'data-product_id' ) ) {
				return true;
			}

			// Clean existing text in mini cart live region and update aria-relevant attribute
			// so screen readers can identify the next update if it's the same as the previous one.
			e.data.addToCartHandler.$liveRegion
				.text( '' )
				.removeAttr( 'aria-relevant' );

			e.preventDefault();

			$thisbutton.removeClass( 'added' );
			$thisbutton.addClass( 'loading' );

			// Allow 3rd parties to validate and quit early.
			if ( false === $( document.body ).triggerHandler( 'should_send_ajax_request.adding_to_cart', [ $thisbutton ] ) ) {
				$( document.body ).trigger( 'ajax_request_not_sent.adding_to_cart', [ false, false, $thisbutton ] );
				return true;
			}

			var data = {};

			// Fetch changes that are directly added by calling $thisbutton.data( key, value )
			$.each( $thisbutton.data(), function( key, value ) {
				data[ key ] = value;
			});

			// Fetch data attributes in $thisbutton. Give preference to data-attributes because they can be directly modified by javascript
			// while `.data` are jquery specific memory stores.
			$.each( $thisbutton[0].dataset, function( key, value ) {
				data[ key ] = value;
			});

			// Trigger event.
			$( document.body ).trigger( 'adding_to_cart', [ $thisbutton, data ] );

			e.data.addToCartHandler.addRequest({
				type: 'POST',
				url: wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' ),
				data: data,
				success: function( response ) {
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
					}

					// Trigger event so themes can refresh other areas.
					$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $thisbutton ] );
				},
				dataType: 'json'
			});
		}
	};

	/**
	 * Update fragments after remove from cart event in mini-cart.
	 */
	AddToCartHandler.prototype.onRemoveFromCart = function( e ) {
		var $thisbutton = $( this ),
			$row        = $thisbutton.closest( '.woocommerce-mini-cart-item' );

		e.data.addToCartHandler.$liveRegion
			.text( '' )
			.removeAttr( 'aria-relevant' );

		e.preventDefault();

		$row.block({
			message: null,
			overlayCSS: {
				opacity: 0.6
			}
		});

		e.data.addToCartHandler.addRequest({
			type: 'POST',
			url: wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'remove_from_cart' ),
			data: {
				cart_item_key : $thisbutton.data( 'cart_item_key' )
			},
			success: function( response ) {
				if ( ! response || ! response.fragments ) {
					window.location = $thisbutton.attr( 'href' );
					return;
				}

				$( document.body ).trigger( 'removed_from_cart', [ response.fragments, response.cart_hash, $thisbutton ] );
			},
			error: function() {
				window.location = $thisbutton.attr( 'href' );
				return;
			},
			dataType: 'json'
		});
	};

	/**
	 * Update cart page elements after add to cart events.
	 */
	AddToCartHandler.prototype.updateButton = function( e, fragments, cart_hash, $button ) {
		// Some themes and plugins manually trigger added_to_cart without passing a button element, which in turn calls this function.
		// If there is no button we don't want to crash.
		$button = typeof $button === 'undefined' ? false : $button;

		if ( $button ) {
			$button.removeClass( 'loading' );

			if ( fragments ) {
				$button.addClass( 'added' );
			}

			// View cart text.
			if ( fragments && ! wc_add_to_cart_params.is_cart && $button.parent().find( '.added_to_cart' ).length === 0 ) {
				$button.after( '<a href="' + wc_add_to_cart_params.cart_url + '" class="added_to_cart wc-forward" title="' +
					wc_add_to_cart_params.i18n_view_cart + '">' + wc_add_to_cart_params.i18n_view_cart + '</a>' );
			}

			$( document.body ).trigger( 'wc_cart_button_updated', [ $button ] );
		}
	};

	/**
	 * Update fragments after add to cart events.
	 */
	AddToCartHandler.prototype.updateFragments = function( e, fragments ) {
		if ( fragments ) {
			$.each( fragments, function( key ) {
				$( key )
					.addClass( 'updating' )
					.fadeTo( '400', '0.6' )
					.block({
						message: null,
						overlayCSS: {
							opacity: 0.6
						}
					});
			});

			$.each( fragments, function( key, value ) {
				$( key ).replaceWith( value );
				$( key ).stop( true ).css( 'opacity', '1' ).unblock();
			});

			$( document.body ).trigger( 'wc_fragments_loaded' );
		}
	};

	/**
	 * Update cart live region message after add/remove cart events.
	 */
	AddToCartHandler.prototype.alertCartUpdated = function( e, fragments, cart_hash, $button ) {
		// Some themes and plugins manually trigger added_to_cart without passing a button element, which in turn calls this function.
		// If there is no button we don't want to crash.
		$button = typeof $button === 'undefined' ? false : $button;

		if ( $button ) {
			var message = $button.data( 'success_message' );

			if ( !message ) {
				return;
			}
		
			// If the response after adding/removing an item to/from the cart is really fast,
			// screen readers may not have time to identify the changes in the live region element. 
			// So, we add a delay to ensure an interval between messages.
			e.data.addToCartHandler.$liveRegion
				.delay(1000)
				.text( message )
				.attr( 'aria-relevant', 'all' );
		}
	};

	/**
	 * Add live region into the body element.
	 */
	AddToCartHandler.prototype.createLiveRegion = function() {
		var existingLiveRegion = $( '.widget_shopping_cart_live_region' );

		if ( existingLiveRegion.length ) {
			return existingLiveRegion;
		}
		
		return $( '<div class="widget_shopping_cart_live_region screen-reader-text" role="status"></div>' ).appendTo( 'body' );
	};

	/**
	 * Callbacks after added to cart event.
	 */
	AddToCartHandler.prototype.onAddedToCart = function( e, fragments, cart_hash, $button ) {
		e.data.addToCartHandler.updateButton( e, fragments, cart_hash, $button );
		e.data.addToCartHandler.updateFragments( e, fragments );
		e.data.addToCartHandler.alertCartUpdated( e, fragments, cart_hash, $button );
	};

	/**
	 * Callbacks after removed from cart event.
	 */
	AddToCartHandler.prototype.onRemovedFromCart = function( e, fragments, cart_hash, $button ) {
		e.data.addToCartHandler.updateFragments( e, fragments );
		e.data.addToCartHandler.alertCartUpdated( e, fragments, cart_hash, $button );
	};

	/**
	 * Init AddToCartHandler.
	 */
	new AddToCartHandler();
});
