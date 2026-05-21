/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

import type { Field } from '@wordpress/dataviews';
import { InputControl, InputLayout } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

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
			const {
				record: storeProductsSettings,
				isResolving: storeProductsSettingsResolving,
			} = useSelect( ( select ) => {
				const coreSelect = select( coreStore );

				return {
					record: coreSelect.getEntityRecord(
						'root',
						'settings',
						'products'
					),
					isResolving: coreSelect.isResolving( 'getEntityRecord', [
						'root',
						'settings',
						'products',
					] ),
				};
			}, [] );

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
