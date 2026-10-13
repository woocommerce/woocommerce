/* Scenario context: This is the root entry of released @woocommerce/currency. Published API docs include getCurrencySymbol(settings). Third-party consumers import that name from the package root. PR describes internal cleanup and includes no announced removal or deprecation. */
export function formatCurrency( amount, symbol ) { return symbol + Number( amount ).toFixed( 2 ); }
function getCurrencySymbol( settings ) { return settings.symbol; }
