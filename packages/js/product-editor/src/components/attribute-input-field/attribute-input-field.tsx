/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { resolveSelect, useDispatch } from '@wordpress/data';
import { Spinner, Icon } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import { createElement, useCallback, useState } from '@wordpress/element';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	QueryProductAttribute,
	ProductAttribute,
	ProductAttributesActions,
	WPDataActions,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
	useAsyncFilter,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';
import { TRACKS_SOURCE } from '../../constants';

type NarrowedQueryAttribute = Pick< QueryProductAttribute, 'id' | 'name' > & {
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

function findLastCreatedAttribute( attributes: QueryProductAttribute[] ) {
	return [ ...attributes ].sort( ( a, b ) => a.id - b.id ).shift();
}

function generateSlugFromLastCreatedAttribute(
	value: string,
	attribute?: QueryProductAttribute
) {
	if ( ! attribute?.slug ) {
		return undefined;
	}
	const incrementalSuffix = +( attribute.slug.split( '-' )[ 1 ] || 0 ) + 1;
	return `${ value }-${ incrementalSuffix }`;
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

	const [ items, setItems ] = useState< NarrowedQueryAttribute[] >( [] );
	const [ lastCreatedAttribute, setLastCreatedAttribute ] =
		useState< QueryProductAttribute >();

	const { isFetching, ...selectProps } =
		useAsyncFilter< NarrowedQueryAttribute >( {
			async filter( inputValue: string = '' ) {
				const attributes = await resolveSelect(
					EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
				).getProductAttributes< NarrowedQueryAttribute[] >();

				const filteredByInputValue = inputValue?.trim()
					? attributes.filter( ( item ) =>
							item.name
								?.toLowerCase()
								.startsWith( inputValue.toLowerCase() )
					  )
					: attributes;

				setLastCreatedAttribute(
					findLastCreatedAttribute(
						attributes as QueryProductAttribute[]
					)
				);

				const filteredByIgnoredIds = filteredByInputValue.filter(
					( item ) =>
						ignoredAttributeIds.length
							? ! ignoredAttributeIds.includes( item.id )
							: true
				);

				const withDisabledMarks = filteredByIgnoredIds.map(
					( attribute ) => ( {
						...attribute,
						isDisabled: disabledAttributeIds.includes(
							attribute.id
						),
					} )
				);

				if ( ! inputValue?.length ) return withDisabledMarks;

				return [
					...withDisabledMarks,
					{
						id: -99,
						name: inputValue,
					},
				];
			},
			onFilterEnd( filteredItems ) {
				setItems( filteredItems );
			},
			fetchOnMount: true,
		} );

	const addNewAttribute = ( attribute: NarrowedQueryAttribute ) => {
		recordEvent( 'product_attribute_add_custom_attribute', {
			source: TRACKS_SOURCE,
		} );
		if ( createNewAttributesAsGlobal ) {
			createProductAttribute( {
				name: attribute.name,
				slug: generateSlugFromLastCreatedAttribute(
					attribute.name,
					lastCreatedAttribute
				),
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
			{ ...selectProps }
			className="woocommerce-attribute-input-field"
			items={ items }
			label={ label || '' }
			disabled={ disabled }
			placeholder={ placeholder }
			getItemLabel={ useCallback( ( item ) => item?.name || '', [] ) }
			getItemValue={ useCallback( ( item ) => item?.id || '', [] ) }
			selected={ value }
			onSelect={ ( attribute ) => {
				if ( isNewAttributeListItem( attribute ) ) {
					addNewAttribute( attribute );
				} else {
					onChange( {
						id: attribute.id,
						name: attribute.name,
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
						{ isFetching ? (
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
											: undefined
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
