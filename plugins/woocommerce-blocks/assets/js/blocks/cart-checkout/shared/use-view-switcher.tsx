/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useDispatch, select } from '@wordpress/data';
import { ToolbarGroup, ToolbarDropdownMenu } from '@wordpress/components';
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
	component: JSX.Element;
} => {
	const initialView = views[ 0 ];
	const [ currentView, setCurrentView ] = useState( initialView );
	const { selectBlock } = useDispatch( 'core/block-editor' );
	const {
		getBlock,
		getSelectedBlockClientId,
		getBlockParentsByBlockName,
	} = select( blockEditorStore );
	const selectedBlockClientId = getSelectedBlockClientId();

	useEffect( () => {
		const viewNames = views.map( ( { view } ) => view );
		const parentBlockIds = getBlockParentsByBlockName(
			selectedBlockClientId,
			viewNames
		);

		if ( parentBlockIds.length !== 1 ) {
			return;
		}
		const parentBlock = getBlock( parentBlockIds[ 0 ] );

		if ( currentView.view === parentBlock.name ) {
			return;
		}

		const filteredViews = views.filter(
			( { view } ) => view === parentBlock.name
		);

		if ( filteredViews.length !== 1 ) {
			return;
		}

		setCurrentView( filteredViews[ 0 ] );
	}, [
		getBlockParentsByBlockName,
		selectedBlockClientId,
		getBlock,
		currentView.view,
		views,
	] );

	const ViewSwitcherComponent = (
		<ToolbarGroup>
			<ToolbarDropdownMenu
				label={ __( 'Switch view', 'woo-gutenberg-products-block' ) }
				text={ currentView.label }
				icon={
					<Icon srcElement={ eye } style={ { marginRight: '8px' } } />
				}
				controls={ views.map( ( view ) => ( {
					...view,
					title: <span>{ view.label }</span>,
					isActive: view.view === currentView.view,
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
		</ToolbarGroup>
	);

	return {
		currentView: currentView.view,
		component: ViewSwitcherComponent,
	};
};
