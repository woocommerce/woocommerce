/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { CheckboxControl, Icon } from '@wordpress/components';
import { useCallback, useState } from '@wordpress/element';
import { plus } from '@wordpress/icons';
import {
	SelectControlProps,
	useSyncFilter,
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './attribute-term-input-field.scss';

type CustomAttributeTermInputFieldProps = {
	value?: string[];
	onChange: ( value: string[] ) => void;
	placeholder?: string;
	label?: string;
	disabled?: boolean;
};

type NewTermItem = {
	id: string;
	label: string;
};

function isNewTermItem(
	item: NewTermItem | string | null
): item is NewTermItem {
	return item !== null && typeof item === 'object' && !! item.label;
}

/**
 * Add a new item at the end of the list if the
 * current set of items do not match exactly the
 * input's value
 *
 * @param allItems The item list
 * @param inputValue The input's value
 * @return The `allItems` + 1 new item if condition is met
 */
function addCreateNewAttributeTermItem(
	allItems: ( string | NewTermItem )[],
	inputValue: string
) {
	const filteredItems = allItems.filter(
		( item ) =>
			! inputValue.length ||
			( ! isNewTermItem( item ) &&
				item.toLowerCase().includes( inputValue.toLowerCase() ) )
	);
	if (
		inputValue.length > 0 &&
		! filteredItems.find(
			( item ) =>
				! isNewTermItem( item ) &&
				item.toLowerCase() === inputValue.toLowerCase()
		)
	) {
		return [
			...filteredItems,
			{
				id: 'is-new',
				label: inputValue,
			},
		];
	}
	return filteredItems;
}

export const CustomAttributeTermInputField: React.FC<
	CustomAttributeTermInputFieldProps
> = ( { value = [], onChange, placeholder, disabled, label } ) => {
	const [ listItems, setListItems ] =
		useState< Array< string | NewTermItem > >( value );

	const onRemove = ( item: string | NewTermItem ) => {
		onChange( value.filter( ( opt ) => opt !== item ) );
	};

	const onSelect = ( item: string | NewTermItem ) => {
		// Add new item.
		if ( isNewTermItem( item ) ) {
			setListItems( [ ...listItems, item.label ] );
			onChange( [ ...value, item.label ] );
			return;
		}
		const isSelected = value.includes( item );
		if ( isSelected ) {
			onRemove( item );
			return;
		}
		onChange( [ ...value, item ] );
	};

	const selectProps: SelectControlProps< string | NewTermItem > = {
		items: listItems,
		multiple: true,
		disabled,
		label: label || '',
		placeholder: placeholder || '',
		getItemLabel: ( item: string | NewTermItem | null ) =>
			isNewTermItem( item ) ? item.label : item || '',
		getItemValue: ( item: string | NewTermItem | null ) =>
			isNewTermItem( item ) ? item.id : item || '',
		selected: value,
		onSelect,
		onRemove,
		className: 'woocommerce-attribute-term-field',
	};

	const selectPropsWithSyncFilter = useSyncFilter< string | NewTermItem >( {
		...selectProps,
		filter: useCallback(
			function filter( inputValue = '' ) {
				return addCreateNewAttributeTermItem( value, inputValue );
			},
			[ value ]
		),
		onFilterEnd( filteredItems: ( string | NewTermItem )[] ) {
			setListItems( filteredItems );
		},
	} );

	return (
		<>
			<SelectControl< string | NewTermItem >
				{ ...selectPropsWithSyncFilter }
			>
				{ ( {
					items,
					highlightedIndex,
					getItemProps,
					getMenuProps,
					isOpen,
				} ) => {
					return (
						<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
							{ items.map( ( item, menuIndex ) => {
								return (
									<MenuItem
										key={ `${
											isNewTermItem( item )
												? item.id
												: item
										}` }
										index={ menuIndex }
										isActive={
											highlightedIndex === menuIndex
										}
										item={ item }
										getItemProps={ getItemProps }
									>
										{ isNewTermItem( item ) ? (
											<div className="woocommerce-attribute-term-field__add-new">
												<Icon
													icon={ plus }
													size={ 20 }
													className="woocommerce-attribute-term-field__add-new-icon"
												/>
												<span>
													{ sprintf(
														/* translators: The name of the new attribute term to be created */
														__(
															'Create "%s"',
															'woocommerce'
														),
														item.label
													) }
												</span>
											</div>
										) : (
											<CheckboxControl
												onChange={ () => null }
												checked={ value.includes(
													item
												) }
												label={
													<span
														style={ {
															fontWeight:
																value.includes(
																	item
																)
																	? 'bold'
																	: 'normal',
														} }
													>
														{ item }
													</span>
												}
											/>
										) }
									</MenuItem>
								);
							} ) }
						</Menu>
					);
				} }
			</SelectControl>
		</>
	);
};
