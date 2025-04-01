/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Template } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { createElement, useMemo } from '@wordpress/element';
import { DataForm } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { useDataFormProductFields } from '../use-data-form-product-fields';

type ProductColumnProps = {
	columnTemplate: Template;
	postType: string;
	productId: number;
};

export function ProductColumn( {
	columnTemplate,
	postType,
	productId,
}: ProductColumnProps ) {
	const innerBlocks = columnTemplate[ 2 ] || [];
	const { editEntityRecord } = useDispatch( coreDataStore );
	const fields = useDataFormProductFields( innerBlocks );

	const { record, hasFinishedResolution } = useSelect(
		( select ) => {
			const {
				getEditedEntityRecord,
				hasFinishedResolution: hasFinished,
			} = select( coreDataStore );

			const args = [ 'postType', postType, productId ];
			return {
				// @ts-expect-error Type definitions are missing
				record: getEditedEntityRecord( ...args ) as Product,
				// @ts-expect-error Type definitions are missing
				hasFinishedResolution: hasFinished(
					'getEditedEntityRecord',
					args
				),
			};
		},
		[ postType, productId ]
	);

	// Convert the fields to DataForm compatible format
	const form = useMemo( () => {
		return {
			type: 'regular' as const,
			fields: columnTemplate[ 2 ]
				?.filter(
					( field ) =>
						field[ 0 ] ===
							'woocommerce/product-regular-price-field' ||
						field[ 0 ] === 'woocommerce/product-sale-price-field'
				)
				.map( ( field ) =>
					field[ 0 ] === 'woocommerce/product-regular-price-field'
						? 'regular_price'
						: 'woocommerce/product-sale-price-field'
				),
		};
	}, [ columnTemplate ] );

	const onChange = ( edits: Partial< Product > ) => {
		editEntityRecord( 'postType', postType, productId, edits );
	};

	// Basic container for a column, styling might be needed later
	// The flex: 1 assumes columns should share space equally by default
	return (
		<div style={ { flex: 1, marginTop: '16px' } }>
			{ hasFinishedResolution && (
				<DataForm
					fields={ fields }
					form={ form }
					data={ record }
					onChange={ onChange }
				/>
			) }
		</div>
	);
}
