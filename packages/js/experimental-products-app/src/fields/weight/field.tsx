/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEntityRecord } from '@wordpress/core-data';
import { InputControl, InputLayout } from '@wordpress/ui';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */

import type { ProductEntityRecord, SettingsEntityRecord } from '../types';

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
	Edit: ( { data, onChange, field } ) => {
		const {
			record: storeProductsSettings,
			isResolving: storeProductsSettingsResolving,
		} = useEntityRecord< SettingsEntityRecord >(
			'root',
			'settings',
			'products'
		);

		if ( storeProductsSettingsResolving ) {
			return null;
		}

		const weightUnit =
			storeProductsSettings?.values?.woocommerce_weight_unit;

		return (
			<InputControl
				label={ field.label }
				value={ data.weight }
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
