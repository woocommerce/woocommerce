/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { ComboboxControl } from '@wordpress/components';
import { createElement, useMemo, useState } from '@wordpress/element';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	ProductAttributesActions,
	WPDataActions,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';
import type {
	AttributeComboboxProps,
	ComboboxAttributeProps,
	NarrowedQueryAttribute,
} from './types';

function mapAttributeToComboboxOption(
	attr: NarrowedQueryAttribute
): ComboboxAttributeProps {
	return {
		label: attr.name,
		value: `attr-${ attr.id }`,
	};
}

const AttributeCombobox: React.FC< AttributeComboboxProps > = ( {
	currentItem = null,
	items = [],
	createNewAttributesAsGlobal = false,
	onChange,
} ) => {
	const { createErrorNotice } = useDispatch( 'core/notices' );
	const { createProductAttribute, invalidateResolution } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	) as unknown as ProductAttributesActions & WPDataActions;

	const [ createOption, updateCreateOption ] =
		useState< ComboboxAttributeProps >( {
			label: '',
			value: '',
		} );

	const clearCreateOption = () => {
		updateCreateOption( {
			label: '',
			value: '',
		} );
	};

	/**
	 * Map the items to the Combobox options.
	 * Each option is an object with a label and value.
	 * Both are strings.
	 */
	const attributeOptions: ComboboxAttributeProps[] = items?.map(
		mapAttributeToComboboxOption
	);

	const options = useMemo( () => {
		if ( ! createOption.label.length ) {
			// Populate the items with the current item if it exists.
			if ( currentItem ) {
				return [
					...attributeOptions,
					mapAttributeToComboboxOption( currentItem ),
				].sort( ( a, b ) => a.label.localeCompare( b.label ) );
			}

			return attributeOptions;
		}

		// Populate the items with the "Create..." option if the user is typing.
		return [
			...attributeOptions,
			{
				label: sprintf(
					/* translators: The name of the new attribute term to be created */
					__( 'Create "%s"', 'woocommerce' ),
					createOption.label
				),
				value: createOption.value,
			},
		];
	}, [ currentItem, attributeOptions, createOption ] );

	const currentValue = currentItem ? `attr-${ currentItem.id }` : '';

	const addNewAttribute = ( name: string ) => {
		recordEvent( 'product_attribute_add_custom_attribute', {
			source: TRACKS_SOURCE,
		} );
		if ( createNewAttributesAsGlobal ) {
			createProductAttribute( {
				name,
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
					clearCreateOption();
				}
			);
		} else {
			onChange( name );
		}
	};

	return (
		<ComboboxControl
			className="woocommerce-attribute-input-field__combobox"
			allowReset={ false }
			options={ options }
			value={ currentValue }
			onChange={ ( newValue ) => {
				if ( ! newValue ) {
					return;
				}

				if ( newValue === 'attribute-create' ) {
					addNewAttribute( createOption.label );
					return;
				}

				const selectedAttribute = items?.find(
					( item ) =>
						item.id === Number( newValue.replace( 'attr-', '' ) )
				);

				if ( ! selectedAttribute ) {
					return;
				}

				onChange( {
					id: selectedAttribute.id,
					name: selectedAttribute.name,
					slug: selectedAttribute.slug as string,
					options: [],
				} );
			} }
			onFilterValueChange={ ( filterValue: string ) => {
				updateCreateOption( {
					label: filterValue,
					value: 'attribute-create',
				} );
			} }
		/>
	);
};

export default AttributeCombobox;
