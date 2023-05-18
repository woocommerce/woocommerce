/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DefaultItemType, useDropdown } from './hooks/use-dropdown';

type SelectControlProps< Item > = {
	getItemLabel?: ( item: Item ) => string;
	getItemValue?: ( item: Item ) => string | number;
	initialSelected?: Item | Item[];
	items: Item[];
	label: string;
	onDeselect?: ( item: Item ) => void;
	onSelect?: ( item: Item ) => void;
	multiple?: boolean;
};

export function SelectControl< Item = DefaultItemType >( {
	getItemLabel = ( item: Item ) => ( item as DefaultItemType ).label,
	getItemValue = ( item: Item ) => ( item as DefaultItemType ).value,
	initialSelected,
	items,
	label,
	multiple = false,
	onDeselect,
	onSelect,
}: SelectControlProps< Item > ) {
	const { inputValue, selected, selectItem } = useDropdown< Item >( {
		initialSelected,
		items,
		multiple,
		onDeselect,
		onSelect,
	} );

	return (
		<div className="woocommerce-experimental-select-control">
			<label htmlFor={ '@todo' }>{ label }</label>
			selected: { JSON.stringify( selected ) }
			<input type="text" value={ inputValue } />
			<ul>
				{ items.map( ( item ) => (
					// Keyboard events will be added in a follow-up.
					/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
					<li
						role="option"
						key={ getItemValue( item ) }
						onClick={ () => selectItem( item ) }
					>
						{ getItemLabel( item ) }
					</li>
				) ) }
			</ul>
		</div>
	);
}
