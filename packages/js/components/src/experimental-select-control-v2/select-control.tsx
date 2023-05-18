/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DefaultItem, getItemLabelType, getItemValueType } from './types';
import { SelectedItems } from './selected-items';
import { useDropdown } from './hooks/use-dropdown';

type SelectControlProps< Item > = {
	getItemLabel?: getItemLabelType< Item >;
	getItemValue?: getItemValueType< Item >;
	initialSelected?: Item | Item[];
	items: Item[];
	label: string;
	onDeselect?: ( item: Item ) => void;
	onSelect?: ( item: Item ) => void;
	multiple?: boolean;
};

export function SelectControl< Item = DefaultItem >( {
	getItemLabel = ( item: Item ) => ( item as DefaultItem ).label,
	getItemValue = ( item: Item ) => ( item as DefaultItem ).value,
	initialSelected,
	items,
	label,
	multiple = false,
	onDeselect = () => null,
	onSelect,
}: SelectControlProps< Item > ) {
	const { deselectItem, inputValue, selected, selectItem } =
		useDropdown< Item >( {
			initialSelected,
			items,
			multiple,
			onDeselect,
			onSelect,
		} );

	const isReadOnly = false;

	return (
		<div className="woocommerce-experimental-select-control">
			<label htmlFor={ '@todo' }>{ label }</label>
			{ multiple && (
				<SelectedItems
					items={ selected as Item[] }
					isReadOnly={ isReadOnly }
					getItemLabel={ getItemLabel }
					getItemValue={ getItemValue }
					onRemove={ ( item ) => {
						deselectItem( item );
						onDeselect( item );
					} }
				/>
			) }
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
