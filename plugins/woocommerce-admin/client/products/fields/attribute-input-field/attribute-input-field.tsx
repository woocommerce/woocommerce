/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { resolveSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { Spinner, Icon } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	QueryProductAttribute,
	ProductAttribute,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	SelectControlProps,
	useAsyncFilter,
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './attribute-input-field.scss';
import { HydratedAttributeType } from '../attribute-field';

type NarrowedQueryAttribute = Pick< QueryProductAttribute, 'id' | 'name' >;

type AttributeInputFieldProps = {
	value?: HydratedAttributeType | null;
	onChange: (
		value?:
			| Omit< ProductAttribute, 'position' | 'visible' | 'variation' >
			| string
	) => void;
	label?: string;
	placeholder?: string;
	disabled?: boolean;
	ignoredAttributeIds?: number[];
};

function isNewAttributeListItem( attribute: NarrowedQueryAttribute ): boolean {
	return attribute.id === -99;
}

/**
 * Add a new item at the end of the list if the
 * current set of items do not match exactly the
 * input's value
 *
 * @param allItems The item list
 * @param inputValue The input's value
 * @param ignoredAttributeIds The item ids to ignore
 * @return The `allItems` + 1 new item if condition is met
 */
function addCreateNewAttributeItem(
	allItems: NarrowedQueryAttribute[] = [],
	inputValue: string,
	ignoredAttributeIds: number[]
) {
	const ignoreIdsFilter = ( item: NarrowedQueryAttribute ) =>
		ignoredAttributeIds.length
			? ! ignoredAttributeIds.includes( item.id )
			: true;

	const filteredItems = allItems.filter(
		( item ) =>
			ignoreIdsFilter( item ) &&
			( item.name || '' )
				.toLowerCase()
				.startsWith( inputValue.toLowerCase() )
	);

	if (
		inputValue.length > 0 &&
		! allItems.find(
			( item ) => item.name.toLowerCase() === inputValue.toLowerCase()
		)
	) {
		return [
			...filteredItems,
			{
				id: -99,
				name: inputValue,
			},
		];
	}

	return filteredItems;
}

export const AttributeInputField: React.FC< AttributeInputFieldProps > = ( {
	value = null,
	onChange,
	placeholder,
	label,
	disabled,
	ignoredAttributeIds = [],
} ) => {
	const [ items, setItems ] = useState< NarrowedQueryAttribute[] >( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	const filter = useCallback(
		async ( searchValue = '' ) => {
			return resolveSelect( EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME )
				.getProductAttributes< NarrowedQueryAttribute[] >()
				.then( ( attributes ) => {
					return addCreateNewAttributeItem(
						attributes,
						searchValue,
						ignoredAttributeIds
					);
				} );
		},
		[ ignoredAttributeIds ]
	);

	useEffect( () => {
		setIsLoading( true );
		filter()
			.then( setItems )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	const selectProps: SelectControlProps< NarrowedQueryAttribute > = {
		className: 'woocommerce-attribute-input-field',
		items,
		label: label || '',
		disabled,
		placeholder,
		getItemLabel: ( item: NarrowedQueryAttribute | null ) =>
			item?.name || '',
		getItemValue: ( item: NarrowedQueryAttribute | null ) => item?.id || '',
		selected: value,
		onSelect: ( attribute: NarrowedQueryAttribute ) => {
			if ( isNewAttributeListItem( attribute ) ) {
				recordEvent( 'product_attribute_add_custom_attribute', {
					new_product_page: true,
				} );
			}
			onChange(
				isNewAttributeListItem( attribute )
					? attribute.name
					: {
							id: attribute.id,
							name: attribute.name,
							options: [],
					  }
			);
		},
		onRemove: () => onChange(),
		__experimentalOpenMenuOnFocus: true,
	};

	const selectPropsWithSyncFilter = useAsyncFilter< NarrowedQueryAttribute >(
		{
			...selectProps,
			filter,
			onFilterStart() {
				setIsLoading( true );
				setItems( [] );
			},
			onFilterEnd( filteredItems: NarrowedQueryAttribute[] ) {
				setItems( filteredItems );
				setIsLoading( false );
			},
		}
	);

	return (
		<SelectControl< NarrowedQueryAttribute >
			{ ...selectPropsWithSyncFilter }
		>
			{ ( {
				items: renderItems,
				highlightedIndex,
				getItemProps,
				getMenuProps,
				isOpen,
			} ) => {
				return (
					<Menu getMenuProps={ getMenuProps } isOpen={ isOpen }>
						{ isLoading ? (
							<Spinner />
						) : (
							renderItems.map( ( item, index: number ) => (
								<MenuItem
									key={ item.id }
									index={ index }
									isActive={ highlightedIndex === index }
									item={ item }
									getItemProps={ getItemProps }
								>
									{ isNewAttributeListItem( item ) ? (
										<div className="woocommerce-attribute-input-field__add-new">
											<Icon
												icon={ plus }
												size={ 20 }
												className="woocommerce-attribute-input-field__add-new-icon"
											/>
											<span>
												{ sprintf(
													/* translators: The name of the new attribute term to be created */
													__(
														'Create "%s"',
														'woocommerce'
													),
													item.name
												) }
											</span>
										</div>
									) : (
										item.name
									) }
								</MenuItem>
							) )
						) }
					</Menu>
				);
			} }
		</SelectControl>
	);
};
