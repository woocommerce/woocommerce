/* global installed_woo_plugins */
( function( $, installed_woo_plugins ) {
	$( function() {
		if ( 'undefined' === typeof installed_woo_plugins ) {
			return;
		}

		var marketplaceSuggestionsApiData = [
			{
				slug: 'products-empty-header',
				context: 'products-list-empty-header',
				title: 'Ready to start selling something awesome?',
				copy: 'Create your first product, import your product data, or browse extensions'
			},
			{
				slug: 'products-empty-memberships',
				context: 'products-list-empty-body',
				title: 'Selling something else?',
				copy: 'Extensions allow you to sell other types of products including bookings, subscriptions, or memberships',
				'button-text': 'Browse extensions',
				url: 'https://woocommerce.com/product-category/woocommerce-extensions/product-extensions/',
			},
			// {
			// 	slug: 'products-empty-addons',
			// 	context: 'products-list-empty-body',
			// 	'show-if-installed': [
			// 		'woocommerce-subscriptions',
			// 		'woocommerce-memberships'
			// 	],
			// 	content: '<div class="marketplace-card">' +
			// 			'<h2>Product Add-Ons</h2>' +
			// 			'<p>Offer add-ons like gift wrapping, special messages or other special options for your products.</p>' +
			// 			'<a class="button" href="https://woocommerce.com/products/product-add-ons/">From $149</a>' +
			// 		'</div>'
			// },
			// {
			// 	slug: 'products-empty-product-bundles',
			// 	context: 'products-list-empty-body',
			// 	'hide-if-installed': 'woocommerce-product-bundles',
			// 	content: '<div class="marketplace-card">' +
			// 			'<h2>Product Bundles</h2>' +
			// 			'<p>Offer customizable bundles and assembled products</p>' +
			// 			'<a class="button" href="https://woocommerce.com/products/product-bundles/">From $49</a>' +
			// 		'</div>'
			// },
			// {
			// 	slug: 'products-empty-composite-products',
			// 	context: 'products-list-empty-body',
			// 	content: '<div class="marketplace-card">' +
			// 			'<h2>Composite Products</h2>' +
			// 			'<p>Create and offer product kits with configurable components</p>' +
			// 			'<a class="button" href=https://woocommerce.com/products/composite-products/">From $79</a>' +
			// 		'</div>'
			// },
			// {
			// 	slug: 'products-empty-more',
			// 	context: 'products-list-empty-body',
			// 	content: '<div class="marketplace-card"><h2>More Extensions</h2></div>'
			// },
			{
				slug: 'products-list-enhancements-category',
				context: 'products-list-inline',
				title: 'Looking to optimize your product pages?',
				'button-text': 'Explore enhancements',
				url: 'https://woocommerce.com/product-category/woocommerce-extensions/product-extensions/',
			}
		];

		function renderLinkoutButton( url, buttonText ) {
			var linkoutButton = document.createElement( 'a' );

			linkoutButton.classList.add( 'button' );
			linkoutButton.setAttribute( 'href', url );
			linkoutButton.textContent = buttonText;

			return linkoutButton;
		}

		function renderTableBanner( title, url, buttonText ) {
			if ( ! title || ! url ) {
				return;
			}

			if ( ! buttonText ) {
				buttonText = 'Go';
			}

			var row = document.createElement( 'tr' );
			row.classList.add( 'marketplace-list-banner' );

			var titleColumn = document.createElement( 'td' );
			titleColumn.setAttribute( 'colspan', 5 );
			titleColumn.classList.add( 'marketplace-list-title' );
			var titleHeading = document.createElement( 'h2' );
			titleColumn.append( titleHeading );
			titleHeading.textContent = title;

			row.appendChild( titleColumn );

			var linkoutColumn = document.createElement( 'td' );
			linkoutColumn.setAttribute( 'colspan', 4 );
			linkoutColumn.classList.add( 'marketplace-list-linkout' );
			var linkoutButton = renderLinkoutButton( url, buttonText );
			linkoutColumn.append( linkoutButton );

			row.appendChild( linkoutColumn );

			return row;
		}

		function renderListItem( title, copy, url, buttonText ) {
			if ( ! title || ! url ) {
				return;
			}

			if ( ! buttonText ) {
				buttonText = 'Go';
			}

			var container = document.createElement( 'div' );
			container.classList.add( 'marketplace-list-container' );

			var titleHeading = document.createElement( 'h4' );
			titleHeading.textContent = title;
			container.appendChild( titleHeading );

			var body = document.createElement( 'p' );
			body.textContent = copy;
			container.appendChild( body );

			var linkoutButton = renderLinkoutButton( url, buttonText );
			container.appendChild( linkoutButton );

			return container;
		}

		var visibleSuggestions = [];

		function getRelevantPromotions( displayContext ) {
			// select based on display context
			var promos = _.filter( marketplaceSuggestionsApiData, function( promo ) {
				return ( displayContext === promo.context );
			} );

			// hide promos for things the user already has installed
			promos = _.filter( promos, function( promo ) {
				return ! _.contains( installed_woo_plugins, promo['hide-if-installed'] );
			} );

			// hide promos that are not applicable based on user's installed extensions
			promos = _.filter( promos, function( promo ) {
				if ( ! promo['show-if-installed'] ) {
					// this promotion is relevant to all
					return true;
				}

				// if the user has any of the prerequisites, show the promo
				return ( _.intersection( installed_woo_plugins, promo['show-if-installed'] ).length > 0 );
			} );

			return promos;
		}

		// iterate over all suggestions containers, rendering promos
		$( '.marketplace-suggestions-container' ).each( function() {
			// determine the context / placement we're populating
			var context = this.dataset.marketplaceSuggestionsContext;

			// find promotions that target this context
			var promos = getRelevantPromotions( context );

			// render the promo content
			for ( var i in promos ) {
				var content = renderListItem(
					promos[ i ].title,
					promos[ i ].copy,
					promos[ i ].url,
					promos[ i ]['button-text']
				);
				$( this ).append( content );
				visibleSuggestions.push( promos[i].context );
			}
		} );

		// render inline promos in products list
		$( '.wp-admin.admin-bar.edit-php.post-type-product table.wp-list-table.posts tbody').first().each( function() {
			var context = 'products-list-inline';

			// find promotions that target this context
			var promos = getRelevantPromotions( context );
			if ( ! promos || ! promos.length ) {
				return;
			}

			// render first promo
			var content = renderTableBanner(
				promos[ 0 ].title,
				promos[ 0 ].url,
				promos[ 0 ]['button-text']
			);

			if ( content ) {
				// where should we put it in the list?
				var rows = $( this ).children();
				var minRow = 3;

				if ( rows.length <= minRow ) {
					// if small number of rows, append at end
					$( this ).append( content );
				}
				else {
					// for more rows, append
					$( rows[ minRow - 1 ] ).after( content );
				}

				visibleSuggestions.push( context );
			}
		} );

		// streamline layout if we're showing empty product list promos
		if ( _.contains( visibleSuggestions, 'products-list-empty-body' ) ) {
			$('#wpfooter').hide();
		}

	});

})( jQuery, installed_woo_plugins );
