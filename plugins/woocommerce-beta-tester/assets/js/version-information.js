/**
 * Handles version information modal.
 *
 * @package
 */

jQuery( function ( $ ) {
	/**
	 * Version information
	 */
	const wc_beta_tester_version_information = {
		/**
		 * Initialize Version Information click
		 */
		init() {
			$( '#wp-admin-bar-show-version-info' ).on(
				'click',
				this.showModal
			);
		},

		/**
		 * Handler for showing/hiding version information modal
		 *
		 * @param  event
		 */
		showModal( event ) {
			event.preventDefault();

			// Prevent multiple modals.
			if (
				$( '.wc-backbone-modal-beta-tester-version-info' ).length > 0
			) {
				return;
			}

			$( this ).WCBackboneModal( {
				template: 'wc-beta-tester-version-info',
				variable: {
					version: wc_beta_tester_version_info_params.version,
					description: wc_beta_tester_version_info_params.description,
				},
			} );
		},
	};

	wc_beta_tester_version_information.init();
} );
