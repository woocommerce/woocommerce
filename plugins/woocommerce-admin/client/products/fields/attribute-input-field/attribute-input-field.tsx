/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	QueryProductAttribute,
	ProductAttribute,
	WCDataSelector,
} from '@woocommerce/data';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';

type NarrowedQueryAttribute = Pick< QueryProductAttribute, 'id' | 'name' >;

type AttributeInputFieldProps = {
	value?: Pick< QueryProductAttribute, 'id' | 'name' > | null;
	onChange: (
		value?: Omit< ProductAttribute, 'position' | 'visible' | 'variation' >
	) => void;
	label?: string;
	placeholder?: string;
	disabled?: boolean;
	ignoredAttributeIds?: number[];
};

export const AttributeInputField: React.FC< AttributeInputFieldProps > = ( {
	value = null,
	onChange,
	placeholder,
	label,
	disabled,
	ignoredAttributeIds = [],
} ) => {
	const { attributes, isLoading } = useSelect( ( select: WCDataSelector ) => {
		const { getProductAttributes, hasFinishedResolution } = select(
			EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
		);
		return {
			isLoading: ! hasFinishedResolution( 'getProductAttributes' ),
			attributes: getProductAttributes(),
		};
	} );

	const getFilteredItems = (
		allItems: NarrowedQueryAttribute[],
		inputValue: string
	) => {
		const ignoreIdsFilter = ( item: NarrowedQueryAttribute ) =>
			ignoredAttributeIds.length
				? ! ignoredAttributeIds.includes( item.id )
				: true;

		return allItems.filter(
			( item ) =>
				ignoreIdsFilter( item ) &&
				( item.name || '' )
					.toLowerCase()
					.startsWith( inputValue.toLowerCase() )
		);
		if (
			inputValue.length > 0 &&
			! filteredItems.find(
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
	};

	return (
		<SelectControl< NarrowedQueryAttribute >
			items={ attributes || [] }
			label={ label || '' }
			disabled={ disabled }
			getFilteredItems={ getFilteredItems }
			placeholder={ placeholder }
			getItemLabel={ ( item ) => item?.name || '' }
			getItemValue={ ( item ) => item?.id || '' }
			selected={ value }
			onSelect={ ( attribute ) => { {
				if ( attribute.id === -99 ) {
					return;
				}
				onChange( {
					id: attribute.id,
					name: attribute.name,
					options: [],
				} );
			} }
			onRemove={ () => onChange() }
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
									{ index === -99
										? sprintf(
												__(
													'Create "%s"',
													'woocommerce'
												),
												item.name
										  )
										: item.name }
								</MenuItem>
							) )
						) }
					</Menu>
				);
			} }
		</SelectControl>
	);
};
