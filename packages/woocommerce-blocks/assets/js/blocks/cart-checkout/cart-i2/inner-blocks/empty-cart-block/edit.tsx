/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import { useForcedLayout } from '../../use-forced-layout';
import { getAllowedBlocks } from '../../editor-utils';
import { useCartBlockContext } from '../../context';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const { currentView } = useCartBlockContext();
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.EMPTY_CART );

	useForcedLayout( {
		clientId,
		template: allowedBlocks,
	} );

	return (
		<div
			{ ...blockProps }
			hidden={ currentView !== 'woocommerce/empty-cart-block' }
		>
			This is the empty cart block.
			<InnerBlocks
				allowedBlocks={ allowedBlocks }
				templateLock={ false }
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
