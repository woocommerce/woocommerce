jQuery( function( $ ) {

	$('body')

		.on( 'wc-enhanced-select-init', function() {

			// Regular select boxes
			$(".wc-enhanced-select, select.chosen_select").each(function() {
				$( this ).select2({
					minimumResultsForSearch: $( this ).data( 'minimum_results_for_search' ) ? $( this ).data( 'minimum_results_for_search' ) : '5',
					allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
					placeholder: $( this ).data( 'placeholder' )
				});
			});

			// Ajax product search box
			$(".wc-product-search").each(function() {
				$( this ).select2({
					multiple: true,
					placeholder: $( this ).data( 'placeholder' ),
					ajax: {
				        url:         wc_enhanced_select_params.ajax_url,
				        dataType:    'json',
				        quietMillis: 250,
				        data: function( term, page ) {
				            return {
								term:     term,
								action:   $(this).data( 'action' ) || 'woocommerce_json_search_products_and_variations',
								security: wc_enhanced_select_params.search_products_nonce
				            };
				        },
				        results: function( data, page ) {
				        	var terms = [];
					        if ( data ) {
								$.each( data, function( id, text ) {
									terms.push( { id: id, text: text } );
								});
							}
				            return { results: terms };
				        },
				        cache: true
				    },
				    initSelection: function( element, callback ) {
						var data     = $.parseJSON( element.attr( 'data-selected' ) );
						var selected = [];

						$( element.val().split( "," ) ).each( function( i, val ) {
							selected.push( { id: val, text: data[ val ] } );
						});
						return callback( selected );
					},
					formatSelection: function( data ) {
						return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
					},
				    escapeMarkup: function(m) { return m; }
				});
			});

		} )

		.trigger( 'wc-enhanced-select-init' );

});