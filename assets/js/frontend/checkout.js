jQuery( function( $ ) {

	// wc_checkout_params is required to continue, ensure the object exists
	if ( typeof wc_checkout_params === 'undefined' ) {
		return false;
	}

	$.blockUI.defaults.overlayCSS.cursor = 'default';

	var wc_checkout_form = {
		updateTimer: false,
		dirtyInput: false,
		xhr: false,
		$order_review: $( '#order_review' ),
		$checkout_form: $( 'form.checkout' ),
	    init: function() {
	    	$( 'body' ).bind( 'update_checkout', this.reset_update_checkout_timer );
			$( 'body' ).bind( 'update_checkout', this.update_checkout );
			$( 'body' ).bind( 'init_checkout', this.init_checkout );

			// Payment methods
			this.$order_review.on( 'click', 'input[name=payment_method]', this.payment_method_selected );

			// Form submission
			this.$checkout_form.on( 'submit', this.submit );

			// Inline validation
			this.$checkout_form.on( 'blur input change', '.input-text, select', this.validate_field );

			// Inputs/selects which update totals
			this.$checkout_form.on( 'input change', 'select.shipping_method, input[name^=shipping_method], #ship-to-different-address input, .update_totals_on_change select, .update_totals_on_change input[type=radio]', this.trigger_update_checkout );
			this.$checkout_form.on( 'change', '.address-field input.input-text, .update_totals_on_change input.input-text', this.maybe_input_changed );
			this.$checkout_form.on( 'input change', '.address-field select', this.input_changed );
			this.$checkout_form.on( 'input keydown', '.address-field input.input-text, .update_totals_on_change input.input-text', this.queue_update_checkout );

			// Address fields
			this.$checkout_form.on( 'change', '#ship-to-different-address input', this.ship_to_different_address );

			// Trigger events
			this.$order_review.find( 'input[name=payment_method]:checked' ).trigger( 'click' );
			this.$checkout_form.find( '#ship-to-different-address input' ).change();

			// Update on page load
			if ( wc_checkout_params.is_checkout === '1' ) {
				$( 'body' ).trigger( 'init_checkout' );
			}
			if ( wc_checkout_params.option_guest_checkout === 'yes' ) {
				$( 'input#createaccount' ).change( this.toggle_create_account ).change();
			}
	    },
	    toggle_create_account: function( e ) {
	    	$( 'div.create-account' ).hide();

			if ( $( this ).is( ':checked' ) ) {
				$( 'div.create-account' ).slideDown();
			}
	    },
	    init_checkout: function( e ) {
	    	$( '#billing_country, #shipping_country, .country_to_state' ).change();
			$( 'body' ).trigger( 'update_checkout' );
	    },
	    maybe_input_changed: function( e ) {
	    	if ( wc_checkout_form.dirtyInput ) {
				wc_checkout_form.input_changed();
			}
	    },
	    input_changed: function( e ) {
	    	wc_checkout_form.dirtyInput = this;
			wc_checkout_form.maybe_update_checkout();
	    },
	    queue_update_checkout: function( e ) {
	    	var code = e.keyCode || e.which || 0;

			if ( code === 9 ) {
				return true;
			}

			wc_checkout_form.dirtyInput = this;
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.updateTimer = setTimeout( wc_checkout_form.maybe_update_checkout, '1000' );
	    },
	    trigger_update_checkout: function( e ) {
	    	wc_checkout_form.reset_update_checkout_timer();
	    	wc_checkout_form.dirtyInput = false;
	    	$( 'body' ).trigger( 'update_checkout' );
	    },
		maybe_update_checkout: function() {
			var update_totals = true;

			if ( $( wc_checkout_form.dirtyInput ).size() ) {
				$required_inputs = $( wc_checkout_form.dirtyInput ).closest( 'div' ).find( '.address-field.validate-required' );

				if ( $required_inputs.size() ) {
					$required_inputs.each( function( e ) {
						if ( $( this ).find( 'input.input-text' ).val() === '' ) {
							update_totals = false;
						}
					});
				}
			}
			if ( update_totals ) {
				wc_checkout_form.trigger_update_checkout();
			}
		},
	    ship_to_different_address: function( e ) {
	    	$( 'div.shipping_address' ).hide();
			if ( $( this ).is( ':checked' ) ) {
				$( 'div.shipping_address' ).slideDown();
			}
	    },
	    payment_method_selected: function( e ) {
	    	if ( $( '.payment_methods input.input-radio' ).length > 1 ) {
				var target_payment_box = $( 'div.payment_box.' + $( this ).attr( 'ID' ) );

				if ( $( this ).is( ':checked' ) && ! target_payment_box.is( ':visible' ) ) {
					$( 'div.payment_box' ).filter( ':visible' ).slideUp( 250 );

					if ( $( this ).is( ':checked' ) ) {
						$( 'div.payment_box.' + $( this ).attr( 'ID' ) ).slideDown( 250 );
					}
				}
			} else {
				$( 'div.payment_box' ).show();
			}

			if ( $( this ).data( 'order_button_text' ) ) {
				$( '#place_order' ).val( $( this ).data( 'order_button_text' ) );
			} else {
				$( '#place_order' ).val( $( '#place_order' ).data( 'value' ) );
			}
	    },
	    reset_update_checkout_timer: function() {
	    	clearTimeout( wc_checkout_form.updateTimer );
	    },
		validate_field: function( e ) {
			var $this     = $( this ),
				$parent   = $this.closest( '.form-row' ),
				validated = true;

			if ( $parent.is( '.validate-required' ) ) {
				if ( $this.val() === '' ) {
					$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
					validated = false;
				}
			}

			if ( $parent.is( '.validate-email' ) ) {
				if ( $this.val() ) {

					/* http://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
					var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

					if ( ! pattern.test( $this.val()  ) ) {
						$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-email' );
						validated = false;
					}
				}
			}

			if ( validated ) {
				$parent.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' ).addClass( 'woocommerce-validated' );
			}
		},
		update_checkout: function() {
			// Small timeout to prevent multiple requests when several fields update at the same time
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.updateTimer = setTimeout( wc_checkout_form.update_checkout_action, '5' );
		},
	    update_checkout_action: function() {
	    	if ( wc_checkout_form.xhr ) {
				wc_checkout_form.xhr.abort();
			}

			if ( $( 'form.checkout' ).size() === 0 ) {
				return;
			}

			var shipping_methods = [];

			$( 'select.shipping_method, input[name^=shipping_method][type=radio]:checked, input[name^=shipping_method][type=hidden]' ).each( function( index, input ) {
				shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
			} );

			var payment_method = $( '#order_review input[name=payment_method]:checked' ).val(),
				country			= $( '#billing_country' ).val(),
				state			= $( '#billing_state' ).val(),
				postcode		= $( 'input#billing_postcode' ).val(),
				city			= $( '#billing_city' ).val(),
				address			= $( 'input#billing_address_1' ).val(),
				address_2		= $( 'input#billing_address_2' ).val(),
				s_country,
				s_state,
				s_postcode,
				s_city,
				s_address,
				s_address_2;

			if ( $( '#ship-to-different-address input' ).is( ':checked' ) ) {
				s_country		= $( '#shipping_country' ).val();
				s_state			= $( '#shipping_state' ).val();
				s_postcode		= $( 'input#shipping_postcode' ).val();
				s_city			= $( '#shipping_city' ).val();
				s_address		= $( 'input#shipping_address_1' ).val();
				s_address_2		= $( 'input#shipping_address_2' ).val();
			} else {
				s_country		= country;
				s_state			= state;
				s_postcode		= postcode;
				s_city			= city;
				s_address		= address;
				s_address_2		= address_2;
			}

			$( '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				action:						'woocommerce_update_order_review',
				security:					wc_checkout_params.update_order_review_nonce,
				shipping_method:			shipping_methods,
				payment_method:				payment_method,
				country:					country,
				state:						state,
				postcode:					postcode,
				city:						city,
				address:					address,
				address_2:					address_2,
				s_country:					s_country,
				s_state:					s_state,
				s_postcode:					s_postcode,
				s_city:						s_city,
				s_address:					s_address,
				s_address_2:				s_address_2,
				post_data:					$( 'form.checkout' ).serialize()
			};

			wc_checkout_form.xhr = $.ajax({
				type:		'POST',
				url:		wc_checkout_params.ajax_url,
				data:		data,
				success:	function( data ) {
					// Always update the fragments
					if ( data && data.fragments ) {
						$.each( data.fragments, function ( key, value ) {
							$( key ).replaceWith( value );
							$( key ).unblock();
						} );
					}

					// Check for error
					if ( 'failure' == data.result ) {

						var $form = $( 'form.checkout' );

						if ( 'true' === data.reload ) {
							window.location.reload();
							return;
						}

						$( '.woocommerce-error, .woocommerce-message' ).remove();

						// Add new errors
						if ( data.messages ) {
							$form.prepend( data.messages );
						} else {
							$form.prepend( data );
						}

						// Lose focus for all fields
						$form.find( '.input-text, select' ).blur();

						// Scroll to top
						$( 'html, body' ).animate( {
							scrollTop: ( $( 'form.checkout' ).offset().top - 100 )
						}, 1000 );

					}

					// Trigger click e on selected payment method
					$( '.woocommerce-checkout' ).find( 'input[name=payment_method]:checked' ).trigger( 'click' );

					// Fire updated_checkout e
					$( 'body' ).trigger( 'updated_checkout' );
				}

			});
	    },
	    submit: function( e ) {
			wc_checkout_form.reset_update_checkout_timer();
			var $form = $( this );

			if ( $form.is( '.processing' ) ) {
				return false;
			}

			// Trigger a handler to let gateways manipulate the checkout if needed
			if ( $form.triggerHandler( 'checkout_place_order' ) !== false && $form.triggerHandler( 'checkout_place_order_' + $( '#order_review input[name=payment_method]:checked' ).val() ) !== false ) {

				$form.addClass( 'processing' );

				var form_data = $form.data();

				if ( form_data["blockUI.isBlocked"] != 1 ) {
					$form.block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				}

				$.ajax({
					type:		'POST',
					url:		wc_checkout_params.checkout_url,
					data:		$form.serialize(),
					success:	function( code ) {
						var result = '';

						try {
							// Get the valid JSON only from the returned string
							if ( code.indexOf( '<!--WC_START-->' ) >= 0 )
								code = code.split( '<!--WC_START-->' )[1]; // Strip off before after WC_START

							if ( code.indexOf( '<!--WC_END-->' ) >= 0 )
								code = code.split( '<!--WC_END-->' )[0]; // Strip off anything after WC_END

							// Parse
							result = $.parseJSON( code );

							if ( result.result === 'success' ) {
								if ( result.redirect.indexOf( "https://" ) != -1 || result.redirect.indexOf( "http://" ) != -1 ) {
									window.location = result.redirect;
								} else {
									window.location = decodeURI( result.redirect );
								}
							} else if ( result.result === 'failure' ) {
								throw 'Result failure';
							} else {
								throw 'Invalid response';
							}
						}

						catch( err ) {

							if ( result.reload === 'true' ) {
								window.location.reload();
								return;
							}

							// Remove old errors
							$( '.woocommerce-error, .woocommerce-message' ).remove();

							// Add new errors
							if ( result.messages ) {
								$form.prepend( result.messages );
							} else {
								$form.prepend( code );
							}

							// Cancel processing
							$form.removeClass( 'processing' ).unblock();

							// Lose focus for all fields
							$form.find( '.input-text, select' ).blur();

							// Scroll to top
							$( 'html, body' ).animate({
								scrollTop: ( $( 'form.checkout' ).offset().top - 100 )
							}, 1000 );

							// Trigger update in case we need a fresh nonce
							if ( result.refresh === 'true' ) {
								$( 'body' ).trigger( 'update_checkout' );
							}

							$( 'body' ).trigger( 'checkout_error' );
						}
					},
					dataType: 'html'
				});

			}

			return false;
	    }
	};

	var wc_checkout_coupons = {
		init: function() {
			$( 'body' ).on( 'click', 'a.showcoupon', this.show_coupon_form );
			$( 'body' ).on( 'click', '.woocommerce-remove-coupon', this.remove_coupon );
			$( 'form.checkout_coupon' ).hide().submit( this.submit );
		},
		show_coupon_form: function( e ) {
	    	$( '.checkout_coupon' ).slideToggle( 400, function( e ) {
				$( '.checkout_coupon' ).find(':input:eq(0)').focus()
			});
			return false;
	    },
	    submit: function( e ) {
			var $form = $( this );

			if ( $form.is( '.processing' ) ) return false;

			$form.addClass( 'processing' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				action:			'woocommerce_apply_coupon',
				security:		wc_checkout_params.apply_coupon_nonce,
				coupon_code:	$form.find( 'input[name=coupon_code]' ).val()
			};

			$.ajax({
				type:		'POST',
				url:		wc_checkout_params.ajax_url,
				data:		data,
				success:	function( code ) {
					$( '.woocommerce-error, .woocommerce-message' ).remove();
					$form.removeClass( 'processing' ).unblock();

					if ( code ) {
						$form.before( code );
						$form.slideUp();

						$( 'body' ).trigger( 'update_checkout' );
					}
				},
				dataType: 'html'
			});

			return false;
	    },
	    remove_coupon: function( e ) {
	    	e.preventDefault();

			var container = $( this ).parents( '.woocommerce-checkout-review-order' ),
				coupon    = $( this ).data( 'coupon' );

			container.addClass( 'processing' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				action:   'woocommerce_remove_coupon',
				security: wc_checkout_params.remove_coupon_nonce,
				coupon:   coupon
			};

			$.ajax({
				type:    'POST',
				url:     wc_checkout_params.ajax_url,
				data:    data,
				success: function( code ) {
					$( '.woocommerce-error, .woocommerce-message' ).remove();
					container.removeClass( 'processing' ).unblock();

					if ( code ) {
						$( 'form.woocommerce-checkout' ).before( code );

						$( 'body' ).trigger( 'update_checkout' );

						// remove coupon code from coupon field
						$( 'form.checkout_coupon' ).find( 'input[name="coupon_code"]' ).val( '' );
					}
				},
				error: function ( jqXHR, textStatus, errorThrown ) {
					if ( wc_checkout_params.debug_mode ) {
						console.log( jqXHR.responseText );
					}
				},
				dataType: 'html'
			});
	    }
	}

	var wc_checkout_login_form = {
		init: function() {
			$( 'body' ).on( 'click', 'a.showlogin', this.show_login_form );
		},
		show_login_form: function( e ) {
	    	$( 'form.login' ).slideToggle();
			return false;
	    }
	}

	wc_checkout_form.init();
	wc_checkout_coupons.init();
	wc_checkout_login_form.init();
});
