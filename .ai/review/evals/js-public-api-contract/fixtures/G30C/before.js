/* Scenario context: Root entry of published @woocommerce/currency. New helper is additive and its documented input is an object with a symbol string. Existing formatter contract is unchanged. */
export function formatCurrency( amount, symbol ) { return symbol + Number( amount ).toFixed( 2 ); }
