/**
 * External dependencies
 */
import { store as coreStore, useEntityRecord } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

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
			const {
				record: storeProductsSettings,
				isResolving: storeProductsSettingsResolving,
			} = useEntityRecord< SettingsEntityRecord >(
				'root',
				'settings',
				'products'
			);

			const parentDimension = useSelect(
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
					return parent?.dimensions?.[ key ];
				},
				[ data.parent_id ]
			);

			if ( storeProductsSettingsResolving ) {
				return null;
			}

			const dimensionUnit =
				storeProductsSettings?.values?.woocommerce_dimension_unit;

			const variationDimension = data.dimensions?.[ key ];
			const isInheritedFromParent =
				Boolean( parentDimension ) &&
				variationDimension === parentDimension;
			const displayValue = isInheritedFromParent
				? ''
				: variationDimension ?? '';

			return (
				<InputControl
					label={ field.label }
					value={ displayValue }
					placeholder={ parentDimension || undefined }
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
