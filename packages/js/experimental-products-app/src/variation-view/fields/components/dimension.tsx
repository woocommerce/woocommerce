/**
 * External dependencies
 */
import { useEntityRecord, store as coreStore } from '@wordpress/core-data';
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

export const createVariationDimensionField = (
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

			const parentProduct = useSelect(
				( select ) => {
					if ( ! data.parent_id ) {
						return undefined;
					}
					return select( coreStore ).getEditedEntityRecord(
						'root',
						'product',
						data.parent_id
					) as unknown as ProductEntityRecord | undefined;
				},
				[ data.parent_id ]
			);

			if ( storeProductsSettingsResolving ) {
				return null;
			}

			const dimensionUnit =
				storeProductsSettings?.values?.woocommerce_dimension_unit;
			const parentValue = parentProduct?.dimensions?.[ key ];
			const placeholder = parentValue ? String( parentValue ) : undefined;

			return (
				<InputControl
					label={ field.label }
					value={ data.dimensions?.[ key ] ?? '' }
					placeholder={ placeholder }
					onChange={ ( event ) => {
						onChange( {
							dimensions: {
								...( data.dimensions ?? {} ),
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

export const createVariationWeightField = (): Partial<
	Field< ProductEntityRecord >
> => {
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

			const parentProduct = useSelect(
				( select ) => {
					if ( ! data.parent_id ) {
						return undefined;
					}
					return select( coreStore ).getEditedEntityRecord(
						'root',
						'product',
						data.parent_id
					) as unknown as ProductEntityRecord | undefined;
				},
				[ data.parent_id ]
			);

			if ( storeProductsSettingsResolving ) {
				return null;
			}

			const weightUnit =
				storeProductsSettings?.values?.woocommerce_weight_unit;
			const parentValue = parentProduct?.weight;
			const placeholder = parentValue ? String( parentValue ) : undefined;

			return (
				<InputControl
					label={ field.label }
					value={ data.weight }
					placeholder={ placeholder }
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
};

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

			if ( storeProductsSettingsResolving ) {
				return null;
			}

			const dimensionUnit =
				storeProductsSettings?.values?.woocommerce_dimension_unit;

			return (
				<InputControl
					label={ field.label }
					value={ data.dimensions?.[ key ] ?? '' }
					onChange={ ( event ) => {
						onChange( {
							dimensions: {
								...( data.dimensions ?? {} ),
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
