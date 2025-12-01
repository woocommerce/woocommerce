/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import type { TemplateArray } from '@wordpress/blocks';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import {
	useForcedLayout,
	getAllowedBlocks,
} from '../../../cart-checkout-shared';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const allowedBlocks = getAllowedBlocks(
		innerBlockAreas.CHECKOUT_ORDER_SUMMARY_TOTALS
	);
	const defaultTemplate = [
		[ 'woocommerce/checkout-order-summary-subtotal-block', {}, [] ],
		[ 'woocommerce/checkout-order-summary-fee-block', {}, [] ],
		[ 'woocommerce/checkout-order-summary-discount-block', {}, [] ],
		[ 'woocommerce/checkout-order-summary-shipping-block', {}, [] ],
		[ 'woocommerce/checkout-order-summary-taxes-block', {}, [] ],
	] as TemplateArray;

	useForcedLayout( {
		clientId,
		registeredBlocks: allowedBlocks,
		defaultTemplate,
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ allowedBlocks }
				template={ defaultTemplate }
			/>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
