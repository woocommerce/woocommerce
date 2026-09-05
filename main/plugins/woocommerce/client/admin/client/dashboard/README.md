# Dashboard

This folder contains the components used in the Dashboard page.

## Extending the Dashboard

New Dashboard sections can be added by hooking into the filter `woocommerce_dashboard_default_sections`. For example:

```js
import { arrowRight } from '@wordpress/icons';
addFilter( 'woocommerce_dashboard_default_sections', ( sections ) => {
	return [
		...sections,
		{
			key: 'example',
			component: ExampleSection,
			title: 'My Example Dashboard Section',
			isVisible: true,
			icon: arrowRight,
			hiddenBlocks: [],
		},
	];
} );
```

Each section is defined by an object containing the following properties.

-   `key` (string): The key used internally to identify the section.
-   `title` (string): The title shown in the Dashboard. It can be modified by users.
-   `icon` (function|WPComponent|null): Icon to be used to identify the section.
-   `component` (react component): The component containing the section content.
-   `isVisible` (boolean): Whether the section is visible by default. Sections can be added/hidden by users.
-   `hiddenBlocks` (array of strings): The keys of the blocks that must be hidden by default. Used in Sections that contain several blocks that can be shown or hidden. It can be modified by users.

The component will get the following props:

-   `hiddenBlocks` (array of strings): Hidden blocks according to the default settings or the user preferences if they had made any modification.
-   `isFirst` (boolean): Whether the component is the first one shown in the Dashboard.
-   `isLast` (boolean): Whether the component is the last one shown in the Dashboard.
-   `onMove` (boolean): Event to trigger when moving the section.
-   `onRemove` (boolean): Event to trigger when removing the section.
-   `onTitleBlur` (function): Event to trigger when the edit title input box is unfocused.
-   `onTitleChange` (function): Event to trigger when the edit title input box receives a change event.
-   `onToggleHiddenBlock` (function): Event to trigger when the user toggles one of the hidden blocks preferences.
-   `titleInput` (string): Current string to be displayed in the edit title input box. Title is only updated on blur, so this value will be different than `title` when the user is modifying the input box.
-   `path` (string): The exact path for this view.
-   `query` (object): The query string for the current view, can be used to read current preferences for time periods or chart interval/type.
-   `title` (string): Title of the section according to the default settings or the user preferences if they had made any modification.
-   `controls` (react component): Controls to move a section up/down or remove it from view to be rendered inside the EllipsisMenu.
