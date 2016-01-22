/* global wp, pwsL10n */
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
				.on( 'keyup', 'form.register #reg_password, form.checkout #account_password, form.edit-account #password_1, form.lost_reset_password #password_1', this.strengthMeter );

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

			// Stop form if password is weak... But not in checkout form!
			if ( 3 === strength || 4 === strength ) {
				submit.removeAttr( 'disabled' );
			} else if ( ! wrapper.hasClass( 'checkout' ) ) {
				submit.attr( 'disabled', 'disabled' );
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
			var meter = $( '.woocommerce-password-strength' );
			var strength = wp.passwordStrength.meter( field.val(), wp.passwordStrength.userInputBlacklist() );

			// Reset classes
			meter.removeClass( 'short bad good strong' );

			switch ( strength ) {
				case 2 :
					meter.addClass( 'bad' ).html( pwsL10n.bad );
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
			}

			return strength;
		}
	};

	wc_password_strength_meter.init();
});
