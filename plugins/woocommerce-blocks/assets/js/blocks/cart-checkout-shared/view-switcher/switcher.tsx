/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, select as dataSelect } from '@wordpress/data';
import { ToolbarGroup, ToolbarDropdownMenu } from '@wordpress/components';
import { BlockControls } from '@wordpress/block-editor';
import { Icon } from '@wordpress/icons';
import { eye } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import type { View } from './types';
import { getView } from './utils';

export const Switcher = ( {
	currentView,
	views,
	clientId,
}: {
	currentView: string;
	views: View[];
	clientId: string;
} ): JSX.Element | null => {
	const currentViewObject = getView( currentView, views ) || views[ 0 ];
	const currentViewLabel = currentViewObject.label;
	const { updateBlockAttributes, selectBlock } =
		useDispatch( 'core/block-editor' );

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarDropdownMenu
					label={ __(
						'Switch view',
						'woo-gutenberg-products-block'
					) }
					text={ currentViewLabel }
					icon={
						<Icon icon={ eye } style={ { marginRight: '8px' } } />
					}
					controls={ views.map( ( view ) => ( {
						...view,
						title: (
							<span style={ { marginLeft: '8px' } }>
								{ view.label }
							</span>
						),
						isActive: view.view === currentView,
						onClick: () => {
							updateBlockAttributes( clientId, {
								currentView: view.view,
							} );
							selectBlock(
								dataSelect( 'core/block-editor' )
									.getBlock( clientId )
									?.innerBlocks.find(
										( block: { name: string } ) =>
											block.name === view.view
									)?.clientId || clientId
							);
						},
					} ) ) }
				/>
			</ToolbarGroup>
		</BlockControls>
	);
};

export default Switcher;
