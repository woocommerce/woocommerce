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
import { defaultGetItemLabel } from './utils/default-get-item-label';
import { Listbox } from './listbox';
import { Option } from './option';
import { useDropdown } from './hooks/use-dropdown';
import { Combobox } from './combobox';

type SelectControlProps< Item > = {
	children?: Children< Item >;
	getItemLabel?: getItemLabelType< Item >;
	getItemValue?: getItemValueType< Item >;
	label: string;
	onDeselect?: ( item: Item ) => void;
	options: Item[];
	onSelect?: ( item: Item ) => void;
	multiple?: boolean;
	selected: Item | Item[] | null;
};

export function SelectControl< Item = DefaultItem >( {
	children,
	getItemLabel = defaultGetItemLabel,
	getItemValue = ( item: Item ) => ( item as DefaultItem ).value,
	label,
	multiple = false,
	options,
	onDeselect = () => null,
	onSelect,
	selected,
}: SelectControlProps< Item > ) {
	const {
		deselectItem,
		comboboxProps,
		filteredOptions,
		getItemProps,
		isListboxOpen,
		selectItem,
		isFocused,
	} = useDropdown< Item >( {
		getItemLabel,
		multiple,
		onDeselect,
		onSelect,
		options,
		selected,
	} );

	const isReadOnly = false;

	return (
		<div className="woocommerce-experimental-select-control">
			<label htmlFor={ '@todo' }>{ label }</label>

			<Combobox
				isFocused={ isFocused }
				selected={ selected as Item[] }
				isReadOnly={ isReadOnly }
				getItemLabel={ getItemLabel }
				getItemValue={ getItemValue }
				multiple={ multiple }
				comboboxProps={ comboboxProps }
				onRemove={ ( item ) => {
					deselectItem( item );
					onDeselect( item );
				} }
			/>
			{ children ? (
				children( {
					filteredOptions,
					isListboxOpen,
					getItemLabel,
					getItemValue,
					options,
					selectItem,
				} )
			) : (
				<Listbox isOpen={ isListboxOpen }>
					{ filteredOptions.map( ( item, index: number ) => {
						const itemProps = getItemProps( item );
						return (
							<Option
								key={ `${ getItemValue( item ) }${ index }` }
								{ ...itemProps }
							>
								{ getItemLabel( item ) }
							</Option>
						);
					} ) }
				</Listbox>
			) }
		</div>
	);
}
