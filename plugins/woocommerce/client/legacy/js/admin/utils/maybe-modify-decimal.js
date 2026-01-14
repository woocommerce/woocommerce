/**
 * Maybe modify decimal utility for WooCommerce shipping forms
 */

/**
 * Maybe modify decimal for WooCommerce shipping forms
 *
 * @param {string} value - The value to modify
 * @param {Object} config - Configuration object with decimal separator
 * @param {string} config.decimalSeparator - Decimal separator (e.g., '.' or ',')
 * @returns {string} The (possibly modified) value
 */
function maybeModifyDecimal( value, config ) {
	// Check if value is a string and config is provided
	if (
		! value
		|| typeof value !== 'string'
		|| ! config
		|| typeof config !== 'object'
		|| ! config.decimalSeparator
	) {
		return value;
	}

	// Formula detection regex matches: brackets [], parentheses (), operators */+-, quotes "', and letters a-z and A-Z.
	const formulaRegex = /[\[\]()\*\+\-\/\"'a-zA-Z]/;
	if ( ! formulaRegex.test( value ) && '.' !== config.decimalSeparator && value.includes( '.' ) ) {
		return value.replace( '.', config.decimalSeparator );
	}
	return value;
}

// Export for different module systems
if ( typeof module !== 'undefined' && module.exports ) {
	// CommonJS (Node.js)
	module.exports = { maybeModifyDecimal };
} else if ( typeof define === 'function' && define.amd ) {
	// AMD
	define( [], function () {
		return { maybeModifyDecimal };
	} );
} else {
	// Browser global
	window.WCMaybeModifyDecimal = { maybeModifyDecimal };
}
