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
import ActivityHeader from '../header/activity-panel/activity-header';
import { ActivityPanel } from './activity-panel';

import './style.scss';
import '../dashboard/style.scss';
import { StoreManagementLinks } from '../store-management-links';
import { Column } from './column';

const TaskList = lazy( () =>
	import( /* webpackChunkName: "task-list" */ '../task-list' )
);

export const Layout = ( {
	defaultHomescreenLayout,
	isBatchUpdating,
	query,
	requestingTaskList,
	taskListHidden,
	shouldShowWelcomeModal,
	updateOptions,
} ) => {
	const userPrefs = useUserPreferences();
	const twoColumns =
		( userPrefs.homepage_layout || defaultHomescreenLayout ) ===
		'two_columns';
	const [ showInbox, setShowInbox ] = useState( true );

	const isTaskListEnabled = taskListHidden === false;
	const isDashboardShown = ! isTaskListEnabled || ! query.task;

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
				<TaskList query={ query } />
			</Suspense>
		);
	};

	return (
		<div
			className={ classnames( 'woocommerce-homescreen', {
				'two-columns': twoColumns,
			} ) }
		>
			{ isDashboardShown
				? renderColumns()
				: isTaskListEnabled && renderTaskList() }
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

		const welcomeModalDismissed =
			getOption( 'woocommerce_task_list_welcome_modal_dismissed' ) ===
			'yes';

		const welcomeModalDismissedHasResolved = hasFinishedResolution(
			'getOption',
			[ 'woocommerce_task_list_welcome_modal_dismissed' ]
		);

		const shouldShowWelcomeModal =
			welcomeModalDismissedHasResolved && ! welcomeModalDismissed;

		const defaultHomescreenLayout =
			getOption( 'woocommerce_default_homepage_layout' ) ||
			'single_column';

		return {
			defaultHomescreenLayout,
			isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
			shouldShowWelcomeModal,
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
