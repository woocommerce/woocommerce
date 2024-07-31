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
| eventName | `String` | The name of the event to record, don't include the `wcadmin_` prefix |
| eventProperties | `Object` | Event properties to include in the event |

### queueRecordEvent( eventName, eventProperties )

Queue a tracks event.

This allows you to delay tracks events that would otherwise cause a race condition.
For example, when we trigger `wcadmin_tasklist_appearance_continue_setup` we're simultaneously moving the user to a new page via
`window.location`. This is an example of a race condition that should be avoided by enqueueing the event,
and therefore running it on the next pageview.
| Param | Type | Description |
| --- | --- | --- |
| eventName | `String` | The name of the event to record, don't include the `wcadmin_` prefix |
| eventProperties | `Object` | Event properties to include in the event |

### recordPageView( path, extraProperties )

Record a page view to Tracks.

| Param | Type | Description |
| --- | --- | --- |
| path | `String` | Path the page/path to record a page view for |
| extraProperties | `Object` | Extra event properties to include in the event |

### bumpStat( statName, statValue )

Bump a stat or group of stats.

```typescript
import { bumpStat } from '@woocommerce/tracks';

// Bump a single stat
bumpStat( 'stat_name', 'stat_value' );

// Bump multiple stats
bumpStat( {
  stat1: 'value1',
  stat2: 'value2'
} );
```

| Param | Type | Description |
| --- | --- | --- |
| statName | `String` or `Object` | The name of the stat to bump, or an object of stat names and values |
| statValue | `String` | The value for the stat (only used when statName is a string) |

Note: Stat names are automatically prefixed with `x_woocommerce-`. Stat tracking is disabled in development mode.

## Debugging

When debugging is activated info for each recorded Tracks event is logged to the browser console.

To activate, open up your browser console and add this:

```js
localStorage.setItem( 'debug', 'wc-admin:*' );
```
