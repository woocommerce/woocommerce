/**
 * External dependencies
 */
import { store, getElement } from '@wordpress/interactivity';

/**
 * Helper function to check base dependencies required for express payment methods rendering.
 *
 * @param {Element | null} container - The DOM container element for the slot.
 * @param {string}         debugName - Name for debugging/logging purposes.
 * @return {boolean} True if all base dependencies are available, false otherwise.
 */
function checkBaseDependencies(
	container: Element | null,
	debugName: string
): boolean {
	if (
		! container ||
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		! ( window as any ).wp?.element?.createElement ||
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		! ( window as any ).wp?.element?.createRoot
	) {
		// eslint-disable-next-line no-console
		console.warn(
			`Express Checkout: Missing base dependencies for rendering ${ debugName }`,
			{
				hasContainer: !! container,
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				hasCreateElement: !! ( window as any ).wp?.element
					?.createElement,
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				hasCreateRoot: !! ( window as any ).wp?.element?.createRoot,
			}
		);
		return false;
	}

	return true;
}

store( 'woocommerce/express-checkout-iapi-block', {
	callbacks: {
		renderExpressPaymentMethodsIsland() {
			const { ref } = getElement();
			const debugName = 'express payment methods island';
			console.log( 'this gets called' );
			// Check if any express payment methods are actually registered
			// Use the window-exposed registry as it's the canonical source populated by the main app
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const expressPaymentMethods =
				(
					window as any
				 ).wc?.wcBlocksRegistry?.getExpressPaymentMethods() || {};
			const registeredMethodsCount = Object.keys(
				expressPaymentMethods
			).length;

			if ( registeredMethodsCount === 0 ) {
				// eslint-disable-next-line no-console
				console.info(
					`Express Checkout: No express payment methods registered, skipping ${ debugName }`
				);
				return;
			}

			// Verify dependencies are available
			const container =
				ref?.querySelector(
					'.wc-block-express-checkout-iapi__express-checkout-slot'
				) || ref;

			if ( ! checkBaseDependencies( container, debugName ) ) {
				return;
			}

			// Check for ExpressPaymentWrapper
			if (
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				! ( window as any ).wc?.blocksCheckout?.ExpressPaymentWrapper
			) {
				// eslint-disable-next-line no-console
				console.warn(
					'Express Checkout: ExpressPaymentWrapper not available'
				);
				return;
			}

			// Render the React island
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const { createElement, createRoot } = ( window as any ).wp.element;
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const { ExpressPaymentWrapper } = ( window as any ).wc
				.blocksCheckout;

			// Create React root if not exists
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			if ( ! ( window as any ).__expressCheckoutRoot ) {
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				( window as any ).__expressCheckoutRoot =
					createRoot( container );
			}

			const wrapperElement = createElement( ExpressPaymentWrapper );
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			( window as any ).__expressCheckoutRoot.render( wrapperElement );
		},
	},
} );
