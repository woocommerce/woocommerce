/* global wp, pwsL10n, wc_password_strength_meter_params */
jQuery( function( $ ) {

	/**
	 * Password Strength Meter class.
	 */
	var wc_password_strength_meter = {

		/**
		 * Initialize strength meter actions.
		 */
		init: function() {
			$( document.body )
				.on( 'keyup', 'form.register #reg_password, form.checkout #account_password, form.edit-account #password_1, form.lost_reset_password #password_1', this.strengthMeter )
				.on( 'submit', 'form.register, form.edit-account, form.lost_reset_password', this.onSubmit );
			$( 'form.checkout #createaccount' ).change();
		},

		/**
		 * Strength Meter.
		 */
		strengthMeter: function() {
			var wrapper  = $( 'form.register, form.checkout, form.edit-account, form.lost_reset_password' ),
				submit   = $( 'input[type="submit"]', wrapper ),
				field    = $( '#reg_password, #account_password, #password_1', wrapper ),
				strength = 1;

			wc_password_strength_meter.includeMeter( wrapper, field );

			strength = wc_password_strength_meter.checkPasswordStrength( field );

			// Add class to wrapper
			if ( 3 === strength || 4 === strength ) {
				wrapper.removeClass( 'has-weak-password' );
			} else {
				wrapper.addClass( 'has-weak-password' );
			}

			// Stop form if password is weak... But not in checkout form!
			if ( 3 === strength || 4 === strength ) {
				submit.removeClass( 'disabled' );
			} else if ( ! wrapper.hasClass( 'checkout' ) ) {
				submit.addClass( 'disabled' );
			}
		},

		onSubmit: function() {
			$( '.woocommerce-password-error' ).remove();

			if ( $( this ).is( '.has-weak-password' ) ) {
				$( this ).prepend( '<div class="woocommerce-error woocommerce-password-error">' + wc_password_strength_meter_params.i18n_password_error + '</div>' );
				return false;
			} else {
				return true;
			}
		},

		/**
		 * Include meter HTML.
		 *
		 * @param {Object} wrapper
		 * @param {Object} field
		 */
		includeMeter: function( wrapper, field ) {
			var meter = wrapper.find( '.woocommerce-password-strength' );

			if ( 0 === meter.length ) {
				field.after( '<div class="woocommerce-password-strength" aria-live="polite"></div>' );
			} else if ( '' === field.val() ) {
				meter.remove();
			}
		},

		/**
		 * Check password strength.
		 *
		 * @param {Object} field
		 *
		 * @return {Int}
		 */
		checkPasswordStrength: function( field ) {
			var meter     = $( '.woocommerce-password-strength' );
			var hint      = $( '.woocommerce-password-hint' );
			var hint_html = '<small class="woocommerce-password-hint">' + wc_password_strength_meter_params.i18n_password_hint + '</small>';
			var strength  = wp.passwordStrength.meter( field.val(), wp.passwordStrength.userInputBlacklist() );

			// Reset
			meter.removeClass( 'short bad good strong' );
			hint.remove();

			switch ( strength ) {
				case 2 :
					meter.addClass( 'bad' ).html( pwsL10n.bad );
					meter.after( hint_html );
					break;
				case 3 :
					meter.addClass( 'good' ).html( pwsL10n.good );
					break;
				case 4 :
					meter.addClass( 'strong' ).html( pwsL10n.strong );
					break;
				case 5 :
					meter.addClass( 'short' ).html( pwsL10n.mismatch );
					break;
				default :
					meter.addClass( 'short' ).html( pwsL10n['short'] );
					meter.after( hint_html );
			}

			return strength;
		}
	};

	wc_password_strength_meter.init();
});
