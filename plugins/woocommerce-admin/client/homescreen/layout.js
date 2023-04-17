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
import { withDispatch, withSelect } from '@wordpress/data';
import classnames from 'classnames';
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
import { TasksPlaceholder, useActiveSetupTasklist } from '../tasks';
import {
	WELCOME_MODAL_DISMISSED_OPTION_NAME,
	WELCOME_FROM_CALYPSO_MODAL_DISMISSED_OPTION_NAME,
} from './constants';
import { WelcomeFromCalypsoModal } from './welcome-from-calypso-modal';
import { WelcomeModal } from './welcome-modal';
import { MobileAppModal } from './mobile-app-modal';
import './style.scss';
import '../dashboard/style.scss';
import { getAdminSetting } from '~/utils/admin-settings';
import { ProgressTitle } from '../task-lists';
import { WooHomescreenHeaderBanner } from './header-banner-slot';
import { WooHomescreenWCPayFeature } from './wcpay-feature-slot';

const Tasks = lazy( () =>
	import( /* webpackChunkName: "tasks" */ '../tasks' ).then( ( module ) => ( {
		default: module.Tasks,
	} ) )
);

export const Layout = ( {
	defaultHomescreenLayout,
	query,
	taskListComplete,
	hasTaskList,
	showingProgressHeader,
	isLoadingTaskLists,
	shouldShowWelcomeModal,
	shouldShowWelcomeFromCalypsoModal,
	isTaskListHidden,
	updateOptions,
} ) => {
	const userPrefs = useUserPreferences();
	const shouldShowStoreLinks = taskListComplete || isTaskListHidden;
	const shouldShowWCPayFeature = taskListComplete || isTaskListHidden;
	const hasTwoColumnContent =
		shouldShowStoreLinks || window.wcAdminFeatures.analytics;
	const isDashboardShown = ! query.task; // ?&task=<x> query param is used to show tasks instead of the homescreen
	const activeSetupTaskList = useActiveSetupTasklist();

	const twoColumns =
		( userPrefs.homepage_layout || defaultHomescreenLayout ) ===
			'two_columns' && hasTwoColumnContent;

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
					{ <ActivityPanel /> }
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

	const renderTaskList = () => {
		return (
			<Suspense fallback={ <TasksPlaceholder query={ query } /> }>
				{ activeSetupTaskList && isDashboardShown && (
					<>
						<ProgressTitle taskListId={ activeSetupTaskList } />
					</>
				) }
				<Tasks query={ query } />
			</Suspense>
		);
	};

	return (
		<>
			{ isDashboardShown && (
				<WooHomescreenHeaderBanner
					className={ classnames( 'woocommerce-homescreen', {
						'woocommerce-homescreen-column': ! twoColumns,
					} ) }
				/>
			) }
			<div
				className={ classnames( 'woocommerce-homescreen', {
					'two-columns': twoColumns,
				} ) }
			>
				{ isDashboardShown ? renderColumns() : renderTaskList() }
				{ shouldShowWelcomeModal && (
					<WelcomeModal
						onClose={ () => {
							updateOptions( {
								[ WELCOME_MODAL_DISMISSED_OPTION_NAME ]: 'yes',
							} );
						} }
					/>
				) }
				{ shouldShowWelcomeFromCalypsoModal && (
					<WelcomeFromCalypsoModal
						onClose={ () => {
							updateOptions( {
								[ WELCOME_FROM_CALYPSO_MODAL_DISMISSED_OPTION_NAME ]:
									'yes',
							} );
						} }
					/>
				) }
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
	/**
	 * Timestamp of WooCommerce Admin installation.
	 */
	installTimestamp: PropTypes.string,
	/**
	 * Resolution of WooCommerce Admin installation timetsamp.
	 */
	installTimestampHasResolved: PropTypes.bool,
	/**
	 * Dispatch an action to update an option
	 */
	updateOptions: PropTypes.func.isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const { isNotesRequesting } = select( NOTES_STORE_NAME );
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );
		const {
			getTaskList,
			getTaskLists,
			hasFinishedResolution: taskListFinishResolution,
		} = select( ONBOARDING_STORE_NAME );
		const taskLists = getTaskLists();
		const isLoadingTaskLists = ! taskListFinishResolution( 'getTaskLists' );

		const welcomeFromCalypsoModalDismissed =
			getOption( WELCOME_FROM_CALYPSO_MODAL_DISMISSED_OPTION_NAME ) !==
			'no';
		const welcomeFromCalypsoModalDismissedResolved = hasFinishedResolution(
			'getOption',
			[ WELCOME_FROM_CALYPSO_MODAL_DISMISSED_OPTION_NAME ]
		);
		const fromCalypsoUrlArgIsPresent =
			!! window.location.search.match( 'from-calypso' );

		const shouldShowWelcomeFromCalypsoModal =
			welcomeFromCalypsoModalDismissedResolved &&
			! welcomeFromCalypsoModalDismissed &&
			fromCalypsoUrlArgIsPresent;

		const welcomeModalDismissed =
			getOption( WELCOME_MODAL_DISMISSED_OPTION_NAME ) !== 'no';

		const welcomeModalDismissedHasResolved = hasFinishedResolution(
			'getOption',
			[ WELCOME_MODAL_DISMISSED_OPTION_NAME ]
		);

		const shouldShowWelcomeModal =
			welcomeModalDismissedHasResolved &&
			! welcomeModalDismissed &&
			welcomeFromCalypsoModalDismissedResolved &&
			! welcomeFromCalypsoModalDismissed;

		const defaultHomescreenLayout =
			getOption( 'woocommerce_default_homepage_layout' ) ||
			'single_column';

		return {
			defaultHomescreenLayout,
			isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
			shouldShowWelcomeModal,
			shouldShowWelcomeFromCalypsoModal,
			isLoadingTaskLists,
			isTaskListHidden: getTaskList( 'setup' )?.isHidden,
			hasTaskList: getAdminSetting( 'visibleTaskListIds', [] ).length > 0,
			showingProgressHeader: !! taskLists.find(
				( list ) => list.isVisible && list.displayProgressHeader
			),
			taskListComplete: getTaskList( 'setup' )?.isComplete,
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		updateOptions: dispatch( OPTIONS_STORE_NAME ).updateOptions,
	} ) )
)( Layout );
