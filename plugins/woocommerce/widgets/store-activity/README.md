# Store Activity Widget

A WooCommerce dashboard widget that surfaces recent store events (orders, customers, bookings, reviews, etc.) in a chronological timeline. The widget itself ships no data — events are contributed by plugins through a WordPress filter, so any extension can populate the timeline with its own activity.

## Activity Sources API

Sources are registered via the `storeActivity.sources` filter from `@wordpress/hooks`. A source is a small object that exposes a React hook returning the events it wants to render.

### Source contract

```ts
type ActivitySource = {
    id: string;                          // unique id (namespacing encouraged)
    useActivity: () => ActivityHookResult;
};

type ActivityHookResult = {
    state: 'loading' | 'empty' | 'success';
    events?: StoreActivityEvent[];       // only when state === 'success'
};

type StoreActivityEvent = {
    id: string | number;                 // unique within the source
    icon: JSX.Element;                   // typically from @wordpress/icons
    renderContent: () => React.ReactNode;
    datetime: string;                    // ISO 8601, used for sorting & grouping
};
```

## Extending the widget from another plugin

A full plugin extension typically needs three pieces:

1. **An init module** (a wp-build script module) that boots on the dashboard and registers the source.
2. **(Optional) Entity registration** for `@wordpress/core-data` when the source reads REST records via `useSelect( coreStore ).getEntityRecords( ... )`.
3. **The activity hook** that adapts records into `StoreActivityEvent`s.

The canonical reference for the WooCommerce-core contribution lives at `plugins/woocommerce/packages/core-dashboard-init/` and you can mirror its shape. The walkthrough below is a minimal end-to-end example for a hypothetical "Reviews" plugin.

### 1. Init module that boots on the dashboard

```ts
// my-plugin/src/index.ts
import { registerReviewEntity } from './data';
import { registerReviewActivitySource } from './store-activity';

// Order matters: entities before any source that reads from them.
registerReviewEntity();
registerReviewActivitySource();

// Forward-compatible with wp-build's formal init module contract.
export async function init(): Promise< void > {}
```

This script module must run on the dashboard page. For consumers that own a page declared via `wpPlugin.pages` in their `package.json`, the formal way is to list the package in that page's `init: [...]` array — wp-build will wire it as a static boot dep and `@wordpress/boot.init()` will call your `init()` automatically.

The dashboard page is owned by Gutenberg/core, not by external plugins, so today consumers also need a small PHP shim that injects an inline `import('@my-plugin/init')` into the dashboard's prerequisites script. See `plugins/woocommerce/src/Internal/Admin/DashboardWidgets/Loader.php::enqueue_init_module_evaluator()` for WooCommerce-core's implementation; copying the shape is enough.

### 2. (Optional) Entity registration

When your source reads REST data through `@wordpress/core-data`, you typically register the entity yourself:

```ts
// my-plugin/src/data/index.ts
import { dispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

export const REVIEW_ENTITY = {
    name: 'review',
    kind: 'my-plugin',                   // your own namespace
    baseURL: '/my-plugin/v1/reviews',    // exact REST URL
    key: 'id',
    label: __( 'Review', 'my-plugin' ),
    plural: __( 'Reviews', 'my-plugin' ),
    supportsPagination: true,
};

const registered = new Set< string >();
export function registerReviewEntity() {
    const key = `${ REVIEW_ENTITY.kind }/${ REVIEW_ENTITY.name }`;
    if ( registered.has( key ) ) return;
    void dispatch( coreStore ).addEntities( [ REVIEW_ENTITY ] );
    registered.add( key );
}
```

> Note: you usually need to register your own entity because most plugins' REST endpoints aren't exposed through `wp/v2/<cpt>` (e.g. WooCommerce's `shop_order` CPT does **not** set `show_in_rest: true`). Pointing the entity at the canonical REST URL (`/wc/v3/orders`, `/my-plugin/v1/reviews`, etc.) is the reliable path.

### 3. The activity hook + filter registration

```tsx
// my-plugin/src/store-activity/use-review-activity.tsx
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { Link } from '@wordpress/ui';
import { starFilled } from '@wordpress/icons';
import { REVIEW_ENTITY } from '../data';

const QUERY = { per_page: 10, orderby: 'date', order: 'desc' };

export function useReviewActivity() {
    const { reviews, isResolving } = useSelect( ( select ) => ( {
        reviews: select( coreStore ).getEntityRecords(
            REVIEW_ENTITY.kind,
            REVIEW_ENTITY.name,
            QUERY
        ),
        isResolving: select( coreStore ).isResolving(
            'getEntityRecords',
            [ REVIEW_ENTITY.kind, REVIEW_ENTITY.name, QUERY ]
        ),
    } ), [] );

    if ( isResolving ) return { state: 'loading' };
    if ( ! reviews?.length ) return { state: 'empty' };

    return {
        state: 'success',
        events: reviews.map( ( review ) => ( {
            id: review.id,
            icon: starFilled,
            renderContent: () => (
                <span>
                    <Link href={ `/wp-admin/edit-comments.php?p=${ review.id }` }>
                        { `Review #${ review.id }` }
                    </Link>
                    { ' from ' }
                    { review.author_name }
                </span>
            ),
            datetime: review.date_gmt,
        } ) ),
    };
}
```

```ts
// my-plugin/src/store-activity/register-sources.ts
import { addFilter } from '@wordpress/hooks';
import { useReviewActivity } from './use-review-activity';

export function registerReviewActivitySource() {
    addFilter(
        'storeActivity.sources',
        'my-plugin/store-activity-sources',
        ( sources ) => [
            ...sources,
            { id: 'my-plugin/reviews', useActivity: useReviewActivity },
        ]
    );
}
```

Register the filter as early as possible (at module-load top level) so the source is available the first time the widget calls `applyFilters`. The reference packaging is in `plugins/woocommerce/packages/core-dashboard-init/`.

## Rendering rich content

`renderContent` is a render function, so events can include links, formatted strings, or any React tree.

### Links

- **Legacy WP-admin destinations** (`/wp-admin/post.php?post=…`, `/wp-admin/edit-comments.php?…`, etc.) — use `Link` from `@wordpress/ui`. It forwards standard anchor props to an `<a>` and carries WPDS tokens (brand color, focus ring), so the styling is consistent with the rest of the dashboard.
- **SPA-internal destinations** (routes owned by the dashboard SPA) — use `Link` or `useNavigate` from `@wordpress/route`. Those go through the TanStack-backed router instead of forcing a full page reload.

```tsx
import { Link } from '@wordpress/ui';

// Legacy admin destination → @wordpress/ui Link.
renderContent: () => (
    <span>
        <Link href={ `/wp-admin/post.php?post=${ order.id }&action=edit` }>
            { `Order #${ order.number }` }
        </Link>
        { ' from ' }
        <Link href={ `/wp-admin/user-edit.php?user_id=${ order.customer_id }` }>
            { customerName }
        </Link>
    </span>
)
```

```tsx
import { Link } from '@wordpress/route';

// SPA-internal destination → @wordpress/route Link.
renderContent: () => (
    <Link to="/customers/$id" params={ { id: customer.id } }>
        { customer.name }
    </Link>
)
```

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

1. Plugins register sources via `addFilter( 'storeActivity.sources', ... )` at boot.
2. The widget calls `useActivitySources()` and receives the merged list.
3. `ActivitySourcesLoader` mounts one ghost child per source — each child runs the source's `useActivity` hook (respecting the Rules of Hooks) and reports its result to the parent via callback.
4. The widget aggregates `success` events from every source, sorts them by `datetime` descending, and renders them through `DataViews` (`activity` layout) grouped by day.

Reactivity is delegated to the source. Hooks typically use `useSelect` from `@wordpress/data`, so any change to the underlying entity store re-renders the widget without polling.
