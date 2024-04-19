/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { ComboboxControl as CoreComboboxControl } from '@wordpress/components';
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
		disabled: !! attr.isDisabled,
	};
}

const temporaryOptionInitialState: ComboboxAttributeProps = {
	label: '',
	value: '',
	state: 'draft',
};

interface ComboboxControlProps extends CoreComboboxControl.Props {
	__experimentalRenderItem?: ( args: {
		item: ComboboxAttributeProps;
	} ) => string | JSX.Element;
}

const ComboboxControl =
	CoreComboboxControl as React.ComponentType< ComboboxControlProps >;

function ComboboxControlItem( { item }: { item: ComboboxAttributeProps } ) {
	if ( item.disabled ) {
		return <div className="item-wrapper is-disabled">{ item.label }</div>;
	}

	return <div className="item-wrapper">{ item.label }</div>;
}

const AttributeCombobox: React.FC< AttributeComboboxProps > = ( {
	currentItem = null,
	items = [],
	createNewAttributesAsGlobal = false,
	onChange,
} ) => {
	const { createErrorNotice } = useDispatch( 'core/notices' );
	const { createProductAttribute } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	) as unknown as ProductAttributesActions & WPDataActions;

	const [ temporaryOption, updateCreateOption ] =
		useState< ComboboxAttributeProps >( temporaryOptionInitialState );

	const clearCreateOption = () =>
		updateCreateOption( temporaryOptionInitialState );

	/**
	 * Map the items to the Combobox options.
	 * Each option is an object with a label and value.
	 * Both are strings.
	 */
	const attributeOptions: ComboboxAttributeProps[] = items?.map(
		mapAttributeToComboboxOption
	);

	const options = useMemo( () => {
		if ( ! temporaryOption.label.length ) {
			return attributeOptions;
		}

		return [
			...attributeOptions,
			{
				label:
					temporaryOption.state === 'draft'
						? sprintf(
								/* translators: The name of the new attribute term to be created */
								__( 'Create "%s"', 'woocommerce' ),
								temporaryOption.label
						  )
						: temporaryOption.label,
				value: temporaryOption.value,
			},
		];
	}, [ attributeOptions, temporaryOption ] );

	let currentValue =
		temporaryOption.state === 'creating' ? 'create-attribute' : '';

	if ( currentItem ) {
		currentValue = `attr-${ currentItem.id }`;
	}
	const addNewAttribute = ( name: string ) => {
		recordEvent( 'product_attribute_add_custom_attribute', {
			source: TRACKS_SOURCE,
		} );
		if ( createNewAttributesAsGlobal ) {
			createProductAttribute(
				{
					name,
					generate_slug: true,
				},
				{
					optimisticQueryUpdate: {
						order_by: 'name',
					},
				}
			).then(
				( newAttr ) => {
					onChange( { ...newAttr, options: [] } );
					clearCreateOption();
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
			className="woocommerce-attribute-combobox"
			allowReset={ false }
			options={ options }
			value={ currentValue }
			onChange={ ( newValue ) => {
				if ( ! newValue ) {
					return;
				}

				if ( newValue === 'create-attribute' ) {
					updateCreateOption( {
						...temporaryOption,
						state: 'creating',
					} );
					addNewAttribute( temporaryOption.label );
					return;
				}

				const selectedAttribute = items?.find(
					( item ) =>
						item.id === Number( newValue.replace( 'attr-', '' ) )
				);

				// Do not select when it is disabled.
				if ( ! selectedAttribute || selectedAttribute.isDisabled ) {
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
					value: 'create-attribute',
					state: 'draft',
				} );
			} }
			__experimentalRenderItem={ ComboboxControlItem }
		/>
	);
};

export default AttributeCombobox;
