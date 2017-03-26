/* global wc_checkout_params */
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
			$( document.body ).bind( 'update_checkout', this.update_checkout );
			$( document.body ).bind( 'init_checkout', this.init_checkout );

			// Payment methods
			this.$checkout_form.on( 'click', 'input[name="payment_method"]', this.payment_method_selected );

			if ( $( document.body ).hasClass( 'woocommerce-order-pay' ) ) {
				this.$order_review.on( 'click', 'input[name="payment_method"]', this.payment_method_selected );
			}

			// Form submission
			this.$checkout_form.on( 'submit', this.submit );

			// Inline validation
			this.$checkout_form.on( 'blur change', '.input-text, select, input:checkbox', this.validate_field );

			// Manual trigger
			this.$checkout_form.on( 'update', this.trigger_update_checkout );

			// Inputs/selects which update totals
			this.$checkout_form.on( 'change', 'select.shipping_method, input[name^="shipping_method"], #ship-to-different-address input, .update_totals_on_change select, .update_totals_on_change input[type="radio"]', this.trigger_update_checkout );
			this.$checkout_form.on( 'change', '.address-field select', this.input_changed );
			this.$checkout_form.on( 'change', '.address-field input.input-text, .update_totals_on_change input.input-text', this.maybe_input_changed );
			this.$checkout_form.on( 'change keydown', '.address-field input.input-text, .update_totals_on_change input.input-text', this.queue_update_checkout );

			// Address fields
			this.$checkout_form.on( 'change', '#ship-to-different-address input', this.ship_to_different_address );

			// Trigger events
			this.$checkout_form.find( '#ship-to-different-address input' ).change();
			this.init_payment_methods();

			// Update on page load
			if ( wc_checkout_params.is_checkout === '1' ) {
				$( document.body ).trigger( 'init_checkout' );
			}
			if ( wc_checkout_params.option_guest_checkout === 'yes' ) {
				$( 'input#createaccount' ).change( this.toggle_create_account ).change();
			}
		},
		init_payment_methods: function( selectedPaymentMethod ) {
			var $payment_methods = $( '.woocommerce-checkout' ).find( 'input[name="payment_method"]' );

			// If there is one method, we can hide the radio input
			if ( 1 === $payment_methods.length ) {
				$payment_methods.eq(0).hide();
			}

			// If there was a previously selected method, check that one.
			if ( selectedPaymentMethod ) {
				$( '#' + selectedPaymentMethod ).prop( 'checked', true );
			}

			// If there are none selected, select the first.
			if ( 0 === $payment_methods.filter( ':checked' ).length ) {
				$payment_methods.eq(0).prop( 'checked', true );
			}

			// Trigger click event for selected method
			$payment_methods.filter( ':checked' ).eq(0).trigger( 'click' );
		},
		get_payment_method: function() {
			return wc_checkout_form.$order_review.find( 'input[name="payment_method"]:checked' ).val();
		},
		payment_method_selected: function() {
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
		toggle_create_account: function() {
			$( 'div.create-account' ).hide();

			if ( $( this ).is( ':checked' ) ) {
				$( 'div.create-account' ).slideDown();
			}
		},
		init_checkout: function() {
			$( '#billing_country, #shipping_country, .country_to_state' ).change();
			$( document.body ).trigger( 'update_checkout' );
		},
		maybe_input_changed: function( e ) {
			if ( wc_checkout_form.dirtyInput ) {
				wc_checkout_form.input_changed( e );
			}
		},
		input_changed: function( e ) {
			wc_checkout_form.dirtyInput = e.target;
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
		trigger_update_checkout: function() {
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.dirtyInput = false;
			$( document.body ).trigger( 'update_checkout' );
		},
		maybe_update_checkout: function() {
			var update_totals = true;

			if ( $( wc_checkout_form.dirtyInput ).length ) {
				var $required_inputs = $( wc_checkout_form.dirtyInput ).closest( 'div' ).find( '.address-field.validate-required' );

				if ( $required_inputs.length ) {
					$required_inputs.each( function() {
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
		ship_to_different_address: function() {
			$( 'div.shipping_address' ).hide();
			if ( $( this ).is( ':checked' ) ) {
				$( 'div.shipping_address' ).slideDown();
			}
		},
		reset_update_checkout_timer: function() {
			clearTimeout( wc_checkout_form.updateTimer );
		},
		validate_field: function() {
			var $this     = $( this ),
				$parent   = $this.closest( '.form-row' ),
				validated = true;

			if ( $parent.is( '.validate-required' ) ) {
				if ( 'checkbox' === $this.attr( 'type' ) && ! $this.is( ':checked' ) ) {
					$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
					validated = false;
				} else if ( $this.val() === '' ) {
					$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
					validated = false;
				}
			}

			if ( $parent.is( '.validate-email' ) ) {
				if ( $this.val() ) {

					/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
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
		update_checkout: function( event, args ) {
			// Small timeout to prevent multiple requests when several fields update at the same time
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.updateTimer = setTimeout( wc_checkout_form.update_checkout_action, '5', args );
		},
		update_checkout_action: function( args ) {
			if ( wc_checkout_form.xhr ) {
				wc_checkout_form.xhr.abort();
			}

			if ( $( 'form.checkout' ).length === 0 ) {
				return;
			}

			args = typeof args !== 'undefined' ? args : {
				update_shipping_method: true
			};

			var country			 = $( '#billing_country' ).val(),
				state			 = $( '#billing_state' ).val(),
				postcode		 = $( 'input#billing_postcode' ).val(),
				city			 = $( '#billing_city' ).val(),
				address			 = $( 'input#billing_address_1' ).val(),
				address_2		 = $( 'input#billing_address_2' ).val(),
				s_country		 = country,
				s_state			 = state,
				s_postcode		 = postcode,
				s_city			 = city,
				s_address		 = address,
				s_address_2		 = address_2;

			if ( $( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) ) {
				s_country		 = $( '#shipping_country' ).val();
				s_state			 = $( '#shipping_state' ).val();
				s_postcode		 = $( 'input#shipping_postcode' ).val();
				s_city			 = $( '#shipping_city' ).val();
				s_address		 = $( 'input#shipping_address_1' ).val();
				s_address_2		 = $( 'input#shipping_address_2' ).val();
			}

			var data = {
				security:					wc_checkout_params.update_order_review_nonce,
				payment_method:				wc_checkout_form.get_payment_method(),
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

			if ( false !== args.update_shipping_method ) {
				var shipping_methods = {};

				$( 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]' ).each( function() {
					shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
				} );

				data.shipping_method = shipping_methods;
			}

			$( '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			wc_checkout_form.xhr = $.ajax({
				type:		'POST',
				url:		wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'update_order_review' ),
				data:		data,
				success:	function( data ) {

					var selectedPaymentMethod = $( '.woocommerce-checkout input[name="payment_method"]:checked' ).attr( 'id' );

					// Reload the page if requested
					if ( 'true' === data.reload ) {
						window.location.reload();
						return;
					}

					// Remove any notices added previously
					$( '.woocommerce-NoticeGroup-updateOrderReview' ).remove();

					var termsCheckBoxChecked = $( '#terms' ).prop( 'checked' );

					// Always update the fragments
					if ( data && data.fragments ) {
						$.each( data.fragments, function ( key, value ) {
							$( key ).replaceWith( value );
							$( key ).unblock();
						} );
					}

					// Recheck the terms and conditions box, if needed
					if ( termsCheckBoxChecked ) {
						$( '#terms' ).prop( 'checked', true );
					}

					// Check for error
					if ( 'failure' === data.result ) {

						var $form = $( 'form.checkout' );

						// Remove notices from all sources
						$( '.woocommerce-error, .woocommerce-message' ).remove();

						// Add new errors returned by this event
						if ( data.messages ) {
							$form.prepend( '<div class="woocommerce-NoticeGroup-updateOrderReview">' + data.messages + '</div>' );
						} else {
							$form.prepend( data );
						}

						// Lose focus for all fields
						$form.find( '.input-text, select, input:checkbox' ).blur();

						// Scroll to top
						$( 'html, body' ).animate( {
							scrollTop: ( $( 'form.checkout' ).offset().top - 100 )
						}, 1000 );

					}

					// Re-init methods
					wc_checkout_form.init_payment_methods( selectedPaymentMethod );

					// Fire updated_checkout e
					$( document.body ).trigger( 'updated_checkout', [ data ] );
				}

			});
		},
		submit: function() {
			wc_checkout_form.reset_update_checkout_timer();
			var $form = $( this );

			if ( $form.is( '.processing' ) ) {
				return false;
			}

			// Trigger a handler to let gateways manipulate the checkout if needed
			if ( $form.triggerHandler( 'checkout_place_order' ) !== false && $form.triggerHandler( 'checkout_place_order_' + wc_checkout_form.get_payment_method() ) !== false ) {

				$form.addClass( 'processing' );

				var form_data = $form.data();

				if ( 1 !== form_data['blockUI.isBlocked'] ) {
					$form.block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				}

				// ajaxSetup is global, but we use it to ensure JSON is valid once returned.
				$.ajaxSetup( {
					dataFilter: function( raw_response, dataType ) {
						// We only want to work with JSON
						if ( 'json' !== dataType ) {
							return raw_response;
						}

						try {
							// Check for valid JSON
							var data = $.parseJSON( raw_response );

							if ( data && 'object' === typeof data ) {

								// Valid - return it so it can be parsed by Ajax handler
								return raw_response;
							}

						} catch ( e ) {

							// Attempt to fix the malformed JSON
							var valid_json = raw_response.match( /{"result.*"}/ );

							if ( null === valid_json ) {
								console.log( 'Unable to fix malformed JSON' );
							} else {
								console.log( 'Fixed malformed JSON. Original:' );
								console.log( raw_response );
								raw_response = valid_json[0];
							}
						}

						return raw_response;
					}
				} );

				$.ajax({
					type:		'POST',
					url:		wc_checkout_params.checkout_url,
					data:		$form.serialize(),
					dataType:   'json',
					success:	function( result ) {
						try {
							if ( result.result === 'success' ) {
								if ( -1 === result.redirect.indexOf( 'https://' ) || -1 === result.redirect.indexOf( 'http://' ) ) {
									window.location = result.redirect;
								} else {
									window.location = decodeURI( result.redirect );
								}
							} else if ( result.result === 'failure' ) {
								throw 'Result failure';
							} else {
								throw 'Invalid response';
							}
						} catch( err ) {
							// Reload page
							if ( result.reload === 'true' ) {
								window.location.reload();
								return;
							}

							// Trigger update in case we need a fresh nonce
							if ( result.refresh === 'true' ) {
								$( document.body ).trigger( 'update_checkout' );
							}

							// Add new errors
							if ( result.messages ) {
								wc_checkout_form.submit_error( result.messages );
							} else {
								wc_checkout_form.submit_error( '<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>' );
							}
						}
					},
					error:	function( jqXHR, textStatus, errorThrown ) {
						wc_checkout_form.submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' );
					}
				});
			}

			return false;
		},
		submit_error: function( error_message ) {
			$( '.woocommerce-error, .woocommerce-message' ).remove();
			wc_checkout_form.$checkout_form.prepend( error_message );
			wc_checkout_form.$checkout_form.removeClass( 'processing' ).unblock();
			wc_checkout_form.$checkout_form.find( '.input-text, select, input:checkbox' ).blur();
			$( 'html, body' ).animate({
				scrollTop: ( $( 'form.checkout' ).offset().top - 100 )
			}, 1000 );
			$( document.body ).trigger( 'checkout_error' );
		}
	};

	var wc_checkout_coupons = {
		init: function() {
			$( document.body ).on( 'click', 'a.showcoupon', this.show_coupon_form );
			$( document.body ).on( 'click', '.woocommerce-remove-coupon', this.remove_coupon );
			$( 'form.checkout_coupon' ).hide().submit( this.submit );
		},
		show_coupon_form: function() {
			$( '.checkout_coupon' ).slideToggle( 400, function() {
				$( '.checkout_coupon' ).find( ':input:eq(0)' ).focus();
			});
			return false;
		},
		submit: function() {
			var $form = $( this );

			if ( $form.is( '.processing' ) ) {
				return false;
			}

			$form.addClass( 'processing' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				security:		wc_checkout_params.apply_coupon_nonce,
				coupon_code:	$form.find( 'input[name="coupon_code"]' ).val()
			};

			$.ajax({
				type:		'POST',
				url:		wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'apply_coupon' ),
				data:		data,
				success:	function( code ) {
					$( '.woocommerce-error, .woocommerce-message' ).remove();
					$form.removeClass( 'processing' ).unblock();

					if ( code ) {
						$form.before( code );
						$form.slideUp();

						$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
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
				security: wc_checkout_params.remove_coupon_nonce,
				coupon:   coupon
			};

			$.ajax({
				type:    'POST',
				url:     wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'remove_coupon' ),
				data:    data,
				success: function( code ) {
					$( '.woocommerce-error, .woocommerce-message' ).remove();
					container.removeClass( 'processing' ).unblock();

					if ( code ) {
						$( 'form.woocommerce-checkout' ).before( code );

						$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );

						// Remove coupon code from coupon field
						$( 'form.checkout_coupon' ).find( 'input[name="coupon_code"]' ).val( '' );
					}
				},
				error: function ( jqXHR ) {
					if ( wc_checkout_params.debug_mode ) {
						/* jshint devel: true */
						console.log( jqXHR.responseText );
					}
				},
				dataType: 'html'
			});
		}
	};

	var wc_checkout_login_form = {
		init: function() {
			$( document.body ).on( 'click', 'a.showlogin', this.show_login_form );
		},
		show_login_form: function() {
			$( 'form.login' ).slideToggle();
			return false;
		}
	};

	wc_checkout_form.init();
	wc_checkout_coupons.init();
	wc_checkout_login_form.init();
});
