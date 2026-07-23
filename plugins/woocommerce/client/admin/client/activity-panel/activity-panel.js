/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { lazy, useState, useEffect, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { uniqueId, find } from 'lodash';
import {
	Icon,
	help as helpIcon,
	external,
	bell,
	bellUnread,
	listView,
	comment,
	store,
} from '@wordpress/icons';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';
import { H, Section } from '@woocommerce/components';
import {
	activityPanelStore,
	onboardingStore,
	optionsStore,
	useUser,
} from '@woocommerce/data';
import { addHistoryListener } from '@woocommerce/navigation';
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
import { hasUnreadNotes as checkIfHasUnreadNotes } from './unread-indicators';
import { Tabs } from './tabs';
import { DisplayOptions } from './display-options';
import { Panel } from './panel';
import { ABBREVIATED_NOTIFICATION_SLOT_NAME } from './panels/inbox/abbreviated-notifications-panel';
import { getAdminSetting } from '~/utils/admin-settings';
import { getUrlParams } from '~/utils';
import { getSegmentsFromPath } from '~/utils/url-helpers';
import { useLaunchYourStore } from '~/launch-your-store';
import { useTaskListsState } from '~/hooks/use-tasklists-state';
import HeaderAccount from '../marketplace/components/header-account/header-account';

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
	const isHomescreen = query.page === 'wc-admin' && ! query.path;

	const [ currentTab, setCurrentTab ] = useState( '' );
	const [ isPanelClosing, setIsPanelClosing ] = useState( false );
	const [ isPanelOpen, setIsPanelOpen ] = useState( false );
	const [ isPanelSwitching, setIsPanelSwitching ] = useState( false );
	const { fills } = useSlot( ABBREVIATED_NOTIFICATION_SLOT_NAME );
	const hasExtendedNotifications = Boolean( fills?.length );
	const { comingSoon } = useLaunchYourStore( {
		enabled: isHomescreen,
	} );

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

	const getPreviewSiteBtnTrackData = useCallback(
		( select, getOption ) => {
			let trackData = {};
			if ( query.page === 'wc-admin' && query.task === 'appearance' ) {
				const { getTaskLists } = select( onboardingStore );
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
		},
		[ query.page, query.task ]
	);

	const checkIfHasAbbreviatedNotifications = useCallback(
		( select, setupTaskListHidden, thingsToDoNextCount ) => {
			const counts =
				select( activityPanelStore ).getActivityPanelCounts();

			const isOrdersCardVisible = setupTaskListHidden
				? ( counts?.orders_to_fulfill_count ?? 0 ) > 0
				: false;
			const isReviewsCardVisible = setupTaskListHidden
				? ( counts?.reviews_to_moderate_count ?? 0 ) > 0
				: false;
			const isLowStockCardVisible = setupTaskListHidden
				? ( counts?.products_low_in_stock_count ?? 0 ) > 0
				: false;

			return (
				thingsToDoNextCount > 0 ||
				isOrdersCardVisible ||
				isReviewsCardVisible ||
				isLowStockCardVisible ||
				hasExtendedNotifications
			);
		},
		[ hasExtendedNotifications ]
	);

	const {
		requestingTaskListOptions,
		setupTaskListComplete,
		setupTaskListHidden,
		thingsToDoNextCount,
	} = useTaskListsState();

	const {
		hasUnreadNotes,
		hasAbbreviatedNotifications,
		previewSiteBtnTrackData,
	} = useSelect(
		( select ) => {
			const { getOption } = select( optionsStore );

			return {
				hasUnreadNotes: checkIfHasUnreadNotes( select ),
				hasAbbreviatedNotifications: checkIfHasAbbreviatedNotifications(
					select,
					setupTaskListHidden,
					thingsToDoNextCount
				),
				previewSiteBtnTrackData: getPreviewSiteBtnTrackData(
					select,
					getOption
				),
			};
		},
		[
			checkIfHasAbbreviatedNotifications,
			thingsToDoNextCount,
			setupTaskListHidden,
			getPreviewSiteBtnTrackData,
		]
	);

	const { showCesModal } = useDispatch( CES_STORE_KEY );

	const { currentUserCan } = useUser();

	// Single decision point for a tab click. Side-effect tabs (Preview store,
	// Feedback CES modal) bail out before any panel state is touched. The
	// rest of the logic decides open / close / switch from the parent's own
	// state (currentTab, isPanelOpen) rather than the click target's intent
	// — that way a focus-outside close racing with a same-tab click can't
	// flip the panel back open after blur fires closePanel().
	const togglePanel = ( tab ) => {
		if ( tab.onClick ) {
			tab.onClick();
			return;
		}

		const tabName = tab.name;
		// Same-tab re-click during a pending close: do nothing. The close
		// from useFocusOutside is already in flight; let it finish.
		if ( isPanelClosing && tabName === currentTab ) {
			return;
		}

		const isSameTab = tabName === currentTab;
		const isClosing = isSameTab && isPanelOpen;
		const isSwitching = ! isSameTab && currentTab !== '' && isPanelOpen;

		// Record a Tracks event when a panel is being opened or switched in
		// (not when closing). Previously the Tabs child fired this — moved
		// here so it stays consistent with the rest of the intent logic.
		if ( ! isClosing ) {
			recordEvent( 'activity_panel_open', { tab: tabName } );
		}

		setCurrentTab( tabName );
		setIsPanelOpen( ! isClosing );
		setIsPanelSwitching( isSwitching );
		setIsPanelClosing( isClosing );
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
			// Use bellUnread (bell + dot baked into the SVG) when there is
			// unread activity so the unread state lives in one source of truth
			// inside @wordpress/icons rather than a separately-positioned CSS
			// pseudo-element on top of the plain bell.
			icon: (
				<Icon
					icon={
						hasUnreadNotes || hasAbbreviatedNotifications
							? bellUnread
							: bell
					}
					size={ 18 }
				/>
			),
			unread: hasUnreadNotes || hasAbbreviatedNotifications,
			visible:
				( isEmbedded || ! isHomescreen ) &&
				! isPerformingSetupTask() &&
				! isProductScreen() &&
				currentUserCan( 'manage_woocommerce' ),
		};

		const feedback = {
			name: 'feedback',
			title: __( 'Feedback', 'woocommerce' ),
			icon: <Icon icon={ comment } size={ 18 } />,
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
			icon: <Icon icon={ listView } size={ 18 } />,
			visible:
				currentUserCan( 'manage_woocommerce' ) &&
				! requestingTaskListOptions &&
				! setupTaskListHidden &&
				! setupTaskListComplete &&
				! isHomescreen &&
				! isProductScreen(),
		};

		const help = {
			name: 'help',
			icon: <Icon icon={ helpIcon } />,
			visible:
				currentUserCan( 'manage_woocommerce' ) &&
				( ( isHomescreen && ! isEmbedded ) || isPerformingSetupTask() ),
		};

		const displayOptions = {
			component: DisplayOptions,
			visible:
				currentUserCan( 'manage_woocommerce' ) &&
				! isEmbedded &&
				isHomescreen &&
				! isPerformingSetupTask(),
		};

		const headerAccount = {
			// Stable component reference — an inline arrow would give React a
			// new component type on every parent render, remounting HeaderAccount
			// and resetting its DropdownMenu's internal isOpen state.
			component: HeaderAccount,
			options: { page: 'wc-admin' },
			visible: isHomescreen,
		};

		const previewSite = {
			name: 'previewSite',
			title: __( 'Preview site', 'woocommerce' ),
			icon: <Icon icon={ external } />,
			visible: isHomescreen && query.task === 'appearance',
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
			title:
				( comingSoon === 'yes' &&
					__( 'Preview store', 'woocommerce' ) ) ||
				__( 'View store', 'woocommerce' ),
			// Tiny shopfront icon for the literal "View store" / "Preview
			// store" semantic, distinct from the other icons in the bar.
			// Required because activity-panel tabs are now icon-only —
			// a tab without an icon renders as an empty button on the
			// floating header.
			icon: <Icon icon={ store } />,
			visible: isHomescreen && query.task !== 'appearance',
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
			headerAccount,
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

	const tabs = getTabs();
	const headerId = uniqueId( 'activity-panel-header_' );

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
						onTabClick={ togglePanel }
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
			</div>
		</LayoutContextProvider>
	);
};

export default ActivityPanel;
