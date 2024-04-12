/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { lazy, useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { uniqueId, find } from 'lodash';
import { Icon, help as helpIcon, external } from '@wordpress/icons';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';
import { H, Section } from '@woocommerce/components';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	useUser,
	useUserPreferences,
	getVisibleTasks,
} from '@woocommerce/data';
import { getHistory, addHistoryListener } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useSlot } from '@woocommerce/experimental';
import {
	LayoutContextProvider,
	useExtendLayout,
} from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import './style.scss';
import { IconFlag } from './icon-flag';
import { hasUnreadNotes as checkIfHasUnreadNotes } from './unread-indicators';
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
import { getUrlParams } from '~/utils';
import { useActiveSetupTasklist } from '~/task-lists';
import { getSegmentsFromPath } from '~/utils/url-helpers';
import { FeedbackIcon } from '~/products/images/feedback-icon';

const HelpPanel = lazy( () =>
	import( /* webpackChunkName: "activity-panels-help" */ './panels/help' )
);

const InboxPanel = lazy( () =>
	import(
		/* webpackChunkName: "activity-panels-inbox" */ './panels/inbox/inbox-panel'
	)
);

const SetupTasksPanel = lazy( () =>
	import(
		/* webpackChunkName: "activity-panels-setup" */ './panels/setup-tasks/setup-tasks-panel.tsx'
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
	const activeSetupList = useActiveSetupTasklist();

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

	useEffect( () => {
		return addHistoryListener( () => {
			closePanel();
			clearPanel();
		} );
	}, [] );

	const updatedLayoutContext = useExtendLayout( 'activity-panel' );

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

	function checkIfHasAbbreviatedNotifications(
		select,
		setupTaskListHidden,
		thingsToDoNextCount
	) {
		const orderStatuses = getOrderStatuses( select );

		const isOrdersCardVisible = setupTaskListHidden
			? getUnreadOrders( select, orderStatuses ) > 0
			: false;
		const isReviewsCardVisible = setupTaskListHidden
			? getUnapprovedReviews( select )
			: false;
		const isLowStockCardVisible = setupTaskListHidden
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
		setupTasksCompleteCount,
		setupTasksCount,
		previewSiteBtnTrackData,
	} = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		const { getTask, getTaskList, hasFinishedResolution } = select(
			ONBOARDING_STORE_NAME
		);

		const setupList = activeSetupList && getTaskList( activeSetupList );

		const isSetupTaskListHidden = setupList?.isHidden ?? false;
		const setupVisibleTasks = getVisibleTasks( setupList?.tasks || [] );
		const extendedTaskList = getTaskList( 'extended' );

		const thingsToDoCount = getThingsToDoNextCount( extendedTaskList );

		return {
			hasUnreadNotes: checkIfHasUnreadNotes( select ),
			hasAbbreviatedNotifications: checkIfHasAbbreviatedNotifications(
				select,
				isSetupTaskListHidden,
				thingsToDoCount
			),
			thingsToDoNextCount: thingsToDoCount,
			requestingTaskListOptions:
				! hasFinishedResolution( 'getTaskLists' ),
			setupTaskListComplete: setupList?.isComplete,
			setupTaskListHidden: isSetupTaskListHidden,
			setupTasksCount: setupVisibleTasks.length,
			setupTasksCompleteCount: setupVisibleTasks.filter(
				( task ) => task.isComplete
			).length,
			isCompletedTask: Boolean(
				query.task && getTask( query.task )?.isComplete
			),
			previewSiteBtnTrackData: getPreviewSiteBtnTrackData(
				select,
				getOption
			),
		};
	} );

	const { showCesModal } = useDispatch( CES_STORE_KEY );

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

	const isHomescreen = () => {
		return query.page === 'wc-admin' && ! query.path;
	};

	const isProductScreen = () => {
		const [ firstPathSegment ] = getSegmentsFromPath( query.path );
		return (
			firstPathSegment === 'add-product' || firstPathSegment === 'product'
		);
	};

	const isAddProductPage = () => {
		const urlParams = getUrlParams( window.location.search );

		return (
			isEmbedded &&
			/post-new\.php$/.test( window.location.pathname ) &&
			urlParams?.post_type === 'product'
		);
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
		const activity = {
			name: 'activity',
			title: __( 'Activity', 'woocommerce' ),
			icon: <IconFlag />,
			unread: hasUnreadNotes || hasAbbreviatedNotifications,
			visible:
				( isEmbedded || ! isHomescreen() ) &&
				! isPerformingSetupTask() &&
				! isProductScreen(),
		};

		const feedback = {
			name: 'feedback',
			title: __( 'Feedback', 'woocommerce' ),
			icon: <FeedbackIcon />,
			onClick: () => {
				setCurrentTab( 'feedback' );
				setIsPanelOpen( true );
				showCesModal(
					{
						action: 'product_feedback',
						title: __(
							"How's your experience with the product editor?",
							'woocommerce'
						),
						firstQuestion: __(
							'The product editing screen is easy to use',
							'woocommerce'
						),
						secondQuestion: __(
							"The product editing screen's functionality meets my needs",
							'woocommerce'
						),
					},
					{
						onRecordScore: () => {
							setCurrentTab( '' );
							setIsPanelOpen( false );
						},
						onCloseModal: () => {
							setCurrentTab( '' );
							setIsPanelOpen( false );
						},
					},
					{
						type: 'snackbar',
						icon: <span>🌟</span>,
					}
				);
			},
			visible: isAddProductPage(),
		};

		const setup = {
			name: 'setup',
			title: __( 'Finish setup', 'woocommerce' ),
			icon: (
				<SetupProgress
					setupTasksComplete={ setupTasksCompleteCount }
					setupCompletePercent={ Math.ceil(
						( setupTasksCompleteCount / setupTasksCount ) * 100
					) }
				/>
			),
			visible:
				currentUserCan( 'manage_woocommerce' ) &&
				! requestingTaskListOptions &&
				! setupTaskListComplete &&
				! setupTaskListHidden &&
				! isHomescreen() &&
				! isProductScreen(),
		};

		const help = {
			name: 'help',
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
			visible: isHomescreen() && query.task !== 'appearance',
			onClick: () => {
				window.open( getAdminSetting( 'shopUrl' ) );
				recordEvent( 'wcadmin_previewstore_click' );

				return null;
			},
		};

		return [
			activity,
			feedback,
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
			case 'setup':
				return <SetupTasksPanel query={ query } />;
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
		<LayoutContextProvider value={ updatedLayoutContext }>
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
		</LayoutContextProvider>
	);
};

ActivityPanel.defaultProps = {
	getHistory,
};

export default ActivityPanel;
