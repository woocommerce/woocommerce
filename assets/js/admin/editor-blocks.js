( function( $, wp, i18n, rest, misc ) {
	var products = {};
	if ( misc.show_all ) {
		for ( var i = 0; i < misc.show_all.length; i++ ) {
			products[ misc.show_all[ i ].id ] = misc.show_all[ i ];
		}
	}
	// The API returns a lot of fields we don't care about.  Simplify.
	function ditch_unused_properties( product ) {
		return {
			id     : product.id,
			name   : product.name,
			price  : product.price,
			images : product.images
		};
	}

	class Product extends wp.element.Component {
		render() {
			var el = wp.element.createElement,
				product = this.props.product;

			if ( 'undefined' === typeof( product ) ) {
				$.ajax({
					url: rest.url + 'wc/v2/products/' + encodeURIComponent( this.props.id ),
					method: 'GET',
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', rest.nonce );
					}
				}).done( function ( response ) {
					response = ditch_unused_properties( response );
					products[ response.id ] = response;
					props.setAttributes({
						product : response
					});
				});

				return el(
					wp.components.Placeholder,
					{
						label : i18n['Loading…'],
						icon : 'products'
					}
				);
			}
			return el(
				'div',
				{
					key : 'woocommerce/product/' + product.id,
					className : 'woocommerce-product',
					'data-id' : product.id,
					onClick : this.props.onClick
				},
				[
					!! product.images && el(
						'img',
						{
							key : 'woocommerce/product/' + product.id + '/image',
							src : product.images[0].src
						}
					),
					el(
						'h3',
						{
							key : 'woocommerce/product/' + product.id + '/title'
						},
						product.name
					),
					el(
						'span',
						{
							key : 'woocommerce/product/' + product.id + '/price',
							className : 'price'
						},
						misc.currency_symbol + product.price
					),
					!! this.props.buttonAction && el(
						'button',
						{
							key : 'woocommerce/product/' + product.id + '/button',
							onClick : this.props.buttonAction,
							className : 'button button-primary'
						},
						this.props.buttonLabel ? this.props.buttonLabel : '→'
					)
				]
			);
		}
	}

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

		edit : function( props ) {
			function handleIdChange( event ) {
				props.setAttributes({
					id : $( event.target ).closest( '[data-id]' ).data('id'),
					s : undefined,
					searchResults : undefined
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
						response = response.map( ditch_unused_properties );
						for ( var i = 0; i < response.length; i++ ) {
							products[ response[ i ].id ] = response[ i ];
						}
						if ( ! props.attributes.s ) {
							response = [];
						}
						props.setAttributes({
							searchResults : response
						});
					});
				}
			}

			return wp.element.createElement(
				'div',
				null,
				[
					!! misc.show_all && ! props.attributes.id && wp.element.createElement(
						wp.components.Placeholder,
						{
							key : 'woocommerce/product/placeholder',
							label : i18n['Select a Product'],
							icon : 'products'
						},
						wp.element.createElement(
							'div',
							{
								key : 'woocommerce/product/results',
								className : 'woocommerce-product-search-results'
							},
							misc.show_all.map( function( p ) {
								return wp.element.createElement(
									Product,
									{
										key : 'woocommerce/product/' + p.id,
										product : p,
										className : 'woocommerce-product',
										onClick : handleIdChange
									}
								)
							})
						)
					),
					! misc.show_all && ! props.attributes.id && wp.element.createElement(
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
									placeholder : i18n['Search products…']
								}
							),
							! props.attributes.searchResults.length && !! props.attributes.s && wp.element.createElement(
								'p',
								{ key : 'woocommerce/product/results/no-results' },
								i18n['No products found.']
							),
							!! props.attributes.searchResults.length && !! props.attributes.s.length && wp.element.createElement(
								'div',
								{
									key : 'woocommerce/product/results',
									className : 'woocommerce-product-search-results'
								},
								props.attributes.searchResults.map( function( p ) {
									return wp.element.createElement(
										Product,
										{
											key : 'woocommerce/product/' + p.id,
											product : p,
											className : 'woocommerce-product',
											onClick : handleIdChange
										}
									)
								})
							)
						]
					),
					!! props.attributes.id && wp.element.createElement(
						Product,
						{
							id : props.attributes.id,
							key : 'woocommerce/product/view',
							product : products[ props.attributes.id ],
							buttonLabel : i18n['Buy Now'],
							buttonAction : function(e){
								e.preventDefault();
							}
						}
					)
				]
			);
		},

		save : function() {
			return null;
		}

	} );
} )( jQuery, window.wp, window.wcEditorBlocksI18n.strings, window.wcEditorBlocksI18n.rest, window.wcEditorBlocksI18n );