/* Scenario context: Only formatCurrency is a published export; normalizeAmount is local to this root module, never exported, and all call sites are shown. No import-time global side effects exist. Supported inputs are finite numbers and numeric strings. */
function normalizeAmount( amount ) { return Number( amount ); }
export function formatCurrency( amount, symbol ) { return symbol + normalizeAmount( amount ).toFixed( 2 ); }
