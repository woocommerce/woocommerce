/**
 * Internal dependencies
 */
import { format, formatValue, type FormatResult } from './format';

export interface BindOptions {
	mask: string;
	onChange?: ( unmasked: string, result: FormatResult ) => void;
}

export interface Bound {
	/** Replaces the input with raw slot values. */
	setValue: ( value: string ) => void;
	destroy: () => void;
}

/**
 * Masks an input as the user types. The input shows the formatted text and `onChange`
 * receives the unmasked value. Text that does not fit the mask stays as typed.
 */
export const bind = (
	input: HTMLInputElement,
	{ mask, onChange }: BindOptions
): Bound => {
	let typed = '';
	let literalPositions: boolean[] = [];
	let result = format( '', mask );

	const typedBefore = ( displayIndex: number ) =>
		result.map.slice( 0, displayIndex ).filter( ( i ) => i >= 0 ).length;

	const displayCaret = ( typedIndex: number ) => {
		const index = result.map.findIndex( ( i ) => i >= typedIndex );
		return index === -1 ? result.display.length : index;
	};

	const render = (
		nextTyped: string,
		caret: number | null,
		nextLiteralPositions: boolean[]
	) => {
		typed = nextTyped;
		literalPositions = nextLiteralPositions;
		result = formatValue(
			typed,
			mask,
			( index ) => literalPositions[ index ]
		);
		if ( input.value !== result.display ) {
			input.value = result.display;
		}
		if ( caret !== null && input.selectionStart !== null ) {
			const position = displayCaret( caret );
			input.setSelectionRange( position, position );
		}
	};

	const onInput = ( event: Event ) => {
		if ( ( event as InputEvent ).isComposing ) {
			return;
		}
		const previous = result.display;
		const value = input.value;
		const caret = input.selectionStart ?? value.length;

		let start = 0;
		while (
			start < caret &&
			start < previous.length &&
			previous[ start ] === value[ start ]
		) {
			start++;
		}
		const inserted = value.slice( start, caret );
		const end = previous.length - ( value.length - caret );

		let from = typedBefore( start );
		let to = typedBefore( Math.max( start, end ) );
		if ( ! inserted && from === to && end > start ) {
			// Only inserted literals were deleted, so delete the typed character next to them too.
			if (
				( event as InputEvent ).inputType === 'deleteContentForward'
			) {
				const codePoint = typed.codePointAt( to );
				to +=
					codePoint === undefined
						? 0
						: String.fromCodePoint( codePoint ).length;
			} else {
				from -=
					Array.from( typed.slice( 0, from ) ).at( -1 )?.length ?? 0;
			}
		}

		render(
			typed.slice( 0, from ) + inserted + typed.slice( to ),
			from + inserted.length,
			[
				...literalPositions.slice( 0, from ),
				...Array< boolean >( inserted.length ).fill( true ),
				...literalPositions.slice( to ),
			]
		);
		onChange?.( result.unmasked, result );
	};

	input.addEventListener( 'input', onInput );
	input.addEventListener( 'compositionend', onInput );
	render(
		input.value,
		null,
		Array< boolean >( input.value.length ).fill( false )
	);

	return {
		setValue: ( value ) =>
			render(
				value,
				null,
				Array< boolean >( value.length ).fill( false )
			),
		destroy: () => {
			input.removeEventListener( 'input', onInput );
			input.removeEventListener( 'compositionend', onInput );
		},
	};
};
