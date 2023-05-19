/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { ChangeEvent } from 'react';

type useComboboxProps = {
	closeListbox: () => void;
	openListbox: () => void;
};

export function useCombobox( { closeListbox, openListbox }: useComboboxProps ) {
	const [ value, setValue ] = useState< string >( '' );

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
			role: 'combobox',
			value,
		},
	};
}
