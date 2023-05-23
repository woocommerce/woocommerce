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
import { SelectedItems } from './selected-items';
import { useDropdown } from './hooks/use-dropdown';

type SelectControlProps< Item > = {
	children?: Children< Item >;
	getItemLabel?: getItemLabelType< Item >;
	getItemValue?: getItemValueType< Item >;
	initialSelected?: Item | Item[];
	label: string;
	onDeselect?: ( item: Item ) => void;
	options: Item[];
	onSelect?: ( item: Item ) => void;
	multiple?: boolean;
};

export function SelectControl< Item = DefaultItem >( {
	children,
	getItemLabel = defaultGetItemLabel,
	getItemValue = ( item: Item ) => ( item as DefaultItem ).value,
	initialSelected,
	label,
	multiple = false,
	options,
	onDeselect = () => null,
	onSelect,
}: SelectControlProps< Item > ) {
	const {
		deselectItem,
		comboboxProps,
		filteredOptions,
		getItemProps,
		isListboxOpen,
		selected,
		selectItem,
	} = useDropdown< Item >( {
		getItemLabel,
		initialSelected,
		multiple,
		onDeselect,
		onSelect,
		options,
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
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore TS complains about autocomplete despite it being a valid property. */ }
			<input type="text" { ...comboboxProps } />
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
