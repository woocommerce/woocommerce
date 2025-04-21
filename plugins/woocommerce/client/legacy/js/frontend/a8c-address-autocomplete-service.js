( function () {
	const permanentlyDisabledServices = [];
	const baseUrl =
		'https://public-api.wordpress.com/wpcom/v2/woo/address-autocomplete';
	const searchUrl = `${ baseUrl }/search`;
	const selectUrl = `${ baseUrl }/select`;

	const MAX_SERVICE_ERROR_RETRIES = 3;

	/**
	 * Copied from Lodash.
	 *
	 * Creates a debounced function that delays invoking `func` until after `wait`
	 * milliseconds have elapsed since the last time the debounced function was
	 * invoked. The debounced function comes with a `cancel` method to cancel
	 * delayed `func` invocations and a `flush` method to immediately invoke them.
	 * Provide `options` to indicate whether `func` should be invoked on the
	 * leading and/or trailing edge of the `wait` timeout. The `func` is invoked
	 * with the last arguments provided to the debounced function. Subsequent
	 * calls to the debounced function return the result of the last `func`
	 * invocation.
	 *
	 * **Note:** If `leading` and `trailing` options are `true`, `func` is
	 * invoked on the trailing edge of the timeout only if the debounced function
	 * is invoked more than once during the `wait` timeout.
	 *
	 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
	 * until to the next tick, similar to `setTimeout` with a timeout of `0`.
	 *
	 * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
	 * for details over the differences between `_.debounce` and `_.throttle`.
	 *
	 * @static
	 * @memberOf _
	 * @since 0.1.0
	 * @category Function
	 * @param {Function} func The function to debounce.
	 * @param {number} [wait=0] The number of milliseconds to delay.
	 * @param {Object} [options={}] The options object.
	 * @param {boolean} [options.leading=false]
	 *  Specify invoking on the leading edge of the timeout.
	 * @param {number} [options.maxWait]
	 *  The maximum time `func` is allowed to be delayed before it's invoked.
	 * @param {boolean} [options.trailing=true]
	 *  Specify invoking on the trailing edge of the timeout.
	 * @returns {Function} Returns the new debounced function.
	 * @example
	 *
	 * // Avoid costly calculations while the window size is in flux.
	 * jQuery(window).on('resize', _.debounce(calculateLayout, 150));
	 *
	 * // Invoke `sendMail` when clicked, debouncing subsequent calls.
	 * jQuery(element).on('click', _.debounce(sendMail, 300, {
	 *   'leading': true,
	 *   'trailing': false
	 * }));
	 *
	 * // Ensure `batchLog` is invoked once after 1 second of debounced calls.
	 * var debounced = _.debounce(batchLog, 250, { 'maxWait': 1000 });
	 * var source = new EventSource('/stream');
	 * jQuery(source).on('message', debounced);
	 *
	 * // Cancel the trailing debounced invocation.
	 * jQuery(window).on('popstate', debounced.cancel);
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
			throw new TypeError( FUNC_ERROR_TEXT );
		}

		if ( typeof options === 'object' ) {
			leading = !! options.leading;
			maxing = 'maxWait' in options;
			maxWait = maxing ? Math.max( options.maxWait || 0, wait ) : maxWait;
			trailing = 'trailing' in options ? !! options.trailing : trailing;
		}

		function invokeFunc( time ) {
			var args = lastArgs,
				thisArg = lastThis;

			lastArgs = lastThis = undefined;
			lastInvokeTime = time;
			result = func.apply( thisArg, args );
			return result;
		}

		function leadingEdge( time ) {
			// Reset any `maxWait` timer.
			lastInvokeTime = time;
			// Start the timer for the trailing edge.
			timerId = setTimeout( timerExpired, wait );
			// Invoke the leading edge.
			return leading ? invokeFunc( time ) : result;
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
			return result;
		}
		debounced.cancel = cancel;
		debounced.flush = flush;
		return debounced;
	}

	Object.entries( a8cAddressAutocompleteServiceKeys ).forEach(
		( [ key, value ] ) => {
			let resultCache = {};
			let lastResult = [];
			let sessionId =
				crypto && crypto.randomUUID
					? crypto.randomUUID()
					: Math.random().toString( 36 ).substring( 2 );
			let requestDurations = [];
			let serviceErrorRetries = 0;
			let controller = new AbortController();
			const debouncedSearch = debounce(
				async ( inputValue, country ) => {
					const params = new URLSearchParams( {
						query: inputValue,
						country,
						lang: document.documentElement.lang,
						session_id: sessionId,
						token: value,
					} );

					try {
						const startTime = Date.now();
						const response = await fetch(
							`${ searchUrl }?${ params.toString() }`
						);
						const endTime = Date.now();
						requestDurations.push( endTime - startTime );
						const data = await response.json();

						if ( data.code ) {
							switch ( data.code ) {
								case 'expired_jwt_token':
								case 'malformed_jwt_token':
								case 'invalid_jwt_token':
								case 'invalid_issuer':
								case 'invalid_service':
								case 'missing_jwt_token':
									permanentlyDisabledServices.push( key );
									console.error(
										'Automattic Address Suggestion has been disabled due to invalid JWT token'
									);
									return [];
								case 'rate_limit_exceeded':
									permanentlyDisabledServices.push( key );
									setTimeout( () => {
										permanentlyDisabledServices.splice(
											permanentlyDisabledServices.indexOf(
												key
											),
											1
										);
									}, response.headers.get( 'RateLimit-Retry-After' ) * 1000 );
									console.error(
										'Automattic Address Suggestion has been disabled due to rate limit exceeded'
									);
									return [];
								case 'missing_query':
								case 'no_suggestions':
									return [];
								case 'missing_session_id':
									sessionId =
										crypto && crypto.randomUUID
											? crypto.randomUUID()
											: Math.random()
													.toString( 36 )
													.substring( 2 );
									return [];
								case 'woo_addression_suggestion_internal_error':
								case 'woo_address_suggestion_service_error':
									serviceErrorRetries++;
									if (
										serviceErrorRetries >=
										MAX_SERVICE_ERROR_RETRIES
									) {
										permanentlyDisabledServices.push( key );
										console.error(
											'Automattic Address Suggestion has been disabled due to internal service error'
										);
									}
									return [];
								default:
									return [];
							}
						}
						if ( Array.isArray( data ) ) {
							lastResult = data.map( ( item ) => ( {
								id: item.id,
								label: item.label,
								matchedSubstrings: item.matched_substrings,
							} ) );
							return lastResult;
						}
					} catch ( e ) {
						if ( e.name === 'AbortError' ) {
							// Ignore abort errors from cancelled requests
							return lastResult;
						}
						console.error(
							'Error fetching address suggestions:',
							e
						);
						return lastResult;
					}
				},
				150,
				{ leading: true, maxWait: 1000 }
			);
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider( {
				id: key,
				canSearch: () => {
					try {
						if ( permanentlyDisabledServices.includes( key ) ) {
							return false;
						}

						// Split JWT into parts
						const [ , payload ] = value.split( '.' );
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
					// We need to return early here beacuse canSearch is not always called from search.
					if ( permanentlyDisabledServices.includes( key ) ) {
						return [];
					}
					inputValue = inputValue.trim();
					const cacheKey = `${ inputValue }-${ country }`;
					if ( resultCache[ cacheKey ] ) {
						return resultCache[ cacheKey ];
					}
					const result = await debouncedSearch( inputValue, country );
					if ( Array.isArray( result ) && result.length > 0 ) {
						resultCache[ cacheKey ] = result;
					}
					return result;
				},
				async select( addressId ) {
					const params = new URLSearchParams( {
						address_id: addressId,
						session_id: sessionId,
						lang: document.documentElement.lang,
						token: value,
					} );

					const response = await fetch(
						`${ selectUrl }?${ params.toString() }`
					);

					const data = await response.json();
					// Reset session ID after successful select
					sessionId = crypto.randomUUID();
					try {
						dispatchEvent(
							new CustomEvent(
								'wc-address-autocomplete-service-request-durations',
								{
									detail: {
										requestDurations,
									},
								}
							)
						);
					} catch ( e ) {
						console.error( e );
					}
					requestDurations = [];
					lastResult = [];
					if ( data.error ) {
						switch ( data.error ) {
							case 'expired_jwt_token':
							case 'malformed_jwt_token':
							case 'invalid_jwt_token':
							case 'invalid_issuer':
							case 'invalid_service':
							case 'missing_jwt_token':
								permanentlyDisabledServices.push( key );
								return null;
							case 'rate_limit_exceeded':
								permanentlyDisabledServices.push( key );
								setTimeout( () => {
									permanentlyDisabledServices.splice(
										permanentlyDisabledServices.indexOf(
											key
										),
										1
									);
								}, response.headers.get( 'RateLimit-Retry-After' ) * 1000 );
								return null;
							case 'missing_address_id':
								console.error(
									'Automattic Address Suggestion: Missing address ID'
								);
								return null;
							case 'no_place':
								console.error(
									'Automattic Address Suggestion: No place found'
								);
								return null;
							case 'missing_session_id':
								sessionId =
									crypto && crypto.randomUUID
										? crypto.randomUUID()
										: Math.random()
												.toString( 36 )
												.substring( 2 );
								return [];
							case 'woo_addression_suggestion_internal_error':
							case 'woo_addression_suggestion_server_error':
								serviceErrorRetries++;
								if (
									serviceErrorRetries >=
									MAX_SERVICE_ERROR_RETRIES
								) {
									permanentlyDisabledServices.push( key );
									console.error(
										'Automattic Address Suggestion has been disabled due to internal service error'
									);
								}
								return null;
							default:
								console.error(
									'Automattic Address Suggestion: Unknown error'
								);
								return null;
						}
					}

					return data;
				},
			} );
		}
	);
} )();

window.addEventListener(
	'wc-address-autocomplete-service-request-durations',
	( e ) => {
		console.log( e.detail.requestDurations );
	}
);
