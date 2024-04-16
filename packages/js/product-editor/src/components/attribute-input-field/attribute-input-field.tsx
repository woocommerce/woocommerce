/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
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
	NarrowedQueryAttribute,
	UseComboboxGetItemPropsOptions,
	UseComboboxGetMenuPropsOptions,
} from './types';

export const AttributeInputField: React.FC< AttributeInputFieldProps > = ( {
	value = null,
	attributes = [],
	isLoading,
	onChange,
	placeholder,
	label,
	disabled,
	disabledAttributeMessage,
	createNewAttributesAsGlobal = false,
} ) => {
	const { createErrorNotice } = useDispatch( 'core/notices' );
	const { createProductAttribute, invalidateResolution } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	) as unknown as ProductAttributesActions & WPDataActions;

	function isNewAttributeListItem(
		attribute: NarrowedQueryAttribute
	): boolean {
		return attribute.id === -99;
	}

	const getFilteredItems = (
		allItems: NarrowedQueryAttribute[],
		inputValue: string
	) => {
		const filteredItems = allItems.filter( ( item ) =>
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
			items={ attributes }
			label={ label || '' }
			disabled={ disabled }
			getFilteredItems={ getFilteredItems }
			placeholder={ placeholder }
			getItemLabel={ ( item ) => item?.name || '' }
			getItemValue={ ( item ) => item?.id || '' }
			selected={ value }
			onSelect={ ( attribute: NarrowedQueryAttribute ) => {
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
			}: {
				items: NarrowedQueryAttribute[];
				highlightedIndex: number;
				getItemProps: (
					options: UseComboboxGetItemPropsOptions< NarrowedQueryAttribute >
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
									) => getItemPropsType< NarrowedQueryAttribute >
								}
							/>
						) }
					</Menu>
				);
			} }
		</SelectControl>
	);
};
