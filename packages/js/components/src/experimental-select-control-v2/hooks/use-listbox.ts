/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

type useListboxProps = {
	multiple: boolean;
};

export function useListbox( { multiple }: useListboxProps ) {
	const [ isOpen, setIsOpen ] = useState< boolean >( false );

	return {
		close: () => setIsOpen( false ),
		isOpen,
		open: () => setIsOpen( true ),
		props: {
			'aria-multiselectable': multiple ? 'true' : false,
			role: 'listbox',
			tabindex: '-1',
		},
	};
}
