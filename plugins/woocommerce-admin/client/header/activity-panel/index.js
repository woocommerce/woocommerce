/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clickOutside from 'react-click-outside';
import { Component, lazy, Suspense } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { uniqueId, find } from 'lodash';
import CrossIcon from 'gridicons/dist/cross-small';
import classnames from 'classnames';
import { Icon, help as helpIcon } from '@wordpress/icons';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { H, Section, Spinner } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanelToggleBubble from './toggle-bubble';
import { getUnreadNotes } from './unread-indicators';
import { isWCAdmin } from '../../dashboard/utils';
import { Tabs } from './tabs';
import { SetupProgress } from './setup-progress';
import { DisplayOptions } from './display-options';
import { HighlightTooltip } from './highlight-tooltip';

const HelpPanel = lazy( () =>
	import( /* webpackChunkName: "activity-panels-help" */ './panels/help' )
);

const InboxPanel = lazy( () =>
	import(
		/* webpackChunkName: "activity-panels-inbox" */ '../../inbox-panel'
	)
);

export class ActivityPanel extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isPanelOpen: false,
			mobileOpen: false,
			currentTab: '',
			isPanelSwitching: false,
		};
	}

	togglePanel( { name: tabName }, isTabOpen ) {
		this.setState( ( state ) => {
			const isPanelSwitching =
				tabName !== state.currentTab &&
				state.currentTab !== '' &&
				isTabOpen &&
				state.isPanelOpen;

			return {
				isPanelOpen: isTabOpen,
				mobileOpen: isTabOpen,
				currentTab: tabName,
				isPanelSwitching,
			};
		} );
	}

	closePanel() {
		this.setState( () => ( {
			isPanelOpen: false,
			currentTab: '',
		} ) );
	}

	clearPanel() {
		this.setState( () => ( {
			isPanelSwitching: false,
		} ) );
	}

	// On smaller screen, the panel buttons are hidden behind a toggle.
	toggleMobile() {
		const tabs = this.getTabs();
		this.setState( ( state ) => ( {
			mobileOpen: ! state.mobileOpen,
			currentTab: state.mobileOpen ? '' : tabs[ 0 ].name,
			isPanelOpen: ! state.mobileOpen,
		} ) );
	}

	handleClickOutside( event ) {
		const { isPanelOpen } = this.state;
		const isClickOnModalOrSnackbar =
			event.target.closest(
				'.woocommerce-inbox-dismiss-confirmation_modal'
			) || event.target.closest( '.components-snackbar__action' );

		if ( isPanelOpen && ! isClickOnModalOrSnackbar ) {
			this.closePanel();
		}
	}

	isHomescreen() {
		const { location } = this.props.getHistory();

		return location.pathname === '/';
	}

	isPerformingSetupTask() {
		const {
			requestingTaskListOptions,
			taskListComplete,
			taskListHidden,
			query,
		} = this.props;

		const isPerformingSetupTask =
			query.task &&
			! query.path &&
			( requestingTaskListOptions === true ||
				( taskListHidden === false && taskListComplete === false ) );

		return isPerformingSetupTask;
	}

	// @todo Pull in dynamic unread status/count
	getTabs() {
		const {
			hasUnreadNotes,
			isEmbedded,
			taskListComplete,
			taskListHidden,
		} = this.props;

		const isPerformingSetupTask = this.isPerformingSetupTask();

		// Don't show the inbox on the Home screen.
		const showInbox =
			( isEmbedded || ! this.isHomescreen() ) && ! isPerformingSetupTask;

		const showHelp =
			( this.isHomescreen() && ! isEmbedded ) || isPerformingSetupTask;

		const showDisplayOptions =
			! isEmbedded && this.isHomescreen() && ! isPerformingSetupTask;

		const showStoreSetup =
			! taskListComplete &&
			! taskListHidden &&
			! isPerformingSetupTask &&
			( ! this.isHomescreen() || isEmbedded );

		const inbox = showInbox
			? {
					name: 'inbox',
					title: __( 'Inbox', 'woocommerce-admin' ),
					icon: <i className="material-icons-outlined">inbox</i>,
					unread: hasUnreadNotes,
			  }
			: null;

		const setup = showStoreSetup
			? {
					name: 'setup',
					title: __( 'Store Setup', 'woocommerce-admin' ),
					icon: <SetupProgress />,
			  }
			: null;

		const help = showHelp
			? {
					name: 'help',
					title: __( 'Help', 'woocommerce-admin' ),
					icon: <Icon icon={ helpIcon } />,
			  }
			: null;

		const displayOptions = showDisplayOptions
			? {
					component: DisplayOptions,
			  }
			: null;

		return [ inbox, setup, displayOptions, help ].filter( Boolean );
	}

	getPanelContent( tab ) {
		const { query } = this.props;
		const { task } = query;

		switch ( tab ) {
			case 'inbox':
				return <InboxPanel />;
			case 'help':
				return <HelpPanel taskName={ task } />;
			default:
				return null;
		}
	}

	renderPanel() {
		const { updateOptions, taskListHidden } = this.props;
		const { isPanelOpen, currentTab, isPanelSwitching } = this.state;
		const tab = find( this.getTabs(), { name: currentTab } );

		if ( ! tab ) {
			return (
				<div className="woocommerce-layout__activity-panel-wrapper" />
			);
		}

		const clearPanel = () => {
			this.clearPanel();
		};

		if ( currentTab === 'display-options' ) {
			return null;
		}

		if ( currentTab === 'setup' ) {
			const currentLocation = window.location.href;
			const homescreenLocation = getAdminLink(
				'admin.php?page=wc-admin'
			);

			// Don't navigate if we're already on the homescreen, this will cause an infinite loop
			if ( currentLocation !== homescreenLocation ) {
				// Ensure that if the user is trying to get to the task list they can see it even if
				// it was dismissed.
				if ( taskListHidden === 'no' ) {
					this.redirectToHomeScreen();
				} else {
					updateOptions( {
						woocommerce_task_list_hidden: 'no',
					} ).then( this.redirectToHomeScreen );
				}
			}

			return null;
		}

		const classNames = classnames(
			'woocommerce-layout__activity-panel-wrapper',
			{
				'is-open': isPanelOpen,
				'is-switching': isPanelSwitching,
			}
		);

		return (
			<div
				className={ classNames }
				tabIndex={ 0 }
				role="tabpanel"
				aria-label={ tab.title }
				onTransitionEnd={ clearPanel }
				onAnimationEnd={ clearPanel }
			>
				<div
					className="woocommerce-layout__activity-panel-content"
					key={ 'activity-panel-' + currentTab }
					id={ 'activity-panel-' + currentTab }
				>
					<Suspense fallback={ <Spinner /> }>
						{ this.getPanelContent( currentTab ) }
					</Suspense>
				</div>
			</div>
		);
	}

	redirectToHomeScreen() {
		if ( isWCAdmin( window.location.href ) ) {
			getHistory().push( getNewPath( {}, '/', {} ) );
		} else {
			window.location.href = getAdminLink( 'admin.php?page=wc-admin' );
		}
	}

	closedHelpPanelHighlight() {
		const { userPreferencesData } = this.props;
		recordEvent( 'help_tooltip_click' );
		if (
			userPreferencesData &&
			userPreferencesData.updateUserPreferences
		) {
			userPreferencesData.updateUserPreferences( {
				help_panel_highlight_shown: 'yes',
			} );
		}
	}

	shouldShowHelpTooltip() {
		const {
			userPreferencesData,
			trackedCompletedTasks,
			query,
		} = this.props;
		const { task } = query;
		const startedTasks =
			userPreferencesData &&
			userPreferencesData.task_list_tracked_started_tasks;
		const highlightShown =
			userPreferencesData &&
			userPreferencesData.help_panel_highlight_shown;
		if (
			task &&
			highlightShown !== 'yes' &&
			( startedTasks || {} )[ task ] > 1 &&
			! trackedCompletedTasks.includes( task )
		) {
			return true;
		}
		return false;
	}

	render() {
		const tabs = this.getTabs();
		const { mobileOpen, currentTab, isPanelOpen } = this.state;
		const headerId = uniqueId( 'activity-panel-header_' );
		const panelClasses = classnames( 'woocommerce-layout__activity-panel', {
			'is-mobile-open': this.state.mobileOpen,
		} );

		const showHelpHighlightTooltip = this.shouldShowHelpTooltip();
		const hasUnread = tabs.some( ( tab ) => tab.unread );
		const viewLabel = hasUnread
			? __(
					'View Activity Panel, you have unread activity',
					'woocommerce-admin'
			  )
			: __( 'View Activity Panel', 'woocommerce-admin' );

		return (
			<div>
				<H id={ headerId } className="screen-reader-text">
					{ __( 'Store Activity', 'woocommerce-admin' ) }
				</H>
				<Section
					component="aside"
					id="woocommerce-activity-panel"
					aria-labelledby={ headerId }
				>
					<Button
						onClick={ () => {
							this.toggleMobile();
						} }
						label={
							mobileOpen
								? __(
										'Close Activity Panel',
										'woocommerce-admin'
								  )
								: viewLabel
						}
						aria-expanded={ mobileOpen }
						className="woocommerce-layout__activity-panel-mobile-toggle"
					>
						{ mobileOpen ? (
							<CrossIcon />
						) : (
							<ActivityPanelToggleBubble
								hasUnread={ hasUnread }
							/>
						) }
					</Button>
					<div className={ panelClasses }>
						<Tabs
							tabs={ tabs }
							tabOpen={ isPanelOpen }
							selectedTab={ currentTab }
							onTabClick={ ( tab, tabOpen ) => {
								this.togglePanel( tab, tabOpen );
							} }
						/>
						{ this.renderPanel() }
					</div>
				</Section>
				{ showHelpHighlightTooltip ? (
					<HighlightTooltip
						delay={ 1000 }
						title={ __(
							"We're here for help",
							'woocommerce-admin'
						) }
						content={ __(
							'If you have any questions, feel free to explore the WooCommerce docs listed here.',
							'woocommerce-admin'
						) }
						closeButtonText={ __( 'Got it', 'woocommerce-admin' ) }
						id="activity-panel-tab-help"
						onClose={ () => this.closedHelpPanelHighlight() }
						onShow={ () => recordEvent( 'help_tooltip_view' ) }
					/>
				) : null }
			</div>
		);
	}
}

ActivityPanel.defaultProps = {
	getHistory,
};

export default compose(
	withSelect( ( select ) => {
		const hasUnreadNotes = getUnreadNotes( select );
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		const taskListComplete =
			getOption( 'woocommerce_task_list_complete' ) === 'yes';
		const taskListHidden =
			getOption( 'woocommerce_task_list_hidden' ) === 'yes';
		const requestingTaskListOptions =
			isResolving( 'getOption', [ 'woocommerce_task_list_complete' ] ) ||
			isResolving( 'getOption', [ 'woocommerce_task_list_hidden' ] );
		const trackedCompletedTasks =
			getOption( 'woocommerce_task_list_tracked_completed_tasks' ) || [];

		return {
			hasUnreadNotes,
			requestingTaskListOptions,
			taskListComplete,
			taskListHidden,
			trackedCompletedTasks,
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		updateOptions: dispatch( OPTIONS_STORE_NAME ).updateOptions,
	} ) ),
	clickOutside
)( ActivityPanel );
