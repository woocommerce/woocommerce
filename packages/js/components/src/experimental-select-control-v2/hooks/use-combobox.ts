/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { ChangeEvent, KeyboardEvent } from 'react';

/**
 * Internal dependencies
 */
import { DefaultItem } from '../types';

type useComboboxProps< Item > = {
	closeListbox: () => void;
	openListbox: () => void;
	highlightedOption: Item | null;
	highlightNextOption: () => void;
	highlightPreviousOption: () => void;
	selectItem: ( item: Item ) => void;
};

export function useCombobox< Item = DefaultItem >( {
	closeListbox,
	highlightedOption,
	highlightNextOption,
	highlightPreviousOption,
	openListbox,
	selectItem,
}: useComboboxProps< Item > ) {
	const [ value, setValue ] = useState< string >( '' );

	function onKeyDown( event: KeyboardEvent< HTMLInputElement > ) {
		switch ( event.key ) {
			case 'ArrowDown':
				openListbox();
				highlightNextOption();
				break;
			case 'ArrowUp':
				openListbox();
				highlightPreviousOption();
				break;
			case 'Enter':
				if ( highlightedOption ) {
					selectItem( highlightedOption );
				}
				break;
			case 'Escape':
				setValue( '' );
				closeListbox();
				break;
			default:
				openListbox();
		}
	}

	return {
		setValue,
		value,
		props: {
			'aria-autocomplete': 'list',
			'aria-controls': 'menu-id', //@todo
			'aria-expanded': 'true', // @todo
			'aria-haspopup': 'true',
			'aria-labelledby': 'label-id', //@todo
			onBlur: closeListbox,
			onChange: ( event: ChangeEvent< HTMLInputElement > ) =>
				setValue( event.target?.value ),
			onFocus: () => {
				openListbox();
			},
			onKeyDown,
			role: 'combobox',
			value,
		},
	};
}
