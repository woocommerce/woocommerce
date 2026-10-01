const SLOTS: Record< string, RegExp > = {
	0: /[0-9]/,
	a: /\p{L}/u,
	'*': /[\s\S]/,
};

type Token = { test: RegExp } | { literal: string };

export interface FormatResult {
	display: string;
	unmasked: string;
	/** False when the typed text does not fit the mask. Display and unmasked are then the typed text. */
	fits: boolean;
	/** UTF-16 typed offset for each display code unit, -1 for an inserted literal. */
	map: number[];
}

const parseMask = ( mask: string ): Token[] => {
	const tokens: Token[] = [];
	const chars = Array.from( mask );
	for ( let i = 0; i < chars.length; i++ ) {
		if ( chars[ i ] === '\\' && i + 1 < chars.length ) {
			tokens.push( { literal: chars[ ++i ] } );
		} else if ( SLOTS[ chars[ i ] ] ) {
			tokens.push( { test: SLOTS[ chars[ i ] ] } );
		} else {
			tokens.push( { literal: chars[ i ] } );
		}
	}
	return tokens;
};

export const unescapeMask = ( mask: string ): string =>
	mask.replace( /\\([\s\S])/gu, '$1' );

/**
 * Formats edits while keeping stored slot values distinct from user-typed literals.
 */
export const formatValue = (
	typed: string,
	mask: string,
	canConsumeLiteral: ( index: number ) => boolean
): FormatResult => {
	const map: number[] = [];
	let display = '';
	let unmasked = '';
	let pending = '';
	let t = 0;

	const flush = () => {
		display += pending;
		for ( let i = 0; i < pending.length; i++ ) {
			map.push( -1 );
		}
		pending = '';
	};

	for ( const token of parseMask( mask ) ) {
		const codePoint = typed.codePointAt( t );
		const char =
			codePoint === undefined ? '' : String.fromCodePoint( codePoint );
		if ( 'literal' in token ) {
			if ( char === token.literal && canConsumeLiteral( t ) ) {
				flush();
				display += token.literal;
				for ( let i = 0; i < char.length; i++ ) {
					map.push( t++ );
				}
			} else {
				pending += token.literal;
			}
		} else if ( t >= typed.length ) {
			pending = '';
			break;
		} else if ( ! token.test.test( char ) ) {
			break;
		} else {
			flush();
			display += char;
			unmasked += char;
			for ( let i = 0; i < char.length; i++ ) {
				map.push( t++ );
			}
		}
	}

	if ( t < typed.length ) {
		return {
			display: typed,
			unmasked: typed,
			fits: false,
			map: Array.from( { length: typed.length }, ( _, i ) => i ),
		};
	}
	flush();
	return { display, unmasked, fits: true, map };
};

/**
 * Formats raw slot values. Mask literals never consume a stored character.
 */
export const format = ( value: string, mask: string ): FormatResult =>
	formatValue( value, mask, () => false );
