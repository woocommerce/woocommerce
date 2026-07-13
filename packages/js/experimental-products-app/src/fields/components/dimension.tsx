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

export function isDimensionVisible( item: ProductEntityRecord ) {
	const isSellableInstance =
		( item.type === 'simple' && ! item.parent_id ) ||
		( item.type === 'variable' && ! item.parent_id ) ||
		item.type === 'variation' ||
		Boolean( item.parent_id );

	return ! item.virtual && ( isSellableInstance || item.downloadable );
}

export const createDimensionField = (
	key: DimensionKey
): Partial< Field< ProductEntityRecord > > => {
	return {
		isVisible: isDimensionVisible,
		Edit: ( { data, onChange, field } ) => {
			const dimensions: Partial< ProductEntityRecord[ 'dimensions' ] > =
				data.dimensions ?? {};
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
					placeholder={ field.placeholder }
					value={ dimensions[ key ] ?? '' }
					onChange={ ( event ) => {
						onChange( {
							dimensions: {
								[ key ]: event.target.value,
							} as ProductEntityRecord[ 'dimensions' ],
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
