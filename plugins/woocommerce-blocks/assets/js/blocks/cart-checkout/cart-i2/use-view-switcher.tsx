/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch, select } from '@wordpress/data';
import { Toolbar, ToolbarDropdownMenu } from '@wordpress/components';
import { Icon, eye } from '@woocommerce/icons';
import { store as blockEditorStore } from '@wordpress/block-editor';

interface View {
	view: string;
	label: string;
	icon: string | JSX.Element;
}

export const useViewSwitcher = (
	clientId: string,
	views: View[]
): {
	currentView: string;
	component: () => JSX.Element;
} => {
	const initialView = views[ 0 ];
	const [ currentView, setCurrentView ] = useState( initialView );
	const { selectBlock } = useDispatch( 'core/block-editor' );
	const { getBlock } = select( blockEditorStore );

	const ViewSwitcherComponent = () => (
		<Toolbar>
			<ToolbarDropdownMenu
				label={ __( 'Switch view', 'woo-gutenberg-products-block' ) }
				text={ currentView.label }
				icon={
					<Icon srcElement={ eye } style={ { marginRight: '8px' } } />
				}
				controls={ views.map( ( view ) => ( {
					...view,
					title: view.label,
					onClick: () => {
						setCurrentView( view );
						selectBlock(
							getBlock( clientId ).innerBlocks.find(
								( block: { name: string } ) =>
									block.name === view.view
							)?.clientId || clientId
						);
					},
				} ) ) }
			/>
		</Toolbar>
	);

	return {
		currentView: currentView.view,
		component: ViewSwitcherComponent,
	};
};

export default useViewSwitcher;
