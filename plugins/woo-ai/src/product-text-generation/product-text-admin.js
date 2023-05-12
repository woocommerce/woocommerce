/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

( function ( $, woocommerce_admin ) {
	$( function () {
		if ( typeof woocommerce_admin === 'undefined' ) {
			return;
		}

		function getWaitingButtonText( action ) {
			let buttonText = '';
			switch ( action ) {
				case 'write':
					buttonText = __( 'Writing description…', 'woocommerce' );
					break;
				case 'simplify':
					buttonText = __(
						'Simplifying description…',
						'woocommerce'
					);
					break;
				case 'rewrite':
					buttonText = __( 'Rewriting description…', 'woocommerce' );
					break;
				case 'more':
					buttonText = __(
						'Making the description longer…',
						'woocommerce'
					);
					break;
			}
			return buttonText;
		}

		const generatingContentPhrases = [
			__( 'Please wait…', 'woocommerce' ),
			__( 'Just a little longer…', 'woocommerce' ),
			__( 'Almost there…', 'woocommerce' ),
		];

		let generatingContentPhraseInterval = null;

		function getGeneratingContentPhrase() {
			return generatingContentPhrases[
				Math.floor( Math.random() * generatingContentPhrases.length )
			];
		}

		$( 'button.gpt-action' ).on( 'click', function () {
			const button = $( this );
			const buttonText = button.text();
			const chatgptAction = button.attr( 'action' );
			let existingDescription = '';

			button.attr( 'disabled', true );
			button.text( getWaitingButtonText( chatgptAction ) );

			const contentTinyMCE =
				typeof tinymce === 'object' ? tinymce.get( 'content' ) : null;

			if ( contentTinyMCE ) {
				existingDescription = contentTinyMCE
					.getContent()
					.replace( /(<([^>]+)>)/gi, '' );

				contentTinyMCE.readonly = true;

				$( '#wp-content-editor-container' ).css( {
					opacity: '0.5',
					'pointer-events': 'none',
				} );

				console.debug(
					'getGeneratingContentPhrase() ',
					getGeneratingContentPhrase()
				);

				contentTinyMCE.setContent( getGeneratingContentPhrase() );
				generatingContentPhraseInterval = setInterval( function () {
					contentTinyMCE.setContent( getGeneratingContentPhrase() );
				}, 2000 );
			}

			$.ajax( {
				url: woocommerce_admin_meta_boxes.ajax_url,
				type: 'POST',
				data: {
					action: 'woocommerce_generate_product_description',
					chatgpt_action: chatgptAction,
					existing_description: existingDescription,
					product_description: $(
						'#woocommerce-product-description-gpt-about'
					).val(),
					tone: $(
						'#woocommerce-product-description-gpt-voice-tone'
					).val(),
				},
				success: ( response ) => {
					$(
						'#woocommerce-product-description-gpt-action-accept'
					).hide();
					$( '.woocommerce-gpt-extra-actions-wrapper' ).show();
					if ( contentTinyMCE ) {
						clearInterval( generatingContentPhraseInterval );
						contentTinyMCE.setContent( response );
					} else {
						$( '#wp-content-editor-container .wp-editor-area' ).val(
							response
						);
					}
				},
				error: ( err ) => {
					if ( contentTinyMCE ) {
						clearInterval( generatingContentPhraseInterval );
						contentTinyMCE.setContent( err );
					}
					console.log( err );
				},
				complete: () => {
					button.attr( 'disabled', false );
					button.text( buttonText );

					if ( contentTinyMCE ) {
						contentTinyMCE.readonly = false;

						$( '#wp-content-editor-container' ).css( {
							opacity: '1',
							'pointer-events': 'auto',
						} );
					}
				},
			} );
		} );
	} );
} )( jQuery, woocommerce_admin );
