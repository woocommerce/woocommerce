( function () {
	const permanentlyDisabledServices = [];
	const baseUrl =
		'https://public-api.wordpress.com/wpcom/v2/woo/address-autocomplete';
	const searchUrl = `${ baseUrl }/search`;
	const selectUrl = `${ baseUrl }/select`;

	const MAX_SERVICE_ERROR_RETRIES = 3;

	/**
	 * Generate a unique session ID using crypto.randomUUID if available, otherwise fallback to Math.random
	 * @returns {string} A unique session ID
	 */
	function generateSessionId() {
		return crypto && crypto.randomUUID
			? crypto.randomUUID()
			: Math.random().toString( 36 ).substring( 2 );
	}

	/**
	 * Debounce function from lodash, modified to return a promise.
	 */
	function debounce( func, wait, options ) {
		var lastArgs,
			lastThis,
			maxWait,
			result,
			timerId,
			lastCallTime,
			lastInvokeTime = 0,
			leading = false,
			maxing = false,
			trailing = true;

		if ( typeof func != 'function' ) {
			throw new TypeError( 'Expected a function' );
		}

		if ( typeof options === 'object' ) {
			leading = !! options.leading;
			maxing = 'maxWait' in options;
			maxWait = maxing ? Math.max( options.maxWait || 0, wait ) : maxWait;
			trailing = 'trailing' in options ? !! options.trailing : trailing;
		}

		function invokeFunc( time ) {
			var args = lastArgs,
				thisArg = lastThis,
				resolve = args._resolve;

			lastArgs = lastThis = undefined;
			lastInvokeTime = time;
			result = func.apply( thisArg, args );

			// If there's a resolve function, call it with the result
			if ( resolve ) {
				resolve( result );
			}

			return result;
		}

		function leadingEdge( time ) {
			// Reset any `maxWait` timer.
			lastInvokeTime = time;
			// Start the timer for the trailing edge.
			timerId = setTimeout( timerExpired, wait );
			// Invoke the leading edge.
			return leading
				? invokeFunc( time )
				: new Promise( ( resolve ) => {
						// Store the resolve function to be called when the function executes
						lastArgs._resolve = resolve;
				  } );
		}

		function remainingWait( time ) {
			var timeSinceLastCall = time - lastCallTime,
				timeSinceLastInvoke = time - lastInvokeTime,
				timeWaiting = wait - timeSinceLastCall;

			return maxing
				? Math.min( timeWaiting, maxWait - timeSinceLastInvoke )
				: timeWaiting;
		}

		function shouldInvoke( time ) {
			var timeSinceLastCall = time - lastCallTime,
				timeSinceLastInvoke = time - lastInvokeTime;

			// Either this is the first call, activity has stopped and we're at the
			// trailing edge, the system time has gone backwards and we're treating
			// it as the trailing edge, or we've hit the `maxWait` limit.
			return (
				lastCallTime === undefined ||
				timeSinceLastCall >= wait ||
				timeSinceLastCall < 0 ||
				( maxing && timeSinceLastInvoke >= maxWait )
			);
		}

		function timerExpired() {
			var time = Date.now();
			if ( shouldInvoke( time ) ) {
				return trailingEdge( time );
			}
			// Restart the timer.
			timerId = setTimeout( timerExpired, remainingWait( time ) );
		}

		function trailingEdge( time ) {
			timerId = undefined;

			// Only invoke if we have `lastArgs` which means `func` has been
			// debounced at least once.
			if ( trailing && lastArgs ) {
				return invokeFunc( time );
			}
			lastArgs = lastThis = undefined;
			return result;
		}

		function cancel() {
			if ( timerId !== undefined ) {
				clearTimeout( timerId );
			}
			// Reject any pending promise
			if ( lastArgs && lastArgs._resolve ) {
				lastArgs._resolve( [] ); // Resolve with empty array for cancelled requests
			}
			lastInvokeTime = 0;
			lastArgs = lastCallTime = lastThis = timerId = undefined;
		}

		function flush() {
			return timerId === undefined ? result : trailingEdge( Date.now() );
		}

		function debounced() {
			var time = Date.now(),
				isInvoking = shouldInvoke( time );

			lastArgs = arguments;
			lastThis = this;
			lastCallTime = time;

			if ( isInvoking ) {
				if ( timerId === undefined ) {
					return leadingEdge( lastCallTime );
				}
				if ( maxing ) {
					// Handle invocations in a tight loop.
					clearTimeout( timerId );
					timerId = setTimeout( timerExpired, wait );
					return invokeFunc( lastCallTime );
				}
			}
			if ( timerId === undefined ) {
				timerId = setTimeout( timerExpired, wait );
			}
			// Return a promise that will resolve when the function is eventually called
			return new Promise( ( resolve ) => {
				// Store the resolve function to be called when the function executes
				lastArgs._resolve = resolve;
			} );
		}
		debounced.cancel = cancel;
		debounced.flush = flush;
		return debounced;
	}

	Object.entries( a8cAddressAutocompleteServiceKeys ).forEach(
		( [ key, value ] ) => {
			let sessionId = generateSessionId();
			let requestDurations = [];
			let serviceErrorRetries = 0;
			// LRU Cache for search results - key: `${inputValue}:${country}`, value: data
			class LRUCache {
				constructor( maxSize = 100 ) {
					this.maxSize = maxSize;
					this.cache = new Map();
				}

				get( key ) {
					if ( this.cache.has( key ) ) {
						// Move to end (most recently used)
						const value = this.cache.get( key );
						this.cache.delete( key );
						this.cache.set( key, value );
						return value;
					}
					return null;
				}

				set( key, value ) {
					if ( this.cache.has( key ) ) {
						// Remove existing entry to move to end
						this.cache.delete( key );
					} else if ( this.cache.size >= this.maxSize ) {
						// Remove least recently used (first entry)
						const firstKey = this.cache.keys().next().value;
						this.cache.delete( firstKey );
					}
					this.cache.set( key, value );
				}

				clear() {
					this.cache.clear();
				}

				get size() {
					return this.cache.size;
				}
			}

			const searchCache = new LRUCache( 100 );

			// Helper function to check cache
			const getCachedResult = ( inputValue, country ) => {
				const cacheKey = `${ inputValue }:${ country }`;
				return searchCache.get( cacheKey );
			};

			// Helper function to store result in cache
			const cacheResult = ( inputValue, country, data ) => {
				const cacheKey = `${ inputValue }:${ country }`;
				searchCache.set( cacheKey, data );
			};

			// Shared error handling function
			const handleApiError = ( data, response ) => {
				if ( ! data.code && ! data.error ) {
					return; // No error to handle
				}

				const errorCode = data.code || data.error;

				switch ( errorCode ) {
					case 'expired_jwt_token':
					case 'malformed_jwt_token':
					case 'invalid_jwt_token':
					case 'invalid_issuer':
					case 'invalid_service':
					case 'missing_jwt_token':
						permanentlyDisabledServices.push( key );
						console.error(
							`Automattic Address Suggestion (${ key }) has been disabled due to invalid JWT token`
						);
						return;
					case 'rate_limit_exceeded':
						permanentlyDisabledServices.push( key );
						setTimeout( () => {
							const index =
								permanentlyDisabledServices.indexOf( key );
							if ( index !== -1 ) {
								permanentlyDisabledServices.splice( index, 1 );
							}
						}, ( Number( response.headers.get( 'RateLimit-Retry-After' ) ) || 60 ) * 1000 );
						console.error(
							`Automattic Address Suggestion (${ key }) has been disabled due to rate limit exceeded`
						);
						return;
					case 'missing_query':
						return;
					case 'no_suggestions':
						return;
					case 'missing_address_id':
						console.error(
							`Automattic Address Suggestion (${ key }) has been disabled due to missing address ID`
						);
						return;
					case 'no_place':
						console.error(
							`Automattic Address Suggestion (${ key }) has been disabled due to no place found`
						);
						return;
					case 'missing_session_id':
						sessionId = generateSessionId();
						return;
					case 'woo_address_suggestion_internal_error':
					case 'woo_address_suggestion_service_error':
					case 'woo_address_suggestion_server_error':
						serviceErrorRetries++;
						if (
							serviceErrorRetries >= MAX_SERVICE_ERROR_RETRIES
						) {
							permanentlyDisabledServices.push( key );
							console.error(
								`Automattic Address Suggestion (${ key }) has been disabled due to internal service error`
							);
						}
						return;
					default:
						return;
				}
			};

			const debouncedSearch = debounce(
				async ( inputValue, country ) => {
					const params = new URLSearchParams( {
						query: inputValue,
						country,
						lang: document.documentElement.lang || navigator.lang,
						session_id: sessionId,
						token: value.key,
					} );

					try {
						const startTime = performance.now();
						const response = await fetch(
							`${ searchUrl }?${ params.toString() }`
						);
						const endTime = performance.now();
						requestDurations.push( endTime - startTime );
						let data = await response.json();

						// Handle errors using shared function
						handleApiError( data, response );

						if ( Array.isArray( data ) ) {
							data = data.map( ( item ) => ( {
								id: item.id,
								label: item.label,
								matchedSubstrings: item.matched_substrings,
							} ) );
							// Cache the successful result.
							// An empty result is still a valid result and is cached.
							cacheResult( inputValue, country, data );
							return data;
						}
					} catch ( e ) {
						if ( e.name === 'AbortError' ) {
							// Ignore abort errors from cancelled requests
							return [];
						}
						console.error(
							`Error fetching address suggestions for ${ key }:`,
							e
						);
						return [];
					}
				},
				300,
				{ leading: false, trailing: true }
			);
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider( {
				id: key,
				canSearch: () => {
					try {
						if ( permanentlyDisabledServices.includes( key ) ) {
							return false;
						}

						// Split JWT into parts
						const [ , payload ] = value.key.split( '.' );
						if ( ! payload ) {
							permanentlyDisabledServices.push( key );
							return false;
						}

						// Decode payload
						const decodedPayload = JSON.parse( atob( payload ) );

						// Check expiration
						const currentTime = Math.floor( Date.now() / 1000 );
						if (
							! decodedPayload.exp ||
							decodedPayload.exp < currentTime
						) {
							permanentlyDisabledServices.push( key );
							return false;
						}

						return true;
					} catch ( e ) {
						permanentlyDisabledServices.push( key );
						return false;
					}
				},
				search: async ( inputValue, country, type ) => {
					// We need to return early here because canSearch is not always called from search.
					if ( permanentlyDisabledServices.includes( key ) ) {
						return [];
					}

					inputValue = inputValue.trim();

					// Check cache first - bypass debounce for cached results
					const cachedResult = getCachedResult( inputValue, country );
					if ( cachedResult !== null ) {
						return cachedResult;
					}

					return await debouncedSearch( inputValue, country );
				},
				async select( addressId ) {
					const params = new URLSearchParams( {
						address_id: addressId,
						session_id: sessionId,
						lang: document.documentElement.lang,
						token: value.key,
					} );

					const response = await fetch(
						`${ selectUrl }?${ params.toString() }`
					);

					let data = await response.json();
					// Reset session ID after successful select
					sessionId = generateSessionId();
					try {
						dispatchEvent(
							new CustomEvent(
								'wc-address-autocomplete-service-request-durations',
								{
									detail: {
										requestDurations,
										provider: key,
									},
								}
							)
						);
					} catch ( e ) {
						console.error( e );
					}
					requestDurations = [];

					// Handle errors using shared function
					handleApiError( data, response );

					return data;
				},
			} );

			window.addEventListener(
				'wc-address-autocomplete-service-request-durations',
				( e ) => {
					if ( ! value.canTelemetry || e.detail.provider !== key ) {
						return;
					}
					// Send request durations to statsd, to keep track of the average request duration.
					new Image().src = createStatsdURL( 'a8c-ac-service', {
						name: 'request-durations',
						value: e.detail.requestDurations,
						type: 'timing',
					} );
				}
			);
		}
	);
} )();

function createBeacon( section, { name, value, type } ) {
	const event = name.replace( '-', '_' );

	// A counting event defaults to incrementing by one.
	if ( type === 'counting' ) {
		value = value === undefined ? 1 : value;
	}
	value = Array.isArray( value ) ? value : [ value ];
	return value.map(
		( v ) =>
			`a8c.${ section }.${ event }:${ v }|${
				type === 'timing' ? 'ms' : 'c'
			}`
	);
}

function createStatsdURL( sectionName, events ) {
	if ( ! Array.isArray( events ) ) {
		events = [ events ]; // Only a single event was passed to process.
	}

	const sanitizedSection = sectionName.replace( /[.:-]/g, '_' );
	const json = JSON.stringify( {
		beacons: events
			.map( ( event ) => createBeacon( sanitizedSection, event ) )
			.flat(),
	} );

	const encodedJson = encodeURIComponent( json );

	return `https://pixel.wp.com/boom.gif?json=${ encodedJson }`;
}
