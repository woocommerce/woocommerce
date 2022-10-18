# Tracks

WooCommerce user event tracking utilities for Automattic based projects.

## Installation

Install the module

```bash
pnpm install @woocommerce/tracks --save
```

## Usage

The store must opt-in to allow tracking via the `woocommerce_allow_tracking` setting. 
If the store is not opted-in no events be recorded when using the following functions.

### recordEvent( eventName, eventProperties )

Record a user event to Tracks.

```jsx
import { recordEvent } from '@woocommerce/tracks';

recordEvent( 'page_view', { path } )
```

| Param | Type | Description |
| --- | --- | --- |
| eventName | <code>String</code> | The name of the event to record, don't include the `wcadmin_` prefix |
| eventProperties | <code>Object</code> | Event properties to include in the event |

### queueRecordEvent( eventName, eventProperties )

Queue a tracks event.

This allows you to delay tracks events that would otherwise cause a race condition.
For example, when we trigger `wcadmin_tasklist_appearance_continue_setup` we're simultaneously moving the user to a new page via
`window.location`. This is an example of a race condition that should be avoided by enqueueing the event,
and therefore running it on the next pageview.

| Param | Type | Description |
| --- | --- | --- |
| eventName | <code>String</code> | The name of the event to record, don't include the `wcadmin_` prefix |
| eventProperties | <code>Object</code> | Event properties to include in the event |

### recordPageView( eventName, eventProperties )

Record a page view to Tracks.

| Param | Type | Description |
| --- | --- | --- |
| path | <code>String</code> | Path the page/path to record a page view for |
| extraProperties | <code>Object</code> | Extra event properties to include in the event |

# Debugging

When debugging is activated info for each recorded Tracks event is logged to the browser console.

To activate, open up your browser console and add this:

```js
localStorage.setItem( 'debug', 'wc-admin:*' );
```
