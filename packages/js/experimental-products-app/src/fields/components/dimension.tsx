/**
 * External dependencies
 */
import { useEntityRecord } from '@wordpress/core-data';

import type { Field } from '@wordpress/dataviews';
import { InputControl, InputLayout } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord, SettingsEntityRecord } from '../types';

export type DimensionKey = 'height' | 'width' | 'length';

export const createDimensionField = (
	key: DimensionKey
): Partial< Field< ProductEntityRecord > > => {
	return {
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

			const dimensionUnit =
				storeProductsSettings?.values?.woocommerce_dimension_unit;

			return (
				<InputControl
					label={ field.label }
					value={ data.dimensions[ key ] }
					onChange={ ( event ) => {
						onChange( {
							dimensions: {
								...data.dimensions,
								[ key ]: event.target.value,
							},
						} );
					} }
					type="number"
					min={ 0 }
					step="any"
					suffix={
						<InputLayout.Slot padding="minimal">
							{ dimensionUnit }
						</InputLayout.Slot>
					}
				/>
			);
		},
	};
};
