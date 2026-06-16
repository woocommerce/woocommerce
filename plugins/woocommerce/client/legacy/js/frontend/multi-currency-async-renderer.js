( function () {
	'use strict';

	var TIMEOUT_MS = 10000;
	var MAX_CACHE_SIZE = 500;
	var SESSION_CACHE_KEY = 'wcpay_mc_async_config';
	var SESSION_CACHE_TTL_MS = 300000;

	function getGlobalConfig() {
		return typeof window !== 'undefined'
			? window.wcpayAsyncPriceConfig
			: undefined;
	}

	function toNumber( value ) {
		var number = Number( value );
		return Number.isFinite( number ) ? number : 0;
	}

	function roundHalfUp( value, decimals ) {
		var factor = Math.pow( 10, decimals );
		return Math.round( ( value + Number.EPSILON ) * factor ) / factor;
	}

	function forEachElement( elements, callback ) {
		Array.prototype.forEach.call( elements, callback );
	}

	class MultiCurrencyAsyncPriceRenderer {
		constructor() {
			this.config = null;
			this.cache = new Map();
			this.initialized = false;
			this.observer = null;
			this.wcEventHandler = null;
			this.debounceTimer = null;
		}

		init() {
			if ( this.initialized ) {
				return Promise.resolve();
			}

			this.initialized = true;

			return this.fetchConfigWithTimeout()
				.then( ( config ) => {
					this.config = config;
					this.convertAllPrices();
					this.syncCurrencySwitchers();
					this.observeDynamicContent();
					this.listenToWooCommerceEvents();
				} )
				.catch( () => {
					this.showErrorState();
				} );
		}

		fetchConfigWithTimeout() {
			var timeoutId;
			var timeoutPromise = new Promise( function ( resolve, reject ) {
				timeoutId = window.setTimeout( function () {
					reject( new Error( 'Config fetch timeout' ) );
				}, TIMEOUT_MS );
			} );

			return Promise.race( [ this.fetchConfig(), timeoutPromise ] )
				.then( function ( config ) {
					window.clearTimeout( timeoutId );
					return config;
				} )
				.catch( function ( error ) {
					window.clearTimeout( timeoutId );
					throw error;
				} );
		}

		fetchConfig() {
			var cached = this.getCachedConfig();
			var asyncConfig = getGlobalConfig();

			if ( cached ) {
				return Promise.resolve( cached );
			}

			if ( ! asyncConfig || ! asyncConfig.apiUrl ) {
				return Promise.reject( new Error( 'Missing async price config URL' ) );
			}

			return window.fetch( asyncConfig.apiUrl ).then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( 'Config fetch failed: ' + response.status );
				}

				return response.json().then( ( config ) => {
					this.decodeCurrencySymbols( config );
					this.cacheConfig( config );
					return config;
				} );
			} );
		}

		getCachedConfig() {
			try {
				var raw = window.sessionStorage.getItem( SESSION_CACHE_KEY );
				if ( ! raw ) {
					return null;
				}

				var entry = JSON.parse( raw );
				if ( Date.now() - entry.timestamp > SESSION_CACHE_TTL_MS ) {
					window.sessionStorage.removeItem( SESSION_CACHE_KEY );
					return null;
				}

				this.decodeCurrencySymbols( entry.data );
				return entry.data;
			} catch ( error ) {
				return null;
			}
		}

		cacheConfig( config ) {
			try {
				window.sessionStorage.setItem(
					SESSION_CACHE_KEY,
					JSON.stringify( {
						data: config,
						timestamp: Date.now(),
					} )
				);
			} catch ( error ) {
				// sessionStorage can be disabled or full.
			}
		}

		decodeCurrencySymbols( config ) {
			if ( ! config || ! config.currencies ) {
				return;
			}

			var textarea = document.createElement( 'textarea' );
			Object.keys( config.currencies ).forEach( function ( code ) {
				var currency = config.currencies[ code ];
				if ( currency.symbol ) {
					// Detached textarea decodes entities and returns plain text via .value.
					textarea.innerHTML = currency.symbol;
					currency.symbol = textarea.value;
				}
			} );
		}

		convertPrice( price, type ) {
			var cacheKey = price + '_' + type;

			if ( this.cache.has( cacheKey ) ) {
				return this.cache.get( cacheKey );
			}

			var selectedCode = this.config.selected_currency;
			var currency = this.config.currencies[ selectedCode ];
			var effectiveCurrency = currency || this.config.currencies[ this.config.default_currency ];
			var amount = toNumber( price );
			var converted = amount;

			if ( currency && selectedCode !== this.config.default_currency ) {
				converted = amount * toNumber( currency.rate );

				if ( 'product' === type || 'shipping' === type ) {
					var rounding = toNumber( currency.rounding );
					if ( rounding > 0 ) {
						converted = Math.ceil( converted / rounding ) * rounding;
					} else {
						converted = roundHalfUp( converted, currency.decimals );
					}

					var charmOnlyProducts = false !== this.config.charm_only_products;
					if ( 'product' === type || ( 'shipping' === type && ! charmOnlyProducts ) ) {
						converted += toNumber( currency.charm );
					}
				} else {
					converted = roundHalfUp( converted, currency.decimals );
				}
			}

			converted = Math.max( 0, converted );

			var formatted = this.formatPrice( converted, effectiveCurrency );
			this.setCacheEntry( cacheKey, formatted );
			return formatted;
		}

		setCacheEntry( key, value ) {
			if ( this.cache.size >= MAX_CACHE_SIZE ) {
				var firstKey = this.cache.keys().next().value;
				if ( undefined !== firstKey ) {
					this.cache.delete( firstKey );
				}
			}

			this.cache.set( key, value );
		}

		formatPrice( price, currency ) {
			var fixed = toNumber( price ).toFixed( currency.decimals );
			var parts = fixed.split( '.' );
			var integerPart = parts[ 0 ];
			var decimalPart = parts[ 1 ] || '';
			var formattedInteger = integerPart.replace(
				/\B(?=(\d{3})+(?!\d))/g,
				currency.thousand_sep
			);
			var formattedNumber = formattedInteger;

			if ( currency.decimals > 0 ) {
				formattedNumber += currency.decimal_sep + decimalPart;
			}

			return formattedNumber;
		}

		buildPriceBdi( formattedNumber, currency ) {
			var bdi = document.createElement( 'bdi' );
			var symbolSpan = document.createElement( 'span' );
			symbolSpan.className = 'woocommerce-Price-currencySymbol';
			symbolSpan.textContent = currency.symbol;

			switch ( currency.symbol_pos ) {
				case 'right':
					bdi.appendChild( document.createTextNode( formattedNumber ) );
					bdi.appendChild( symbolSpan );
					break;
				case 'right_space':
					bdi.appendChild( document.createTextNode( formattedNumber ) );
					bdi.appendChild( document.createTextNode( '\u00a0' ) );
					bdi.appendChild( symbolSpan );
					break;
				case 'left_space':
					bdi.appendChild( symbolSpan );
					bdi.appendChild( document.createTextNode( '\u00a0' ) );
					bdi.appendChild( document.createTextNode( formattedNumber ) );
					break;
				default:
					bdi.appendChild( symbolSpan );
					bdi.appendChild( document.createTextNode( formattedNumber ) );
			}

			return bdi;
		}

		buildPriceText( formattedNumber, currency ) {
			switch ( currency.symbol_pos ) {
				case 'right':
					return formattedNumber + currency.symbol;
				case 'right_space':
					return formattedNumber + '\u00a0' + currency.symbol;
				case 'left_space':
					return currency.symbol + '\u00a0' + formattedNumber;
				default:
					return currency.symbol + formattedNumber;
			}
		}

		getEffectiveCurrency() {
			var selectedCode = this.config.selected_currency;
			var selectedCurrency = this.config.currencies[ selectedCode ];

			if ( ! selectedCurrency || selectedCode === this.config.default_currency ) {
				return this.config.currencies[ this.config.default_currency ];
			}

			return selectedCurrency;
		}

		convertAllPrices() {
			if ( ! this.config || ! this.config.currencies ) {
				return;
			}

			var effectiveCurrency = this.getEffectiveCurrency();
			var elements = document.querySelectorAll(
				'[data-wcpay-price]:not(.wcpay-price-converted)'
			);

			forEachElement(
				elements,
				( el ) => {
					var price = el.getAttribute( 'data-wcpay-price' );
					var type = el.getAttribute( 'data-wcpay-price-type' ) || 'product';
					var converted = this.convertPrice( price, type );
					var skeleton = el.querySelector( '.wcpay-price-skeleton' );
					var placeholder = el.querySelector( '.wcpay-price-placeholder' );

					if ( skeleton ) {
						skeleton.parentNode.removeChild( skeleton );
					}
					if ( placeholder ) {
						placeholder.parentNode.removeChild( placeholder );
					}

					el.appendChild( this.buildPriceBdi( converted, effectiveCurrency ) );
					el.classList.add( 'wcpay-price-converted' );
				}
			);

			this.convertScreenReaderText();
		}

		convertScreenReaderText() {
			if ( ! this.config || ! this.config.currencies ) {
				return;
			}

			var asyncConfig = getGlobalConfig();
			var srConfig = asyncConfig ? asyncConfig.srText : undefined;
			if ( ! srConfig ) {
				return;
			}

			var effectiveCurrency = this.getEffectiveCurrency();
			var elements = document.querySelectorAll(
				'[data-wcpay-sr-type]:not(.wcpay-sr-converted)'
			);

			forEachElement(
				elements,
				( el ) => {
					var type = el.getAttribute( 'data-wcpay-sr-type' );

					if ( 'sale_original' === type || 'sale_current' === type ) {
						var template = srConfig[ type ];
						var rawPrice = el.getAttribute( 'data-wcpay-sr-price' );

						if ( ! template || null === rawPrice ) {
							return;
						}

						var converted = this.convertPrice( rawPrice, 'product' );
						var priceText = this.buildPriceText( converted, effectiveCurrency );
						el.textContent = template.replace( '%1$s', priceText ).replace( '%s', priceText );
						el.classList.add( 'wcpay-sr-converted' );
					} else if ( 'range' === type ) {
						var from = el.getAttribute( 'data-wcpay-sr-price-from' );
						var to = el.getAttribute( 'data-wcpay-sr-price-to' );

						if ( ! srConfig.range || null === from || null === to ) {
							return;
						}

						var fromText = this.buildPriceText(
							this.convertPrice( from, 'product' ),
							effectiveCurrency
						);
						var toText = this.buildPriceText(
							this.convertPrice( to, 'product' ),
							effectiveCurrency
						);

						el.textContent = srConfig.range
							.replace( '%1$s', fromText )
							.replace( '%2$s', toText );
						el.classList.add( 'wcpay-sr-converted' );
					}
				}
			);
		}

		syncCurrencySwitchers() {
			var selectedCode = this.config ? this.config.selected_currency : null;
			if ( ! selectedCode ) {
				return;
			}

			var selects = document.querySelectorAll( 'select.js-woopayments-currency-switcher' );
			forEachElement( selects, function ( select ) {
				var hasOption = Array.prototype.some.call( select.options, function ( option ) {
					return option.value === selectedCode;
				} );

				if ( hasOption && select.value !== selectedCode ) {
					select.value = selectedCode;
				}
			} );
		}

		observeDynamicContent() {
			if ( 'undefined' === typeof MutationObserver || ! document.body ) {
				return;
			}

			this.observer = new MutationObserver( ( mutations ) => {
				var hasNewPrices = false;

				mutations.forEach( function ( mutation ) {
					forEachElement( mutation.addedNodes, function ( node ) {
						if ( hasNewPrices || node.nodeType !== window.Node.ELEMENT_NODE ) {
							return;
						}

						if (
							node.matches( '[data-wcpay-price]:not(.wcpay-price-converted)' ) ||
							node.querySelector( '[data-wcpay-price]:not(.wcpay-price-converted)' ) ||
							node.matches( '[data-wcpay-sr-type]:not(.wcpay-sr-converted)' ) ||
							node.querySelector( '[data-wcpay-sr-type]:not(.wcpay-sr-converted)' )
						) {
							hasNewPrices = true;
						}
					} );
				} );

				if ( hasNewPrices ) {
					window.clearTimeout( this.debounceTimer );
					this.debounceTimer = window.setTimeout( () => this.convertAllPrices(), 50 );
				}
			} );

			this.observer.observe( document.body, {
				childList: true,
				subtree: true,
			} );
		}

		listenToWooCommerceEvents() {
			if ( 'undefined' === typeof window.jQuery ) {
				return;
			}

			this.wcEventHandler = () => this.convertAllPrices();
			window.jQuery( document.body ).on(
				'updated_cart_totals updated_checkout updated_wc_div',
				this.wcEventHandler
			);
		}

		showErrorState() {
			var asyncConfig = getGlobalConfig() || {};
			var defaultCurrency = asyncConfig.defaultCurrency;
			var elements = document.querySelectorAll(
				'[data-wcpay-price]:not(.wcpay-price-converted)'
			);

			forEachElement(
				elements,
				( el ) => {
					var skeleton = el.querySelector( '.wcpay-price-skeleton' );
					var rawPrice = el.getAttribute( 'data-wcpay-price' );

					if ( defaultCurrency && null !== rawPrice ) {
						try {
							var formatted = this.formatPrice( toNumber( rawPrice ), defaultCurrency );
							var placeholder = el.querySelector( '.wcpay-price-placeholder' );

							if ( skeleton ) {
								skeleton.parentNode.removeChild( skeleton );
							}
							if ( placeholder ) {
								placeholder.parentNode.removeChild( placeholder );
							}

							el.appendChild( this.buildPriceBdi( formatted, defaultCurrency ) );
							el.classList.add( 'wcpay-price-converted' );
							return;
						} catch ( error ) {
							// Fall back to visual error state below.
						}
					}

					if ( skeleton ) {
						skeleton.classList.remove( 'wcpay-price-skeleton' );
						skeleton.classList.add( 'wcpay-price-error' );
						skeleton.textContent = '\u2014';
					}
				}
			);
		}

		destroy() {
			if ( this.observer ) {
				this.observer.disconnect();
				this.observer = null;
			}

			if ( this.wcEventHandler && 'undefined' !== typeof window.jQuery ) {
				window.jQuery( document.body ).off(
					'updated_cart_totals updated_checkout updated_wc_div',
					this.wcEventHandler
				);
			}

			window.clearTimeout( this.debounceTimer );
			this.debounceTimer = null;
			this.wcEventHandler = null;
			this.cache.clear();
			this.initialized = false;
			this.config = null;
		}
	}

	if ( typeof module !== 'undefined' && module.exports ) {
		module.exports = { MultiCurrencyAsyncPriceRenderer };
	}

	if ( typeof window !== 'undefined' ) {
		window.MultiCurrencyAsyncPriceRenderer = MultiCurrencyAsyncPriceRenderer;

		if ( getGlobalConfig() ) {
			var renderer = new MultiCurrencyAsyncPriceRenderer();
			if ( 'loading' === document.readyState ) {
				document.addEventListener( 'DOMContentLoaded', function () {
					renderer.init();
				} );
			} else {
				renderer.init();
			}
		}
	}
}() );
