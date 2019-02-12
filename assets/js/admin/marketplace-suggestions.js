/* global installed_woo_plugins */
( function( $, woocommerce_admin ) {
	$( function() {
		if ( 'undefined' === typeof woocommerce_admin ) {
			return;
		}

		const marketplaceSuggestionsApiData = [
			{
				slug: 'products-empty-header',
				context: 'products-list-empty-header',
				content: '<h1>Selling something different?</h1><p>You can install extensions to sell all kinds of things!</p>',
			},
			{
				slug: 'products-empty-memberships',
				context: 'products-list-empty-body',
				content: '<div class="card">' +
						'<h2>Memberships</h2>' +
						'<p>Give members access to restricted content or products</p>' +
						'<a class="button" href="https://woocommerce.com/products/woocommerce-memberships/">From $149</a>' +
					'</div>'
			},
			{
				slug: 'products-empty-product-bundles',
				context: 'products-list-empty-body',
				'hide-if-installed': 'woocommerce-product-bundles',
				content: '<div class="card">' +
						'<h2>Product Bundles</h2>' +
						'<p>Offer customizable bundles and assembled products</p>' +
						'<a class="button" href="https://woocommerce.com/products/product-bundles/">From $49</a>' +
					'</div>'
			},
			{
				slug: 'products-empty-composite-products',
				context: 'products-list-empty-body',
				content: '<div class="card">' +
						'<h2>Composite Products</h2>' +
						'<p>Create and offer product kits with configurable components</p>' +
						'<a class="button" href=https://woocommerce.com/products/composite-products/">From $79</a>' +
					'</div>'
			},
			{
				slug: 'products-empty-more',
				context: 'products-list-empty-body',
				content: '<div class="card"><h2>More Extensions</h2></div>'
			},
			{
				slug: 'products-list-enhancements-category',
				context: 'products-list-inline',
				content: '<tr><td></td>' +
					'<td colspan="6"><h2>Looking to optimize your product pages?</h2></td>' +
					'<td colspan="4" style="vertical-align: middle">' +
					'<a class="button" href="https://woocommerce.com/product-category/woocommerce-extensions/product-extensions/">' +
						'Explore enhancements</a></td></tr>'
			},
		];

		function getRelevantPromotions( displayContext ) {
			// select based on display context
			var promos = _.filter( marketplaceSuggestionsApiData, ( promo ) => displayContext === promo.context );

			// hide promos for things the user already has installed
			promos = _.filter( promos, ( promo ) => ! _.contains( installed_woo_plugins, promo['hide-if-installed'] ) );

			return promos;
		}

		// iterate over all suggestions containers, rendering promos
		$( '.marketplace-suggestions-container' ).each( function() {
			// determine the context / placement we're populating
			const context = this.dataset.marketplaceSuggestionsContext;

			// find promotions that target this context
			const promos = getRelevantPromotions( context );

			// render the promo content
			for ( var i in promos ) {
				$( this ).append( promos[ i ].content );
			}
		} );

		// render inline promos in products list
		$( '.wp-admin.admin-bar.edit-php.post-type-product table.wp-list-table.posts tbody').first().each( function() {
			const context = 'products-list-inline';

			// find promotions that target this context
			const promos = getRelevantPromotions( context );

			// render one of the promos
			if ( promos && promos.length ) {
				const content = $( promos[ 0 ].content );

				// where should we put it in the list?
				const rows = $( this ).children();
				const minRow = 3;

				if ( rows.length <= minRow ) {
					// if small number of rows, append at end
					$( this ).append( content );
				}
				else {
					// for more rows, append
					$( rows[ minRow - 1 ] ).after( content );
				}

			}
		} );

	});

})( jQuery, woocommerce_admin );
