/*global woocommerce_admin_meta_boxes */
jQuery( function ( $ ) {
	// Store the original position of .wp-editor-tabs
	const wpEditorTabs = $( '.wp-editor-tabs' ).first();
	const originalPrevElement = wpEditorTabs.prev();

	// Hook up "write it for me" button, and the hide button, to toggle the GPT form
	$( '.wc-write-it-for-me, #woocommerce-product-description-gpt-hide' ).on(
		'click',
		function () {
			const gptForm = $( '.woocommerce-gpt-integration' );

			if ( gptForm.is( ':visible' ) ) {
				// Move .wp-editor-tabs back to its original position
				originalPrevElement.after( wpEditorTabs );
				gptForm.slideUp( 'fast' );
			} else {
				// Move .wp-editor-tabs after the GPT form
				$( '.wp-editor-tabs:first' ).insertAfter(
					'.woocommerce-gpt-integration'
				);
				gptForm.slideDown( {
					duration: 'fast',
					start: function () {
						$( this ).css( 'display', 'grid' );
					},
				} );
			}
		}
	);

	// Add a descriptive tooltip to the "write it for me" about field
	$( '#woocommerce-product-description-gpt-about-wrapper' )
		.append( '<span class="woocommerce-help-tip" tabindex="-1"></span>' )
		.find( '.woocommerce-help-tip' )
		.attr( 'for', 'content' )
		.attr(
			'aria-label',
			woocommerce_admin_meta_boxes.i18n_product_description_gpt_about_tip
		)
		.tipTip( {
			attribute: 'data-tip',
			content:
				woocommerce_admin_meta_boxes.i18n_product_description_gpt_about_tip,
			fadeIn: 50,
			fadeOut: 50,
			delay: 200,
			keepAlive: true,
		} );

	const gptVoiceToneDescriptions = {
		casual: woocommerce_admin_meta_boxes.i18n_product_gpt_desc_casual,
		formal: woocommerce_admin_meta_boxes.i18n_product_gpt_desc_formal,
		flowery: woocommerce_admin_meta_boxes.i18n_product_gpt_desc_flowery,
		convincing:
			woocommerce_admin_meta_boxes.i18n_product_gpt_desc_convincing,
	};

	// Update the voice tone description when the voice tone is changed
	$( '#woocommerce-product-description-gpt-voice-tone' ).on(
		'change',
		function () {
			const value = $( this ).val();
			const description = gptVoiceToneDescriptions[ value ];
			$(
				'#woocommerce-product-description-gpt-voice-tone-description'
			).text( description );
		}
	);
} );
