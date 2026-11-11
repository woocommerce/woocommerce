/* eslint-disable @typescript-eslint/no-require-imports */

/**
 * External dependencies
 */
const fs = require( 'fs' );
const path = require( 'path' );
const {
	POSTCODE_REGEXES,
} = require( 'postcode-validator/lib/cjs/postcode-regexes.js' );
const { version } = require( 'postcode-validator/package.json' );
const { dependencies } = require( '../package.json' );

const OUTPUT_PATH = path.resolve(
	__dirname,
	'../../../i18n/postcode-validation-rules.json'
);

// Countries with an explicit server-side rule or a Blocks override before the
// validators were unified. Country expansion is intentionally handled
// separately.
const COUNTRY_CODES = [
	'AT',
	'BA',
	'BE',
	'BR',
	'CA',
	'CH',
	'CZ',
	'DE',
	'DK',
	'EE',
	'ES',
	'FI',
	'FR',
	'GB',
	'HU',
	'IE',
	'IN',
	'IT',
	'JP',
	'KH',
	'LI',
	'LV',
	'MN',
	'NI',
	'NL',
	'NO',
	'PL',
	'PR',
	'PT',
	'SE',
	'SI',
	'SK',
	'US',
];

// Preserve WooCommerce's existing explicit validation behaviour where it is
// intentionally different from the upstream package. These overrides are
// applied only while generating the shared artifact.
const COMPATIBILITY_OVERRIDES = {
	AT: { pattern: '[0-9]{4}' },
	BA: { pattern: '[7-8][0-9]{4}' },
	CA: {
		pattern:
			'[ABCEGHJKLMNPRSTVXY][0-9][ABCEGHJKLMNPRSTVWXYZ] ?[0-9][ABCEGHJKLMNPRSTVWXYZ][0-9]',
		flags: 'i',
	},
	CZ: { pattern: '(?:CZ-)?[0-9]{3}\\s?[0-9]{2}' },
	DE: { pattern: '(?:0[1-9]|[1-9][0-9])[0-9]{3}' },
	DK: {
		pattern: '(?:DK[- ]?)?(?:[1-24-9]\\d{3}|3[0-8]\\d{2})',
		flags: 'i',
	},
	ES: { pattern: '[0-9]{5}' },
	FI: { pattern: '[0-9]{5}' },
	FR: { pattern: '[0-9]{5}', flags: 'i' },
	GB: {
		pattern:
			'(?:[abcdefghijklmnoprstuwyz][abcdefghklmnopqrstuvwxy]?[0-9]{1,2}[0-9][abdefghjlnpqrstuwxyz]{2}|[abcdefghijklmnoprstuwyz][0-9][abcdefghjkpstuw][0-9][abdefghjlnpqrstuwxyz]{2}|[abcdefghijklmnoprstuwyz][abcdefghklmnopqrstuvwxy][0-9][abehmnprvwxy][0-9][abdefghjlnpqrstuwxyz]{2}|gir0aa|bfpo[0-9]{1,4}|bfpoc\\/o[0-9]{1,3})',
		flags: 'i',
		normalization: 'removeSpaces',
	},
	IE: {
		pattern: '(?:[AC-FHKNPRTV-Y][0-9]{2}|D6W)[0-9AC-FHKNPRTV-Y]{4}',
		flags: 'i',
		normalization: 'removeSpacesAndHyphens',
	},
	IN: { pattern: '[1-9][0-9]{2}\\s?[0-9]{3}' },
	JP: { pattern: '[0-9]{3}-?[0-9]{4}' },
	KH: { pattern: '[0-9]{6}' },
	LI: { pattern: '94[8-9][0-9]' },
	LV: { pattern: '(?:LV[- ]?)?[1-9][0-9]{3}', flags: 'i' },
	MN: { pattern: '[0-9]{5}(?:-[0-9]{4})?' },
	NI: { pattern: '[1-9][0-9]{4}' },
	NL: {
		pattern: '[1-9][0-9]{3}\\s?(?!SA|SD|SS)[A-Z]{2}',
		flags: 'i',
	},
	PR: { pattern: '[0-9]{5}(?:-[0-9]{4})?', flags: 'i' },
	PT: { pattern: '[0-9]{4}-[0-9]{3}' },
	SE: { pattern: '(?:SE-)?[0-9]{3}\\s?[0-9]{2}' },
	SI: { pattern: '[1-9][0-9]{3}' },
	SK: { pattern: '(?:SK-)?[0-9]{3}\\s?[0-9]{2}' },
};

if ( dependencies[ 'postcode-validator' ] !== version ) {
	throw new Error(
		`Installed postcode-validator ${ version } does not match the ${ dependencies[ 'postcode-validator' ] } pin`
	);
}

/**
 * Remove the ECMAScript anchors supplied by postcode-validator. Consumers add
 * native anchors so PHP can use \A/\z while JavaScript uses ^/$.
 *
 * @param {RegExp} regex Upstream regular expression.
 * @return {string} Portable, unanchored expression source.
 */
function removeAnchors( regex ) {
	if ( ! regex.source.startsWith( '^' ) || ! regex.source.endsWith( '$' ) ) {
		throw new Error( `Expected an anchored expression: ${ regex.source }` );
	}

	return regex.source.slice( 1, -1 );
}

/**
 * Replace ECMAScript whitespace tokens with literal spaces. JavaScript's \s
 * also matches Unicode whitespace that the PHP validator rejects before
 * applying country-specific rules.
 *
 * @param {string} pattern Regular expression source.
 * @return {string} Expression source with portable space matching.
 */
function replaceWhitespaceTokens( pattern ) {
	return pattern.replaceAll( '\\s', '[ ]' );
}

const rules = Object.fromEntries(
	COUNTRY_CODES.map( ( countryCode ) => {
		const upstreamRegex = POSTCODE_REGEXES.get( countryCode );
		if ( ! upstreamRegex ) {
			throw new Error(
				`No postcode-validator rule for ${ countryCode }`
			);
		}

		const sourceRule = {
			pattern: removeAnchors( upstreamRegex ),
			...( upstreamRegex.flags ? { flags: upstreamRegex.flags } : {} ),
			...COMPATIBILITY_OVERRIDES[ countryCode ],
		};
		const rule = {
			...sourceRule,
			pattern: replaceWhitespaceTokens( sourceRule.pattern ),
		};

		if ( rule.pattern.includes( '~' ) ) {
			throw new Error( `Unsupported delimiter in ${ countryCode } rule` );
		}
		if ( rule.flags && rule.flags !== 'i' ) {
			throw new Error( `Unsupported flags in ${ countryCode } rule` );
		}

		// Catch malformed generated rules before they reach either consumer.
		new RegExp( `^(?:${ rule.pattern })$`, rule.flags || '' );

		return [ countryCode, rule ];
	} )
);

const artifact = {
	generatedFrom: {
		package: 'postcode-validator',
		version,
	},
	rules,
};

fs.writeFileSync(
	OUTPUT_PATH,
	`${ JSON.stringify( artifact, null, '\t' ) }\n`
);
