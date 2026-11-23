/* Checkout extension fee preview. Registered sample-shipping schema permits a finite number or decimal string for amount. */
export function extensionAmount( extensions ) { const raw = extensions['sample-shipping'].amount; const value = Number( raw ); return Number.isFinite( value ) ? value : 0; }

export const extensionPayload = JSON.parse( '{"sample-shipping":{"amount":"5.00"}}' );
