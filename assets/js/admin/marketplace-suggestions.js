/* global marketplace_suggestions ajaxurl */
( function( $, marketplace_suggestions ) {
	$( function() {
		if ( 'undefined' === typeof marketplace_suggestions ) {
			return;
		}

		// Stand-in wcTracks.recordEvent in case tracks is not available (for any reason).
		window.wcTracks = window.wcTracks || {};
		window.wcTracks.recordEvent = window.wcTracks.recordEvent  || function() { };

		// Tracks events sent in this file:
		// - marketplace_suggestion_displayed
		// - marketplace_suggestion_clicked
		// - marketplace_suggestion_dismissed
		// All are prefixed by {WC_Tracks::PREFIX}.
		// All have one property for `suggestionSlug`, to identify the specific suggestion message.

		// Dismiss the specified suggestion from the UI, and save the dismissal in settings.
		function dismissSuggestion( suggestionSlug ) {
			// hide the suggestion in the UI
			var selector = '[data-suggestion-slug=' + suggestionSlug + ']';
			$( selector ).fadeOut( function() {
				$( this ).remove();
			} );

			// save dismissal in user settings
			jQuery.post(
				ajaxurl,
				{
					'action': 'woocommerce_add_dismissed_marketplace_suggestion',
					'_wpnonce': marketplace_suggestions.dismiss_suggestion_nonce,
					'slug': suggestionSlug,
				}
			);

			window.wcTracks.recordEvent( 'marketplace_suggestion_dismissed', {
				suggestionSlug: suggestionSlug
			} );
		}

		// Render DOM element for suggestion dismiss button.
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

		function addUTMParameters( context, url ) {
			var utmParams = {
				utm_source: 'unknown',
				utm_campaign: 'marketplacesuggestions',
				utm_medium: 'product'
			};
			var sourceContextMap = {
				'productstable': [
					'products-list-inline'
				],
				'productsempty': [
					'products-list-empty-header',
					'products-list-empty-footer',
					'products-list-empty-body'
				],
				'ordersempty': [
					'orders-list-empty-header',
					'orders-list-empty-footer',
					'orders-list-empty-body'
				],
				'editproduct': [
					'product-edit-meta-tab-header',
					'product-edit-meta-tab-footer',
					'product-edit-meta-tab-body'
				]
			};
			var utmSource = _.findKey( sourceContextMap, function( sourceInfo ) {
				return _.contains( sourceInfo, context );
			} );
			if ( utmSource ) {
				utmParams.utm_source = utmSource;
			}

			return url + '?' + jQuery.param( utmParams );
		}

		// Render DOM element for suggestion linkout, optionally with button style.
		function renderLinkout( context, slug, url, text, isButton ) {
			var linkoutButton = document.createElement( 'a' );

			var utmUrl = addUTMParameters( context, url );
			linkoutButton.setAttribute( 'href', utmUrl );
			linkoutButton.setAttribute( 'target', 'blank' );
			linkoutButton.textContent = text;

			linkoutButton.onclick = function( event ) {
				window.wcTracks.recordEvent( 'marketplace_suggestion_clicked', {
					suggestionSlug: slug
				} );
			}

			if ( isButton ) {
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

		// Render DOM element for suggestion icon image.
		function renderSuggestionIcon( iconUrl ) {
			if ( ! iconUrl ) {
				return null;
			}

			var image = document.createElement( 'img' );
			image.src = iconUrl;
			image.classList.add( 'marketplace-suggestion-icon' );

			return image;
		}

		// Render DOM elements for suggestion content.
		function renderSuggestionContent( title, copy ) {
			var left = document.createElement( 'div' );

			left.classList.add( 'marketplace-suggestion-container-content' );

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

			return left;
		}

		// Render DOM elements for suggestion call-to-action – button or link with dismiss 'x'.
		function renderSuggestionCTA( context, slug, url, linkText, linkIsButton, allowDismiss ) {
			var right = document.createElement( 'div' );

			right.classList.add( 'marketplace-suggestion-container-cta' );
			if ( url && linkText ) {
				var linkoutElement = renderLinkout( context, slug, url, linkText, linkIsButton );
				right.appendChild( linkoutElement );
			}

			if ( allowDismiss ) {
				right.appendChild( renderDismissButton( slug ) )
			}

			return right;
		}

		// Render a "table banner" style suggestion.
		// These are used in admin lists, e.g. products list.
		function renderTableBanner( context, slug, iconUrl, title, copy, url, buttonText, allowDismiss ) {
			if ( ! title || ! url ) {
				return;
			}

			if ( ! buttonText ) {
				buttonText = 'Go';
			}

			var row = document.createElement( 'tr' );
			row.classList.add( 'marketplace-table-banner' );
			row.classList.add( 'marketplace-suggestions-container' );
			row.classList.add( 'showing-suggestion' );
			row.dataset.marketplaceSuggestionsContext = 'products-list-inline';
			row.dataset.suggestionSlug = slug;

			var cell = document.createElement( 'td' );
			cell.setAttribute( 'colspan', 10 );

			var container = document.createElement( 'div' );
			container.classList.add( 'marketplace-suggestion-container' );
			container.dataset.suggestionSlug = slug;

			var icon = renderSuggestionIcon( iconUrl );
			if ( icon ) {
				container.appendChild( icon );
			}
			container.appendChild(
				renderSuggestionContent( title, copy )
			);
			container.appendChild(
				renderSuggestionCTA( context, slug, url, buttonText, true, allowDismiss )
			);

			cell.appendChild( container );
			row.appendChild( cell );

			return row;
		}

		// Render a "list item" style suggestion.
		// These are used in onboarding style contexts, e.g. products list empty state.
		function renderListItem( context, slug, iconUrl, title, copy, url, linkText, linkIsButton, allowDismiss ) {
			var container = document.createElement( 'div' );
			container.classList.add( 'marketplace-suggestion-container' );
			container.dataset.suggestionSlug = slug;

			var icon = renderSuggestionIcon( iconUrl );
			if ( icon ) {
				container.appendChild( icon );
			}
			container.appendChild(
				renderSuggestionContent( title, copy )
			);
			container.appendChild(
				renderSuggestionCTA( context, slug, url, linkText, linkIsButton, allowDismiss )
			);

			return container;
		}

		// Filter suggestion data to remove less-relevant suggestions.
		function getRelevantPromotions( marketplaceSuggestionsApiData, displayContext ) {
			// select based on display context
			var promos = _.filter( marketplaceSuggestionsApiData, function( promo ) {
				if ( _.isArray( promo.context ) ) {
					return _.contains( promo.context, displayContext );
				}
				return ( displayContext === promo.context );
			} );

			// hide promos the user has dismissed
			promos = _.filter( promos, function( promo ) {
				return ! _.contains( marketplace_suggestions.dismissed_suggestions, promo.slug );
			} );

			// hide promos for things the user already has installed
			promos = _.filter( promos, function( promo ) {
				return ! _.contains( marketplace_suggestions.active_plugins, promo['hide-if-active'] );
			} );

			// hide promos that are not applicable based on user's installed extensions
			promos = _.filter( promos, function( promo ) {
				if ( ! promo['show-if-active'] ) {
					// this promotion is relevant to all
					return true;
				}

				// if the user has any of the prerequisites, show the promo
				return ( _.intersection( marketplace_suggestions.active_plugins, promo['show-if-active'] ).length > 0 );
			} );

			return promos;
		}

		// Show and hide page elements dependent on suggestion state.
		function hidePageElementsForSuggestionState( usedSuggestionsContexts ) {
			var showingEmptyStateSuggestions = _.intersection(
				usedSuggestionsContexts,
				[ 'products-list-empty-body', 'orders-list-empty-body' ]
			).length > 0;

			// Streamline onboarding UI if we're in 'empty state' welcome mode.
			if ( showingEmptyStateSuggestions ) {
				$( '#screen-meta-links' ).hide();
				$( '#wpfooter' ).hide();
			}

			// Hide the header & footer, they don't make sense without specific promotion content
			if ( ! showingEmptyStateSuggestions ) {
				$( '.marketplace-suggestions-container[data-marketplace-suggestions-context="products-list-empty-header"]' ).hide();
				$( '.marketplace-suggestions-container[data-marketplace-suggestions-context="products-list-empty-footer"]' ).hide();
				$( '.marketplace-suggestions-container[data-marketplace-suggestions-context="orders-list-empty-header"]' ).hide();
				$( '.marketplace-suggestions-container[data-marketplace-suggestions-context="orders-list-empty-footer"]' ).hide();
			}

			var showingProductMetaboxSuggestions = _.contains( usedSuggestionsContexts, 'product-edit-meta-tab-body' );
			if ( ! showingProductMetaboxSuggestions ) {
				$( '.marketplace-suggestions_options.marketplace-suggestions_tab' ).hide();
				$( '#marketplace_suggestions.panel.woocommerce_options_panel' ).hide();
			}
		}

		// Render suggestion data in appropriate places in UI.
		function displaySuggestions( marketplaceSuggestionsApiData ) {
			var usedSuggestionsContexts = [];

			// iterate over all suggestions containers, rendering promos
			$( '.marketplace-suggestions-container' ).each( function() {
				// determine the context / placement we're populating
				var context = this.dataset.marketplaceSuggestionsContext;

				// find promotions that target this context
				var promos = getRelevantPromotions( marketplaceSuggestionsApiData, context );

				// shuffle/randomly select five suggestions to display
				var suggestionsToDisplay = _.sample( promos, 5 );

				// render the promo content
				for ( var i in suggestionsToDisplay ) {

					var linkText = suggestionsToDisplay[ i ]['link-text'];
					var linkoutIsButton = false;
					if ( suggestionsToDisplay[ i ]['button-text'] ) {
						linkText = suggestionsToDisplay[ i ]['button-text'];
						linkoutIsButton = true;
					}

					// dismiss is allowed by default
					var allowDismiss = true;
					if ( suggestionsToDisplay[ i ]['allow-dismiss'] === false ) {
						allowDismiss = false;
					}

					var content = renderListItem(
						context,
						suggestionsToDisplay[ i ].slug,
						suggestionsToDisplay[ i ].icon,
						suggestionsToDisplay[ i ].title,
						suggestionsToDisplay[ i ].copy,
						suggestionsToDisplay[ i ].url,
						linkText,
						linkoutIsButton,
						allowDismiss
					);
					$( this ).append( content );
					$( this ).addClass( 'showing-suggestion' );
					usedSuggestionsContexts.push( context );

					window.wcTracks.recordEvent( 'marketplace_suggestion_displayed', {
						suggestionSlug: suggestionsToDisplay[ i ].slug
					} );
				}
			} );

			// render inline promos in products list
			if ( 0 === usedSuggestionsContexts.length ) {
				$( '.wp-admin.admin-bar.edit-php.post-type-product table.wp-list-table.posts tbody').first().each( function() {
					var context = 'products-list-inline';

					// find promotions that target this context
					var promos = getRelevantPromotions( marketplaceSuggestionsApiData, context );
					if ( ! promos || ! promos.length ) {
						return;
					}

					// shuffle/randomly select the suggestion to display
					var suggestionToDisplay = _.sample( promos );

					// dismiss is allowed by default
					var allowDismiss = true;
					if ( false === suggestionToDisplay['allow-dismiss'] ) {
						allowDismiss = false;
					}

					// render first promo
					var content = renderTableBanner(
						context,
						suggestionToDisplay.slug,
						suggestionToDisplay.icon,
						suggestionToDisplay.title,
						suggestionToDisplay.copy,
						suggestionToDisplay.url,
						suggestionToDisplay['button-text'],
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

						usedSuggestionsContexts.push( context );

						window.wcTracks.recordEvent( 'marketplace_suggestion_displayed', {
							suggestionSlug: suggestionToDisplay.slug
						} );
					}
				} );
			}

			hidePageElementsForSuggestionState( usedSuggestionsContexts );
		}

		if ( marketplace_suggestions.suggestions_data ) {
			displaySuggestions( marketplace_suggestions.suggestions_data );
		}
	});

})( jQuery, marketplace_suggestions, ajaxurl );
