/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { lazy, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { uniqueId, find } from 'lodash';
import { Icon, help as helpIcon, external } from '@wordpress/icons';
import { getAdminLink } from '@woocommerce/settings';
import { H, Section } from '@woocommerce/components';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	useUser,
	useUserPreferences,
} from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './style.scss';
import { IconFlag } from './icon-flag';
import { isNotesPanelVisible } from './unread-indicators';
import { isWCAdmin } from '~/dashboard/utils';
import { Tabs } from './tabs';
import { SetupProgress } from './setup-progress';
import { DisplayOptions } from './display-options';
import { HighlightTooltip } from './highlight-tooltip';
import { Panel } from './panel';
import {
	getLowStockCount as getLowStockProducts,
	getOrderStatuses,
	getUnreadOrders,
} from '../homescreen/activity-panel/orders/utils';
import { getUnapprovedReviews } from '../homescreen/activity-panel/reviews/utils';
import { ABBREVIATED_NOTIFICATION_SLOT_NAME } from './panels/inbox/abbreviated-notifications-panel';
import { getAdminSetting } from '~/utils/admin-settings';

const HelpPanel = lazy( () =>
	import( /* webpackChunkName: "activity-panels-help" */ './panels/help' )
);

const InboxPanel = lazy( () =>
	import(
		/* webpackChunkName: "activity-panels-inbox" */ './panels/inbox/inbox-panel'
	)
);

export const ActivityPanel = ( { isEmbedded, query } ) => {
	const [ currentTab, setCurrentTab ] = useState( '' );
	const [ isPanelClosing, setIsPanelClosing ] = useState( false );
	const [ isPanelOpen, setIsPanelOpen ] = useState( false );
	const [ isPanelSwitching, setIsPanelSwitching ] = useState( false );
	const { fills } = useSlot( ABBREVIATED_NOTIFICATION_SLOT_NAME );
	const hasExtendedNotifications = Boolean( fills?.length );
	const { updateUserPreferences, ...userData } = useUserPreferences();

	const getPreviewSiteBtnTrackData = ( select, getOption ) => {
		let trackData = {};
		if ( query.page === 'wc-admin' && query.task === 'appearance' ) {
			const { getTaskLists } = select( ONBOARDING_STORE_NAME );
			const taskLists = getTaskLists();
			const tasks = taskLists.reduce(
				( acc, taskList ) => [ ...acc, ...taskList.tasks ],
				[]
			);
			const task = tasks.find( ( t ) => t.id === 'appearance' );

			const demoNotice = getOption( 'woocommerce_demo_store_notice' );
			trackData = {
				set_notice: demoNotice ? 'Y' : 'N',
				create_homepage:
					task?.additionalData?.hasHomepage === true ? 'Y' : 'N',
				upload_logo: task?.additionalData?.themeMods?.custom_logo
					? 'Y'
					: 'N',
			};
		}

		return trackData;
	};

	function getThingsToDoNextCount( extendedTaskList ) {
		if (
			! extendedTaskList ||
			! extendedTaskList.tasks.length ||
			extendedTaskList.isHidden
		) {
			return 0;
		}
		return extendedTaskList.tasks.filter(
			( task ) => task.canView && ! task.isComplete && ! task.isDismissed
		).length;
	}

	function isAbbreviatedPanelVisible(
		select,
		setupTaskListHidden,
		thingsToDoNextCount
	) {
		const orderStatuses = getOrderStatuses( select );

		const isOrdersCardVisible =
			setupTaskListHidden && isPanelOpen
				? getUnreadOrders( select, orderStatuses ) > 0
				: false;
		const isReviewsCardVisible =
			setupTaskListHidden && isPanelOpen
				? getUnapprovedReviews( select )
				: false;
		const isLowStockCardVisible =
			setupTaskListHidden && isPanelOpen
				? getLowStockProducts( select )
				: false;

		return (
			thingsToDoNextCount > 0 ||
			isOrdersCardVisible ||
			isReviewsCardVisible ||
			isLowStockCardVisible ||
			hasExtendedNotifications
		);
	}

	const {
		hasUnreadNotes,
		hasAbbreviatedNotifications,
		isCompletedTask,
		thingsToDoNextCount,
		requestingTaskListOptions,
		setupTaskListComplete,
		setupTaskListHidden,
		previewSiteBtnTrackData,
	} = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		const { getTask, getTaskList, hasFinishedResolution } = select(
			ONBOARDING_STORE_NAME
		);

		const isSetupTaskListHidden = getTaskList( 'setup' )?.isHidden;
		const extendedTaskList = getTaskList( 'extended' );

		const thingsToDoCount = getThingsToDoNextCount( extendedTaskList );

		return {
			hasUnreadNotes: isNotesPanelVisible( select ),
			hasAbbreviatedNotifications: isAbbreviatedPanelVisible(
				select,
				isSetupTaskListHidden,
				thingsToDoCount
			),
			thingsToDoNextCount: thingsToDoCount,
			requestingTaskListOptions: ! hasFinishedResolution(
				'getTaskLists'
			),
			setupTaskListComplete: getTaskList( 'setup' )?.isComplete,
			setupTaskListHidden: isSetupTaskListHidden,
			isCompletedTask: Boolean(
				query.task && getTask( query.task )?.isComplete
			),
			previewSiteBtnTrackData: getPreviewSiteBtnTrackData(
				select,
				getOption
			),
		};
	} );
	const { unhideTaskList } = useDispatch( ONBOARDING_STORE_NAME );
	const { currentUserCan } = useUser();

	const togglePanel = ( { name: tabName }, isTabOpen ) => {
		const panelSwitching =
			tabName !== currentTab &&
			currentTab !== '' &&
			isTabOpen &&
			isPanelOpen;

		if ( isPanelClosing ) {
			return;
		}

		setCurrentTab( tabName );
		setIsPanelOpen( isTabOpen );
		setIsPanelSwitching( panelSwitching );
	};

	const closePanel = () => {
		setIsPanelClosing( true );
		setIsPanelOpen( false );
	};

	const clearPanel = () => {
		if ( ! isPanelOpen ) {
			setIsPanelClosing( false );
			setIsPanelSwitching( false );
			setCurrentTab( '' );
		}
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

	const redirectToHomeScreen = () => {
		if ( isWCAdmin( window.location.href ) ) {
			getHistory().push( getNewPath( {}, '/', {} ) );
		} else {
			window.location.href = getAdminLink( 'admin.php?page=wc-admin' );
		}
	};

	// @todo Pull in dynamic unread status/count
	const getTabs = () => {
		const activity = {
			name: 'activity',
			title: __( 'Activity', 'woocommerce' ),
			icon: <IconFlag />,
			unread: hasUnreadNotes || hasAbbreviatedNotifications,
			visible:
				( isEmbedded || ! isHomescreen() ) && ! isPerformingSetupTask(),
		};

		const setup = {
			name: 'setup',
			title: __( 'Finish setup', 'woocommerce' ),
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
					if (
						setupTaskListHidden === 'no' ||
						setupTaskListHidden === false
					) {
						redirectToHomeScreen();
					} else {
						unhideTaskList( 'setup' ).then( redirectToHomeScreen );
					}
				}

				return null;
			},
			visible:
				currentUserCan( 'manage_woocommerce' ) &&
				! requestingTaskListOptions &&
				! setupTaskListComplete &&
				! setupTaskListHidden &&
				! isPerformingSetupTask() &&
				( ! isHomescreen() || isEmbedded ),
		};

		const help = {
			name: 'help',
			title: __( 'Help', 'woocommerce' ),
			icon: <Icon icon={ helpIcon } />,
			visible:
				currentUserCan( 'manage_woocommerce' ) &&
				( ( isHomescreen() && ! isEmbedded ) ||
					isPerformingSetupTask() ),
		};

		const displayOptions = {
			component: DisplayOptions,
			visible:
				currentUserCan( 'manage_woocommerce' ) &&
				! isEmbedded &&
				isHomescreen() &&
				! isPerformingSetupTask(),
		};

		const previewSite = {
			name: 'previewSite',
			title: __( 'Preview site', 'woocommerce' ),
			icon: <Icon icon={ external } />,
			visible: isHomescreen() && query.task === 'appearance',
			onClick: () => {
				window.open( getAdminSetting( 'siteUrl' ) );
				recordEvent(
					'wcadmin_tasklist_previewsite',
					previewSiteBtnTrackData
				);

				return null;
			},
		};

		const previewStore = {
			name: 'previewStore',
			title: __( 'Preview store', 'woocommerce' ),
			icon: <Icon icon={ external } />,
			visible: isHomescreen() && query.task !== 'appearance',
			onClick: () => {
				window.open( getAdminSetting( 'shopUrl' ) );
				recordEvent( 'wcadmin_previewstore_click' );

				return null;
			},
		};

		return [
			activity,
			setup,
			previewSite,
			previewStore,
			displayOptions,
			help,
		].filter( ( tab ) => tab.visible );
	};

	const getPanelContent = ( tab ) => {
		const { task } = query;

		switch ( tab ) {
			case 'activity':
				return (
					<InboxPanel
						hasAbbreviatedNotifications={
							hasAbbreviatedNotifications
						}
						thingsToDoNextCount={ thingsToDoNextCount }
					/>
				);
			case 'help':
				return <HelpPanel taskName={ task } />;
			default:
				return null;
		}
	};

	const closedHelpPanelHighlight = () => {
		recordEvent( 'help_tooltip_click' );
		if ( userData && updateUserPreferences ) {
			updateUserPreferences( {
				help_panel_highlight_shown: 'yes',
			} );
		}
	};

	const shouldShowHelpTooltip = () => {
		const { task } = query;
		const startedTasks =
			userData && userData.task_list_tracked_started_tasks;
		const highlightShown = userData && userData.help_panel_highlight_shown;
		if (
			task &&
			highlightShown !== 'yes' &&
			( startedTasks || {} )[ task ] > 1 &&
			! isCompletedTask
		) {
			return true;
		}
		return false;
	};

	const tabs = getTabs();
	const headerId = uniqueId( 'activity-panel-header_' );
	const showHelpHighlightTooltip = shouldShowHelpTooltip();

	return (
		<div>
			<H id={ headerId } className="screen-reader-text">
				{ __( 'Store Activity', 'woocommerce' ) }
			</H>
			<Section
				component="aside"
				id="woocommerce-activity-panel"
				className="woocommerce-layout__activity-panel"
				aria-labelledby={ headerId }
			>
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
			</Section>
			{ showHelpHighlightTooltip ? (
				<HighlightTooltip
					delay={ 1000 }
					useAnchor={ true }
					title={ __( "We're here for help", 'woocommerce' ) }
					content={ __(
						'If you have any questions, feel free to explore the WooCommerce docs listed here.',
						'woocommerce'
					) }
					closeButtonText={ __( 'Got it', 'woocommerce' ) }
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
