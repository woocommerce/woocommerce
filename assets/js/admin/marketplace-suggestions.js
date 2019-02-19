/* global marketplace_suggestions ajaxurl */
( function( $, marketplace_suggestions ) {
	$( function() {
		if ( 'undefined' === typeof marketplace_suggestions ) {
			return;
		}

		function dismissSuggestion( suggestionSlug ) {
			// hide the suggestion in the UI
			var selector = '[data-suggestion-slug=' + suggestionSlug + ']';
			$( selector ).fadeOut();

			// save dismissal in user settings
			jQuery.post(
				ajaxurl,
				{
					'action': 'add_dismissed_marketplace_suggestion',
					'_wpnonce': marketplace_suggestions.dismiss_suggestion_nonce,
					'slug': suggestionSlug,
				}
			);
		}

		function renderDismissButton( suggestionSlug ) {
			var dismissButton = document.createElement( 'a' );

			dismissButton.classList.add( 'suggestion-dismiss' );
			dismissButton.setAttribute( 'href', '#' );
			dismissButton.onclick = function( event ) {
				event.preventDefault();
				dismissSuggestion( suggestionSlug );
			}

			return dismissButton;
		}

		function renderLinkout( url, text, isButton ) {
			var linkoutButton = document.createElement( 'a' );

			linkoutButton.setAttribute( 'href', url );
			linkoutButton.setAttribute( 'target', 'blank' );
			linkoutButton.textContent = text;

			if (isButton) {
				linkoutButton.classList.add( 'button' );
			}
			else {
				linkoutButton.classList.add( 'linkout' );
				var linkoutIcon = document.createElement( 'span' );
				linkoutIcon.classList.add( 'dashicons', 'dashicons-external' );
				linkoutButton.appendChild( linkoutIcon );
			}

			return linkoutButton;
		}

		function renderTableBanner( slug, title, url, buttonText, allowDismiss ) {
			if ( ! title || ! url ) {
				return;
			}

			if ( ! buttonText ) {
				buttonText = 'Go';
			}

			var row = document.createElement( 'tr' );
			row.classList.add( 'marketplace-suggestions-container' );
			row.classList.add( 'marketplace-table-banner' );
			row.dataset.suggestionSlug = slug;

			var titleColumn = document.createElement( 'td' );
			titleColumn.setAttribute( 'colspan', 5 );
			titleColumn.classList.add( 'marketplace-table-title' );
			var titleHeading = document.createElement( 'h2' );
			titleColumn.appendChild( titleHeading );
			titleHeading.textContent = title;

			row.appendChild( titleColumn );

			var linkoutColumn = document.createElement( 'td' );
			linkoutColumn.setAttribute( 'colspan', 5 );
			linkoutColumn.classList.add( 'marketplace-table-linkout' );
			var linkoutButton = renderLinkout( url, buttonText );
			linkoutColumn.appendChild( linkoutButton );
			if ( allowDismiss ) {
				linkoutColumn.appendChild( renderDismissButton( slug ) )
			}

			row.appendChild( linkoutColumn );

			return row;
		}

		function renderListItem( slug, title, url, linkText, linkIsButton, copy, allowDismiss ) {
			var container = document.createElement( 'div' );
			container.classList.add( 'marketplace-listitem-container' );
			container.dataset.suggestionSlug = slug;

			var left = document.createElement( 'div' );
			left.classList.add( 'marketplace-listitem-container-content' );
			var right = document.createElement( 'div' );
			right.classList.add( 'marketplace-listitem-container-cta' );

			if ( title ) {
				var titleHeading = document.createElement( 'h4' );
				titleHeading.textContent = title;
				left.appendChild( titleHeading );
			}

			if ( copy ) {
				var body = document.createElement( 'p' );
				body.textContent = copy;
				left.appendChild( body );
			}

			if ( url && linkText ) {
				var linkoutElement = renderLinkout( url, linkText, linkIsButton );
				right.appendChild( linkoutElement );
			}

			if ( allowDismiss ) {
				right.appendChild( renderDismissButton( slug ) )
			}

			container.appendChild( left );
			container.appendChild( right );

			return container;
		}

		function getRelevantPromotions( marketplaceSuggestionsApiData, displayContext ) {
			// select based on display context
			var promos = _.filter( marketplaceSuggestionsApiData, function( promo ) {
				return ( displayContext === promo.context );
			} );

			// hide promos the user has dismissed
			promos = _.filter( promos, function( promo ) {
				return ! _.contains( marketplace_suggestions.dismissed_suggestions, promo.slug );
			} );


			// hide promos for things the user already has installed
			promos = _.filter( promos, function( promo ) {
				return ! _.contains( marketplace_suggestions.installed_woo_plugins, promo['hide-if-installed'] );
			} );

			// hide promos that are not applicable based on user's installed extensions
			promos = _.filter( promos, function( promo ) {
				if ( ! promo['show-if-installed'] ) {
					// this promotion is relevant to all
					return true;
				}

				// if the user has any of the prerequisites, show the promo
				return ( _.intersection( marketplace_suggestions.installed_woo_plugins, promo['show-if-installed'] ).length > 0 );
			} );

			return promos;
		}

		function hidePageElementsForEmptyState( visibleSuggestions ) {
			var showingProductsEmptyStateSuggestions = _.contains( visibleSuggestions, 'products-list-empty-body' );
			if ( showingProductsEmptyStateSuggestions ) {
				$( '#screen-meta-links' ).hide();
				$( '#wpfooter' ).hide();
			}
			if ( ! showingProductsEmptyStateSuggestions ) {
				// hide the header & footer, they don't make sense without specific promotion content
				$( '.marketplace-suggestions-container[data-marketplace-suggestions-context="products-list-empty-header"]' ).hide();
				$( '.marketplace-suggestions-container[data-marketplace-suggestions-context="products-list-empty-footer"]' ).hide();
			}
		}

		function displaySuggestions( marketplaceSuggestionsApiData ) {
			var visibleSuggestions = [];

			// iterate over all suggestions containers, rendering promos
			$( '.marketplace-suggestions-container' ).each( function() {
				// determine the context / placement we're populating
				var context = this.dataset.marketplaceSuggestionsContext;

				// find promotions that target this context
				var promos = getRelevantPromotions( marketplaceSuggestionsApiData, context );

				// render the promo content
				for ( var i in promos ) {
					var linkText = promos[ i ]['link-text'];
					var linkoutIsButton = false;
					if ( promos[ i ]['button-text'] ) {
						linkText = promos[ i ]['button-text'];
						linkoutIsButton = true;
					}

					// dismiss is allowed by default
					var allowDismiss = true;
					if ( promos[ 0 ]['allow-dismiss'] === false ) {
						allowDismiss = false;
					}

					var content = renderListItem(
						promos[ i ].slug,
						promos[ i ].title,
						promos[ i ].url,
						linkText,
						linkoutIsButton,
						promos[ i ].copy,
						allowDismiss
					);
					$( this ).append( content );
					$( this ).addClass( 'showing-suggestion' );
					visibleSuggestions.push( promos[i].context );
				}
			} );

			// render inline promos in products list
			$( '.wp-admin.admin-bar.edit-php.post-type-product table.wp-list-table.posts tbody').first().each( function() {
				var context = 'products-list-inline';

				// find promotions that target this context
				var promos = getRelevantPromotions( marketplaceSuggestionsApiData, context );
				if ( ! promos || ! promos.length ) {
					return;
				}

				// dismiss is allowed by default
				var allowDismiss = true;
				if ( promos[ 0 ]['allow-dismiss'] === false ) {
					allowDismiss = false;
				}

				// render first promo
				var content = renderTableBanner(
					promos[ 0 ].slug,
					promos[ 0 ].title,
					promos[ 0 ].url,
					promos[ 0 ]['button-text'],
					allowDismiss
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

			hidePageElementsForEmptyState( visibleSuggestions );
		}

		var data =
		jQuery.getJSON(
			ajaxurl,
			{
				'action': 'marketplace_suggestions',
			},
			displaySuggestions
		);

	});

})( jQuery, marketplace_suggestions, ajaxurl );
