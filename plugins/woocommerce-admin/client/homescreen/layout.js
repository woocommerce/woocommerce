/**
 * External dependencies
 */
import {
	Fragment,
	Suspense,
	lazy,
	useState,
	useRef,
	useEffect,
} from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { NOTES_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import QuickLinks from '../quick-links';
import StatsOverview from './stats-overview';
import './style.scss';
import '../dashboard/style.scss';
import { isOnboardingEnabled } from '../dashboard/utils';
import TaskListPlaceholder from '../task-list/placeholder';
import InboxPanel from '../header/activity-panel/panels/inbox';
import withWCApiSelect from '../wc-api/with-select';
import { WelcomeModal } from './welcome-modal';

const TaskList = lazy( () =>
	import( /* webpackChunkName: "task-list" */ '../task-list' )
);

export const Layout = ( {
	isBatchUpdating,
	query,
	requestingTaskList,
	taskListComplete,
	taskListHidden,
	shouldShowWelcomeModal,
	updateOptions,
} ) => {
	const [ showInbox, setShowInbox ] = useState( true );
	const [ isContentSticky, setIsContentSticky ] = useState( false );
	const content = useRef( null );
	const maybeStickContent = () => {
		if ( ! content.current ) {
			return;
		}
		const { bottom } = content.current.getBoundingClientRect();
		const shouldBeSticky = showInbox && bottom < window.innerHeight;

		setIsContentSticky( shouldBeSticky );
	};

	useEffect( () => {
		maybeStickContent();
		window.addEventListener( 'resize', maybeStickContent );

		return () => {
			window.removeEventListener( 'resize', maybeStickContent );
		};
	}, [] );

	const isTaskListEnabled = taskListHidden === false && ! taskListComplete;
	const isDashboardShown = ! isTaskListEnabled || ! query.task;

	const isInboxPanelEmpty = ( isEmpty ) => {
		if ( isBatchUpdating ) {
			return;
		}
		setShowInbox( ! isEmpty );
	};

	if ( isBatchUpdating && ! showInbox ) {
		setShowInbox( true );
	}

	const renderColumns = () => {
		return (
			<Fragment>
				{ showInbox && (
					<div className="woocommerce-homescreen-column is-inbox">
						<InboxPanel isPanelEmpty={ isInboxPanelEmpty } />
					</div>
				) }
				<div
					className="woocommerce-homescreen-column"
					ref={ content }
					style={ {
						position: isContentSticky ? 'sticky' : 'static',
					} }
				>
					{ isTaskListEnabled && renderTaskList() }
					<StatsOverview />
					{ ! isTaskListEnabled && <QuickLinks /> }
				</div>
			</Fragment>
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
				hasInbox: showInbox,
			} ) }
		>
			{ isDashboardShown
				? renderColumns()
				: isTaskListEnabled && renderTaskList() }
			{ shouldShowWelcomeModal && (
				<WelcomeModal
					onClose={ () => {
						updateOptions( {
							woocommerce_task_list_welcome_modal_dismissed: true,
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
	withWCApiSelect( ( select ) => {
		const { isNotesRequesting } = select( NOTES_STORE_NAME );
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		const welcomeModalDismissed =
			getOption( 'woocommerce_task_list_welcome_modal_dismissed' ) ===
			'1';

		const welcomeModalDismissedIsResolving = isResolving( 'getOption', [
			'woocommerce_task_list_welcome_modal_dismissed',
		] );

		const shouldShowWelcomeModal =
			! welcomeModalDismissedIsResolving && ! welcomeModalDismissed;

		if ( isOnboardingEnabled() ) {
			return {
				isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
				shouldShowWelcomeModal,
				taskListComplete:
					getOption( 'woocommerce_task_list_complete' ) === 'yes',
				taskListHidden:
					getOption( 'woocommerce_task_list_hidden' ) === 'yes',
				requestingTaskList:
					isResolving( 'getOption', [
						'woocommerce_task_list_complete',
					] ) ||
					isResolving( 'getOption', [
						'woocommerce_task_list_hidden',
					] ),
			};
		}

		return {
			shouldShowWelcomeModal,
			requestingTaskList: false,
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		updateOptions: dispatch( OPTIONS_STORE_NAME ).updateOptions,
	} ) )
)( Layout );
