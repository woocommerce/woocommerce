/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';
import { getRegisteredBlocks } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import type { InnerBlockTemplate } from '../../types';

const ALLOWED_BLOCKS: string[] = [
	'woocommerce/checkout-order-summary-block',
	...getRegisteredBlocks( 'totals' ),
];
const TEMPLATE: InnerBlockTemplate[] = [
	[ 'woocommerce/checkout-order-summary-block', {}, [] ],
];

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps();
	return (
		<Sidebar className="wc-block-checkout__sidebar">
			<div { ...blockProps }>
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					templateLock={ false }
				/>
			</div>
		</Sidebar>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
