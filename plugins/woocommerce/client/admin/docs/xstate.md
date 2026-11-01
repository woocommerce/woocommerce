# XState Dev Tooling

XState is a state management framework for Javascript applications. It is based on the concept of [finite state machines](https://en.wikipedia.org/wiki/Finite-state_machine) and statecharts.

The state machines in WooCommerce Admin (for example the core profiler and the Launch Your Store hub) use XState v5 and `@xstate/react` v4.

## Inspecting a state machine

WooCommerce Admin doesn't ship a built-in inspector. To debug a machine locally, wire up the [Stately Inspector](https://stately.ai/docs/inspector) in your working copy and don't commit the change:

1. Add the inspector package to the admin client:

    ```sh
    pnpm --filter=@woocommerce/admin-library add -D @statelyai/inspect
    ```

2. Create a browser inspector and pass its `inspect` callback to the machine you want to debug:

    ```ts
    import { createBrowserInspector } from '@statelyai/inspect';

    const { inspect } = createBrowserInspector();

    const [ state, send, actor ] = useMachine( machine, { inspect } );
    ```

3. Rebuild the admin client (`pnpm --filter=@woocommerce/plugin-woocommerce watch:build:admin`) and load the page. The inspector opens in a new tab and shows the machine's current state, context and events as you use the page. Allow pop-ups for your local site if the tab doesn't open.
