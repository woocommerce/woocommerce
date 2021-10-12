/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { parse } from 'qs';
import deprecated from '@wordpress/deprecated';

function getQuery() {
	const searchString = window.location && window.location.search;
	if ( ! searchString ) {
		return {};
	}

	const search = searchString.substring( 1 );
	return parse( search );
}

/**
 * A simple class to handle deprecated tasks using the woocommerce_admin_onboarding_task_list filter.
 */
export class DeprecatedTasks {
	constructor() {
		this.filteredTasks = applyFilters(
			'woocommerce_admin_onboarding_task_list',
			[],
			getQuery()
		);
		if ( this.filteredTasks && this.filteredTasks.length > 0 ) {
			deprecated( 'woocommerce_admin_onboarding_task_list', {
				version: '2.10.0',
				alternative: 'TaskLists::add_task()',
				plugin: '@woocommerce/data',
			} );
		}
		this.tasks = this.filteredTasks.reduce( ( org, task ) => {
			return {
				...org,
				[ task.key ]: task,
			};
		}, {} );
	}

	hasDeprecatedTasks() {
		return this.filteredTasks.length > 0;
	}

	getPostData() {
		return this.hasDeprecatedTasks()
			? {
					extended_tasks: this.filteredTasks.map( ( task ) => ( {
						title: task.title,
						content: task.content,
						additional_info: task.additionalInfo,
						time: task.time,
						level: task.level ? parseInt( task.level, 10 ) : 3,
						list_id: task.type || 'extended',
						is_visible: task.visible,
						id: task.key,
						is_snoozeable: task.allowRemindMeLater,
						is_dismissable: task.isDismissable,
						is_complete: task.completed,
					} ) ),
			  }
			: null;
	}

	mergeDeprecatedCallbackFunctions( taskLists ) {
		if ( this.filteredTasks.length > 0 ) {
			for ( const taskList of taskLists ) {
				// Merge any extended task list items, to keep the callback functions around.
				taskList.tasks = taskList.tasks.map( ( task ) => {
					if ( this.tasks && this.tasks[ task.id ] ) {
						return {
							...this.tasks[ task.id ],
							...task,
							isDeprecated: true,
						};
					}
					return task;
				} );
			}
		}
		return taskLists;
	}

	/**
	 * Used to keep backwards compatibility with the extended task list filter on the client.
	 * This can be removed after version WC Admin 2.10 (see deprecated notice in resolvers.js).
	 *
	 * @param {Object} task the returned task object.
	 * @param {Array}  keys to keep in the task object.
	 * @return {Object} task with the keys specified.
	 */
	static possiblyPruneTaskData( task, keys ) {
		if ( ! task.time && ! task.title ) {
			// client side task
			return keys.reduce(
				( simplifiedTask, key ) => {
					return {
						...simplifiedTask,
						[ key ]: task[ key ],
					};
				},
				{ id: task.id }
			);
		}
		return task;
	}
}
