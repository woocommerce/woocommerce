/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { Main } from '@woocommerce/base-components/sidebar-layout';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import { useForcedLayout } from '../../use-forced-layout';
import { getAllowedBlocks } from '../../editor-utils';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.CART_ITEMS );

	useForcedLayout( {
		clientId,
		template: allowedBlocks,
	} );
	return (
		<Main className="wc-block-cart__main">
			<div { ...blockProps }>
				<InnerBlocks
					allowedBlocks={ allowedBlocks }
					templateLock={ false }
					renderAppender={ InnerBlocks.ButtonBlockAppender }
				/>
			</div>
		</Main>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
