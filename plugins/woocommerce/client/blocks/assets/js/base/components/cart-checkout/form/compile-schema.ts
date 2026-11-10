/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type Ajv from 'ajv';
import type { ErrorObject } from 'ajv';

/**
 * Patterns already reported, so a re-render does not repeat the message.
 */
const reportedPatterns = new Set< string >();

/**
 * Collect the patterns in a schema, so a compile failure can name them.
 *
 * @param schema The schema to walk.
 * @return The patterns found.
 */
const collectPatterns = ( schema: unknown ): string[] => {
	if ( ! schema || typeof schema !== 'object' || Array.isArray( schema ) ) {
		return [];
	}

	return Object.entries( schema as Record< string, unknown > ).flatMap(
		( [ key, value ] ) => {
			if ( key === 'pattern' ) {
				return typeof value === 'string' ? [ value ] : [];
			}

			if ( ! value || typeof value !== 'object' ) {
				return [];
			}

			// Only schema keywords contain rules; const, enum and annotations hold data.
			switch ( key ) {
				case 'patternProperties':
					return [
						...Object.keys( value ),
						...Object.values( value ).flatMap( collectPatterns ),
					];
				case 'properties':
				case 'definitions':
				case '$defs':
				case 'dependencies':
				case 'allOf':
				case 'anyOf':
				case 'oneOf':
					return Object.values( value ).flatMap( collectPatterns );
				case 'items':
					return Array.isArray( value )
						? value.flatMap( collectPatterns )
						: collectPatterns( value );
				case 'additionalItems':
				case 'additionalProperties':
				case 'contains':
				case 'propertyNames':
				case 'not':
				case 'if':
				case 'then':
				case 'else':
					return collectPatterns( value );
				default:
					return [];
			}
		}
	);
};

/**
 * A compiled schema validator.
 *
 * AJV attaches the errors of the last run to the validator itself, which is how
 * the caller reads them.
 */
export type CompiledSchema = ( ( data: unknown ) => boolean ) & {
	errors?: ErrorObject[] | null;
};

/**
 * Whether the browser can compile every pattern in a schema.
 *
 * `pattern` values and `patternProperties` keys are compiled with the `u` flag,
 * so this is the same check AJV makes, without needing a parser to hand.
 *
 * @param schema The schema to check.
 * @return True when at least one pattern is one the browser rejects.
 */
export const hasUncompilablePattern = ( schema: unknown ): boolean =>
	collectPatterns( schema ).some( ( pattern ) => {
		try {
			new RegExp( pattern, 'u' );
			return false;
		} catch {
			return true;
		}
	} );

/**
 * Compile a schema without letting a pattern the browser rejects take the form down.
 *
 * AJV compiles `pattern` as a JavaScript `RegExp` with the `u` flag, so a
 * pattern only PHP accepts throws here. The exception would reach the block's
 * error boundary and unmount the whole section, every field with it. Skipping
 * schema validation keeps the form usable; the server still enforces the rule.
 *
 * @param parser The schema parser.
 * @param schema The schema to compile.
 * @return The compiled validator, or null when the schema does not compile.
 */
export const compileSchema = (
	parser: Ajv,
	schema: Record< string, unknown >
): CompiledSchema | null => {
	try {
		// AJV's own type says the validator may return a Promise when the schema
		// is async. None of the schemas here are, so the boolean is unwrapped.
		return parser.compile( schema as never ) as unknown as CompiledSchema;
	} catch ( error ) {
		const unseen = collectPatterns( schema ).filter(
			( pattern ) => ! reportedPatterns.has( pattern )
		);

		if ( unseen.length > 0 ) {
			unseen.forEach( ( pattern ) => reportedPatterns.add( pattern ) );

			// eslint-disable-next-line no-console
			console.error(
				__(
					'The checkout form skipped validation for a field whose pattern is not a valid JavaScript regular expression. Patterns are compiled in the browser with the `u` flag, so they must be valid ECMA-262. Offending pattern(s):',
					'woocommerce'
				),
				unseen
			);

			// eslint-disable-next-line no-console
			console.error( error );
		}

		return null;
	}
};
