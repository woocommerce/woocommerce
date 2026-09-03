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
	/** Typed index of each display character, -1 for a literal the mask inserted. */
	map: number[];
}

const parseMask = ( mask: string ): Token[] => {
	const tokens: Token[] = [];
	for ( let i = 0; i < mask.length; i++ ) {
		if ( mask[ i ] === '\\' && i + 1 < mask.length ) {
			tokens.push( { literal: mask[ ++i ] } );
		} else if ( SLOTS[ mask[ i ] ] ) {
			tokens.push( { test: SLOTS[ mask[ i ] ] } );
		} else {
			tokens.push( { literal: mask[ i ] } );
		}
	}
	return tokens;
};

export const unescapeMask = ( mask: string ): string =>
	mask.replace( /\\(.)/g, '$1' );

/**
 * Formats the typed text against the mask.
 *
 * A slot takes the next typed character when it matches. A literal takes the next typed
 * character when it is the same. Otherwise the mask inserts the literal once a later slot
 * fills or the mask ends.
 */
export const format = ( typed: string, mask: string ): FormatResult => {
	const map: number[] = [];
	let display = '';
	let unmasked = '';
	let pending = '';
	let t = 0;

	const flush = () => {
		for ( const char of pending ) {
			display += char;
			map.push( -1 );
		}
		pending = '';
	};

	for ( const token of parseMask( mask ) ) {
		if ( 'literal' in token ) {
			if ( typed[ t ] === token.literal ) {
				flush();
				display += token.literal;
				map.push( t++ );
			} else {
				pending += token.literal;
			}
		} else if ( t >= typed.length ) {
			pending = '';
			break;
		} else if ( ! token.test.test( typed[ t ] ) ) {
			break;
		} else {
			flush();
			display += typed[ t ];
			unmasked += typed[ t ];
			map.push( t++ );
		}
	}

	if ( t < typed.length ) {
		return {
			display: typed,
			unmasked: typed,
			fits: false,
			map: Array.from( typed, ( _, i ) => i ),
		};
	}
	flush();
	return { display, unmasked, fits: true, map };
};
