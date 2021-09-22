/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import { SidebarLayout } from '@woocommerce/base-components/sidebar-layout';

/**
 * Internal dependencies
 */
import { useForcedLayout } from '../../use-forced-layout';
import { getAllowedBlocks } from '../../editor-utils';
import { Columns } from './../../columns';
import './editor.scss';
import { useCartBlockControlsContext } from '../../context';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.FILLED_CART );

	useForcedLayout( {
		clientId,
		template: allowedBlocks,
	} );

	const {
		viewSwitcher: { currentView, component: ViewSwitcherComponent },
	} = useCartBlockControlsContext();

	return (
		<div { ...blockProps } hidden={ currentView !== 'filledCart' }>
			<Columns>
				<SidebarLayout className={ 'wc-block-cart' }>
					<InnerBlocks
						allowedBlocks={ allowedBlocks }
						templateLock={ false }
					/>
				</SidebarLayout>
			</Columns>
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
