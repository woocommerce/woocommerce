/* eslint-disable @typescript-eslint/no-require-imports */

/**
 * External dependencies
 */
const fs = require( 'fs' );
const path = require( 'path' );

const OUTPUT_PATH = path.resolve( __dirname, '../../../i18n/postcodes.php' );
const DATA_URL = 'https://chromium-i18n.appspot.com/ssl-address/data';

const COMPATIBILITY_OVERRIDES = {
	// Argentina still uses the old four digit codes alongside the lettered ones.
	AR: '[A-HJ-NP-Z]?[0-9]{4}([A-Z]{3})?',
	BA: '[7-8][0-9]{4}',
	DE: '(?:0[1-9]|[1-9][0-9])[0-9]{3}',
	DK: '[1-24-9][0-9]{3}|3[0-8][0-9]{2}',
	GB: '(?:[abcdefghijklmnoprstuwyz][abcdefghklmnopqrstuvwxy]?[0-9]{1,2}[0-9][abdefghjlnpqrstuwxyz]{2}|[abcdefghijklmnoprstuwyz][0-9][abcdefghjkpstuw][0-9][abdefghjlnpqrstuwxyz]{2}|[abcdefghijklmnoprstuwyz][abcdefghklmnopqrstuvwxy][0-9][abehmnprvwxy][0-9][abdefghjlnpqrstuwxyz]{2}|gir0aa|bfpo[0-9]{1,4}|bfpoc\\/o[0-9]{1,3})',
	// Google accepts any seven characters; keep the Eircode routing key check.
	IE: '(?:[AC-FHKNPRTV-Y][0-9]{2}|D6W)[0-9AC-FHKNPRTV-Y]{4}',
	IN: '[1-9][0-9]{5}',
	KH: '[0-9]{6}',
	LI: '94[8-9][0-9]',
	MN: '[0-9]{5}(?:[0-9]{4})?',
	NG: '[0-9]{6}|[A-Z]{2}(?:0[1-9]|[1-9][0-9])[A-Z0-9]{3}[A-Z]{2}(?:0[1-9]|[1-9][0-9])',
	NI: '[1-9][0-9]{4}',
	PR: '[0-9]{5}(?:[0-9]{4})?',
	SI: '[1-9][0-9]{3}',
};

// Prefixes that older WooCommerce versions accepted and Google does not list.
const LEGACY_PREFIXES = {
	CZ: 'CZ',
	DK: 'DK',
	SK: 'SK',
	SV: 'CP',
};

// Formats that older WooCommerce versions accepted and Google's rule rejects.
// The generator adds each one to the country's rule, so both formats pass.
const LEGACY_FORMATS = {
	AD: 'AD[0-9]{3}',
	EC: '[A-Z][0-9]{4}[A-Z]|[A-Z]{2}[0-9]{6}',
	GU: '969[1-3][0-9](?:[0-9]{4})?',
	IL: '[0-9]{6}',
	LV: '[0-9]{4}',
	MU: '[A-Z][0-9]{4}',
	PY: '[0-9]{4}',
	SO: '[0-9]{5}',
	XK: '[0-9]{5}',
};

/**
 * Fetch Google's postcode rule, examples and postal prefix for each country.
 *
 * @return {Promise<Object>} Country data keyed by country code.
 */
async function fetchGoogleData() {
	const response = await fetch( DATA_URL );
	if ( ! response.ok ) {
		throw new Error( `Google address data returned ${ response.status }` );
	}
	const { countries } = await response.json();
	const countryCodes = countries.split( '~' ).sort();
	const data = {};

	for ( let i = 0; i < countryCodes.length; i += 12 ) {
		const batch = countryCodes.slice( i, i + 12 );
		const entries = await Promise.all(
			batch.map( async ( countryCode ) => {
				const countryResponse = await fetch(
					`${ DATA_URL }/${ countryCode }`
				);
				if ( ! countryResponse.ok ) {
					throw new Error(
						`Google address data for ${ countryCode } returned ${ countryResponse.status }`
					);
				}
				return [ countryCode, await countryResponse.json() ];
			} )
		);

		for ( const [ countryCode, { zip, zipex, postprefix } ] of entries ) {
			if ( typeof zip === 'string' && zip ) {
				data[ countryCode ] = {
					zip,
					examples: typeof zipex === 'string' ? zipex : '',
					prefix: typeof postprefix === 'string' ? postprefix : '',
				};
			}
		}
	}

	if ( Object.keys( data ).length < 100 ) {
		throw new Error(
			'Google address data returned too few postcode rules'
		);
	}

	return data;
}

/**
 * Remove every separator (space, hyphen, or a class or escape that only matches
 * those) from a pattern, along with its quantifier. Hyphens inside other
 * character classes (ranges) are kept.
 *
 * @param {string} pattern Regular expression source.
 * @return {string} Expression source for a postcode with no separators.
 */
function stripSeparators( pattern ) {
	let out = '';

	for ( let i = 0; i < pattern.length; i++ ) {
		let token = pattern[ i ];
		let isSeparator = token === ' ' || token === '-';

		if ( token === '\\' ) {
			token += pattern[ i + 1 ];
			i++;
			isSeparator = token === '\\s' || token === '\\-';
		} else if ( token === '[' ) {
			const end = pattern.indexOf( ']', i + 1 );
			token = pattern.slice( i, end + 1 );
			i = end;
			isSeparator = /^\[(?:[ -]|\\[s-])+\]$/.test( token );
		}

		if ( ! isSeparator ) {
			out += token;
		} else if ( /[?*+]/.test( pattern[ i + 1 ] || '' ) ) {
			i++;
		}
	}

	return out;
}

/**
 * Quote a value for a PHP single quoted string.
 *
 * @param {string} value Value to quote.
 * @return {string} Quoted PHP literal.
 */
function toPhpString( value ) {
	return `'${ value.replaceAll( '\\', '\\\\' ).replaceAll( "'", "\\'" ) }'`;
}

/**
 * Read the rules from the current output file.
 *
 * @return {Object} Rules keyed by country code.
 */
function readCurrentRules() {
	if ( ! fs.existsSync( OUTPUT_PATH ) ) {
		return {};
	}

	const rules = {};
	const source = fs.readFileSync( OUTPUT_PATH, 'utf8' );
	for ( const [ , countryCode, literal ] of source.matchAll(
		/^\t'([A-Z]{2})' => '((?:[^'\\]|\\.)*)',$/gm
	) ) {
		rules[ countryCode ] = literal.replace( /\\(['\\])/g, '$1' );
	}

	return rules;
}

async function generate() {
	const sourceData = await fetchGoogleData();

	const unknownOverrides = [
		...Object.keys( COMPATIBILITY_OVERRIDES ),
		...Object.keys( LEGACY_PREFIXES ),
		...Object.keys( LEGACY_FORMATS ),
	].filter( ( countryCode ) => ! sourceData[ countryCode ] );
	if ( unknownOverrides.length ) {
		throw new Error(
			`Google has no postcode rule to override for ${ unknownOverrides.join(
				', '
			) }`
		);
	}

	const rules = {};
	for ( const countryCode of Object.keys( sourceData ) ) {
		const { zip, examples, prefix } = sourceData[ countryCode ];
		const upstream = stripSeparators( zip );
		const override = COMPATIBILITY_OVERRIDES[ countryCode ];

		if ( override === upstream ) {
			throw new Error(
				`The ${ countryCode } override matches Google; remove it`
			);
		}

		// Google lists the prefix that goes before the postcode on an envelope,
		// such as "L-" for Luxembourg. Shoppers may type it, so accept it.
		const postalPrefix = prefix.replace( /[\s-]/g, '' );
		if ( ! /^[A-Z]*$/.test( postalPrefix ) ) {
			throw new Error(
				`Unsupported postal prefix ${ prefix } for ${ countryCode }`
			);
		}

		const legacyPrefix = LEGACY_PREFIXES[ countryCode ];
		if ( postalPrefix && legacyPrefix ) {
			throw new Error(
				`Google now lists a postal prefix for ${ countryCode }; remove the legacy prefix`
			);
		}

		const legacyFormat = LEGACY_FORMATS[ countryCode ];
		const rule = legacyFormat
			? `${ override ?? upstream }|${ legacyFormat }`
			: override ?? upstream;
		const anyPrefix = postalPrefix || legacyPrefix;
		const pattern = anyPrefix
			? `(?:${ anyPrefix })?${
					rule.includes( '|' ) ? `(?:${ rule })` : rule
			  }`
			: rule;

		// PHP wraps the rule in ~ delimiters.
		if ( pattern.includes( '~' ) ) {
			throw new Error( `Unsupported delimiter in ${ countryCode } rule` );
		}

		// Catch malformed rules and examples that fail after normalization.
		const regex = new RegExp( `^(?:${ pattern })$`, 'i' );
		for ( const example of examples.split( ',' ).filter( Boolean ) ) {
			const normalized = example.replace( /[\s-]/g, '' );
			if (
				! regex.test( normalized ) ||
				! regex.test( postalPrefix + normalized )
			) {
				throw new Error(
					`Google example ${ example } does not match ${ countryCode } rule`
				);
			}
		}

		rules[ countryCode ] = pattern;
	}

	// Google's data has no versions, so list every change for the reviewer.
	const currentRules = readCurrentRules();
	for ( const countryCode of new Set( [
		...Object.keys( currentRules ),
		...Object.keys( rules ),
	] ) ) {
		if ( currentRules[ countryCode ] !== rules[ countryCode ] ) {
			process.stdout.write(
				`${ countryCode }: ${
					currentRules[ countryCode ] ?? '(none)'
				} -> ${ rules[ countryCode ] ?? '(none)' }\n`
			);
		}
	}

	const phpEntries = Object.keys( rules )
		.map(
			( countryCode ) =>
				`\t'${ countryCode }' => ${ toPhpString(
					rules[ countryCode ]
				) },`
		)
		.join( '\n' );

	const php = `<?php
/**
 * Postcode validation rules.
 *
 * Google libaddressinput data, CC BY 4.0. WooCommerce removes separators,
 * accepts Google's postal prefixes and keeps some older postcode formats.
 * Source and license:
 * ${ DATA_URL }
 * https://creativecommons.org/licenses/by/4.0/
 *
 * Do not edit this file by hand. Run \`pnpm --filter @woocommerce/block-library generate:postcode-validation\`
 * to rebuild it. To change a rule on a store, use the \`woocommerce_postcode_validation_rules\` filter.
 *
 * @package WooCommerce\\i18n
 */

defined( 'ABSPATH' ) || exit;

return array(
${ phpEntries }
);
`;

	fs.writeFileSync( OUTPUT_PATH, php );
}

generate().catch( ( error ) => {
	process.stderr.write( `${ error.stack || error }\n` );
	process.exitCode = 1;
} );
