# TaskItem

Use `TaskItem` to display a task item.

## Usage

```jsx
<TaskItem
	action={ () => alert( '"My action" button has been clicked' ) }
	actionLabel="My action"
	additionalInfo="Additional task information"
	completed={ true }
	content="Task content"
	expandable={ false }
	expanded={ false }
	level="Task title"
	onClick={ () => alert( 'The task has been clicked' ) }
	onCollapse={ () => alert( 'The task was collapsed' ) }
	onDelete={ () => alert( 'The task has been deleted' ) }
	onDismiss={ () => alert( 'The task was dismissed' ) }
	onExpand={ () => alert( 'The task was expanded' ) }
	onSnooze={ () => alert( 'The task was snoozed' ) }
	showActionButton={ false }
	time="10 minutes"
	title="Task title"
/>
```

### Props

| Name               | Type     | Default | Description                                                  |
| ------------------ | -------- | ------- | ------------------------------------------------------------ |
| `action`           | Function | `null`  | A function to be called when the primary action is triggered |
| `actionLabel`      | String   | `null`  | Primary action label                                         |
| `additionalInfo`   | String   | `null`  | Additional task information                                  |
| `completed`        | Boolean  | `null`  | Whether the task is completed or not                         |
| `content`          | String   | `null`  | Task content                                                 |
| `expandable`       | Boolean  | `false` | Whether it's an expandable task                              |
| `expanded`         | Boolean  | `false` | Whether the task is expanded by default                      |
| `level`            | Number   | `3`     | Task hierarchy level (between 1 and 3)                       |
| `onClick`          | Function | `null`  | A function to be called after clicking on the task item      |
| `onCollapse`       | Function | `null`  | A function to be called after the task is collapsed          |
| `onDelete`         | Function | `null`  | A function to be called after the task is deleted            |
| `onDismiss`        | Function | `null`  | A function to be called after the task is dismissed          |
| `onExpand`         | Function | `null`  | A function to be called after the task is expanded           |
| `onSnooze`         | Function | `null`  | A function to be called after the task is snoozed            |
| `showActionButton` | Boolean  | `null`  | Whether the primary action (button) will be shown            |
| `time`             | String   | `null`  | Time to finish the task                                      |
| `title`            | String   | `null`  | (required) Task title                                        |
