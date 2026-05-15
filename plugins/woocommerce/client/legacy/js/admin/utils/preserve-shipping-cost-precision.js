/**
 * Preserve shipping cost precision utility for the shipping zone methods admin UI.
 *
 * When a stored shipping cost has more decimal places than the configured currency
 * precision, the form must still render the full stored value so the merchant does
 * not silently lose precision by re-saving the modal. See #43838.
 */

/**
 * Build a number formatting config that preserves the precision of the supplied
 * cost value when it exceeds the currency's configured precision.
 *
 * The returned object is a shallow clone of `config` with `precision` adjusted
 * upward to match the number of decimals actually present in `value`. If the
 * value's precision is less than or equal to the configured precision, the
 * original `config` is returned unchanged.
 *
 * @param {string} value  The shipping cost as a string, using `config.decimalSeparator`.
 * @param {Object} config Number formatting configuration. Must include
 *                        `decimalSeparator` and `precision`.
 * @return {Object} A config object suitable for `localiseMonetaryValue`.
 */
function getPrecisionPreservingConfig( value, config ) {
	if ( ! config || typeof config !== 'object' ) {
		return config;
	}

	if ( typeof value !== 'string' || ! config.decimalSeparator ) {
		return config;
	}

	const parts = value.split( config.decimalSeparator );
	if ( parts.length < 2 ) {
		return config;
	}

	if ( config.precision === null || config.precision === undefined ) {
		// localiseMonetaryValue auto-detects precision from the value, so we don't need to override.
		return config;
	}

	const valuePrecision = parts[ 1 ].length;
	const configuredPrecision = Number( config.precision );
	if ( ! Number.isFinite( configuredPrecision ) || valuePrecision <= configuredPrecision ) {
		return config;
	}

	return Object.assign( {}, config, { precision: valuePrecision } );
}

// Export for different module systems
if ( typeof module !== 'undefined' && module.exports ) {
	// CommonJS (Node.js)
	module.exports = { getPrecisionPreservingConfig };
} else if ( typeof define === 'function' && define.amd ) {
	// AMD
	define( [], function () {
		return { getPrecisionPreservingConfig };
	} );
} else {
	// Browser global
	window.WCPreserveShippingCostPrecision = { getPrecisionPreservingConfig };
}
