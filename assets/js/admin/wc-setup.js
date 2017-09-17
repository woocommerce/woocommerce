/*global wc_setup_params */
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
		} else {
			$( this ).closest( '.wc-wizard-service-toggle' ).addClass( 'disabled' );
			$( this ).closest( '.wc-wizard-service-item' ).removeClass( 'checked' );
		}
	} );

	$( '.wc-wizard-services' ).on( 'click', '.wc-wizard-service-enable', function( e ) {
		e.stopPropagation();

		var $checkbox = $( this ).find( '.wc-wizard-service-toggle input' );
		$checkbox.prop( 'checked', ! $checkbox.prop( 'checked' ) ).change();
	} );

	$( '.wc-wizard-services-list-toggle' ).on( 'change', '.wc-wizard-service-enable input', function() {
			$( this ).closest( '.wc-wizard-services' ).find( '.wc-wizard-service-item' )
				.slideToggle()
				.css( 'display', 'flex' );
	} );

	$( '.wc-wizard-services' ).on( 'change', '.wc-wizard-shipping-method-select .method', function( e ) {
		var $zone = $( this ).parent( 'div' );
		var selectedMethod = e.target.value;

		var $descriptions = $zone.find( '.shipping-method-description' );
		$descriptions.find( 'p' ).hide();
		$descriptions.find( 'p.' + selectedMethod ).show();

		var $settings = $zone.find( '.shipping-method-settings' );
		$settings.find( 'div' ).hide();
		$settings.find( 'div.' + selectedMethod ).show();
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
	$( '.button-jetpack-connect' ).on( 'click', function( e ) {
		blockWizardUI();

		if ( 'no' === wc_setup_params.pending_jetpack_install ) {
			return true;
		}

		e.preventDefault();
		waitForJetpackInstall();
	} );
} );
