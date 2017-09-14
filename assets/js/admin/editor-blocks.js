( function( wp, i18n, rest ) {
	wp.blocks.registerBlockType( 'woocommerce/product', {
		title : i18n['Product'],
		icon : 'products',
		category : 'common',

		attributes : {
			id : {
				type : 'integer',
				default : null
			},
			s : {
				type : 'string',
				default : ''
			},
			searchResults : {
				type : 'array',
				default : []
			}
		},

		// /wc/v2/products/?search=hoodie
		// /wc/v2/products/123
		edit : function( props ) {
			var searchResults;
			function handleIdChange( event ) {
				props.setAttributes({
					id : event.target.value
				});
			}

			function handleSearch( event ) {
				props.setAttributes({
					s : event.target.value
				});
				if ( ! event.target.value.length ) {
					props.setAttributes({
						searchResults : []
					});
				} else {
					$.ajax({
						url: rest.url + 'wc/v2/products/?search=' + encodeURIComponent(event.target.value),
						method: 'GET',
						beforeSend: function ( xhr ) {
							xhr.setRequestHeader( 'X-WP-Nonce', rest.nonce );
						}
					}).done( function ( response ) {
						if ( ! props.attributes.s ) {
							response = [];
						}
						props.setAttributes({
							searchResults: response
						});
					});
				}
			}

			return wp.element.createElement(
				'div',
				null,
				[
					! props.attributes.id && wp.element.createElement(
						wp.components.Placeholder,
						{
							key : 'woocommerce/product/placeholder',
							label : i18n['Search Products'],
							icon : 'products'
						},
						[
							wp.element.createElement(
								'input',
								{
									key : 'woocommerce/product/id',
									type : 'search',
									onChange : handleSearch,
									value : props.attributes.s,
									placeholder : i18n['Enter search terms...']
								}
							),
							! props.attributes.searchResults.length && !! props.attributes.s && wp.element.createElement(
								'p',
								{ key : 'woocommerce/product/results/no-results' },
								i18n['No products found.']
							),
							!! props.attributes.searchResults.length && !! props.attributes.s.length && wp.element.createElement(
								'pre',
								{ key : 'woocommerce/product/results' },
								JSON.stringify( props.attributes.searchResults, null, 2 )
							)
						]
					),
					!! props.attributes.id && wp.element.createElement(
						'h1',
						{
							key : 'woocommerce/product/view'
						},
						"PRODUCT DETAILS HERE"
					)
				]
			);
		},

		save : function() {
			return null;
		}

	} );
} )( window.wp, window.wcEditorBlocksI18n.strings, window.wcEditorBlocksI18n.rest );