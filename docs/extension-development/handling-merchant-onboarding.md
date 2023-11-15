# Handling merchant onboarding

## Introduction

Onboarding is a critical part of the merchant’s user experience. It helps set them up for success and ensures they’re not only using your extension correctly but also getting the most out of it. There are a few especially useful features that you can take advantage of as a developer to help onboard merchants who are using your extension:

- Setup tasks
- Store management links
- Admin notes

---

## Using setup tasks

Setup tasks appear on the WooCommerce Admin home screen and prompt a merchant to complete certain steps in order to set up your extension. Adding tasks is a two-step process that requires:

- Registering the task (and its JavaScript) using PHP
- Using JavaScript to build the task, set its configuration, and add it to the task list

### Registering the task with PHP

To register your task as an extended task list item, you’ll need to hook in to the `woocommerce_get_registered_extended_tasks` filter with a function that appends your task to the array the filter provides.

```php
// Task registration
function my_extension_register_the_task( $registered_tasks_list_items ) {
    $new_task_name = 'your_task_name';
    if ( ! in_array( $new_task_name, $registered_tasks_list_items, true ) ) {
        array_push( $registered_tasks_list_items, $new_task_name );
    }
    return $registered_tasks_list_items;
}
add_filter( 'woocommerce_get_registered_extended_tasks', 'my_extension_register_the_task', 10, 1 );
```

### Registering the task’s JavaScript

In addition to registering the task name, you’ll also need to register and enqueue the transpiled JavaScript file containing your task component, its configuration, and its event-handlers. A common way to do this is to create a dedicated registration function that hooks into the `admin_enqueue_scripts` action in WordPress. If you do things this way, you can nest the `add_filter` call for `woocommerce_get_registered_extended_tasks` in this function as well. Below is an annotated example of how this registration might look:

```php
// Register the task list item and the JS.
function add_task_register_script() {
 
    // Check to make sure that this is a request for an Admin page.
    if (
        ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) ||
        ! \Automattic\WooCommerce\Admin\Loader::is_admin_page() ||
        ! Onboarding::should_show_tasks()
    ) {
        return;
    }
 
    // Register a handle for your extension's transpiled JavaScript file.
    wp_register_script(
        'add-task',
        plugins_url( '/dist/index.js', __FILE__ ),
        array(
            'wp-hooks',
            'wp-element',
            'wp-i18n',
            'wc-components',
        ),
        filemtime( dirname( __FILE__ ) . '/dist/index.js' ),
        true
    );
 
    // Get server-side data via PHP and send it to the JavaScript using wp_localize_script
    $client_data = array(
        'isComplete' => get_option( 'woocommerce_admin_add_task_example_complete', false ),
    );
    wp_localize_script( 'add-task', 'addTaskData', $client_data );
 
    // Enqueue the script in WordPress
    wp_enqueue_script( 'add-task' );
 
    // Hook your task registration script to the relevant extended tasks filter
    add_filter( 'woocommerce_get_registered_extended_tasks', 'my_extension_register_the_task', 10, 1 );
}
```

### Unregistering the task upon deactivation

It is also helpful to define a function that will unregister your task when your extension is deactivated.

```php
// Unregister task.
function my_extension_deactivate_task() {
    remove_filter( 'woocommerce_get_registered_extended_tasks', 'my_extension_register_the_task', 10, 1 );
}
 
register_deactivation_hook( __FILE__, 'my_extension_deactivate_task' );

```

### Adding the task using JavaScript

Once the task has been registered in WooCommerce, you need to build the task component, set its configuration, and add it to the task list. For example, the JavaScript file for a simple task might look something like this:

```js
// External dependencies.
import { addFilter } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';
import { Card, CardBody } from '@wordpress/components';
 
// WooCommerce dependencies.
import { getHistory, getNewPath } from '@woocommerce/navigation';
 
// Event handler for handling mouse clicks that mark a task complete.
const markTaskComplete = () => {
    // Here we're using apiFetch to set option values in WooCommerce.
    apiFetch( {
        path: '/wc-admin/options',
        method: 'POST',
        data: { woocommerce_admin_add_task_example_complete: true },
    } )
        .then( () => {
            // Set the local `isComplete` to `true` so that task appears complete on the list.
            addTaskData.isComplete = true;
            // Redirect back to the root WooCommerce Admin page.
            getHistory().push( getNewPath( {}, '/', {} ) );
        } )
        .catch( ( error ) => {
            // Something went wrong with our update.
            console.log( error );
        } );
};
 
// Event handler for handling mouse clicks that mark a task incomplete.
const markTaskIncomplete = () => {
    apiFetch( {
        path: '/wc-admin/options',
        method: 'POST',
        data: { woocommerce_admin_add_task_example_complete: false },
    } )
        .then( () => {
            addTaskData.isComplete = false;
            getHistory().push( getNewPath( {}, '/', {} ) );
        } )
        .catch( ( error ) => {
            console.log( error );
        } );
};
 
// Build the Task component.
const Task = () => {
    return (
        <Card className="woocommerce-task-card">
            <CardBody>
                Example task card content.
                <br />
                <br />
                <div>
                    { addTaskData.isComplete ? (
                        <button onClick={ markTaskIncomplete }>
                            Mark task incomplete
                        </button>
                    ) : (
                        <button onClick={ markTaskComplete }>
                            Mark task complete
                        </button>
                    ) }
                </div>
            </CardBody>
        </Card>
    );
};
 
// Use the 'woocommerce_admin_onboarding_task_list' filter to add a task.
addFilter(
    'woocommerce_admin_onboarding_task_list',
    'plugin-domain',
    ( tasks ) => {
        return [
            ...tasks,
            {
                key: 'example',
                title: 'Example',
                content: 'This is an example task.',
                container: <Task />,
                completed: addTaskData.isComplete,
                visible: true,
                additionalInfo: 'Additional info here',
                time: '2 minutes',
                isDismissable: true,
                onDismiss: () => console.log( 'The task was dismissed' ),
            },
        ];
    }
);
```

In the example above, the extension does a few different things. Let’s break it down:

#### Handle imports

First, import any functions, components, or other utilities from external dependencies. We’ve kept WooCommerce-related dependencies separate from others for the sake of keeping things tidy. In a real-world extension, you may be importing other local modules. In those cases, we recommend creating a visually separate section for those imports as well.

```js
// External dependencies
import { addFilter } from '@wordpress/hooks'``;
import apiFetch from '@wordpress/api-fetch'``;
import { Card, CardBody } from '@wordpress/components'``;

// WooCommerce dependencies
import { getHistory, getNewPath } from '@woocommerce/navigation'``;
```

The `addFilter` function allows us to hook in to JavaScript filters the same way that the traditional PHP call to `add_filter()` does. The `apiFetch` utility allows our extension to query the WordPress REST API without needing to deal with keys or authentication. Finally, the `Card` and `CardBody` are predefined React components that we’ll use as building blocks for our extension’s Task component.

#### Create Event Handlers

Next we define the logic for the functions that will handle events for our task. In the example above, we created two functions to handle mouse clicks that toggle the completion status of our task.

```js
const markTaskComplete = () => {
    apiFetch( {
        path: '/wc-admin/options',
        method: 'POST',
        data: { woocommerce_admin_add_task_example_complete: true },
    } )
        .then( () => {
            addTaskData.isComplete = true;
            getHistory().push( getNewPath( {}, '/', {} ) );
        } )
        .catch( ( error ) => {
            console.log( error );
        } );
};
```

In the example above, the event handler uses `apiFetch` to set the `woocommerce_admin_add_task_example_complete` option’s value to `true` and then updates the component’s state data and redirects the browser to the Admin root. In the case of an error, we’re simply logging it to the console, but you may want to implement your own solution here.

The `markTaskIncomplete` function is more or less an inverse of `markTaskComplete` that toggles the task’s completion status in the opposite direction.

#### Construct the component

Next, we create a [functional component](https://reactjs.org/docs/components-and-props.html) that returns our task card. The intermixed JavaScript/HTML syntax we’re using here is called JSX. If you’re unfamiliar with it, you can [read more about it in the React docs](https://reactjs.org/docs/introducing-jsx.html).

```js
const Task = () => {
    return (
        <Card className="woocommerce-task-card">
            <CardBody>
                Example task card content.
                <br />
                <br />
                <div>
                    { addTaskData.isComplete ? (
                        <button onClick={ markTaskIncomplete }>
                            Mark task incomplete
                        </button>
                    ) : (
                        <button onClick={ markTaskComplete }>
                            Mark task complete
                        </button>
                    ) }
                </div>
            </CardBody>
        </Card>
    );
};
```

In the example above, we’re using the `Card` and `CardBody` components to construct our task’s component. The `div` inside the `CardBody` uses a [JavaScript expression](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Expressions_and_Operators#Expressions) (`{}`) to embed a ternary operator that uses the component’s state to determine whether to display the task as complete or incomplete.

#### Configure task and add it to the WooCommerce task list

Finally, we’ll set some configuration values for our task and then use the `addFilter` function to append our task to the WooCommerce Admin Onboarding Task List.

```js
addFilter(
    'woocommerce_admin_onboarding_task_list',
    'plugin-domain',
    ( tasks ) => {
        return [
            ...tasks,
            {
                key: 'example',
                title: 'Example',
                content: 'This is an example task.',
                container: <Task />,
                completed: addTaskData.isComplete,
                visible: true,
                additionalInfo: 'Additional info here',
                time: '2 minutes',
                isDismissable: true,
                onDismiss: () => console.log( 'The task was dismissed' ),
            },
        ];
    }
);
```

In the example above, we’re setting our task’s configuration as we pass it into the filter for simplicity, but in a real-world extension, you might encapsulate this somewhere else for better separation of concerns. Below is a list of properties that the task-list component supports for tasks.

| Name           | Type       | Required | Description |
|----------------|------------|----------|-------------|
| key            | String     | Yes      | Identifier  |
| title          | String     | Yes      | Task title  |
| content        | String     | No       | The content that will be visible in the Extensions setup list |
| container      | Component  | Yes      | The task component that will be visible after selecting the item |
| completed      | Boolean    | Yes      | Whether the task is completed or not |
| visible        | Boolean    | Yes      | Whether the task is visible or not |
| additionalInfo | String     | No       | Additional information |
| time           | String     | Yes      | Time it takes to finish up the task |
| isDismissable  | Boolean    | No       | Whether the task is dismissable or not. If false the Dismiss button won’t be visible |
| onDismiss      | Function   | No       | Callback method that it’s triggered on dismission |
| type           | String     | Yes      | Type of task list item, setup items will be in the store setup and extension in the extensions setup |


---

## Using Store Management Links

When a merchant completes all of the items on the onboarding task list, WooCommerce replaces it with a section containing a list of handy store management links. Discoverability can be a challenge for extensions, so this section is a great way to bring more attention to key features of your extension and help merchants navigate to them.

The store management section has a relatively narrow purpose, so this section does not currently support external links. Instead, it is meant for navigating quickly within WooCommerce.

Adding your own store management links is a simple process that involves:

- Installing dependencies for icon support
- Enqueuing an admin script in your PHP
- Hooking in via a JavaScript filter to provide your link object

### Installing the Icons package

Store management links use the `@wordpress/icons` package. If your extension isn’t already using it, you’ll need to add it to your extension’s list of dependencies.

`npm` `install` ` @wordpress``/icons ` `--save`

### Enqueuing the JavaScript

The logic that adds your custom link to the store management section will live in a JavaScript file. We’ll register and enqueue that file with WordPress in our PHP file:

```js
function custom_store_management_link() {
    wp_enqueue_script(
        'add-my-custom-link',
        plugins_url( '/dist/add-my-custom-link.js', __FILE__ ),
        array( 'wp-hooks' ),
        10
    );
}
add_action( 'admin_enqueue_scripts', 'custom_store_management_link' );

```

The first argument of this call is a handle, the name by which WordPress will refer to the script we’re enqueuing. The second argument is the URL where the script is located.

The third argument is an array of script dependencies. By supplying the `wp-hooks` handle in that array, we’re ensuring that our script will have access to the `addFilter` function we’ll be using to add our link to WooCommerce’s list.

The fourth argument is a priority, which determines the order in which JavaScripts are loaded in WordPress. We’re setting a priority of 10 in our example. It’s important that your script runs before the store management section is rendered. With that in mind, make sure your priority value is lower than 15 to ensure your link is rendered properly.

### Supply your link via JavaScript

Finally, in the JavaScript file you enqueued above, hook in to the `woocommerce_admin_homescreen_quicklinks` filter and supply your task as a simple JavaScript object.

```js
import { megaphone } from '@wordpress/icons';
import { addFilter } from '@wordpress/hooks';
 
addFilter(
    'woocommerce_admin_homescreen_quicklinks',
    'my-extension',
    ( quickLinks ) => {
        return [
            ...quickLinks,
            {
                title: 'My link',
                href: 'link/to/something',
                icon: megaphone,
            },
        ];
    }
);
```

---

## Using Admin Notes

Admin Notes are meant for displaying insightful information about your WooCommerce store, extensions, activity, and achievements. They’re also useful for displaying information that can help with the day-to-day tasks of managing and optimizing a store. A good general rule is to use Admin Notes for information that is:

1.  Timely
2.  Relevant
3.  Useful

With that in mind, you might consider using Admin Notes to celebrate a particular milestone that a merchant has passed, or to provide additional guidance about using a specific feature or flow. Conversely, you shouldn’t use Admin Notes to send repeated messages about the same topic or target all users with a note that is only relevant to a subset of merchants. It’s okay to use Admin Notes for specific promotions, but you shouldn’t abuse the system. Use your best judgement and remember the home screen is meant to highlight a store’s most important actionable tasks.

Despite being a part of the new React-powered admin experience in WooCommerce, Admin Notes are available to developers via a standard PHP interface.

The recommended approach for using Admin Notes is to encapsulate your note within its own class that uses the [NoteTraits](https://github.com/woocommerce/woocommerce-admin/blob/831c9ff13a862f22cf53d3ae676daeabbefe90ad/src/Notes/NoteTraits.php) trait included with WooCommerce Admin. Below is a simple example of what this might look like:

```php
<?php
/**
 * Simple note provider
 *
 * Adds a note with a timestamp showing when the note was added.
 */
 
namespace My\Wonderfully\Namespaced\Extension\Area;
 
// Exit if this code is accessed outside of WordPress.
defined ( 'ABSPATH' ) || exit;
 
// Check for Admin Note support
if ( ! class_exists( 'Automattic\WooCommerce\Admin\Notes\Notes' ) ||
     ! class_exists( 'Automattic\WooCommerce\Admin\Notes\NoteTraits' )) {
    return;
}
 
// Make sure the WooCommerce Data Store is available
if ( ! class_exists( 'WC_Data_Store' ) ) {
    return;
}
 
 
/**
 * Example note class.
 */
class ExampleNote {
 
    // Use the Note class to create Admin Note objects
    use Automatic\WooCommerce\Admin\Notes\Note;
 
    // Use the NoteTraits trait, which handles common note operations.
    use Automatic\WooCommerce\Admin\Notes\NoteTraits;
 
    // Provide a note name.
    const NOTE_NAME = 'my-prefix-example-note';
 
    public static function get_note() {
    // Our welcome note will include information about when the extension
    // was activated.  This is just for demonstration. You might include
    // other logic here depending on what data your note should contain.
        $activated_time = current_time( 'timestamp', 0 );
        $activated_time_formatted = date( 'F jS', $activated_time );
 
        // Instantiate a new Note object
        $note = new Automattic\WooCommerce\Admin\Notes\Note();
 
        // Set our note's title.
        $note->set_title( 'Getting Started' );
 
        // Set our note's content.
        $note->set_content(
            sprintf(
                'Extension activated on %s.', $activated_time_formatted
            )
        );
 
        // In addition to content, notes also support structured content.
        // You can use this property to re-localize notes on the fly, but
        // that is just one use. You can store other data here too. This
        // is backed by a longtext column in the database.
        $note->set_content_data( (object) array(
            'getting_started'       => true,
            'activated'             => $activated_time,
            'activated_formatted'   => $activated_time_formatted
        ) );
 
        // Set the type of the note. Note types are defined as enum-style
        // constants in the Note class. Available note types are:
        // error, warning, update, info, marketing.
        $note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
 
        // Set the type of layout the note uses. Supported layout types are:
        // 'banner', 'plain', 'thumbnail'
        $note->set_layout( 'plain' );
 
        // Set the image for the note. This property renders as the src
        // attribute for an img tag, so use a string here.
        $note->set_image( '' );
 
 
        // Set the note name and source.  You should store your extension's
        //   name (slug) in the source property of the note.  You can use
        //   the name property of the note to support multiple sub-types of
        //   notes.  This also gives you a handy way of namespacing your notes.
        $note->set_source( 'inbox-note-example');
        $note->set_name( self::NOTE_NAME );
 
        // Add action buttons to the note.  A note can support 0, 1, or 2 actions.
        //   The first parameter is the action name, which can be used for event handling.
        //   The second parameter renders as the label for the button.
        //   The third parameter is an optional URL for actions that require navigation.
        $note->add_action(
            'settings', 'Open Settings', '?page=wc-settings&tab=general'
        );
        $note->add_action(
            'learn_more', 'Learn More', 'https://example.com'
        );
 
        return $note;
    }
}
 
function my_great_extension_activate() {
    // This uses the functionality from the NoteTraits trait to conditionally add your note if it passes all of the appropriate checks.
    ExampleNote::possibly_add_note();
}
register_activation_hook( __FILE__, 'my_great_extension_activate' );
 
function my_great_extension_deactivate() {
    // This uses the functionality from the NoteTraits trait to conditionally remove your note if it passes all of the appropriate checks.
    ExampleNote::possibly_delete_note();
}
register_deactivation_hook( __FILE__, 'my_great_extension_deactivate' );
```


### Breaking it down

Let’s break down the example above to examine what each section does.

#### Namespacing and feature availability checks

First, we’re doing some basic namespacing and feature availability checks, along with a safeguard to make sure this file only executes within the WordPress application space.

```php
namespace My\Wonderfully\Namespaced\Extension\Area;
 
defined ( 'ABSPATH' ) || exit;
 
if ( ! class_exists( 'Automattic\WooCommerce\Admin\Notes\Notes') ||
     ! class_exists( 'Automattic\WooCommerce\Admin\Notes\NoteTraits') ) {
    return;
}
 
if ( ! class_exists( 'WC_Data_Store' ) ) {
    return;
}
```

#### Using Note and NoteTraits objects

Next, we define a simple class that will serve as a note provider for our note. To create and manage note objects, we’ll import the `Note` and `NotesTraits` classes from WooCommerce Admin.

```php
class ExampleNote {
 
    use Automatic\WooCommerce\Admin\Notes\Note;
    use Automatic\WooCommerce\Admin\Notes\NoteTraits;
 
}
```

#### Provide a unique note name

Before proceeding, create a constant called `NOTE_NAME` and assign a unique note name to it. The `NoteTraits` class uses this constant for queries and note operations.

`const NOTE_NAME = 'my-prefix-example-note';`

#### Configure the note’s details

Once you’ve set your note’s name, you can define and configure your note. The `NoteTraits` class will call `self::get_note()` when performing operations, so you should encapsulate your note’s instantiation and configuration in a static function called `get_note()` that returns a `Note` object.

```php
public static function get_note() {
    // We'll fill this in with logic that instantiates a Note object
    //   and sets its properties.
}
```

Inside our `get_note()` function, we’ll handle any logic for collecting data our Note may need to display. Our example note will include information about when the extension was activated, so this bit of code is just for demonstration. You might include other logic here depending on what data your note should contain.

```php
$activated_time = current_time( 'timestamp', 0);
$activated_time_formatted = date( 'F jS', $activated_time );

```

Next, we’ll instantiate a new `Note` object.

`$note = new Note();`

Once we have an instance of the Note class, we can work with its API to set its properties, starting with its title.

`$note->set_title( 'Getting Started' );`

Then we’ll use some of the timestamp data we collected above to set the note’s content.

```php
$note->set_content(
    sprintf(
        'Extension activated on %s.', $activated_time_formatted
    )
);
```

In addition to regular content, notes also support structured content using the `content_data` property. You can use this property to re-localize notes on the fly, but that is just one use case. You can store other data here too. This is backed by a `longtext` column in the database.

```php
$note->set_content_data( (object) array(
    'getting_started'     => true,
    'activated'           => $activated_time,
    'activated_formatted' => $activated_time_formatted
) );
```

Next, we’ll set the note’s `type` property. Note types are defined as enum-style class constants in the `Note` class. Available note types are _error_, _warning_, _update_, _info_, and _marketing_. When selecting a note type, be aware that the _error_ and _update_ result in the note being shown as a Store Alert, not in the Inbox. It’s best to avoid using these types of notes unless you absolutely need to.

`$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );`

Admin Notes also support a few different layouts. You can specify `banner`, `plain`, or `thumbnail` as the layout. If you’re interested in seeing the different layouts in action, take a look at [this simple plugin](https://gist.github.com/octaedro/864315edaf9c6a2a6de71d297be1ed88) that you can install to experiment with them.

We’ll choose `plain` as our layout, but it’s also the default, so we could leave this property alone and the effect would be the same.

`$note->set_layout( 'plain' );`

If you have an image that you want to add to your Admin Note, you can specify it using the `set_image` function. This property ultimately renders as the `src` attribute on an `img` tag, so use a string here.

`$note->set_image( '' );`

Next, we’ll set the values for our Admin Note’s `name` and `source` properties. As a best practice, you should store your extension’s name (i.e. its slug) in the `source` property of the note. You can use the `name` property to support multiple sub-types of notes. This gives you a handy way of namespacing your notes and managing them at both a high and low level.

```php
$note->set_source( 'inbox-note-example');
$note->set_name( self::NOTE_NAME );
```

Admin Notes can support 0, 1, or 2 actions (buttons). You can use these actions to capture events that trigger asynchronous processes or help the merchant navigate to a particular view to complete a step, or even simply to provide an external link for further information. The `add_action()` function takes up to three arguments. The first is the action name, which can be used for event handling, the second renders as a label for the action’s button, and the third is an optional URL for actions that require navigation.

```php
$note->add_action(
    'settings', 'Open Settings', '?page=wc-settings&tab=general'
);
$note->add_action(
    'learn_more', 'Learn More', 'https://example.com'
);
```

Finally, remember to have the `get_note()` function return the configured Note object.

`return $note;`

#### Adding and deleting notes

To add and delete notes, you can use the helper functions that are part of the `NoteTraits` class: `possibly_add_note()` and its counterpart `possibly_delete_note()`. These functions will handle some of the repetitive logic related to note management and will also run checks to help you avoid creating duplicate notes.

Our example extension ties these calls to activation and deactivation hooks for the sake of simplicity. While there are many events for which you may want to add Notes to a merchant’s inbox, deleting notes upon deactivation and uninstallation is an important part of managing your extension’s lifecycle.

```php
function my_great_extension_activate() {
    ExampleNote::possibly_add_note();
}
register_activation_hook( __FILE__, 'my_great_extension_activate' );
 
function my_great_extension_deactivate() {
    ExampleNote::possibly_delete_note();
}
register_deactivation_hook( __FILE__, 'my_great_extension_deactivate' );

```
