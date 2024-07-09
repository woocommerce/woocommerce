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
	NOTES_STORE_NAME,
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
} from '@woocommerce/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ActivityHeader from '~/activity-panel/activity-header';
import { ActivityPanel } from './activity-panel';
import { Column } from './column';
import InboxPanel from '../inbox-panel';
import { IntroModal as NavigationIntroModal } from '../navigation/components/intro-modal';
import StatsOverview from './stats-overview';
import { StoreManagementLinks } from '../store-management-links';
import {
	TasksPlaceholder,
	useActiveSetupTasklist,
	ProgressTitle,
} from '../task-lists';
import { MobileAppModal } from './mobile-app-modal';
import './style.scss';
import '../dashboard/style.scss';
import { getAdminSetting } from '~/utils/admin-settings';
import { WooHomescreenHeaderBanner } from './header-banner-slot';
import { WooHomescreenWCPayFeature } from './wcpay-feature-slot';

const TaskLists = lazy( () =>
	import( /* webpackChunkName: "tasks" */ '../task-lists' ).then(
		( module ) => ( {
			default: module.TaskLists,
		} )
	)
);

export const hasTwoColumnLayout = (
	userPrefLayout,
	defaultHomescreenLayout,
	taskListComplete,
	isTaskListHidden
) => {
	const hasTwoColumnContent =
		taskListComplete ||
		isTaskListHidden ||
		window.wcAdminFeatures.analytics;

	return (
		( userPrefLayout || defaultHomescreenLayout ) === 'two_columns' &&
		hasTwoColumnContent
	);
};

export const Layout = ( {
	defaultHomescreenLayout,
	query,
	taskListComplete,
	hasTaskList,
	showingProgressHeader,
	isLoadingTaskLists,
	isTaskListHidden,
} ) => {
	const userPrefs = useUserPreferences();
	const shouldShowStoreLinks = taskListComplete || isTaskListHidden;
	const shouldShowWCPayFeature = taskListComplete || isTaskListHidden;
	const isDashboardShown = Object.keys( query ).length > 0 && ! query.task; // ?&task=<x> query param is used to show tasks instead of the homescreen
	const activeSetupTaskList = useActiveSetupTasklist();

	const twoColumns = hasTwoColumnLayout(
		userPrefs.homepage_layout,
		defaultHomescreenLayout,
		taskListComplete,
		isTaskListHidden
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
				{ activeSetupTaskList && isDashboardShown && (
					<>
						<ProgressTitle taskListId={ activeSetupTaskList } />
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
								"To do's, tips, and insights for your business",
								'woocommerce'
							) }
						/>
					) }
					{ shouldShowWCPayFeature && <WooHomescreenWCPayFeature /> }
					{ isTaskListHidden && <ActivityPanel /> }
					{ hasTaskList && renderTaskList() }
					<InboxPanel />
				</Column>
				<Column shouldStick={ shouldStickColumns }>
					{ window.wcAdminFeatures.analytics && <StatsOverview /> }
					{ shouldShowStoreLinks && <StoreManagementLinks /> }
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
				{ window.wcAdminFeatures.navigation && (
					<NavigationIntroModal />
				) }
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
		const { isNotesRequesting } = select( NOTES_STORE_NAME );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const {
			getTaskList,
			getTaskLists,
			hasFinishedResolution: taskListFinishResolution,
		} = select( ONBOARDING_STORE_NAME );
		const taskLists = getTaskLists();
		const isLoadingTaskLists = ! taskListFinishResolution( 'getTaskLists' );

		const defaultHomescreenLayout =
			getOption( 'woocommerce_default_homepage_layout' ) ||
			'single_column';

		return {
			defaultHomescreenLayout,
			isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
			isLoadingTaskLists,
			isTaskListHidden: getTaskList( 'setup' )?.isHidden,
			hasTaskList: getAdminSetting( 'visibleTaskListIds', [] ).length > 0,
			showingProgressHeader: !! taskLists.find(
				( list ) => list.isVisible && list.displayProgressHeader
			),
			taskListComplete: getTaskList( 'setup' )?.isComplete,
		};
	} )
)( Layout );
