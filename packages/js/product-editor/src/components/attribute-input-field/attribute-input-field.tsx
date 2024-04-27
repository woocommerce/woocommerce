/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { createElement, useMemo } from '@wordpress/element';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	WCDataSelector,
	ProductAttributesActions,
	WPDataActions,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';
import { MenuAttributeList } from './menu-attribute-list';
import {
	AttributeInputFieldProps,
	getItemPropsType,
	getMenuPropsType,
	AttributeInputFieldItemProps,
	UseComboboxGetItemPropsOptions,
	UseComboboxGetMenuPropsOptions,
} from './types';

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
	const { createProductAttribute } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	) as unknown as ProductAttributesActions & WPDataActions;

	const sortCriteria = { order_by: 'name' };
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const { attributes, isLoading } = useSelect( ( select: WCDataSelector ) => {
		const { getProductAttributes, hasFinishedResolution } = select(
			EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
		);
		return {
			isLoading: ! hasFinishedResolution( 'getProductAttributes', [
				sortCriteria,
			] ),
			attributes: getProductAttributes( sortCriteria ),
		};
	} );

	const markedAttributes = useMemo(
		function setDisabledAttribute() {
			return (
				attributes?.map(
					( attribute: AttributeInputFieldItemProps ) => ( {
						...attribute,
						isDisabled: disabledAttributeIds.includes(
							attribute.id
						),
					} )
				) ?? []
			);
		},
		[ attributes, disabledAttributeIds ]
	);

	function isNewAttributeListItem(
		attribute: AttributeInputFieldItemProps
	): boolean {
		return attribute.id === -99;
	}

	const getFilteredItems = (
		allItems: AttributeInputFieldItemProps[],
		inputValue: string
	) => {
		const ignoreIdsFilter = ( item: AttributeInputFieldItemProps ) =>
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

	const addNewAttribute = ( attribute: AttributeInputFieldItemProps ) => {
		recordEvent( 'product_attribute_add_custom_attribute', {
			source: TRACKS_SOURCE,
		} );
		if ( createNewAttributesAsGlobal ) {
			createProductAttribute(
				{
					name: attribute.name,
					generate_slug: true,
				},
				{
					optimisticQueryUpdate: sortCriteria,
				}
			).then(
				( newAttr ) => {
					onChange( { ...newAttr } );
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
		<SelectControl< AttributeInputFieldItemProps >
			className="woocommerce-attribute-input-field"
			items={ markedAttributes || [] }
			label={ label || '' }
			disabled={ disabled }
			getFilteredItems={ getFilteredItems }
			placeholder={ placeholder }
			getItemLabel={ ( item ) => item?.name || '' }
			getItemValue={ ( item ) => item?.id || '' }
			selected={ value }
			onSelect={ ( attribute: AttributeInputFieldItemProps ) => {
				if ( isNewAttributeListItem( attribute ) ) {
					addNewAttribute( attribute );
				} else {
					onChange( {
						id: attribute.id,
						name: attribute.name,
						slug: attribute.slug as string,
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
			}: {
				items: AttributeInputFieldItemProps[];
				highlightedIndex: number;
				getItemProps: (
					options: UseComboboxGetItemPropsOptions< AttributeInputFieldItemProps >
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
				) => any;
				getMenuProps: getMenuPropsType;
				isOpen: boolean;
			} ) => {
				return (
					<Menu getMenuProps={ getMenuProps } isOpen={ isOpen }>
						{ isLoading ? (
							<Spinner />
						) : (
							<MenuAttributeList
								renderItems={ renderItems }
								highlightedIndex={ highlightedIndex }
								disabledAttributeMessage={
									disabledAttributeMessage
								}
								getItemProps={
									getItemProps as (
										options: UseComboboxGetMenuPropsOptions
									) => getItemPropsType< AttributeInputFieldItemProps >
								}
							/>
						) }
					</Menu>
				);
			} }
		</SelectControl>
	);
};
