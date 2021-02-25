/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { lazy, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { uniqueId, find } from 'lodash';
import CrossIcon from 'gridicons/dist/cross-small';
import classnames from 'classnames';
import { Icon, help as helpIcon, inbox as inboxIcon } from '@wordpress/icons';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { H, Section } from '@woocommerce/components';
import { OPTIONS_STORE_NAME, useUser } from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanelToggleBubble from './toggle-bubble';
import { getUnreadNotes } from './unread-indicators';
import { isWCAdmin } from '../../dashboard/utils';
import { Tabs } from './tabs';
import { SetupProgress } from './setup-progress';
import { DisplayOptions } from './display-options';
import { HighlightTooltip } from './highlight-tooltip';
import { Panel } from './panel';

const HelpPanel = lazy( () =>
	import( /* webpackChunkName: "activity-panels-help" */ './panels/help' )
);

const InboxPanel = lazy( () =>
	import(
		/* webpackChunkName: "activity-panels-inbox" */ '../../inbox-panel'
	)
);

export const ActivityPanel = ( { isEmbedded, query, userPreferencesData } ) => {
	const [ currentTab, setCurrentTab ] = useState( '' );
	const [ isPanelOpen, setIsPanelOpen ] = useState( false );
	const [ isPanelSwitching, setIsPanelSwitching ] = useState( false );
	const [ mobileOpen, setMobileOpen ] = useState( false );

	const {
		hasUnreadNotes,
		requestingTaskListOptions,
		setupTaskListComplete,
		setupTaskListHidden,
		trackedCompletedTasks,
	} = useSelect( ( select ) => {
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		return {
			hasUnreadNotes: getUnreadNotes( select ),
			requestingTaskListOptions:
				isResolving( 'getOption', [
					'woocommerce_task_list_complete',
				] ) ||
				isResolving( 'getOption', [ 'woocommerce_task_list_hidden' ] ),
			setupTaskListComplete:
				getOption( 'woocommerce_task_list_complete' ) === 'yes',
			setupTaskListHidden:
				getOption( 'woocommerce_task_list_hidden' ) === 'yes',
			trackedCompletedTasks:
				getOption( 'woocommerce_task_list_tracked_completed_tasks' ) ||
				[],
		};
	} );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { currentUserCan } = useUser();

	const togglePanel = ( { name: tabName }, isTabOpen ) => {
		const panelSwitching =
			tabName !== currentTab &&
			currentTab !== '' &&
			isTabOpen &&
			isPanelOpen;

		setCurrentTab( tabName );
		setMobileOpen( isTabOpen );
		setIsPanelOpen( isTabOpen );
		setIsPanelSwitching( panelSwitching );
	};

	const closePanel = () => {
		setIsPanelOpen( false );
	};

	const clearPanel = () => {
		if ( ! isPanelOpen ) {
			setIsPanelSwitching( false );
			setCurrentTab( '' );
		}
	};

	// On smaller screen, the panel buttons are hidden behind a toggle.
	const toggleMobile = () => {
		const tabs = getTabs();
		setCurrentTab( mobileOpen ? '' : tabs[ 0 ].name );
		setMobileOpen( ! mobileOpen );
		setIsPanelOpen( ! mobileOpen );
	};

	const isHomescreen = () => {
		return query.page === 'wc-admin' && ! query.path;
	};

	const isPerformingSetupTask = () => {
		return (
			query.task &&
			! query.path &&
			( requestingTaskListOptions === true ||
				( setupTaskListHidden === false &&
					setupTaskListComplete === false ) )
		);
	};

	// @todo Pull in dynamic unread status/count
	const getTabs = () => {
		const inbox = {
			name: 'inbox',
			title: __( 'Inbox', 'woocommerce-admin' ),
			icon: <Icon icon={ inboxIcon } />,
			unread: hasUnreadNotes,
			visible:
				( isEmbedded || ! isHomescreen() ) && ! isPerformingSetupTask(),
		};

		const setup = {
			name: 'setup',
			title: __( 'Store Setup', 'woocommerce-admin' ),
			icon: <SetupProgress />,
			onClick: () => {
				const currentLocation = window.location.href;
				const homescreenLocation = getAdminLink(
					'admin.php?page=wc-admin'
				);

				// Don't navigate if we're already on the homescreen, this will cause an infinite loop
				if ( currentLocation !== homescreenLocation ) {
					// Ensure that if the user is trying to get to the task list they can see it even if
					// it was dismissed.
					if ( setupTaskListHidden === 'no' ) {
						redirectToHomeScreen();
					} else {
						updateOptions( {
							woocommerce_task_list_hidden: 'no',
						} ).then( redirectToHomeScreen );
					}
				}

				return null;
			},
			visible:
				currentUserCan( 'manage_woocommerce' ) &&
				! setupTaskListComplete &&
				! setupTaskListHidden &&
				! isPerformingSetupTask() &&
				( ! isHomescreen() || isEmbedded ),
		};

		const help = {
			name: 'help',
			title: __( 'Help', 'woocommerce-admin' ),
			icon: <Icon icon={ helpIcon } />,
			visible:
				( isHomescreen() && ! isEmbedded ) || isPerformingSetupTask(),
		};

		const displayOptions = {
			component: DisplayOptions,
			visible:
				! isEmbedded && isHomescreen() && ! isPerformingSetupTask(),
		};

		return [ inbox, setup, displayOptions, help ].filter(
			( tab ) => tab.visible
		);
	};

	const getPanelContent = ( tab ) => {
		const { task } = query;

		switch ( tab ) {
			case 'inbox':
				return <InboxPanel />;
			case 'help':
				return <HelpPanel taskName={ task } />;
			default:
				return null;
		}
	};

	const redirectToHomeScreen = () => {
		if ( isWCAdmin( window.location.href ) ) {
			getHistory().push( getNewPath( {}, '/', {} ) );
		} else {
			window.location.href = getAdminLink( 'admin.php?page=wc-admin' );
		}
	};

	const closedHelpPanelHighlight = () => {
		recordEvent( 'help_tooltip_click' );
		if (
			userPreferencesData &&
			userPreferencesData.updateUserPreferences
		) {
			userPreferencesData.updateUserPreferences( {
				help_panel_highlight_shown: 'yes',
			} );
		}
	};

	const shouldShowHelpTooltip = () => {
		const { task } = query;
		const startedTasks =
			userPreferencesData &&
			userPreferencesData.task_list_tracked_started_tasks;
		const highlightShown =
			userPreferencesData &&
			userPreferencesData.help_panel_highlight_shown;
		if (
			task &&
			highlightShown !== 'yes' &&
			( startedTasks || {} )[ task ] > 1 &&
			! trackedCompletedTasks.includes( task )
		) {
			return true;
		}
		return false;
	};

	const tabs = getTabs();
	const headerId = uniqueId( 'activity-panel-header_' );
	const panelClasses = classnames( 'woocommerce-layout__activity-panel', {
		'is-mobile-open': mobileOpen,
	} );

	const showHelpHighlightTooltip = shouldShowHelpTooltip();
	const hasUnread = tabs.some( ( tab ) => tab.unread );
	const viewLabel = hasUnread
		? __(
				'View Activity Panel, you have unread activity',
				'woocommerce-admin'
		  )
		: __( 'View Activity Panel', 'woocommerce-admin' );

	return (
		<div>
			<H id={ headerId } className="screen-reader-text">
				{ __( 'Store Activity', 'woocommerce-admin' ) }
			</H>
			<Section
				component="aside"
				id="woocommerce-activity-panel"
				aria-labelledby={ headerId }
			>
				<Button
					onClick={ () => {
						toggleMobile();
					} }
					label={
						mobileOpen
							? __( 'Close Activity Panel', 'woocommerce-admin' )
							: viewLabel
					}
					aria-expanded={ mobileOpen }
					className="woocommerce-layout__activity-panel-mobile-toggle"
				>
					{ mobileOpen ? (
						<CrossIcon />
					) : (
						<ActivityPanelToggleBubble hasUnread={ hasUnread } />
					) }
				</Button>
				<div className={ panelClasses }>
					<Tabs
						tabs={ tabs }
						tabOpen={ isPanelOpen }
						selectedTab={ currentTab }
						onTabClick={ ( tab, tabOpen ) => {
							if ( tab.onClick ) {
								tab.onClick();
								return;
							}
							togglePanel( tab, tabOpen );
						} }
					/>
					<Panel
						currentTab
						isPanelOpen={ isPanelOpen }
						isPanelSwitching={ isPanelSwitching }
						tab={ find( getTabs(), { name: currentTab } ) }
						content={ getPanelContent( currentTab ) }
						closePanel={ () => closePanel() }
						clearPanel={ () => clearPanel() }
					/>
				</div>
			</Section>
			{ showHelpHighlightTooltip ? (
				<HighlightTooltip
					delay={ 1000 }
					useAnchor={ true }
					title={ __( "We're here for help", 'woocommerce-admin' ) }
					content={ __(
						'If you have any questions, feel free to explore the WooCommerce docs listed here.',
						'woocommerce-admin'
					) }
					closeButtonText={ __( 'Got it', 'woocommerce-admin' ) }
					id="activity-panel-tab-help"
					onClose={ () => closedHelpPanelHighlight() }
					onShow={ () => recordEvent( 'help_tooltip_view' ) }
				/>
			) : null }
		</div>
	);
};

ActivityPanel.defaultProps = {
	getHistory,
};

export default ActivityPanel;
