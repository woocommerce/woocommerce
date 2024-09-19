/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { withConsole } from '@storybook/addon-console';
import { Meta, Story } from '@storybook/react';
/**
 * Internal dependencies
 */
import { List, ListItem, CollapsibleList } from '../..';
import { TaskItem } from '../task-item';
import { ListProps } from '../experimental-list';
import './style.scss';

export default {
	title: 'WooCommerce Admin/experimental/List',
	component: List,
	decorators: [ ( storyFn, context ) => withConsole()( storyFn )( context ) ],
	argTypes: {
		direction: {
			control: {
				type: 'select',
				options: [ 'up', 'down' ],
			},
		},
	},
} as Meta;

const Template: Story< ListProps > = ( args ) => (
	<List { ...args }>
		<ListItem disableGutters onClick={ () => {} }>
			<div>Without gutters no padding is added to the list item.</div>
		</ListItem>
		<ListItem onClick={ () => {} }>
			<div>Any markup can go here.</div>
		</ListItem>
		<ListItem onClick={ () => {} }>
			<div>Any markup can go here.</div>
		</ListItem>
		<ListItem onClick={ () => {} }>
			<div>Any markup can go here.</div>
		</ListItem>
	</List>
);

export const Primary = Template.bind( { onClick: () => {} } );

Primary.args = {
	listType: 'ul',
	animation: 'slide-right',
	direction: 'top',
};

export const CollapsibleListExample: Story = ( args ) => {
	return (
		<CollapsibleList
			collapseLabel="Show less"
			expandLabel="Show more items"
			show={ 2 }
			onCollapse={ () => {
				// eslint-disable-next-line no-console
				console.log( 'collapsed' );
			} }
			onExpand={ () => {
				// eslint-disable-next-line no-console
				console.log( 'expanded' );
			} }
			direction="top"
			{ ...args }
		>
			<ListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ListItem>
			<ListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ListItem>
			<ListItem onClick={ () => {} }>
				<div>
					Any markup can go here.
					<br />
					Bigger task item
					<br />
					Another line
				</div>
			</ListItem>
			<ListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ListItem>
			<ListItem onClick={ () => {} }>
				<div>Any markup can go here.</div>
			</ListItem>
		</CollapsibleList>
	);
};

CollapsibleListExample.storyName = 'List with CollapsibleListItem.';

export const TaskItemExample: Story = ( args ) => (
	<List { ...args }>
		<TaskItem
			action={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Primary action clicked' )
			}
			actionLabel="Primary action"
			completed={ false }
			content="Task content"
			expandable={ true }
			expanded={ true }
			level={ 1 }
			onClick={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task clicked' )
			}
			onCollapse={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task will be expanded' )
			}
			onExpand={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task will be collapsed' )
			}
			showActionButton={ true }
			title="A high-priority task"
		/>
		<TaskItem
			action={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Primary action clicked' )
			}
			actionLabel="Primary action"
			completed={ false }
			content="Task content"
			expandable={ false }
			expanded={ true }
			level={ 1 }
			onClick={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task clicked' )
			}
			showActionButton={ false }
			title="A high-priority task without `Primary action`"
			badge="Badge content"
		/>
		<TaskItem
			action={ () => {} }
			completed={ false }
			content="Task content"
			expandable={ false }
			expanded={ true }
			level={ 2 }
			onClick={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task clicked' )
			}
			title="Setup task"
			onDismiss={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task dismissed' )
			}
			onSnooze={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task snoozed' )
			}
			time="5 minutes"
		/>
		<TaskItem
			action={ () => {} }
			completed={ false }
			content="Task content"
			expandable={ false }
			expanded={ true }
			level={ 3 }
			onClick={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task clicked' )
			}
			title="A low-priority task"
			onDismiss={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task dismissed' )
			}
			onSnooze={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task snoozed' )
			}
			time="3 minutes"
		/>
		<TaskItem
			action={ () => {} }
			completed={ true }
			content="Task content"
			expandable={ false }
			expanded={ true }
			level={ 3 }
			onClick={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task clicked' )
			}
			title="Another low-priority task"
			onDelete={ () =>
				// eslint-disable-next-line no-console
				console.log( 'Task deleted' )
			}
		/>
	</List>
);

TaskItemExample.storyName = 'TaskItems.';
