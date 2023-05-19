/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	Children,
	DefaultItem,
	getItemLabelType,
	getItemValueType,
} from './types';
import { Listbox } from './listbox';
import { Option } from './option';
import { SelectedItems } from './selected-items';
import { useDropdown } from './hooks/use-dropdown';

type SelectControlProps< Item > = {
	children?: Children< Item >;
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
	children,
	getItemLabel = ( item: Item ) => ( item as DefaultItem ).label,
	getItemValue = ( item: Item ) => ( item as DefaultItem ).value,
	initialSelected,
	items,
	label,
	multiple = false,
	onDeselect = () => null,
	onSelect,
}: SelectControlProps< Item > ) {
	const { deselectItem, comboboxProps, isListboxOpen, selected, selectItem } =
		useDropdown< Item >( {
			initialSelected,
			items,
			multiple,
			onDeselect,
			onSelect,
		} );

	const isReadOnly = false;

	function isSelected( item: Item ) {
		if ( Array.isArray( selected ) ) {
			return ( selected as Item[] ).includes( item );
		}
		return selected === item;
	}

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
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore TS complains about autocomplete despite it being a valid property. */ }
			<input type="text" { ...comboboxProps } />
			{ children ? (
				children( {
					isListboxOpen,
					getItemLabel,
					getItemValue,
					items,
					selectItem,
				} )
			) : (
				<Listbox isOpen={ isListboxOpen }>
					{ items.map( ( item, index: number ) => (
						<Option
							key={ `${ getItemValue( item ) }${ index }` }
							isActive={ false }
							item={ item }
							onClick={ () => {
								if ( multiple && isSelected( item ) ) {
									deselectItem( item );
									return;
								}
								selectItem( item );
							} }
						>
							{ getItemLabel( item ) }
						</Option>
					) ) }
				</Listbox>
			) }
		</div>
	);
}
