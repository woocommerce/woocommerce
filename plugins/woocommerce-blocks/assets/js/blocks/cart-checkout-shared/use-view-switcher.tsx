/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useDispatch, select } from '@wordpress/data';
import { ToolbarGroup, ToolbarDropdownMenu } from '@wordpress/components';
import { eye } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { store as blockEditorStore } from '@wordpress/block-editor';

interface View {
	view: string;
	label: string;
	icon: string | JSX.Element;
}

function getView( viewName: string, views: View[] ) {
	return views.find( ( view ) => view.view === viewName );
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
	const { getBlock, getSelectedBlockClientId, getBlockParentsByBlockName } =
		select( blockEditorStore );
	const selectedBlockClientId = getSelectedBlockClientId();

	useEffect( () => {
		const selectedBlock = getBlock( selectedBlockClientId );

		if ( ! selectedBlock ) {
			return;
		}

		if ( currentView.view === selectedBlock.name ) {
			return;
		}

		const viewNames = views.map( ( { view } ) => view );

		if ( viewNames.includes( selectedBlock.name ) ) {
			const newView = getView( selectedBlock.name, views );
			if ( newView ) {
				return setCurrentView( newView );
			}
		}

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

		const newView = getView( parentBlock.name, views );

		if ( newView ) {
			setCurrentView( newView );
		}
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
				icon={ <Icon icon={ eye } style={ { marginRight: '8px' } } /> }
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
