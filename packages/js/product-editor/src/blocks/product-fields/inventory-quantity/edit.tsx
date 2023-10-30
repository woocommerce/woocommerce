/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';
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
import { useValidation } from '../../../contexts/validation-context';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
	clientId,
	context,
}: ProductEditorBlockEditProps< TrackInventoryBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	const [ manageStock ] = useEntityProp< boolean >(
		'postType',
		context.postType,
		'manage_stock'
	);

	const [ stockQuantity, setStockQuantity ] = useEntityProp< number | null >(
		'postType',
		context.postType,
		'stock_quantity'
	);

	const stockQuantityId = useInstanceId(
		BaseControl,
		'product_stock_quantity'
	) as string;

	const {
		ref: stockQuantityRef,
		error: stockQuantityValidationError,
		validate: validateStockQuantity,
	} = useValidation< Product >(
		`stock_quantity-${ clientId }`,
		async function stockQuantityValidator() {
			if ( manageStock && stockQuantity && stockQuantity < 0 ) {
				return __(
					'Stock quantity must be a positive number.',
					'woocommerce'
				);
			}
		},
		[ manageStock, stockQuantity ]
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
							ref={ stockQuantityRef }
							label={ __( 'Available quantity', 'woocommerce' ) }
							value={ stockQuantity }
							onChange={ setStockQuantity }
							onBlur={ validateStockQuantity }
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
