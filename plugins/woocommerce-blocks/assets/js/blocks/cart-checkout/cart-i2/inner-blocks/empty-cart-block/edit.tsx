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
import { useCartBlockControlsContext } from '../../context';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.EMPTY_CART );

	useForcedLayout( {
		clientId,
		template: allowedBlocks,
	} );

	const {
		viewSwitcher: { currentView, component: ViewSwitcherComponent },
	} = useCartBlockControlsContext();

	return (
		<div { ...blockProps } hidden={ currentView !== 'emptyCart' }>
			This is the empty cart block.
			<InnerBlocks
				allowedBlocks={ allowedBlocks }
				templateLock={ false }
			/>
			<ViewSwitcherComponent />
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
