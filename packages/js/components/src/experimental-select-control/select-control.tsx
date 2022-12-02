/**
 * External dependencies
 */
import classnames from 'classnames';
import { search } from '@wordpress/icons';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ChildrenProps, DefaultItemType } from './types';
import { SelectedItems } from './selected-items';
import { ComboBox } from './combo-box';
import { Menu } from './menu';
import { MenuItem } from './menu-item';
import { SuffixIcon } from './suffix-icon';
import useDownshiftCombobox, {
	UseDownshiftComboboxInput,
} from './hooks/use-downshift-combobox';

export { selectControlStateChangeTypes } from './hooks/use-downshift-combobox';

export type SelectControlProps< ItemType > =
	UseDownshiftComboboxInput< ItemType >;

export function SelectControl< ItemType = DefaultItemType >(
	props: SelectControlProps< ItemType >
) {
	const {
		className,
		labelProps,
		comboBoxProps,
		inputProps,
		selectedItemsProps,
		children,
		hasExternalTags,
		suffix,
	} = useDownshiftCombobox( {
		...props,
		children: props.children ?? renderChildren,
	} );

	const selectedItemTags = props.multiple ? (
		<SelectedItems { ...selectedItemsProps } />
	) : null;

	return (
		<div
			className={ classnames(
				className,
				'woocommerce-experimental-select-control'
			) }
		>
			{ labelProps.children && (
				<label
					{ ...labelProps }
					className={ classnames(
						labelProps.className,
						'woocommerce-experimental-select-control__label'
					) }
				/>
			) }
			<ComboBox
				comboBoxProps={ comboBoxProps }
				inputProps={ {
					...inputProps,
					className: classnames(
						inputProps.className,
						'woocommerce-experimental-select-control__input'
					),
				} }
				suffix={ suffix ?? <SuffixIcon icon={ search } /> }
			>
				<>
					{ children }
					{ ! hasExternalTags && selectedItemTags }
				</>
			</ComboBox>

			{ hasExternalTags && selectedItemTags }
		</div>
	);
}

function renderChildren< T >( {
	items,
	highlightedIndex,
	getItemProps,
	getMenuProps,
	getItemValue,
	getItemLabel,
	isOpen,
}: ChildrenProps< T > ) {
	return (
		<Menu getMenuProps={ getMenuProps } isOpen={ isOpen }>
			{ items.map( ( item, index: number ) => (
				<MenuItem
					key={ `${ getItemValue( item ) }${ index }` }
					index={ index }
					isActive={ highlightedIndex === index }
					item={ item }
					getItemProps={ getItemProps }
				>
					{ getItemLabel( item ) }
				</MenuItem>
			) ) }
		</Menu>
	);
}
