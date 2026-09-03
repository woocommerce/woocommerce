/**
 * Internal dependencies
 */
import { format, type FormatResult } from './format';

export interface BindOptions {
	mask: string;
	onChange?: ( unmasked: string, result: FormatResult ) => void;
}

export interface Bound {
	/** Replaces the typed text. Accepts raw or formatted text. */
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
	let result = format( '', mask );

	const typedBefore = ( displayIndex: number ) =>
		result.map.slice( 0, displayIndex ).filter( ( i ) => i >= 0 ).length;

	const displayCaret = ( typedIndex: number ) => {
		const index = result.map.findIndex( ( i ) => i >= typedIndex );
		return index === -1 ? result.display.length : index;
	};

	const render = ( nextTyped: string, caret: number | null ) => {
		typed = nextTyped;
		result = format( typed, mask );
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
				to++;
			} else {
				from = Math.max( 0, from - 1 );
			}
		}

		render(
			typed.slice( 0, from ) + inserted + typed.slice( to ),
			from + inserted.length
		);
		onChange?.( result.unmasked, result );
	};

	input.addEventListener( 'input', onInput );
	input.addEventListener( 'compositionend', onInput );
	render( input.value, null );

	return {
		setValue: ( value ) => render( value, null ),
		destroy: () => {
			input.removeEventListener( 'input', onInput );
			input.removeEventListener( 'compositionend', onInput );
		},
	};
};
