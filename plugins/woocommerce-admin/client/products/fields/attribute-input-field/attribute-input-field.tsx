/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Spinner, Icon } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	QueryProductAttribute,
	ProductAttribute,
	WCDataSelector,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './attribute-input-field.scss';
import { EnhancedProductAttribute } from '~/products/hooks/use-product-attributes';

type NarrowedQueryAttribute = Pick< QueryProductAttribute, 'id' | 'name' >;

type AttributeInputFieldProps = {
	value?: EnhancedProductAttribute | null;
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
	};

	return (
		<SelectControl< NarrowedQueryAttribute >
			className="woocommerce-attribute-input-field"
			items={ attributes || [] }
			label={ label || '' }
			disabled={ disabled }
			getFilteredItems={ getFilteredItems }
			placeholder={ placeholder }
			getItemLabel={ ( item ) => item?.name || '' }
			getItemValue={ ( item ) => item?.id || '' }
			selected={ value }
			onSelect={ ( attribute ) => {
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
			} }
			onRemove={ () => onChange() }
			__experimentalOpenMenuOnFocus
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
