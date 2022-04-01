# WooCommerce Onboarding Tasks

The onboarding tasks provides a way to help store owners get their sites quickly set up.

The task list is easily extensible to allow inserting custom tasks around plugin setup that benefits store owners.

### Models and classes

#### TaskLists

The `TaskLists` class acts as a data store for tasks and provides a way to add or retrieve tasks and lists.

* `TaskLists::get_lists()` - Get all registered task lists
* `TaskLists::get_visible()` - Get visible task lists
* `TaskLists::get_list( $id )` - Get a list by ID
* `TaskLists::get_task( $id )` - Get a task by ID
* `TaskLists::add_list( $args )` - Add a list with the given arguments
* `TaskLists::add_task( $list_id, $args )` - Add a task to a given list ID

#### Task

**Arguments**

```php
$args = array(
  'id'              => 'my-task', // A unique task ID.
  'title'           => 'My Task', // Task title.
  'content'         => 'Task explanation and instructions', // Content shown in the task list item.
  'action_label'    => __( "Do the task!", 'woocommerce' ), // Text used for the action button.
  'action_url'      => 'http://wordpress.com/my/task', // URL used when clicking the task item in lieu of SlotFill.
  'is_complete'     => get_option( 'my-task-option', false ), // Determine if the task is complete.
  'can_view'        => 'US:CA' === wc_get_base_location(),
  'level'           => 3, // Priority level shown for extended tasks.
  'time'            => __( '2 minutes', 'plugin-text-domain' ), // Time string for time to complete the task.
  'is_dismissable'  => false, // Determine if the taks is dismissable.
  'is_snoozeable'   => true, // Determine if the taks is snoozeable.
  'additional_info' => array( 'apples', 'oranges', 'bananas' ), // Additional info passed to the task.
)
$task = new Task( $args );
```

**Methods**

*   `$task->dismiss()` - Dismiss the task
*   `$task->undo_dismiss()` - Undo dismissal of a task
*   `$task->is_dismissed()` - Check if a task is dismissed
*   `$task->snooze()` - Snooze a task for later
*   `$task->undo_snooze()` - Undo snoozing of a task
*   `$task->is_snoozed()` - Check if a task has been snoozed
*   `$task->mark_actioned()` - Mark a task as actioned.  Optional to help determine completion
*   `$task->is_actioned()` - Check if a task has been actioned
*   `$task->get_json()` - Get the camelcase JSON for use in the client
    * `id` (int) - Task ID.
    * `title` (string) - Task title.
    * `canView` (bool) - If a task should be viewable on a given store.
    * `content` (string) - Task content.
    * `additionalInfo` (object) - Additional extensible information about the task.
    * `actionLabel` (string) - The label used for the action button.
    * `actionUrl` (string) - The URL used when clicking the task if no task card is required.
    * `isComplete` (bool) - If the task has been completed or not.
    * `time` (string) - Length of time to complete the task.
    * `level` (integer) - A priority for task list sorting.
    * `isActioned` (bool) - If a task has been actioned.
    * `isDismissed` (bool) - If a task has been dismissed.
    * `isDismissable` (bool) - Whether or not a task is dismissable.
    * `isSnoozed` (bool) - If a task has been snoozed.
    * `isSnoozeable` (bool) - Whether or not a task can be snoozed.
    * `snoozedUntil` (int) - Timestamp in milliseconds that the task has been snoozed until.

#### TaskList

**Arguments**

```php
$args = array(
  'id'      => 'my-list', // A unique task list ID.
  'title'   => 'My List', // Task list title.
  'sort_by' => array( // An array of keys to sort the tasks by.
    array(
      'key'   => 'is_complete',
      'order' => 'asc',
    ),
    array(
      'key'   => 'level',
      'order' => 'asc',
    ),
  ),
)
$list = new TaskList( $args );
```

**Methods**

*   `$task_list->is_hidden()` - Check if a task list is hidden
*   `$task_list->is_visible()` - Check if a task list is visible (opposite value of `is_hidden()`)
*   `$task_list->hide()` - Hide a task list
*   `$task_list->unhide()` - Undo hiding of a task list
*   `$task_list->is_complete()` - Check if a task list is complete
*   `$task_list->add_task( $args )` - Add a task to a task list
*   `$task_list->get_viewable_tasks()` - Get tasks that are marked as `can_view` for the store
*   `$task_list->sort_tasks( $sort_by )` - Sort the tasks by the provided `sort_by` value or the task list `sort_by` property if no argument is passed.
*   `$task_list->get_json()` - Get the camelcase JSON for use in the client
    * `id` (int) - Task list ID.
    * `title` (string) - Task list title.
    * `isHidden` (bool) - If a task has been hidden.
    * `isVisible` (bool) - If a task list is visible.
    * `isComplete` (bool) - Whether or not all viewable tasks have been completed.
    * `tasks` (array) - An array of `Task` objects.

#### Data store actions

Using the `@woocommerce/data` package, the following selectors and actions are available to interact with the task lists under the onboarding store.

```js
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

const { snoozeTask } = useDispatch( ONBOARDING_STORE_NAME );
const { taskLists } = useSelect( ( select ) => {
  const { getTaskLists } = select( ONBOARDING_STORE_NAME );

  return {
    taskLists: getTaskLists(),
  };
} );
```


* `getTaskLists` - (select) Resolve any registered task lists with their nested tasks
* `hideTaskList( id )` - (dispatch) Hide a task list
* `actionTask( id )` - (dispatch) Mark a task as actioned
* `snoozeTask( id )` - (dispatch) Snooze a task
* `dismissTask( id )` - (dispatch) Dismiss a task
* `optimisticallyCompleteTask( id )` - (dispatch) Optimistically mark a task as complete


### SlotFills

The task UI can be supplemented by registering plugins that fill the provided task slots.

#### Task content

A task list fill is required if no `action_url` is provided for the task.  This is the content shown after a task list item has been clicked.

```js
import { WooOnboardingTask } from '@woocommerce/onboarding';

registerPlugin( 'my-task-plugin', {
	scope: 'woocommerce-tasks',
	render: () => (
    <WooOnboardingTask id="my-task">
      { ( { onComplete, query } ) => (
        <MyTask onComplete={ onComplete } />
      ) }
    </WooOnboardingTask>
	),
} );
```
#### Task list item

The items shown in the list can be customized beyond the default task list item.  This can allow for custom appearance or specific `onClick` behavior for your task.

```js
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';

registerPlugin( 'my-task-list-item-plugin', {
	scope: 'woocommerce-tasks',
	render: () => (
    <WooOnboardingTaskListItem id="appearance">
      { ( { defaultTaskItem, onComplete } ) => (
        <MyTaskListItem onComplete={ onComplete } />
      ) }
    </WooOnboardingTaskListItem>
	),
} );
```

### Endpoints

The following REST endpoints are available to interact with tasks.  For ease of use, we recommend using the data store actions above to interact with these endpoints.

*   `/wc-admin/onboarding/tasks` (GET) - Retrieve all tasks and their statuses
*   `/wc-admin/onboarding/tasks/{list_id}/hide` (POST) - Hide a given task list
*   `/wc-admin/onboarding/tasks/{task_id}/dismiss` (POST) - Dismiss a task
*   `/wc-admin/onboarding/tasks/{task_id}/undo_dismiss` (POST) - Undo dismissal of a task
*   `/wc-admin/onboarding/tasks/{task_id}/snooze` (POST) - Snooze a task for later
*   `/wc-admin/onboarding/tasks/{task_id}/undo_snooze` (POST) - Undo snoozing of a task
*   `/wc-admin/onboarding/tasks/{task_id}/action` (POST) - Mark a task as actioned