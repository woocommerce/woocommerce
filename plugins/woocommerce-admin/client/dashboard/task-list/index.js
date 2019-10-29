/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { filter, get } from 'lodash';
import { compose } from '@wordpress/compose';
import classNames from 'classnames';
import { Snackbar, Icon, Button } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, List, MenuItem, EllipsisMenu } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal depdencies
 */
import './style.scss';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';
import { getTasks } from './tasks';

class TaskDashboard extends Component {
	componentDidMount() {
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-task-dashboard__body' );

		this.recordTaskView();
		this.recordTaskListView();

		if ( this.props.inline ) {
			this.props.updateOptions( {
				woocommerce_task_list_complete: true,
			} );
		}
	}

	componentDidUpdate( prevProps ) {
		const { task: prevTask } = prevProps.query;
		const { task } = this.props.query;

		if ( prevTask !== task ) {
			window.document.documentElement.scrollTop = 0;
			this.recordTaskView();
		}
	}

	componentWillUnmount() {
		document.body.classList.remove( 'woocommerce-onboarding' );
		document.body.classList.remove( 'woocommerce-task-dashboard__body' );
	}

	recordTaskView() {
		const { task } = this.props.query;

		if ( ! task ) {
			return;
		}

		recordEvent( 'task_view', {
			task_name: task,
		} );
	}

	recordTaskListView() {
		if ( this.getCurrentTask() ) {
			return;
		}
		const { profileItems } = this.props;
		const tasks = filter( this.props.tasks, task => task.visible );
		recordEvent( 'tasklist_view', {
			number_tasks: tasks.length,
			store_connected: profileItems.wccom_connected,
		} );
	}

	keepTaskCard() {
		recordEvent( 'tasklist_completed', {
			action: 'keep_card',
		} );

		this.props.updateOptions( {
			woocommerce_task_list_prompt_shown: true,
		} );
	}

	hideTaskCard( action ) {
		recordEvent( 'tasklist_completed', {
			action,
		} );
		this.props.updateOptions( {
			woocommerce_task_list_hidden: 'yes',
			woocommerce_task_list_prompt_shown: true,
		} );
	}

	getCurrentTask() {
		const { task } = this.props.query;
		const currentTask = this.props.tasks.find( s => s.key === task );

		if ( ! currentTask ) {
			return null;
		}

		return currentTask;
	}

	renderPrompt() {
		if ( this.props.promptShown ) {
			return null;
		}

		return (
			<Snackbar className="woocommerce-task-card__prompt">
				<div>
					<div className="woocommerce-task-card__prompt-pointer" />
					<span>{ __( 'Is this card useful?', 'woocommerce-admin' ) }</span>
				</div>
				<div className="woocommerce-task-card__prompt-actions">
					<Button isLink onClick={ () => this.hideTaskCard( 'hide_card' ) }>
						{ __( 'No, hide it', 'woocommerce-admin' ) }
					</Button>

					<Button isLink onClick={ () => this.keepTaskCard() }>
						{ __( 'Yes, keep it', 'woocommerce-admin' ) }
					</Button>
				</div>
			</Snackbar>
		);
	}

	renderMenu() {
		return (
			<EllipsisMenu
				label={ __( 'Task List Options', 'woocommerce-admin' ) }
				renderContent={ () => (
					<div className="woocommerce-task-card__section-controls">
						<MenuItem isClickable onInvoke={ () => this.hideTaskCard( 'remove_card' ) }>
							<Icon icon={ 'trash' } label={ __( 'Remove block' ) } />
							{ __( 'Remove this card', 'woocommerce-admin' ) }
						</MenuItem>
					</div>
				) }
			/>
		);
	}

	render() {
		const currentTask = this.getCurrentTask();
		const tasks = filter( this.props.tasks, task => task.visible ).map( task => {
			task.className = classNames( task.completed ? 'is-complete' : null, task.className );
			task.before = task.completed ? (
				<i className="material-icons-outlined">check_circle</i>
			) : (
				<i className="material-icons-outlined">{ task.icon }</i>
			);
			task.after = <i className="material-icons-outlined">chevron_right</i>;

			if ( ! task.onClick ) {
				task.onClick = () => updateQueryString( { task: task.key } );
			}

			return task;
		} );

		return (
			<Fragment>
				<div className="woocommerce-task-dashboard__container">
					{ currentTask ? (
						currentTask.container
					) : (
						<Fragment>
							<Card
								className="woocommerce-task-card"
								title={ __( 'Set up your store and start selling', 'woocommerce-admin' ) }
								description={ __(
									'Below you’ll find a list of the most important steps to get your store up and running.',
									'woocommerce-admin'
								) }
								menu={ this.props.inline && this.renderMenu() }
							>
								<List items={ tasks } />
							</Card>
							{ this.props.inline && this.renderPrompt() }
						</Fragment>
					) }
				</div>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { getProfileItems, getOptions } = select( 'wc-api' );
		const profileItems = getProfileItems();

		const promptShown = get(
			getOptions( [ 'woocommerce_task_list_prompt_shown' ] ),
			[ 'woocommerce_task_list_prompt_shown' ],
			false
		);

		const tasks = getTasks( {
			profileItems,
			options: getOptions( [ 'woocommerce_onboarding_payments' ] ),
			query: props.query,
		} );

		return {
			profileItems,
			promptShown,
			tasks,
		};
	} ),
	withDispatch( dispatch => {
		const { updateOptions } = dispatch( 'wc-api' );
		return {
			updateOptions,
		};
	} )
)( TaskDashboard );
