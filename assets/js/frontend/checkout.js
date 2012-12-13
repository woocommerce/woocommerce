jQuery(document).ready(function($) {

	var updateTimer;
	var dirtyInput = false;
	var xhr;

	function update_checkout() {

		if (xhr) xhr.abort();

		if ( $('select#shipping_method').size() > 0 )
			var method = $('select#shipping_method').val();
		else
			var method = $('input[name=shipping_method]:checked').val();

		var payment_method 	= $('#order_review input[name=payment_method]:checked').val();
		var country 		= $('#billing_country').val();
		var state 			= $('#billing_state').val();
		var postcode 		= $('input#billing_postcode').val();

		if ($('#shiptobilling input').is(':checked') || $('#shiptobilling input').size()==0) {
			var s_country 	= country;
			var s_state 	= state;
			var s_postcode 	= postcode;

		} else {
			var s_country 	= $('#shipping_country').val();
			var s_state 	= $('#shipping_state').val();
			var s_postcode 	= $('input#shipping_postcode').val();
		}

		$('#order_methods, #order_review').block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', opacity: 0.6}});

		var data = {
			action: 			'woocommerce_update_order_review',
			security: 			woocommerce_params.update_order_review_nonce,
			shipping_method: 	method,
			payment_method:		payment_method,
			country: 			country,
			state: 				state,
			postcode: 			postcode,
			s_country: 			s_country,
			s_state: 			s_state,
			s_postcode: 		s_postcode,
			post_data:			$('form.checkout').serialize()
		};

		xhr = $.ajax({
			type: 		'POST',
			url: 		woocommerce_params.ajax_url,
			data: 		data,
			success: 	function( response ) {
				$('#order_review').after(response).remove();
				$('#order_review input[name=payment_method]:checked').click();
				$('body').trigger('updated_checkout');
			}
		});

	}

	// Event for updating the checkout
	$('body').bind('update_checkout', function() {
		clearTimeout(updateTimer);
		update_checkout();
	});

	$('p.password, form.login, .checkout_coupon, div.shipping_address').hide();

	$('input.show_password').change(function(){
		$('p.password').slideToggle();
	});

	$('a.showlogin').click(function(){
		$('form.login').slideToggle();
		return false;
	});

	$('a.showcoupon').click(function(){
		$('.checkout_coupon').slideToggle();
		return false;
	});

	$('#shiptobilling input').change(function(){
		$('div.shipping_address').hide();
		if (!$(this).is(':checked')) {
			$('div.shipping_address').slideDown();
		}
	}).change();

	if (woocommerce_params.option_guest_checkout=='yes') {

		$('div.create-account').hide();

		$('input#createaccount').change(function(){
			$('div.create-account').hide();
			if ($(this).is(':checked')) {
				$('div.create-account').slideDown();
			}
		}).change();

	}

	$('.payment_methods input.input-radio').live('click', function(){
		$('div.payment_box').filter(':visible').slideUp(250);
		if ($(this).is(':checked')) {
			$('div.payment_box.' + $(this).attr('ID')).slideDown(250);
		}
	});

	$('#order_review input[name=payment_method]:checked').click();

	/* Update totals */
	// Inputs/selects which update totals instantly
	$('select#shipping_method, input[name=shipping_method], #shiptobilling input, .update_totals_on_change select').live('change', function(){
		clearTimeout( updateTimer );
		$('body').trigger('update_checkout');
	});

	// Inputs which update totals on change
	function input_changed() {
		dirtyInput = false;
		$('body').trigger('update_checkout');
	}
	$('.update_totals_on_change input').live('change', function(){
		if ( dirtyInput ) {
			clearTimeout( updateTimer );
			$('body').trigger('update_checkout');
		}
	});
	$('.update_totals_on_change input').live('keydown', function( e ){
		var code = e.keyCode || e.which;
		if ( code == '9' )
			return;
		dirtyInput = true;
		clearTimeout( updateTimer );
		updateTimer = setTimeout( input_changed, '1000' );
	});

	// Update on page load
	if ( woocommerce_params.is_checkout == 1 )
		$('body').trigger('update_checkout');

	/* AJAX Coupon Form Submission */
	$('form.checkout_coupon').submit( function() {
		var $form = $(this);

		if ( $form.is('.processing') ) return false;

		$form.addClass('processing').block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', opacity: 0.6}});

		var data = {
			action: 			'woocommerce_apply_coupon',
			security: 			woocommerce_params.apply_coupon_nonce,
			coupon_code:		$form.find('input[name=coupon_code]').val()
		};

		$.ajax({
			type: 		'POST',
			url: 		woocommerce_params.ajax_url,
			data: 		data,
			success: 	function( code ) {
				$('.woocommerce_error, .woocommerce_message').remove();
				$form.removeClass('processing').unblock();

				if ( code ) {
					$form.before( code );
					$form.slideUp();

					$('body').trigger('update_checkout');
				}
			},
			dataType: 	"html"
		});
		return false;
	});

	/* AJAX Form Submission */
	$('form.checkout').submit( function() {
		clearTimeout( updateTimer );

		var $form = $(this);

		if ( $form.is('.processing') )
			return false;

		// Trigger a handler to let gateways manipulate the checkout if needed
		if ( $form.triggerHandler('checkout_place_order') !== false && $form.triggerHandler('checkout_place_order_' + $('#order_review input[name=payment_method]:checked').val() ) !== false ) {

			$form.addClass('processing');

			var form_data = $form.data();

			if ( form_data["blockUI.isBlocked"] != 1 )
				$form.block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', opacity: 0.6}});

			$.ajax({
				type: 		'POST',
				url: 		woocommerce_params.checkout_url,
				data: 		$form.serialize(),
				success: 	function( code ) {
						try {
							// Get the valid JSON only
							var code = code.split("<!--WC_END-->")[0];

							// Parse
							var result = $.parseJSON( code );

							if (result.result=='success') {

								window.location = decodeURI(result.redirect);

							} else if (result.result=='failure') {

								$('.woocommerce_error, .woocommerce_message').remove();
								$form.prepend( result.messages );
								$form.removeClass('processing').unblock();

								if (result.refresh=='true') $('body').trigger('update_checkout');

								$('html, body').animate({
								    scrollTop: ($('form.checkout').offset().top - 100)
								}, 1000);

							} else {
								throw "Invalid response";
							}
						}
						catch(err) {
							$('.woocommerce_error, .woocommerce_message').remove();
						  	$form.prepend( code );
							$form.removeClass('processing').unblock();

							$('html, body').animate({
							    scrollTop: ($('form.checkout').offset().top - 100)
							}, 1000);
						}
					},
				dataType: 	"html"
			});

		}

		return false;
	});

	/* Localisation */
	var locale_json = woocommerce_params.locale.replace(/&quot;/g, '"');
	var locale = $.parseJSON( locale_json );
	var required = ' <abbr class="required" title="' + woocommerce_params.required_text + '">*</abbr>';

	// Handle locale
	$('body').bind('country_to_state_changing', function( event, country, wrapper ){

		var thisform = wrapper;

		if ( locale[country] ) {
			var thislocale = locale[country];
		} else {
			var thislocale = locale['default'];
		}

		// Handle locale fields
		var locale_fields = {
			'address_1'	: 	'#billing_address_1_field, #shipping_address_1_field',
			'address_2'	: 	'#billing_address_2_field, #shipping_address_2_field',
			'state'		: 	'#billing_state_field, #shipping_state_field',
			'postcode'	:	'#billing_postcode_field, #shipping_postcode_field',
			'city'		: 	'#billing_city_field, #shipping_city_field'
		};

		$.each( locale_fields, function( key, value ) {

			var field = thisform.find( value );

			if ( thislocale[key] ) {

				if ( thislocale[key]['label'] ) {
					field.find('label').html( thislocale[key]['label'] );
				}

				if ( thislocale[key]['placeholder'] ) {
					field.find('input').attr( 'placeholder', thislocale[key]['placeholder'] );
				}

				field.find('label abbr').remove();

				if ( typeof thislocale[key]['required'] == 'undefined' || thislocale[key]['required'] == true ) {
					field.find('label').append( required );
				}

				if ( key !== 'state' ) {
					if ( thislocale[key]['hidden'] == true ) {
						field.fadeOut(200).find('input').val('');
					} else {
						field.fadeIn(500);
					}
				}

			} else if ( locale['default'][key] ) {
				if ( locale['default'][key]['required'] == true ) {
					if (field.find('label abbr').size()==0) field.find('label').append( required );
				}
				if ( key !== 'state' && (typeof locale['default'][key]['hidden'] == 'undefined' || locale['default'][key]['hidden'] == false) ) {
					field.fadeIn(500);
				}
			}

		});

		var postcodefield = thisform.find('#billing_postcode_field, #shipping_postcode_field');
		var cityfield = thisform.find('#billing_city_field, #shipping_city_field');

		// Re-order postcode/city
		if ( thislocale['postcode_before_city'] ) {
			if (cityfield.is('.form-row-first')) {
				cityfield.fadeOut(200, function() {
					cityfield.removeClass('form-row-first').addClass('form-row-last').insertAfter( postcodefield ).fadeIn(500);
				});
				postcodefield.fadeOut(200, function (){
					postcodefield.removeClass('form-row-last').addClass('form-row-first').fadeIn(500);
				});
			}
		} else {
			if (cityfield.is('.form-row-last')) {
				cityfield.fadeOut(200, function() {
					cityfield.removeClass('form-row-last').addClass('form-row-first').insertBefore( postcodefield ).fadeIn(500);
				});
				postcodefield.fadeOut(200, function (){
					postcodefield.removeClass('form-row-first').addClass('form-row-last').fadeIn(500);
				});
			}
		}

	});

});