/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useContext, useRef, useEffect, memo } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	BlockInspector,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { ComplementaryArea } from '@wordpress/interface';
import { drawerRight } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	storeName,
	mainSidebarDocumentTab,
	mainSidebarBlockTab,
	mainSidebarId,
} from '../../store';
import { Header } from './header';
import { EmailSettings } from './email-settings';
import { TemplateSettings } from './template-settings';
import { useEditorMode } from '../../hooks';
import { Tabs } from '../../private-apis';

import './style.scss';
import { recordEvent } from '../../events';

type Props = React.ComponentProps< typeof ComplementaryArea >;

function SidebarContent( props: Props ) {
	const [ editorMode ] = useEditorMode();

	const tabListRef = useRef( null );
	// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
	const tabsContextValue = useContext( Tabs.Context );

	return (
		<ComplementaryArea
			identifier={ mainSidebarId }
			closeLabel={ __( 'Close sidebar', 'woocommerce' ) }
			headerClassName="editor-sidebar__panel-tabs"
			className="edit-post-sidebar"
			header={
				<Tabs.Context.Provider value={ tabsContextValue }>
					<Header ref={ tabListRef } />
				</Tabs.Context.Provider>
			}
			icon={ drawerRight }
			scope={ storeName }
			smallScreenTitle={ __( 'No title', 'woocommerce' ) }
			isActiveByDefault
			{ ...props }
		>
			<Tabs.Context.Provider value={ tabsContextValue }>
				<Tabs.TabPanel tabId={ mainSidebarDocumentTab }>
					{ editorMode === 'template' ? (
						<TemplateSettings />
					) : (
						<EmailSettings />
					) }
				</Tabs.TabPanel>
				<Tabs.TabPanel tabId={ mainSidebarBlockTab }>
					<BlockInspector />
				</Tabs.TabPanel>
			</Tabs.Context.Provider>
		</ComplementaryArea>
	);
}

function RawSidebar( props: Props ) {
	const { toggleSettingsSidebarActiveTab } = useDispatch( storeName );
	const { activeTab, selectedBlockId } = useSelect(
		( select ) => ( {
			activeTab: select( storeName ).getSettingsSidebarActiveTab(),
			selectedBlockId:
				select( blockEditorStore ).getSelectedBlockClientId(),
		} ),
		[]
	);

	// Switch tab based on selected block.
	useEffect( () => {
		if ( selectedBlockId ) {
			void toggleSettingsSidebarActiveTab( mainSidebarBlockTab );
		} else {
			void toggleSettingsSidebarActiveTab( mainSidebarDocumentTab );
		}
	}, [ selectedBlockId, toggleSettingsSidebarActiveTab ] );

	return (
		<Tabs
			selectedTabId={ activeTab || mainSidebarDocumentTab }
			onSelect={ ( key ) => {
				recordEvent( 'sidebar_tab_selected', { tabKey: key } );
				return toggleSettingsSidebarActiveTab( key as string );
			} }
		>
			<SidebarContent { ...props } />
		</Tabs>
	);
}

export const Sidebar = memo( RawSidebar );
