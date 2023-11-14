/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { Spinner, Icon } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import { createElement, useMemo } from '@wordpress/element';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	QueryProductAttribute,
	ProductAttribute,
	WCDataSelector,
	ProductAttributesActions,
	WPDataActions,
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
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';
import { TRACKS_SOURCE } from '../../constants';

type NarrowedQueryAttribute = Pick< QueryProductAttribute, 'id' | 'name' > & {
	slug?: string;
	isDisabled?: boolean;
};

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
	disabledAttributeIds?: number[];
	disabledAttributeMessage?: string;
	ignoredAttributeIds?: number[];
	createNewAttributesAsGlobal?: boolean;
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
	disabledAttributeIds = [],
	disabledAttributeMessage,
	ignoredAttributeIds = [],
	createNewAttributesAsGlobal = false,
} ) => {
	const { createErrorNotice } = useDispatch( 'core/notices' );
	const { createProductAttribute, invalidateResolution } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	) as ProductAttributesActions & WPDataActions;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const { attributes, isLoading } = useSelect( ( select: WCDataSelector ) => {
		const { getProductAttributes, hasFinishedResolution } = select(
			EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
		);
		return {
			isLoading: ! hasFinishedResolution( 'getProductAttributes' ),
			attributes: getProductAttributes(),
		};
	} );

	const markedAttributes = useMemo(
		function setDisabledAttribute() {
			return (
				attributes?.map( ( attribute ) => ( {
					...attribute,
					isDisabled: disabledAttributeIds.includes( attribute.id ),
				} ) ) ?? []
			);
		},
		[ attributes, disabledAttributeIds ]
	);

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
			( createNewAttributesAsGlobal ||
				! allItems.find(
					( item ) =>
						item.name.toLowerCase() === inputValue.toLowerCase()
				) )
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

	const addNewAttribute = ( attribute: NarrowedQueryAttribute ) => {
		recordEvent( 'product_attribute_add_custom_attribute', {
			source: TRACKS_SOURCE,
		} );
		if ( createNewAttributesAsGlobal ) {
			createProductAttribute( {
				name: attribute.name,
				generate_slug: true,
			} ).then(
				( newAttr ) => {
					invalidateResolution( 'getProductAttributes' );
					onChange( { ...newAttr, options: [] } );
				},
				( error ) => {
					let message = __(
						'Failed to create new attribute.',
						'woocommerce'
					);
					if ( error.code === 'woocommerce_rest_cannot_create' ) {
						message = error.message;
					}

					createErrorNotice( message, {
						explicitDismiss: true,
					} );
				}
			);
		} else {
			onChange( attribute.name );
		}
	};

	return (
		<SelectControl< NarrowedQueryAttribute >
			className="woocommerce-attribute-input-field"
			items={ markedAttributes || [] }
			label={ label || '' }
			disabled={ disabled }
			getFilteredItems={ getFilteredItems }
			placeholder={ placeholder }
			getItemLabel={ ( item ) => item?.name || '' }
			getItemValue={ ( item ) => item?.id || '' }
			selected={ value }
			onSelect={ ( attribute ) => {
				if ( isNewAttributeListItem( attribute ) ) {
					addNewAttribute( attribute );
				} else {
					onChange( {
						id: attribute.id,
						name: attribute.name,
						slug: attribute.slug as string,
						options: [],
					} );
				}
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
									getItemProps={ ( options ) => ( {
										...getItemProps( options ),
										disabled: item.isDisabled || undefined,
									} ) }
									tooltipText={
										item.isDisabled
											? disabledAttributeMessage
											: item.slug
									}
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
