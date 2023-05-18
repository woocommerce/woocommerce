/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { ChangeEvent } from 'react';

export function useCombobox() {
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
			onChange: ( event: ChangeEvent< HTMLInputElement > ) =>
				setValue( event.target?.value ),
			role: 'combobox',
			value,
		},
	};
}
