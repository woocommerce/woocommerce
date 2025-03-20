/**
 * External dependencies
 */
import {
	Suspense,
	lazy,
	useCallback,
	useLayoutEffect,
	useRef,
} from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import clsx from 'clsx';
import PropTypes from 'prop-types';
import {
	useUserPreferences,
	notesStore,
	onboardingStore,
	optionsStore,
} from '@woocommerce/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ActivityHeader from '~/activity-panel/activity-header';
import Promotions from '~/marketplace/components/promotions/promotions';
import { ActivityPanel } from './activity-panel';
import { Column } from './column';
import InboxPanel from '../inbox-panel';
import StatsOverview from './stats-overview';
import { StoreManagementLinks } from '../store-management-links';
import { TasksPlaceholder, ProgressTitle } from '../task-lists';
import { MobileAppModal } from './mobile-app-modal';
import './style.scss';
import '../dashboard/style.scss';
import { getAdminSetting } from '~/utils/admin-settings';
import { WooHomescreenHeaderBanner } from './header-banner-slot';
import { WooHomescreenWCPayFeature } from './wcpay-feature-slot';
import {
	isTaskListVisible,
	useTaskListsState,
} from '~/hooks/use-tasklists-state';
import { hasTwoColumnLayout } from './utils';

const TaskLists = lazy( () =>
	import( /* webpackChunkName: "tasks" */ '../task-lists' ).then(
		( module ) => ( {
			default: module.TaskLists,
		} )
	)
);

export const Layout = ( {
	defaultHomescreenLayout,
	query,
	hasTaskList,
	showingProgressHeader,
	isLoadingTaskLists,
} ) => {
	const userPrefs = useUserPreferences();

	// Use hook to get setup task list state so when the task list is completed or hidden, the homescreen layout is updated immediately
	const { setupTaskListActive: isSetupTaskListActive, setupTaskListHidden } =
		useTaskListsState( {
			setupTasklist: true,
			extendedTaskList: false,
		} );

	const isTaskScreen = Object.keys( query ).length > 0 && !! query.task; // ?&task=<x> query param is used to show tasks instead of the homescreen
	const isDashboardShown = ! isTaskScreen;
	const twoColumns = hasTwoColumnLayout(
		userPrefs.homepage_layout,
		defaultHomescreenLayout,
		isSetupTaskListActive
	);

	const isWideViewport = useRef( true );
	const maybeToggleColumns = useCallback( () => {
		isWideViewport.current = window.innerWidth >= 782;
	}, [] );

	useLayoutEffect( () => {
		maybeToggleColumns();
		window.addEventListener( 'resize', maybeToggleColumns );

		return () => {
			window.removeEventListener( 'resize', maybeToggleColumns );
		};
	}, [ maybeToggleColumns ] );

	const shouldStickColumns = isWideViewport.current && twoColumns;
	const shouldShowMobileAppModal = query.mobileAppModal ?? false;

	const renderTaskList = () => {
		return (
			<Suspense fallback={ <TasksPlaceholder query={ query } /> }>
				{ ! setupTaskListHidden && isDashboardShown && (
					<>
						<ProgressTitle taskListId="setup" />
					</>
				) }
				<TaskLists query={ query } />
			</Suspense>
		);
	};

	const renderColumns = () => {
		return (
			<>
				<Column shouldStick={ shouldStickColumns }>
					{ ! isLoadingTaskLists && ! showingProgressHeader && (
						<ActivityHeader
							className="your-store-today"
							title={ __( 'Your store today', 'woocommerce' ) }
							subtitle={ __(
								'To-dos, tips, and insights for your business',
								'woocommerce'
							) }
						/>
					) }
					{ ! isSetupTaskListActive && <WooHomescreenWCPayFeature /> }
					{ ! isTaskListVisible( 'setup' ) && <ActivityPanel /> }
					{ hasTaskList && renderTaskList() }
					<Promotions format="promo-card" />
					<InboxPanel />
				</Column>
				<Column shouldStick={ shouldStickColumns }>
					{ window.wcAdminFeatures.analytics && <StatsOverview /> }
					{ ! isSetupTaskListActive && <StoreManagementLinks /> }
				</Column>
			</>
		);
	};

	return (
		<>
			{ isDashboardShown && (
				<WooHomescreenHeaderBanner
					className={ clsx( 'woocommerce-homescreen', {
						'woocommerce-homescreen-column': ! twoColumns,
					} ) }
				/>
			) }
			<div
				className={ clsx( 'woocommerce-homescreen', {
					'two-columns': twoColumns,
				} ) }
			>
				{ isDashboardShown ? renderColumns() : renderTaskList() }
				{ shouldShowMobileAppModal && <MobileAppModal /> }
			</div>
		</>
	);
};

Layout.propTypes = {
	/**
	 * If the task list has been completed.
	 */
	taskListComplete: PropTypes.bool,
	/**
	 * If any task list is visible.
	 */
	hasTaskList: PropTypes.bool,
	/**
	 * Page query, used to determine the current task if any.
	 */
	query: PropTypes.object.isRequired,
	/**
	 * If the welcome modal should display
	 */
	shouldShowWelcomeModal: PropTypes.bool,
	/**
	 * If the welcome from Calypso modal should display.
	 */
	shouldShowWelcomeFromCalypsoModal: PropTypes.bool,
};

export default compose(
	withSelect( ( select ) => {
		const { isNotesRequesting } = select( notesStore );
		const { getOption } = select( optionsStore );
		const defaultHomescreenLayout =
			getOption( 'woocommerce_default_homepage_layout' ) ||
			'single_column';

		const {
			getTaskLists,
			hasFinishedResolution: taskListFinishResolution,
		} = select( onboardingStore );

		const visibleTaskListIds = getAdminSetting( 'visibleTaskListIds', [] );
		const hasTaskList = visibleTaskListIds.length > 0;

		// Only fetch task lists if there are any visible task lists to avoid unnecessary API calls
		let isLoadingTaskLists = false;
		let taskLists = [];
		if ( hasTaskList ) {
			isLoadingTaskLists = ! taskListFinishResolution( 'getTaskLists' );
			taskLists = getTaskLists();
		}

		return {
			defaultHomescreenLayout,
			isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
			isLoadingTaskLists,
			hasTaskList,
			showingProgressHeader: !! taskLists.find(
				( list ) => list.isVisible && list.displayProgressHeader
			),
		};
	} )
)( Layout );
