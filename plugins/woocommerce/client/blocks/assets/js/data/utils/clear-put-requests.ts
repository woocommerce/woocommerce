/**
 * Internal dependencies
 */
import { clearChanges as clearCheckoutChanges } from '../checkout/push-changes';

// This is used to abort the checkout PUT requests
export let CheckoutPutAbortController = new AbortController();

export function clearCheckoutPutRequests(): void {
	// Abort any requests already in flight (batch not supported yet)
	CheckoutPutAbortController.abort();

	// Abort controller can only be used once, so we need to create a new one.
	CheckoutPutAbortController = new AbortController();

	// Clear any debounced requests awaiting to be sent
	clearCheckoutChanges();
}
