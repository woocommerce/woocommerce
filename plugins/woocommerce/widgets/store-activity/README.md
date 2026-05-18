# Store Activity Widget

A WooCommerce dashboard widget that surfaces recent store events (orders, customers, bookings, reviews, etc.) in a chronological timeline. The widget itself ships no data: events are contributed by plugins through a WordPress filter, so any extension can plug its own activity in.

## Activity Sources API

Sources are registered via the `storeActivity.sources` filter from `@wordpress/hooks`. A source is a small object that exposes a React hook returning the events it wants to render.

### Registering a source

```tsx
import { addFilter } from '@wordpress/hooks';
import { useSelect } from '@wordpress/data';
import { receipt } from '@wordpress/icons';

function useOrdersActivity() {
	const { orders, isResolving } = useSelect( ( select ) => ( {
		orders: select( 'core' ).getEntityRecords(
			'postType',
			'shop_order',
			{ per_page: 20, orderby: 'date', order: 'desc' }
		),
		isResolving: select( 'core' ).isResolving(
			'getEntityRecords',
			[ 'postType', 'shop_order', { per_page: 20, orderby: 'date', order: 'desc' } ]
		),
	} ), [] );

	if ( isResolving ) {
		return { state: 'loading' };
	}

	if ( ! orders?.length ) {
		return { state: 'empty' };
	}

	return {
		state: 'success',
		events: orders.map( ( order ) => ( {
			id: order.id,
			icon: receipt,
			renderContent: () => (
				<>
					<a href={ `/orders/${ order.id }` }>
						Order #{ order.id }
					</a>
					{ ' placed' }
				</>
			),
			datetime: order.date_created_gmt,
		} ) ),
	};
}

addFilter(
	'storeActivity.sources',
	'my-plugin/store-activity-sources',
	( sources ) => [
		...sources,
		{ id: 'my-plugin/orders', useActivity: useOrdersActivity },
	]
);
```

Register the filter as early as possible — typically when your script module boots — so the source is available the first time the widget renders.

## Contracts

### `ActivitySource`

| Field | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier (namespaced is encouraged) |
| `useActivity` | `() => ActivityHookResult` | React hook that returns the source's current events |

### `ActivityHookResult`

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `state` | `'loading' \| 'empty' \| 'success'` | Yes | Current source state |
| `events` | `StoreActivityEvent[]` | No | Events to render — only present when `state === 'success'` |

### `StoreActivityEvent`

| Field | Type | Description |
| --- | --- | --- |
| `id` | `string \| number` | Unique within the source |
| `icon` | `JSX.Element` | Icon for the event (typically from `@wordpress/icons`) |
| `renderContent` | `() => React.ReactNode` | Render function for the event content — links, formatted strings, anything React |
| `datetime` | `string` | ISO 8601 datetime, used for sorting and grouping by day |

## Architecture

```
store-activity/
├── components/
│   ├── activity-source-loader/  # Ghost component that runs each source hook
│   └── empty-state/             # Empty state UI
├── hooks/
│   └── use-activity-sources.ts  # Applies the `storeActivity.sources` filter
├── render.tsx                   # Widget UI (DataViews `activity` layout)
├── style.scss                   # DataViews overrides + layout helpers
├── types.ts                     # ActivitySource / ActivityHookResult / StoreActivityEvent
├── widget.json                  # Static metadata read by @wordpress/build
└── widget.ts                    # Runtime entry consumed by the dashboard
```

### Data flow

1. Plugins register sources via `addFilter( 'storeActivity.sources', ... )`.
2. The widget calls `useActivitySources()` and receives the merged list.
3. `ActivitySourcesLoader` mounts one ghost child per source — each child runs the source's `useActivity` hook (respecting the Rules of Hooks) and reports its result to the parent via callback.
4. The widget aggregates `success` events from every source, sorts them by `datetime` descending, and renders them through `DataViews` (`activity` layout) grouped by day.

Reactivity is delegated to the source. Hooks typically use `useSelect` from `@wordpress/data`, so any change to the underlying entity store re-renders the widget without polling.

## Rendering rich content

`renderContent` is a render function, so events can include links, formatted strings, or any React tree:

```tsx
{
	id: order.id,
	icon: receipt,
	renderContent: () => (
		<>
			<a href={ `/orders/${ order.id }` }>Order #{ order.number }</a>
			{ ' from ' }
			<a href={ `/customers/${ order.customer_id }` }>
				{ order.billing.first_name } { order.billing.last_name }
			</a>
		</>
	),
	datetime: order.date_created_gmt,
}
```
