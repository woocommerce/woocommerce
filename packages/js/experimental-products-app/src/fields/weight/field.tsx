/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as coreStore, useEntityRecord } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { InputControl, InputLayout } from '@wordpress/ui';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */

import type { ProductEntityRecord, SettingsEntityRecord } from '../types';
import { isDimensionVisible } from '../components/dimension';

const fieldDefinition = {
	type: 'text',
	label: __( 'Weight', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	label: __( 'Weight', 'woocommerce' ),
	isVisible: isDimensionVisible,
	Edit: ( { data, onChange, field } ) => {
		const {
			record: storeProductsSettings,
			isResolving: storeProductsSettingsResolving,
		} = useEntityRecord< SettingsEntityRecord >(
			'root',
			'settings',
			'products'
		);

		const parentWeight = useSelect(
			( select ) => {
				const parentId = data.parent_id;
				if ( ! parentId ) {
					return undefined;
				}
				const parent = select( coreStore ).getEditedEntityRecord(
					'root',
					'product',
					parentId
				) as unknown as ProductEntityRecord | undefined;
				return parent?.weight;
			},
			[ data.parent_id ]
		);

		if ( storeProductsSettingsResolving ) {
			return null;
		}

		const weightUnit =
			storeProductsSettings?.values?.woocommerce_weight_unit;

		const isInheritedFromParent =
			Boolean( parentWeight ) && data.weight === parentWeight;
		const displayValue = isInheritedFromParent ? '' : data.weight ?? '';

		return (
			<InputControl
				label={ field.label }
				value={ displayValue }
				placeholder={ parentWeight || undefined }
				onChange={ ( event ) =>
					onChange( { weight: event.target.value } )
				}
				type="number"
				min={ 0 }
				step="any"
				suffix={
					<InputLayout.Slot padding="minimal">
						{ weightUnit }
					</InputLayout.Slot>
				}
			/>
		);
	},
};
