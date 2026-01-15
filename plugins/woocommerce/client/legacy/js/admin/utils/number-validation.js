/**
 * Number validation utilities for WooCommerce shipping forms
 */

/**
 * Validates formatted number strings with support for different locales and formulas
 *
 * @param {string} value - The value to validate
 * @param {Object} config - Configuration object with decimal and thousand separators
 * @param {string} config.decimalSeparator - Decimal separator (e.g., '.' or ',')
 * @param {string} config.thousandSeparator - Thousand separator (e.g., ',' or ' ' or '.')
 * @returns {boolean} Whether the value is a valid formatted number or formula
 */
function isValidFormattedNumber( value, config ) {
    // Ensure we are dealing with a string; non-strings are invalid.
    if ( typeof value !== 'string' ) {
        return false;
    }

    // Treat empty input as valid so optional fields (e.g. Flat rate main cost)
    // can be saved as blank to rely on class-only costs.
    // This preserves 10.0.x behavior where blank values were allowed.
    if ( value.trim() === '' ) {
        return true;
    }

    // For non-empty values, require a config object.
    if ( ! config || typeof config !== 'object' ) {
        return false;
    }

	var decimalSeparator = config.decimalSeparator || '.';
	var thousandSeparator = config.thousandSeparator || ',';

	// Prepare regex to match numbers with the given separators
	var escapedThousand = thousandSeparator.replace(
		/[.*+?^${}()|[\]\\]/g,
		'\\$&'
	);
	var escapedDecimal = decimalSeparator.replace(
		/[.*+?^${}()|[\]\\]/g,
		'\\$&'
	);
	var regex = new RegExp(
		"([0-9,.' " + escapedDecimal + escapedThousand + ']+)',
		'g'
	);

	// Find all possible number matches in the value
	const matches = ( value.match( regex ) || [] )
		.map( ( num ) => num.trim() )
		.filter( ( num ) => num !== '' );

	// If no numbers found, check if it's a shortcode format.
	if ( 0 === matches.length ) {
		// Check if the value is a shortcode format like [qty] or [cost]
		const shortcodeRegex = /^\[([a-zA-Z0-9_"'= ]+)\]/;
		return shortcodeRegex.test( value );
	}

	// Check if all matches are valid numbers with the correct separators
	return matches.every( ( num ) => {
		if ( ! num || num.length === 0 || ! num[ 0 ].match( /\d/ ) ) {
			return false; // If the first character is not a digit, it's invalid
		}
		// Extract the separators used in the number
		const usedSeparators = num.match( /([^0-9])+/g );
		if ( ! usedSeparators ) return true; // No separators found, a valid number.
		// Get the last separator used, which is assumed to be the decimal separator
		const usedDecimalSeparator = usedSeparators.pop();

		// If there are remaining separators, they should all be the same, and equal to the thousand separator
		if ( usedSeparators.length > 0 ) {
			// Check if remaining separators are all the same (thousand separator)
			const uniqueSeparators = new Set( usedSeparators );
			if ( uniqueSeparators.size > 1 ) {
				return false; // Invalid separators used
			}

			// If all remaining separators are the same, they should match the thousand separator
			if ( usedSeparators[ 0 ] !== thousandSeparator ) {
				return false; // Invalid separator used
			}
		}

		if ( usedDecimalSeparator.trim() !== decimalSeparator.trim() ) {
			// If the last separator is not the decimal separator, it must be the thousand separator
            if ( usedDecimalSeparator.trim() !== thousandSeparator.trim() ) {
                return false; // Invalid separator used
            }
            // Check if the last group has exactly 3 digits for thousand separator
            const lastGroup = num.split( usedDecimalSeparator ).pop();
            if ( ! lastGroup || lastGroup.length !== 3 || ! /^\d{3}$/.test( lastGroup ) ) {
                return false; // Invalid thousand separator format
            }
		}

		return true; // Valid decimal.
	} ); // All decimals use the correct separator
}

// Export for different module systems
if ( typeof module !== 'undefined' && module.exports ) {
	// CommonJS (Node.js)
	module.exports = { isValidFormattedNumber };
} else if ( typeof define === 'function' && define.amd ) {
	// AMD
	define( [], function () {
		return { isValidFormattedNumber };
	} );
} else {
	// Browser global
	window.WCNumberValidation = { isValidFormattedNumber };
}
