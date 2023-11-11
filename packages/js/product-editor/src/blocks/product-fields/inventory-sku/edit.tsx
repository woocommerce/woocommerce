/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockAttributes } from '@wordpress/blocks';
import { createElement, createInterpolateElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';

/**
 * Internal dependencies
 */

export function Edit( {
	attributes,
	context,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	const [ sku, setSku ] = useEntityProp(
		'postType',
		context.postType,
		'sku'
	);

	return (
		<div { ...blockProps }>
			<BaseControl
				id={ 'product_sku' }
				className="woocommerce-product-form_inventory-sku"
				label={ createInterpolateElement(
					__( 'Sku <description />', 'woocommerce' ),
					{
						description: (
							<span className="woocommerce-product-form__optional-input">
								{ __( '(STOCK KEEPING UNIT)', 'woocommerce' ) }
							</span>
						),
					}
				) }
			>
				<InputControl
					name={ 'woocommerce-product-sku' }
					onChange={ setSku }
					value={ sku || '' }
				/>
			</BaseControl>
		</div>
	);
}
