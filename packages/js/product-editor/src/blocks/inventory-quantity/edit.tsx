/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { TrackInventoryBlockAttributes } from './types';

import { useValidation } from '../../hooks/use-validation';

export function Edit( {}: BlockEditProps< TrackInventoryBlockAttributes > ) {
	const blockProps = useBlockProps();

	const [ manageStock ] = useEntityProp< boolean >(
		'postType',
		'product',
		'manage_stock'
	);

	const [ stockQuantity, setStockQuantity ] = useEntityProp< number | null >(
		'postType',
		'product',
		'stock_quantity'
	);

	const stockQuantityId = useInstanceId(
		BaseControl,
		'product_stock_quantity'
	) as string;

	const stockQuantityValidationError = useValidation(
		'product/stock_quantity',
		function stockQuantityValidator() {
			if ( manageStock && stockQuantity && stockQuantity < 0 ) {
				return __(
					'Stock quantity must be a positive number.',
					'woocommerce'
				);
			}
		}
	);

	useEffect( () => {
		if ( manageStock && stockQuantity === null ) {
			setStockQuantity( 1 );
		}
	}, [ manageStock, stockQuantity ] );

	return (
		<div { ...blockProps }>
			<div className="wp-block-columns">
				<div className="wp-block-column">
					<BaseControl
						id={ stockQuantityId }
						className={
							stockQuantityValidationError && 'has-error'
						}
						help={ stockQuantityValidationError ?? '' }
					>
						<InputControl
							id={ stockQuantityId }
							name="stock_quantity"
							label={ __( 'Available quantity', 'woocommerce' ) }
							value={ stockQuantity }
							onChange={ setStockQuantity }
							type="number"
							min={ 0 }
						/>
					</BaseControl>
				</div>

				<div className="wp-block-column" />
			</div>
		</div>
	);
}
