/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Card, CardHeader } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Badge } from '@woocommerce/components';
import {
	getVisibleTasks,
	ONBOARDING_STORE_NAME,
	TaskListType,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text, List, CollapsibleList } from '@woocommerce/experimental';
import { useLayoutContext } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { TaskListItem } from './task-list-item';
import { TaskListMenu } from './task-list-menu';
import './task-list.scss';
import { ProgressHeader } from '~/task-lists/components/progress-header';

export type TaskListProps = TaskListType & {
	query: {
		task?: string;
	};
	eventName?: string;
	keepCompletedTaskList?: 'yes' | 'no';
	cesHeader?: boolean;
};

export const TaskList: React.FC< TaskListProps > = ( {
	id,
	eventPrefix,
	tasks,
	title: listTitle,
	isCollapsible = false,
	isExpandable = false,
	displayProgressHeader = false,
	query,
} ) => {
	const { profileItems } = useSelect( ( select ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );

		return {
			profileItems: getProfileItems(),
		};
	} );
	const prevQueryRef = useRef( query );
	const visibleTasks = getVisibleTasks( tasks );
	const { layoutString } = useLayoutContext();

	const incompleteTasks = tasks.filter(
		( task ) => ! task.isComplete && ! task.isDismissed
	);

	const [ expandedTask, setExpandedTask ] = useState(
		incompleteTasks[ 0 ]?.id
	);

	const recordTaskListView = () => {
		recordEvent( eventPrefix + 'view', {
			number_tasks: visibleTasks.length,
			store_connected: profileItems.wccom_connected,
			context: layoutString,
		} );
	};

	useEffect( () => {
		recordTaskListView();
	}, [] );

	useEffect( () => {
		const { task: prevTask } = prevQueryRef.current;
		const { task } = query;

		if ( prevTask !== task ) {
			window.document.documentElement.scrollTop = 0;
			prevQueryRef.current = query;
		}
	}, [ query ] );

	if ( ! visibleTasks.length ) {
		return <div className="woocommerce-task-dashboard__container"></div>;
	}

	const expandLabel = sprintf(
		/* translators: %d = number of hidden tasks */
		_n(
			'Show %d more task.',
			'Show %d more tasks.',
			visibleTasks.length - 2,
			'woocommerce'
		),
		visibleTasks.length - 2
	);
	const collapseLabel = __( 'Show less', 'woocommerce' );

	const taskListItems = visibleTasks.map( ( task ) => (
		<TaskListItem
			key={ task.id }
			isExpanded={ expandedTask === task.id }
			isExpandable={ isExpandable }
			task={ task }
			setExpandedTask={ setExpandedTask }
		/>
	) );

	return (
		<>
			<div
				className={
					'woocommerce-task-dashboard__container woocommerce-task-list__' +
					id
				}
			>
				{ displayProgressHeader ? (
					<ProgressHeader taskListId={ id } />
				) : null }
				<Card
					size="large"
					className="woocommerce-task-card woocommerce-homescreen-card"
				>
					<CardHeader size="medium">
						<div className="woocommerce-task-card__header">
							<Text
								size="20"
								lineHeight="28px"
								variant="title.small"
							>
								{ listTitle }
							</Text>
							<Badge count={ incompleteTasks.length } />
						</div>
						<TaskListMenu id={ id } />
					</CardHeader>
					{ isCollapsible ? (
						<CollapsibleList
							animation="custom"
							collapseLabel={ collapseLabel }
							expandLabel={ expandLabel }
							show={ 2 }
							onCollapse={ () =>
								recordEvent( eventPrefix + 'collapse', {} )
							}
							onExpand={ () =>
								recordEvent( eventPrefix + 'expand', {} )
							}
						>
							{ taskListItems }
						</CollapsibleList>
					) : (
						<List animation="custom">{ taskListItems }</List>
					) }
				</Card>
			</div>
		</>
	);
};

export default TaskList;
