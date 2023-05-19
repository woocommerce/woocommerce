/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DefaultItem } from '../types';

type useListboxProps< Item > = {
	multiple: boolean;
	options: Item[];
};

export function useListbox< Item = DefaultItem >( {
	multiple,
	options,
}: useListboxProps< Item > ) {
	const [ highlightedIndex, setHighlightedIndex ] = useState< number | null >(
		null
	);
	const [ isOpen, setIsOpen ] = useState< boolean >( false );

	useEffect( () => {
		setHighlightedIndex( null );
	}, [ options ] );

	function highlightNextOption() {
		if ( highlightedIndex === null ) {
			setHighlightedIndex( 0 );
			return;
		}
		const next = highlightedIndex + 1;
		setHighlightedIndex( next > options.length - 1 ? 0 : next );
	}

	function highlightPreviousOption() {
		if ( highlightedIndex === null ) {
			setHighlightedIndex( options.length - 1 );
			return;
		}
		const previous = highlightedIndex - 1;
		setHighlightedIndex( previous < 0 ? options.length - 1 : previous );
	}

	return {
		close: () => setIsOpen( false ),
		highlightedIndex,
		highlightedOption:
			highlightedIndex !== null ? options[ highlightedIndex ] : null,
		highlightNextOption,
		highlightPreviousOption,
		isOpen,
		open: () => setIsOpen( true ),
		props: {
			'aria-multiselectable': multiple ? 'true' : false,
			role: 'listbox',
			tabindex: '-1',
		},
	};
}
