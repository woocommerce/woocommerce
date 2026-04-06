# Cart IAPI POC: Proceed-to-Checkout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the Cart proceed-to-checkout inner block from React to the Interactivity API as a proof of concept, behind a feature flag, with full backwards compatibility.

**Architecture:** Feature-flag-gated dual rendering. PHP renders IAPI HTML when flag is on. A pass-through React component preserves the IAPI HTML inside React's DOM tree. A new cart event emitter (`window.wc.blocksCartEvents`) replaces the React-only observer pattern, with the existing React `CartEventsProvider` delegating to it for backwards compatibility.

**Tech Stack:** WordPress Interactivity API, PHP, TypeScript, Webpack script modules

**Spec:** `docs/superpowers/specs/2026-04-05-cart-iapi-poc-design.md`

---

## File Structure

### New Files

| File | Responsibility |
|------|---------------|
| `client/blocks/assets/js/events/cart-events.ts` | Cart event emitter instance with `onProceedToCheckout` |
| `client/blocks/assets/js/blocks/cart/inner-blocks/proceed-to-checkout-block/iapi-frontend.ts` | IAPI store for proceed-to-checkout |

### Modified Files

| File | Change |
|------|--------|
| `client/blocks/assets/js/events/index.ts` | Re-export cart events |
| `client/blocks/bin/webpack-helpers.js` | Add `blocksCartEvents` external mapping |
| `client/blocks/bin/webpack-entries.js` | Add `blocksCartEvents` build entry |
| `client/blocks/bin/webpack-config-interactive-blocks.js` | Add manual entry for proceed-to-checkout IAPI frontend |
| `client/blocks/assets/js/base/stores/woocommerce/cart.ts` | Expose `isProcessing` from mutation batcher |
| `client/blocks/assets/js/base/context/providers/cart-checkout/cart-events/index.tsx` | Delegate to shared cart event emitter |
| `client/blocks/assets/js/blocks/cart/inner-blocks/register-components.ts` | Skip React registration when IAPI detected in DOM |
| `src/Blocks/BlockTypes/ProceedToCheckoutBlock.php` | Add IAPI render path behind feature flag |
| `client/admin/config/core.json` | Add `experimental-iapi-cart` flag |
| `client/admin/config/development.json` | Add `experimental-iapi-cart` flag |

All paths below are relative to `plugins/woocommerce/`.

---

## Task 1: Feature Flag Registration

**Files:**
- Modify: `client/admin/config/core.json`
- Modify: `client/admin/config/development.json`

- [ ] **Step 1: Add feature flag to core.json**

In `client/admin/config/core.json`, add after the `experimental-iapi-mini-cart` line:

```json
"experimental-iapi-cart": false,
```

Set to `false` in core (production off by default).

- [ ] **Step 2: Add feature flag to development.json**

In `client/admin/config/development.json`, add after the `experimental-iapi-mini-cart` line:

```json
"experimental-iapi-cart": true,
```

Set to `true` in development (on for testing).

- [ ] **Step 3: Verify flag is generated**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce build:feature-config`

Check that `includes/react-admin/feature-config.php` now contains `'experimental-iapi-cart'`. If the build command doesn't exist, check git diff to confirm the JSON changes are correct — the PHP file is auto-generated during the full build.

- [ ] **Step 4: Commit**

```bash
git add plugins/woocommerce/client/admin/config/core.json plugins/woocommerce/client/admin/config/development.json
git commit -m "feat: add experimental-iapi-cart feature flag"
```

---

## Task 2: Cart Event Emitter

**Files:**
- Create: `client/blocks/assets/js/events/cart-events.ts`
- Modify: `client/blocks/assets/js/events/index.ts`
- Modify: `client/blocks/bin/webpack-helpers.js`
- Modify: `client/blocks/bin/webpack-entries.js`

- [ ] **Step 1: Create cart-events.ts**

Create `client/blocks/assets/js/events/cart-events.ts`:

```typescript
/**
 * Internal dependencies
 */
import { createEmitter } from './event-emitter';

export const CART_EVENTS = {
	/**
	 * Event emitted when the user clicks "Proceed to Checkout" in the cart.
	 * Observers can return an error/fail response to prevent navigation.
	 */
	PROCEED_TO_CHECKOUT: 'cart_proceed_to_checkout',
};

export const cartEventsEmitter = createEmitter();

export const cartEvents = {
	onProceedToCheckout: cartEventsEmitter.createSubscribeFunction(
		CART_EVENTS.PROCEED_TO_CHECKOUT
	),
};
```

- [ ] **Step 2: Export from index.ts**

In `client/blocks/assets/js/events/index.ts`, add:

```typescript
export * from './cart-events';
```

So the file becomes:

```typescript
export * from './checkout-events';
export * from './cart-events';
```

- [ ] **Step 3: Add webpack external mapping**

In `client/blocks/bin/webpack-helpers.js`, find the `wcDepMap` object and add:

```javascript
'@woocommerce/blocks-cart-events': [ 'wc', 'blocksCartEvents' ],
```

Find the `wcHandleMap` object and add:

```javascript
'@woocommerce/blocks-cart-events': 'wc-blocks-cart-events',
```

- [ ] **Step 4: Add webpack build entry**

In `client/blocks/bin/webpack-entries.js`, find the `core` section (where `blocksCheckoutEvents` is defined) and add:

```javascript
blocksCartEvents: './assets/js/events/cart-events.ts',
```

- [ ] **Step 5: Verify build**

Run from `plugins/woocommerce/client/blocks/`:

```bash
pnpm run build
```

Check that `build/wc-blocks-cart-events.js` (or similar) is generated without errors.

- [ ] **Step 6: Commit**

```bash
git add plugins/woocommerce/client/blocks/assets/js/events/ plugins/woocommerce/client/blocks/bin/webpack-helpers.js plugins/woocommerce/client/blocks/bin/webpack-entries.js
git commit -m "feat: add cart events emitter (window.wc.blocksCartEvents)"
```

---

## Task 3: Bridge CartEventsProvider to Shared Emitter

**Files:**
- Modify: `client/blocks/assets/js/base/context/providers/cart-checkout/cart-events/index.tsx`

This is the backwards-compatibility bridge. The React `CartEventsProvider` currently uses its own internal reducer-based observer system. We change it to delegate to the shared `cartEventsEmitter` so both React hooks and IAPI stores use the same observer registry.

- [ ] **Step 1: Rewrite CartEventsProvider to delegate to shared emitter**

Replace the contents of `client/blocks/assets/js/base/context/providers/cart-checkout/cart-events/index.tsx`:

```typescript
/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';
import type { ObserverResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { cartEventsEmitter, CART_EVENTS } from '../../../../events/cart-events';
import type { EventListener } from '../../../../events/event-emitter';

type CartEventsContextType = {
	onProceedToCheckout: (
		callback: EventListener,
		priority?: number
	) => () => void;
	dispatchOnProceedToCheckout: () => Promise< ObserverResponse[] >;
};

const CartEventsContext = createContext< CartEventsContextType >( {
	onProceedToCheckout: () => () => void null,
	dispatchOnProceedToCheckout: () => Promise.resolve( [] ),
} );

export const useCartEventsContext = () => {
	return useContext( CartEventsContext );
};

/**
 * Cart Events provider
 * Delegates to the shared cartEventsEmitter so that both React hooks
 * and Interactivity API stores share the same observer registry.
 */
export const CartEventsProvider = ( {
	children,
}: {
	children: React.ReactNode;
} ): JSX.Element => {
	const cartEventsValue: CartEventsContextType = {
		onProceedToCheckout: ( callback: EventListener, priority = 10 ) =>
			cartEventsEmitter.subscribe(
				callback,
				priority,
				CART_EVENTS.PROCEED_TO_CHECKOUT
			),
		dispatchOnProceedToCheckout: () =>
			cartEventsEmitter.emitWithAbort(
				CART_EVENTS.PROCEED_TO_CHECKOUT,
				null
			),
	};

	return (
		<CartEventsContext.Provider value={ cartEventsValue }>
			{ children }
		</CartEventsContext.Provider>
	);
};
```

- [ ] **Step 2: Run existing cart block tests to verify backwards compat**

Run from monorepo root:

```bash
pnpm --filter=@woocommerce/block-library test:js -- --testPathPattern="cart-events|proceed-to-checkout"
```

If no unit tests match, that's OK — the E2E tests will cover this. The key thing is the build succeeds.

- [ ] **Step 3: Verify build**

```bash
cd plugins/woocommerce/client/blocks && pnpm run build
```

No errors expected.

- [ ] **Step 4: Commit**

```bash
git add plugins/woocommerce/client/blocks/assets/js/base/context/providers/cart-checkout/cart-events/
git commit -m "refactor: bridge CartEventsProvider to shared cart event emitter"
```

---

## Task 4: Expose isProcessing in Shared Cart Store

**Files:**
- Modify: `client/blocks/assets/js/base/stores/woocommerce/cart.ts`

The shared IAPI cart store needs to expose whether the mutation batcher is currently processing, so the IAPI proceed-to-checkout block can disable the button during cart operations.

- [ ] **Step 1: Add isProcessing getter to cart store state**

In `client/blocks/assets/js/base/stores/woocommerce/cart.ts`, find the store definition and add an `isProcessing` getter to the state. The mutation batcher's `getStatus()` method returns `{ isProcessing, pendingCount }`.

Find where `cartQueue` is defined (it's lazily initialized in `sendCartRequest()`). The `isProcessing` state needs to check `cartQueue?.getStatus().isProcessing`.

Add to the store's `state` object:

```typescript
get isProcessing(): boolean {
	return cartQueue?.getStatus().isProcessing ?? false;
},
```

Look at the existing state properties to find the right location. It should go near the top of the `state` section alongside other cart status properties.

- [ ] **Step 2: Verify build**

```bash
cd plugins/woocommerce/client/blocks && pnpm run build
```

- [ ] **Step 3: Commit**

```bash
git add plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/cart.ts
git commit -m "feat: expose isProcessing state in shared woocommerce cart store"
```

---

## Task 5: PHP IAPI Render Path

**Files:**
- Modify: `src/Blocks/BlockTypes/ProceedToCheckoutBlock.php`

- [ ] **Step 1: Add IAPI rendering to ProceedToCheckoutBlock.php**

Replace the entire file with:

```php
<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Admin\Features\Features;

/**
 * ProceedToCheckoutBlock class.
 */
class ProceedToCheckoutBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'proceed-to-checkout-block';

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes Any attributes that currently are available from the block.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		$this->asset_data_registry->register_page_id( isset( $attributes['checkoutPageId'] ) ? $attributes['checkoutPageId'] : 0 );
	}

	/**
	 * Enable interactivity support when the feature flag is on.
	 */
	protected function initialize() {
		parent::initialize();

		if ( Features::is_enabled( 'experimental-iapi-cart' ) ) {
			add_action( 'init', array( $this, 'enable_interactivity_support' ), 20 );
		}
	}

	/**
	 * Dynamically enable interactivity support on the block type.
	 */
	public function enable_interactivity_support() {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/' . $this->block_name );
		if ( $block_type ) {
			$block_type->supports['interactivity'] = true;
		}
	}

	/**
	 * Render the block. When the IAPI flag is enabled, render interactive markup.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param \WP_Block $block     Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! Features::is_enabled( 'experimental-iapi-cart' ) ) {
			return $content;
		}

		return $this->render_iapi( $attributes, $content, $block );
	}

	/**
	 * Render the IAPI version of the proceed-to-checkout block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param \WP_Block $block     Block instance.
	 * @return string Rendered block output.
	 */
	private function render_iapi( $attributes, $content, $block ) {
		wp_enqueue_script_module( 'woocommerce/proceed-to-checkout' );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_cart_state( $consent );

		// Resolve checkout URL.
		$checkout_page_id = isset( $attributes['checkoutPageId'] ) ? $attributes['checkoutPageId'] : 0;
		$checkout_url     = $checkout_page_id ? get_permalink( $checkout_page_id ) : wc_get_checkout_url();
		if ( ! $checkout_url ) {
			$checkout_url = wc_get_checkout_url();
		}

		// Resolve button label.
		$button_label = isset( $attributes['buttonLabel'] ) && ! empty( $attributes['buttonLabel'] )
			? $attributes['buttonLabel']
			: __( 'Proceed to Checkout', 'woocommerce' );

		$context = array(
			'checkoutUrl' => $checkout_url,
			'buttonLabel' => $button_label,
			'isLoading'   => false,
		);

		$context_attr = wp_interactivity_data_wp_context( $context );

		return sprintf(
			'<div
				class="wc-block-cart__submit"
				data-wp-interactive="woocommerce/proceed-to-checkout"
				%1$s
			>
				<div aria-hidden="true" style="height:0;overflow:hidden;position:relative" data-wp-init="callbacks.initStickyObserver"></div>
				<div
					class="wc-block-cart__submit-container"
					data-wp-class--wc-block-cart__submit-container--sticky="state.isStickyVisible"
					data-wp-style--background-color="state.stickyBackgroundColor"
				>
					<a
						class="wc-block-cart__submit-button wc-block-components-button wp-element-button"
						data-wp-bind--href="context.checkoutUrl"
						data-wp-bind--aria-disabled="state.isDisabled"
						data-wp-class--wc-block-cart__submit-button--loading="context.isLoading"
						data-wp-on--click="actions.handleClick"
						href="%2$s"
					>
						<span class="wc-block-components-button__text" data-wp-text="context.buttonLabel">%3$s</span>
					</a>
				</div>
			</div>',
			$context_attr,
			esc_url( $checkout_url ),
			esc_html( $button_label )
		);
	}
}
```

**Key details:**
- `render()` returns `$content` unchanged when flag is off (existing React behavior)
- When flag is on, `render_iapi()` produces server-rendered HTML with `data-wp-interactive` directives
- The sentinel div (height:0) is used by `initStickyObserver` callback
- The button is an `<a>` tag with `href` for accessibility (E2E tests expect `role="link"`)
- Initial values are rendered as HTML attributes for SSR (visible before JS loads)
- `BlocksSharedState::load_cart_state()` ensures the shared `woocommerce` store is hydrated

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l plugins/woocommerce/src/Blocks/BlockTypes/ProceedToCheckoutBlock.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add plugins/woocommerce/src/Blocks/BlockTypes/ProceedToCheckoutBlock.php
git commit -m "feat: add IAPI render path for proceed-to-checkout block"
```

---

## Task 6: IAPI Store (Frontend)

**Files:**
- Create: `client/blocks/assets/js/blocks/cart/inner-blocks/proceed-to-checkout-block/iapi-frontend.ts`
- Modify: `client/blocks/bin/webpack-config-interactive-blocks.js`

- [ ] **Step 1: Create iapi-frontend.ts**

Create `client/blocks/assets/js/blocks/cart/inner-blocks/proceed-to-checkout-block/iapi-frontend.ts`:

```typescript
/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { isErrorResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { cartEventsEmitter, CART_EVENTS } from '../../../../events/cart-events';

type WooCommerce = {
	state: {
		cart: {
			items: unknown[];
		};
		isProcessing: boolean;
	};
};

type ProceedToCheckoutContext = {
	checkoutUrl: string;
	buttonLabel: string;
	isLoading: boolean;
};

type ProceedToCheckoutStore = {
	state: {
		readonly isDisabled: boolean;
		isStickyVisible: boolean;
		stickyBackgroundColor: string;
	};
	actions: {
		handleClick: ( event: MouseEvent ) => void;
	};
	callbacks: {
		onPageShow: () => () => void;
		initStickyObserver: () => () => void;
	};
};

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// Access shared woocommerce store for cart state.
const { state: woocommerceState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

// Store-level state for sticky behavior. These are in the store's state
// object so the IAPI reactivity system tracks reads/writes and triggers
// re-renders of directives that depend on them.
const { state: ptcState } = store< ProceedToCheckoutStore >(
	'woocommerce/proceed-to-checkout',
	{
		state: {
			get isDisabled(): boolean {
				return woocommerceState.isProcessing;
			},
			isStickyVisible: false,
			stickyBackgroundColor: '',
		},
		actions: {
			*handleClick( event: MouseEvent ) {
				event.preventDefault();

				const context = getContext< ProceedToCheckoutContext >();

				if ( woocommerceState.isProcessing || context.isLoading ) {
					return;
				}

				// Dispatch proceed-to-checkout event. Observers can abort.
				const responses: Awaited<
					ReturnType< typeof cartEventsEmitter.emitWithAbort >
				> = yield cartEventsEmitter.emitWithAbort(
					CART_EVENTS.PROCEED_TO_CHECKOUT,
					null
				);

				if ( responses.some( isErrorResponse ) ) {
					return;
				}

				context.isLoading = true;
				window.location.href = context.checkoutUrl;
			},
		},
		callbacks: {
			onPageShow() {
				// Capture context while in directive scope (getContext only
				// works inside IAPI directive callbacks, not in event handlers).
				const context = getContext< ProceedToCheckoutContext >();
				const callback = () => {
					context.isLoading = false;
				};
				window.addEventListener( 'pageshow', callback );
				return () => {
					window.removeEventListener( 'pageshow', callback );
				};
			},
			initStickyObserver() {
				const { ref } = getElement();
				if ( ! ref ) {
					return;
				}

				// Compute body background color once.
				const computedColor =
					getComputedStyle( document.body ).backgroundColor;
				const bgColor =
					! computedColor ||
					computedColor === 'rgba(0, 0, 0, 0)' ||
					computedColor === 'transparent'
						? '#fff'
						: computedColor;

				const observer = new IntersectionObserver(
					( entries ) => {
						const entry = entries[ 0 ];
						if ( entry.isIntersecting ) {
							ptcState.isStickyVisible = false;
							ptcState.stickyBackgroundColor = '';
						} else {
							const isBelow =
								entry.boundingClientRect.top > 0;
							ptcState.isStickyVisible = isBelow;
							ptcState.stickyBackgroundColor = isBelow
								? bgColor
								: '';
						}
					},
					{ threshold: [ 0, 0.5, 1 ] }
				);

				observer.observe( ref );

				return () => {
					observer.disconnect();
				};
			},
		},
	},
	{ lock: true }
);
```

- [ ] **Step 2: Add webpack entry**

In `client/blocks/bin/webpack-config-interactive-blocks.js`, add a manual entry alongside the mini-cart one (around line 31):

```javascript
// Experimental proceed-to-checkout IAPI frontend, only enqueued when experimental-iapi-cart feature flag is enabled.
'woocommerce/proceed-to-checkout':
	'./assets/js/blocks/cart/inner-blocks/proceed-to-checkout-block/iapi-frontend.ts',
```

- [ ] **Step 3: Verify build**

```bash
cd plugins/woocommerce/client/blocks && pnpm run build
```

Check that `build/woocommerce/proceed-to-checkout.js` is generated.

- [ ] **Step 4: Commit**

```bash
git add plugins/woocommerce/client/blocks/assets/js/blocks/cart/inner-blocks/proceed-to-checkout-block/iapi-frontend.ts plugins/woocommerce/client/blocks/bin/webpack-config-interactive-blocks.js
git commit -m "feat: add IAPI store for proceed-to-checkout block"
```

---

## Task 7: Unregister React Component When IAPI Enabled

**Files:**
- Modify: `client/blocks/assets/js/blocks/cart/inner-blocks/register-components.ts`

When the IAPI flag is on, the server renders `data-wp-interactive` HTML. The parent Cart block's React `renderInnerBlocks()` finds blocks via `data-block-name` and replaces them with registered React components. If we **don't register** a React component for this block, `renderInnerBlocks` falls through to `html-react-parser` (line 167-212 of `render-parent-block.tsx`) which preserves the HTML as React elements. The IAPI runtime then binds to the real DOM nodes independently.

This is simpler and more reliable than a pass-through component — no risk of React re-rendering and destroying IAPI bindings.

- [ ] **Step 1: Conditionally skip registration**

In `client/blocks/assets/js/blocks/cart/inner-blocks/register-components.ts`, find the proceed-to-checkout registration:

```typescript
registerCheckoutBlock( {
	metadata: metadata.PROCEED_TO_CHECKOUT,
	component: ProceedToCheckoutFrontend,
} );
```

Wrap it in an IAPI detection check. The detection uses the DOM since this script runs after DOM is available (cart scripts are deferred and load in footer):

```typescript
// When the IAPI flag is on, PHP renders data-wp-interactive markup.
// Skip React registration so renderInnerBlocks preserves the server HTML
// and the IAPI runtime can bind to it.
if (
	! document.querySelector(
		'[data-wp-interactive="woocommerce/proceed-to-checkout"]'
	)
) {
	registerCheckoutBlock( {
		metadata: metadata.PROCEED_TO_CHECKOUT,
		component: ProceedToCheckoutFrontend,
	} );
}
```

- [ ] **Step 2: Verify `frontend.tsx` is unchanged**

The existing `frontend.tsx` stays as-is. It's only loaded when the React path is active (i.e., when the block IS registered).

```typescript
// frontend.tsx — no changes needed
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import Block from './block';
import attributes from './attributes';
export default withFilteredAttributes( attributes )( Block );
```

- [ ] **Step 3: Verify build**

```bash
cd plugins/woocommerce/client/blocks && pnpm run build
```

- [ ] **Step 4: Commit**

```bash
git add plugins/woocommerce/client/blocks/assets/js/blocks/cart/inner-blocks/register-components.ts
git commit -m "feat: skip React registration for proceed-to-checkout when IAPI enabled"
```

---

## Task 8: E2E Verification

The existing Cart E2E tests are our validation. They test:
- "Proceed to Checkout" button navigation (`cart-block.shopper.block_theme.spec.ts:233`)
- Button disabled during network requests (`cart-block.shopper.block_theme.spec.ts:174-224`)
- Button visibility and translation (`cart-checkout-block-translations.shopper.block_theme.spec.ts`)

- [ ] **Step 1: Start the test environment**

```bash
cd plugins/woocommerce/client/blocks && pnpm env:start
```

- [ ] **Step 2: Enable the feature flag**

The development config has `experimental-iapi-cart: true`, so it should be enabled in the test env. Verify by checking wp-admin or by running:

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli -- wp option get woocommerce_feature_flags
```

If the flag isn't automatically picked up, enable it manually:

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli -- wp option update woocommerce_feature_experimental-iapi-cart yes
```

- [ ] **Step 3: Run cart E2E tests**

```bash
cd plugins/woocommerce/client/blocks && pnpm test:e2e -- --grep "cart-block.shopper"
```

Key tests that must pass:
- "User can proceed to checkout" — button navigates to checkout
- "User can update product quantity" — button disables during network requests
- "User can remove a product" — cart still works after removal

- [ ] **Step 4: Run broader cart E2E suite**

```bash
cd plugins/woocommerce/client/blocks && pnpm test:e2e -- --grep "cart"
```

This runs all cart-related tests including coupons, shipping, translations.

- [ ] **Step 5: If tests fail, debug**

Common failure modes:
- **Button not found as `role="link"`**: The IAPI HTML must render `<a>` not `<button>`. Check PHP output.
- **Button not disabling**: `isProcessing` not being read correctly. Check cart store getter.
- **Navigation blocked**: Event emitter not dispatching correctly. Check CartEventsProvider bridge.
- **Sticky not working**: IntersectionObserver not binding. Check sentinel element rendering.

- [ ] **Step 6: Commit any fixes and tag as complete**

```bash
git add -A
git commit -m "fix: address E2E test feedback for IAPI proceed-to-checkout"
```
