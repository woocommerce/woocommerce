/**
 * External dependencies
 */
import {
	Suspense,
	lazy,
	useCallback,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import {
	useUserPreferences,
	NOTES_STORE_NAME,
	OPTIONS_STORE_NAME,
} from '@woocommerce/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ActivityHeader from '../header/activity-panel/activity-header';
import { ActivityPanel } from './activity-panel';
import { Column } from './column';
import InboxPanel from '../inbox-panel';
import { IntroModal as NavigationIntroModal } from '../navigation/components/intro-modal';
import StatsOverview from './stats-overview';
import { StoreManagementLinks } from '../store-management-links';
import TaskListPlaceholder from '../task-list/placeholder';
import { TasksPlaceholder } from '../tasks';
import {
	WELCOME_MODAL_DISMISSED_OPTION_NAME,
	WELCOME_FROM_CALYPSO_MODAL_DISMISSED_OPTION_NAME,
} from './constants';
import { WelcomeFromCalypsoModal } from './welcome-from-calypso-modal';
import { WelcomeModal } from './welcome-modal';
import './style.scss';
import '../dashboard/style.scss';

const TaskList = lazy( () =>
	import( /* webpackChunkName: "task-list" */ '../task-list' )
);

const Tasks = lazy( () =>
	import( /* webpackChunkName: "tasks" */ '../tasks' )
);

export const Layout = ( {
	defaultHomescreenLayout,
	isBatchUpdating,
	query,
	taskListComplete,
	bothTaskListsHidden,
	shouldShowWelcomeModal,
	shouldShowWelcomeFromCalypsoModal,
	isTaskListHidden,
	updateOptions,
} ) => {
	const userPrefs = useUserPreferences();
	const shouldShowStoreLinks = taskListComplete || isTaskListHidden;
	const hasTwoColumnContent =
		shouldShowStoreLinks || window.wcAdminFeatures.analytics;
	const twoColumns =
		( userPrefs.homepage_layout || defaultHomescreenLayout ) ===
			'two_columns' && hasTwoColumnContent;
	const [ showInbox, setShowInbox ] = useState( true );

	const isTaskListEnabled = bothTaskListsHidden === false;
	const isDashboardShown = ! query.task;

	if ( isBatchUpdating && ! showInbox ) {
		setShowInbox( true );
	}

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

	const renderColumns = () => {
		return (
			<>
				<Column shouldStick={ shouldStickColumns }>
					<ActivityHeader
						className="your-store-today"
						title={ __( 'Your store today', 'woocommerce-admin' ) }
						subtitle={ __(
							"To do's, tips, and insights for your business",
							'woocommerce-admin'
						) }
					/>
					<ActivityPanel />
					{ isTaskListEnabled && renderTaskList() }
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
		const isSingleTask = Boolean( query.task );

		if ( window.wcAdminFeatures && window.wcAdminFeatures.tasks ) {
			return (
				<Suspense fallback={ <TasksPlaceholder query={ query } /> }>
					<Tasks query={ query } />
				</Suspense>
			);
		}

		return (
			<Suspense
				fallback={ isSingleTask ? null : <TaskListPlaceholder /> }
			>
				<TaskList query={ query } userPreferences={ userPrefs } />
			</Suspense>
		);
	};

	return (
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
			{ window.wcAdminFeatures.navigation && <NavigationIntroModal /> }
		</div>
	);
};

Layout.propTypes = {
	/**
	 * If the task list has been completed.
	 */
	taskListComplete: PropTypes.bool,
	/**
	 * If the task list is hidden.
	 */
	bothTaskListsHidden: PropTypes.bool,
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
	 * Dispatch an action to update an option
	 */
	updateOptions: PropTypes.func.isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const { isNotesRequesting } = select( NOTES_STORE_NAME );
		const { getOption, hasFinishedResolution } = select(
			OPTIONS_STORE_NAME
		);

		const welcomeFromCalypsoModalDismissed =
			getOption( WELCOME_FROM_CALYPSO_MODAL_DISMISSED_OPTION_NAME ) ===
			'yes';
		const welcomeFromCalypsoModalDismissedResolved = hasFinishedResolution(
			'getOption',
			[ WELCOME_FROM_CALYPSO_MODAL_DISMISSED_OPTION_NAME ]
		);
		const fromCalypsoUrlArgIsPresent = !! window.location.search.match(
			'from-calypso'
		);

		const shouldShowWelcomeFromCalypsoModal =
			welcomeFromCalypsoModalDismissedResolved &&
			! welcomeFromCalypsoModalDismissed &&
			fromCalypsoUrlArgIsPresent;

		const welcomeModalDismissed =
			getOption( WELCOME_MODAL_DISMISSED_OPTION_NAME ) === 'yes';

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
		const isTaskListHidden =
			getOption( 'woocommerce_task_list_hidden' ) === 'yes';

		return {
			defaultHomescreenLayout,
			isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
			shouldShowWelcomeModal,
			shouldShowWelcomeFromCalypsoModal,
			isTaskListHidden,
			bothTaskListsHidden:
				isTaskListHidden &&
				getOption( 'woocommerce_extended_task_list_hidden' ) === 'yes',
			taskListComplete:
				getOption( 'woocommerce_task_list_complete' ) === 'yes',
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		updateOptions: dispatch( OPTIONS_STORE_NAME ).updateOptions,
	} ) )
)( Layout );
