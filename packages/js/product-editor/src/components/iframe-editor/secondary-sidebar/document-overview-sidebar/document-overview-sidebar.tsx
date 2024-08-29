/**
 * External dependencies
 */
import { Button, TabPanel } from '@wordpress/components';
import {
	useFocusOnMount,
	useFocusReturn,
	useMergeRefs,
} from '@wordpress/compose';
import {
	createElement,
	useRef,
	useState,
	useContext,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import {
	// @ts-expect-error Module "@wordpress/block-editor" has no exported member '__experimentalListView'
	__experimentalListView as ListView,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { EditorContext } from '../../context';

export function DocumentOverviewSidebar() {
	const { setIsDocumentOverviewOpened: setIsListViewOpened } =
		useContext( EditorContext );

	// This hook handles focus when the sidebar first renders.
	const focusOnMountRef = useFocusOnMount( 'firstElement' );
	// The next 2 hooks handle focus for when the sidebar closes and returning focus to the element that had focus before sidebar opened.
	const headerFocusReturnRef = useFocusReturn();
	const contentFocusReturnRef = useFocusReturn();

	function closeOnEscape( event: React.KeyboardEvent< HTMLDivElement > ) {
		if ( event.code === 'Escape' && ! event.defaultPrevented ) {
			event.preventDefault();
			setIsListViewOpened( false );
		}
	}

	// Use internal state instead of a ref to make sure that the component
	// re-renders when the dropZoneElement updates.
	const [ dropZoneElement, setDropZoneElement ] = useState( null );
	// Tracks our current tab.
	const [ tab, setTab ] = useState( 'list-view' );

	// This ref refers to the list view application area.
	const listViewRef = useRef< HTMLDivElement >( null );

	// Must merge the refs together so focus can be handled properly in the next function.
	const listViewContainerRef = useMergeRefs( [
		contentFocusReturnRef,
		focusOnMountRef,
		listViewRef,
		setDropZoneElement,
	] );

	/**
	 * Render tab content for a given tab name.
	 *
	 * @param tabName The name of the tab to render.
	 */
	function renderTabContent( tabName: string ) {
		if ( tabName === 'list-view' ) {
			return <ListView dropZoneElement={ dropZoneElement } />;
		}
		return null;
	}

	return (
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions
		<div
			className="woocommerce-iframe-editor__document-overview-sidebar"
			onKeyDown={ closeOnEscape }
		>
			<Button
				className="woocommerce-iframe-editor__document-overview-sidebar-close-button"
				ref={ headerFocusReturnRef }
				icon={ closeSmall }
				label={ __( 'Close', 'woocommerce' ) }
				onClick={ () => setIsListViewOpened( false ) }
			/>
			<TabPanel
				className="woocommerce-iframe-editor__document-overview-sidebar-tab-panel"
				initialTabName={ tab }
				onSelect={ setTab }
				tabs={ [
					{
						name: 'list-view',
						title: __( 'List View', 'woocommerce' ),
						className:
							'woocommerce-iframe-editor__document-overview-sidebar-tab-item',
					},
				] }
			>
				{ ( currentTab ) => (
					<div
						className="woocommerce-iframe-editor__document-overview-sidebar-tab-content"
						ref={ listViewContainerRef }
					>
						{ renderTabContent( currentTab.name ) }
					</div>
				) }
			</TabPanel>
		</div>
	);
}
