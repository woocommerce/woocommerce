/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockAttributes } from '@wordpress/blocks';
import { createElement, createInterpolateElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { select } from '@wordpress/data';

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

const handleProductInventoryAdvanced = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			if (
				props?.attributes?._templateBlockId !==
				'product-inventory-advanced'
			) {
				return <BlockEdit { ...props } />;
			}

			// get the inventory section block instance
			const advancedBlock = select( 'core/block-editor' ).getBlock(
				props?.clientId
			);

			// No inner blocks, so we can render the default block edit.
			if ( ! advancedBlock?.innerBlocks?.length ) {
				return <BlockEdit { ...props } />;
			}

			// Find the `product-limit-purchase` block instance
			const advancedSectionBlock = advancedBlock?.innerBlocks[ 0 ];
			const limitPurchaseBlock = advancedSectionBlock?.innerBlocks?.find(
				( block ) => {
					return (
						block?.attributes?._templateBlockId ===
						'product-limit-purchase'
					);
				}
			);

			// Update the condition to don't render
			if ( ! limitPurchaseBlock ) {
				return null;
			}

			return <BlockEdit { ...props } />;
		};
	},
	'checkAdvancedSection '
);

addFilter(
	'editor.BlockEdit',
	'woocommerce/handle-product-inventory-advanced',
	handleProductInventoryAdvanced
);
