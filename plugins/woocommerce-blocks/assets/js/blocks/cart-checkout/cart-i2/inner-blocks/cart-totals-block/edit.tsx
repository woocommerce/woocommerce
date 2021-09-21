/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { Sidebar } from '@woocommerce/base-components/sidebar-layout';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import Title from '@woocommerce/base-components/title';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import { useForcedLayout } from '../../use-forced-layout';
import { getAllowedBlocks } from '../../editor-utils';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.CART_TOTALS );

	useForcedLayout( {
		clientId,
		template: allowedBlocks,
	} );

	return (
		<Sidebar className="wc-block-cart__sidebar">
			<div { ...blockProps }>
				<Title headingLevel="2" className="wc-block-cart__totals-title">
					{ __( 'Cart totals', 'woo-gutenberg-products-block' ) }
				</Title>
				<InnerBlocks
					allowedBlocks={ allowedBlocks }
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
