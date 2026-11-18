/* Checkout extension fee preview. Registered sample-shipping schema permits a finite number or decimal string for amount. */
export function extensionAmount( extensions ) { const raw = extensions['sample-shipping'].amount; return typeof raw === 'number' && Number.isFinite( raw ) ? raw : 0; }

export const extensionPayload = JSON.parse( '{"sample-shipping":{"amount":"5.00"}}' );
