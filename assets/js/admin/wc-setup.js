/*global wc_setup_params */
/*global wc_setup_currencies */
jQuery( function( $ ) {
	function blockWizardUI() {
		$('.wc-setup-content').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
	}

	$( '.button-next' ).on( 'click', function() {
		var form = $( this ).parents( 'form' ).get( 0 );

		if ( ( 'function' !== typeof form.checkValidity ) || form.checkValidity() ) {
			blockWizardUI();
		}

		return true;
	} );

	$( '.wc-wizard-services' ).on( 'change', '.wc-wizard-service-enable input', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.wc-wizard-service-toggle' ).removeClass( 'disabled' );
			$( this ).closest( '.wc-wizard-service-item' ).addClass( 'checked' );
			$( this ).closest( '.wc-wizard-service-item' )
				.find( '.wc-wizard-service-settings' ).removeClass( 'hide' );
		} else {
			$( this ).closest( '.wc-wizard-service-toggle' ).addClass( 'disabled' );
			$( this ).closest( '.wc-wizard-service-item' ).removeClass( 'checked' );
			$( this ).closest( '.wc-wizard-service-item' )
				.find( '.wc-wizard-service-settings' ).addClass( 'hide' );
		}
	} );

	$( '.wc-wizard-services' ).on( 'click', '.wc-wizard-service-enable', function( e ) {
		var eventTarget = $( e.target );

		if ( eventTarget.is( 'input' ) ) {
			e.stopPropagation();
			return;
		}

		var $checkbox = $( this ).find( 'input[type="checkbox"]' );

		$checkbox.prop( 'checked', ! $checkbox.prop( 'checked' ) ).change();
	} );

	$( '.wc-wizard-services-list-toggle' ).on( 'change', '.wc-wizard-service-enable input', function() {
		$( this ).closest( '.wc-wizard-services-list-toggle' ).toggleClass( 'closed' );
		$( this ).closest( '.wc-wizard-services' ).find( '.wc-wizard-service-item' )
			.slideToggle()
			.css( 'display', 'flex' );
	} );

	$( '.wc-wizard-services' ).on( 'change', '.wc-wizard-shipping-method-select .method', function( e ) {
		var zone = $( this ).closest( '.wc-wizard-service-description' );
		var selectedMethod = e.target.value;

		var description = zone.find( '.shipping-method-descriptions' );
		description.find( '.shipping-method-description' ).addClass( 'hide' );
		description.find( '.' + selectedMethod ).removeClass( 'hide' );

		var settings = zone.find( '.shipping-method-settings' );
		settings
			.find( '.shipping-method-setting' )
			.addClass( 'hide' )
			.find( '.shipping-method-required-field' )
			.prop( 'required', false );
		settings
			.find( '.' + selectedMethod )
			.removeClass( 'hide' )
			.find( '.shipping-method-required-field' )
			.prop( 'required', true );
	} );

	$( '.wc-wizard-services' ).on( 'change', '.wc-wizard-shipping-method-enable', function() {
		var checked = $( this ).is( ':checked' );

		$( this )
			.closest( '.wc-wizard-service-item' )
			.find( '.shipping-method-required-field' )
			.prop( 'required', checked );
	} );

	function submitActivateForm() {
		$( 'form.activate-jetpack' ).submit();
	}

	function waitForJetpackInstall() {
		wp.ajax.post( 'setup_wizard_check_jetpack' )
			.then( function( result ) {
				// If we receive success, or an unexpected result
				// let the form submit.
				if (
					! result ||
					! result.is_active ||
					'yes' === result.is_active
				) {
					return submitActivateForm();
				}

				// Wait until checking the status again
				setTimeout( waitForJetpackInstall, 3000 );
			} )
			.fail( function() {
				// Submit the form as normal if the request fails
				submitActivateForm();
			} );
	}

	// Wait for a pending Jetpack install to finish before triggering a "save"
	// on the activate step, which launches the Jetpack connection flow.
	$( '.activate-jetpack' ).on( 'click', '.button-primary', function( e ) {
		blockWizardUI();

		if ( 'no' === wc_setup_params.pending_jetpack_install ) {
			return true;
		}

		e.preventDefault();
		waitForJetpackInstall();
	} );

	$( '.wc-wizard-services' ).on( 'change', 'input#stripe_create_account', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.wc-wizard-service-settings' )
				.find( 'input.payment-email-input' )
				.prop( 'required', true );
			$( this ).closest( '.wc-wizard-service-settings' )
				.find( '.wc-wizard-service-setting-stripe_email' )
				.show();
		} else {
			$( this ).closest( '.wc-wizard-service-settings' )
				.find( 'input.payment-email-input' )
				.prop( 'required', false );
			$( this ).closest( '.wc-wizard-service-settings' )
				.find( '.wc-wizard-service-setting-stripe_email' )
				.hide();
		}
	} );

	$( '.wc-wizard-services input#stripe_create_account' ).change();

	$( 'select#store_country_state' ).on( 'change', function() {
		var countryCode = this.value.split( ':' )[ 0 ];
		$( 'select#currency_code' ).val( wc_setup_currencies[ countryCode ] ).change();
	} );
} );
