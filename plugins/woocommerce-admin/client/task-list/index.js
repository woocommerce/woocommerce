/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuGroup, MenuItem } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { check } from '@wordpress/icons';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import CartModal from '../dashboard/components/cart-modal';
import { getAllTasks } from './tasks';
import { getCountryCode } from '../dashboard/utils';
import TaskList from './list';
import { DisplayOption } from '../header/activity-panel/display-options';

export class TaskDashboard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isCartModalOpen: false,
		};

		this.toggleExtensionTaskList = this.toggleExtensionTaskList.bind(
			this
		);
	}
	componentDidMount() {
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-task-dashboard__body' );
	}

	getTaskStartedCount = ( taskName ) => {
		const { userPreferences } = this.props;
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks;
		if ( ! trackedStartedTasks || ! trackedStartedTasks[ taskName ] ) {
			return 0;
		}
		return trackedStartedTasks[ taskName ];
	};

	updateTrackStartedCount = ( taskName, newCount ) => {
		const { userPreferences } = this.props;
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks || {};
		userPreferences.updateUserPreferences( {
			task_list_tracked_started_tasks: {
				...( trackedStartedTasks || {} ),
				[ taskName ]: newCount,
			},
		} );
	};

	isTaskCompleted = ( taskName ) => {
		const { trackedCompletedTasks } = this.props;
		if ( ! trackedCompletedTasks ) {
			return false;
		}
		return trackedCompletedTasks.includes( taskName );
	};

	onTaskSelect = ( taskName ) => {
		const trackStartedCount = this.getTaskStartedCount( taskName );
		recordEvent( 'tasklist_click', {
			task_name: taskName,
		} );
		if ( ! this.isTaskCompleted( taskName ) ) {
			this.updateTrackStartedCount( taskName, trackStartedCount + 1 );
		}
	};

	toggleExtensionTaskList = () => {
		const { isExtendedTaskListHidden, updateOptions } = this.props;
		const newValue = ! isExtendedTaskListHidden;

		recordEvent(
			newValue ? 'extended_tasklist_hide' : 'extended_tasklist_show'
		);
		updateOptions( {
			woocommerce_extended_task_list_hidden: newValue ? 'yes' : 'no',
		} );
	};

	getAllTasks() {
		const {
			activePlugins,
			countryCode,
			createNotice,
			installAndActivatePlugins,
			installedPlugins,
			isJetpackConnected,
			onboardingStatus,
			profileItems,
			query,
		} = this.props;

		return getAllTasks( {
			activePlugins,
			countryCode,
			createNotice,
			installAndActivatePlugins,
			installedPlugins,
			isJetpackConnected,
			onboardingStatus,
			profileItems,
			query,
			toggleCartModal: this.toggleCartModal.bind( this ),
			onTaskSelect: this.onTaskSelect,
		} );
	}

	toggleCartModal() {
		const { isCartModalOpen } = this.state;

		if ( ! isCartModalOpen ) {
			recordEvent( 'tasklist_purchase_extensions' );
		}

		this.setState( { isCartModalOpen: ! isCartModalOpen } );
	}

	render() {
		const {
			dismissedTasks,
			isExtendedTaskListComplete,
			isExtendedTaskListHidden,
			isSetupTaskListHidden,
			isTaskListComplete,
			query,
			trackedCompletedTasks,
		} = this.props;
		const { isCartModalOpen } = this.state;
		const allTasks = this.getAllTasks();
		const { extension, setup: setupTasks } = allTasks;
		const { task } = query;

		const extensionTasks =
			Array.isArray( extension ) &&
			extension.sort( ( a, b ) => {
				if ( Boolean( a.completed ) === Boolean( b.completed ) ) {
					return 0;
				}

				return a.completed ? 1 : -1;
			} );

		return (
			<>
				{ setupTasks && ( ! isSetupTaskListHidden || task ) && (
					<TaskList
						dismissedTasks={ dismissedTasks || [] }
						isComplete={ isTaskListComplete }
						query={ query }
						tasks={ setupTasks }
						title={ __(
							'Get ready to start selling',
							'woocommerce-admin'
						) }
						trackedCompletedTasks={ trackedCompletedTasks || [] }
					/>
				) }
				{ extensionTasks && (
					<DisplayOption>
						<MenuGroup
							className="woocommerce-layout__homescreen-display-options"
							label={ __( 'Display', 'woocommerce-admin' ) }
						>
							<MenuItem
								className="woocommerce-layout__homescreen-extension-tasklist-toggle"
								icon={ ! isExtendedTaskListHidden && check }
								isSelected={ ! isExtendedTaskListHidden }
								role="menuitemcheckbox"
								onClick={ this.toggleExtensionTaskList }
							>
								{ __(
									'Show things to do next',
									'woocommerce-admin'
								) }
							</MenuItem>
						</MenuGroup>
					</DisplayOption>
				) }
				{ extensionTasks && ! isExtendedTaskListHidden && (
					<TaskList
						dismissedTasks={ dismissedTasks || [] }
						isComplete={ isExtendedTaskListComplete }
						name={ 'extended_task_list' }
						query={ query }
						tasks={ extensionTasks }
						title={ __( 'Things to do next', 'woocommerce-admin' ) }
						trackedCompletedTasks={ trackedCompletedTasks || [] }
					/>
				) }
				{ isCartModalOpen && (
					<CartModal
						onClose={ () => this.toggleCartModal() }
						onClickPurchaseLater={ () => this.toggleCartModal() }
					/>
				) }
			</>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getTasksStatus } = select(
			ONBOARDING_STORE_NAME
		);
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const {
			getActivePlugins,
			getInstalledPlugins,
			isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );
		const profileItems = getProfileItems();

		const trackedCompletedTasks =
			getOption( 'woocommerce_task_list_tracked_completed_tasks' ) || [];

		const { general: generalSettings = {} } = getSettings( 'general' );
		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);

		const activePlugins = getActivePlugins();
		const installedPlugins = getInstalledPlugins();
		const onboardingStatus = getTasksStatus();

		return {
			activePlugins,
			countryCode,
			dismissedTasks: getOption(
				'woocommerce_task_list_dismissed_tasks'
			),
			isExtendedTaskListComplete:
				getOption( 'woocommerce_extended_task_list_complete' ) ===
				'yes',
			isExtendedTaskListHidden:
				getOption( 'woocommerce_extended_task_list_hidden' ) === 'yes',
			isJetpackConnected: isJetpackConnected(),
			isSetupTaskListHidden:
				getOption( 'woocommerce_task_list_hidden' ) === 'yes',
			isTaskListComplete:
				getOption( 'woocommerce_task_list_complete' ) === 'yes',
			installedPlugins,
			onboardingStatus,
			profileItems,
			trackedCompletedTasks,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );

		return {
			createNotice,
			installAndActivatePlugins,
			updateOptions,
		};
	} )
)( TaskDashboard );
