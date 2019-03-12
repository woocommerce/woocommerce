/* global marketplace_suggestions, ajaxurl, Cookies */
( function( $, marketplace_suggestions, ajaxurl ) {
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
		function dismissSuggestion( context, suggestionSlug ) {
			// hide the suggestion in the UI
			var selector = '[data-suggestion-slug=' + suggestionSlug + ']';
			$( selector ).fadeOut( function() {
				$( this ).remove();
				tidyProductEditMetabox();
			} );

			// save dismissal in user settings
			jQuery.post(
				ajaxurl,
				{
					'action': 'woocommerce_add_dismissed_marketplace_suggestion',
					'_wpnonce': marketplace_suggestions.dismiss_suggestion_nonce,
					'slug': suggestionSlug
				}
			);

			// if this is a high-use area, delay new suggestion that area for a short while
			var highUseSuggestionContexts = [ 'products-list-inline' ];
			if ( _.contains( highUseSuggestionContexts, context ) ) {
				Cookies.set( 'woocommerce_snooze_products_list_suggestions', '1', { expires: 2 } );
			}

			window.wcTracks.recordEvent( 'marketplace_suggestion_dismissed', {
				suggestionSlug: suggestionSlug
			} );
		}

		// Render DOM element for suggestion dismiss button.
		function renderDismissButton( context, suggestionSlug ) {
			var dismissButton = document.createElement( 'a' );

			dismissButton.classList.add( 'suggestion-dismiss' );
			dismissButton.setAttribute( 'title', marketplace_suggestions.i18n_marketplace_suggestions_dismiss_tooltip );
			dismissButton.setAttribute( 'href', '#' );
			dismissButton.onclick = function( event ) {
				event.preventDefault();
				dismissSuggestion( context, suggestionSlug );
			};

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

			linkoutButton.onclick = function() {
				window.wcTracks.recordEvent( 'marketplace_suggestion_clicked', {
					suggestionSlug: slug
				} );
			};

			if ( isButton ) {
				linkoutButton.classList.add( 'button' );
			} else {
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
			var container = document.createElement( 'div' );

			container.classList.add( 'marketplace-suggestion-container-content' );

			if ( title ) {
				var titleHeading = document.createElement( 'h4' );
				titleHeading.textContent = title;
				container.appendChild( titleHeading );
			}

			if ( copy ) {
				var body = document.createElement( 'p' );
				body.textContent = copy;
				container.appendChild( body );
			}

			return container;
		}

		// Render DOM elements for suggestion call-to-action – button or link with dismiss 'x'.
		function renderSuggestionCTA( context, slug, url, linkText, linkIsButton, allowDismiss ) {
			var container = document.createElement( 'div' );

			if ( ! linkText ) {
				linkText = marketplace_suggestions.i18n_marketplace_suggestions_default_cta;
			}

			container.classList.add( 'marketplace-suggestion-container-cta' );
			if ( url && linkText ) {
				var linkoutElement = renderLinkout( context, slug, url, linkText, linkIsButton );
				container.appendChild( linkoutElement );
			}

			if ( allowDismiss ) {
				container.appendChild( renderDismissButton( context, slug ) );
			}

			return container;
		}

		function getTableBannerColspan() {
			return $( 'table.wp-list-table.posts thead th:not(.hidden)' ).length + 1;
		}

		// Render a "table banner" style suggestion.
		// These are used in admin lists, e.g. products list.
		function renderTableBanner( context, slug, iconUrl, title, copy, url, buttonText, allowDismiss ) {
			if ( ! title || ! url ) {
				return;
			}

			var row = document.createElement( 'tr' );
			row.classList.add( 'marketplace-table-banner' );
			row.classList.add( 'marketplace-suggestions-container' );
			row.classList.add( 'showing-suggestion' );
			row.dataset.marketplaceSuggestionsContext = 'products-list-inline';
			row.dataset.suggestionSlug = slug;

			var cell = document.createElement( 'td' );
			cell.classList.add( 'marketplace-table-banner-td' );
			cell.setAttribute( 'colspan', getTableBannerColspan() );

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

			// hide promos that are not applicable based on user's active extensions
			promos = _.filter( promos, function( promo ) {
				if ( ! promo['show-if-active'] ) {
					// this promotion is relevant to all
					return true;
				}

				// if the user has any of the prerequisites, show the promo
				return ( _.intersection( marketplace_suggestions.active_plugins, promo['show-if-active'] ).length > 0 );
			} );

			// sort the promos so extension-dependent ones are at the start of the list
			promos = _.sortBy( promos, function( promo ) {
				return _.isEmpty( promo['show-if-active'] ) ? 1 : 0;
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
		}

		// Streamline the product edit suggestions tab dependent on what's visible.
		function tidyProductEditMetabox() {
			var productMetaboxSuggestions = $(
				'.marketplace-suggestions-container[data-marketplace-suggestions-context="product-edit-meta-tab-body"]'
			).children();
			if ( 0 >= productMetaboxSuggestions.length ) {
				$( '.marketplace-suggestions-container[data-marketplace-suggestions-context="product-edit-meta-tab-header"]' ).slideUp();
				$( '.marketplace-suggestions-container[data-marketplace-suggestions-context="product-edit-meta-tab-body"]' ).fadeOut();
			}
		}

		function refreshBannerColspanForScreenOptions( content ) {
			$( '#show-settings-link' ).on( 'focus.scroll-into-view', function() {
				$( '.marketplace-table-banner-td' ).attr( 'colspan', getTableBannerColspan() );
			});
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
					var linkoutIsButton = true;
					if ( suggestionsToDisplay[ i ]['link-text'] ) {
						linkText = suggestionsToDisplay[ i ]['link-text'];
						linkoutIsButton = false;
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

					// product list banner suggestion is temporarily suppressed after a recent dismissal
					if ( Cookies.get( 'woocommerce_snooze_products_list_suggestions' ) ) {
						return;
					}

					// find promotions that target this context
					var promos = getRelevantPromotions( marketplaceSuggestionsApiData, context );
					if ( ! promos || ! promos.length ) {
						return;
					}

					// show the first suggestion from the list
					var suggestionToDisplay = promos[0];

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

						$( content ).hide();

						if ( rows.length <= minRow ) {
							// if small number of rows, append at end
							$( this ).append( content );
						}
						else {
							// for more rows, insert
							$( rows[ minRow - 1 ] ).after( content );
						}

						$( content ).fadeIn();

						usedSuggestionsContexts.push( context );

						refreshBannerColspanForScreenOptions( content );

						window.wcTracks.recordEvent( 'marketplace_suggestion_displayed', {
							suggestionSlug: suggestionToDisplay.slug
						} );
					}
				} );
			}

			hidePageElementsForSuggestionState( usedSuggestionsContexts );
			tidyProductEditMetabox();
		}

		if ( marketplace_suggestions.suggestions_data ) {
			displaySuggestions( marketplace_suggestions.suggestions_data );
		}
	});

})( jQuery, marketplace_suggestions, ajaxurl );
