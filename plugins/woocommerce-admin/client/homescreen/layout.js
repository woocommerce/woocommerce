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
import StatsOverview from './stats-overview';
import TaskListPlaceholder from '../task-list/placeholder';
import InboxPanel from '../inbox-panel';
import { WelcomeModal } from './welcome-modal';
import { WelcomeFromCalypsoModal } from './welcome-from-calypso-modal';
import ActivityHeader from '../header/activity-panel/activity-header';
import { ActivityPanel } from './activity-panel';

import './style.scss';
import '../dashboard/style.scss';
import { StoreManagementLinks } from '../store-management-links';
import { Column } from './column';

const TaskList = lazy( () =>
	import( /* webpackChunkName: "task-list" */ '../task-list' )
);

const WELCOME_FROM_CALYPSO_MODAL_DISMISSED_OPTION_NAME =
	'woocommerce_welcome_from_calypso_modal_dismissed';

export const Layout = ( {
	defaultHomescreenLayout,
	isBatchUpdating,
	query,
	requestingTaskList,
	taskListHidden,
	shouldShowWelcomeModal,
	shouldShowWelcomeFromCalypsoModal,
	updateOptions,
} ) => {
	const userPrefs = useUserPreferences();
	const twoColumns =
		( userPrefs.homepage_layout || defaultHomescreenLayout ) ===
		'two_columns';
	const [ showInbox, setShowInbox ] = useState( true );

	const isTaskListEnabled = taskListHidden === false;
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
					{ ! isTaskListEnabled && shouldStickColumns && (
						<StoreManagementLinks />
					) }
				</Column>
				<Column shouldStick={ shouldStickColumns }>
					<StatsOverview />
					<InboxPanel />
					{ ! isTaskListEnabled && ! shouldStickColumns && (
						<StoreManagementLinks />
					) }
				</Column>
			</>
		);
	};

	const renderTaskList = () => {
		if ( requestingTaskList ) {
			return <TaskListPlaceholder />;
		}

		return (
			<Suspense fallback={ <TaskListPlaceholder /> }>
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
							woocommerce_task_list_welcome_modal_dismissed:
								'yes',
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
		</div>
	);
};

Layout.propTypes = {
	/**
	 * If the task list option is being fetched.
	 */
	requestingTaskList: PropTypes.bool.isRequired,
	/**
	 * If the task list has been completed.
	 */
	taskListComplete: PropTypes.bool,
	/**
	 * If the task list is hidden.
	 */
	taskListHidden: PropTypes.bool,
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
		const { getOption, isResolving, hasFinishedResolution } = select(
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
			getOption( 'woocommerce_task_list_welcome_modal_dismissed' ) ===
			'yes';

		const welcomeModalDismissedHasResolved = hasFinishedResolution(
			'getOption',
			[ 'woocommerce_task_list_welcome_modal_dismissed' ]
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
			taskListHidden:
				getOption( 'woocommerce_task_list_hidden' ) === 'yes' &&
				getOption( 'woocommerce_extended_task_list_hidden' ) !== 'no',
			requestingTaskList:
				isResolving( 'getOption', [
					'woocommerce_task_list_complete',
				] ) ||
				isResolving( 'getOption', [
					'woocommerce_task_list_hidden',
				] ) ||
				isResolving( 'getOption', [
					'woocommerce_extended_task_list_hidden',
				] ),
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		updateOptions: dispatch( OPTIONS_STORE_NAME ).updateOptions,
	} ) )
)( Layout );
