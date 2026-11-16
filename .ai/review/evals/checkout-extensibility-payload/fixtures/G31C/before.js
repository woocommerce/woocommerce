/* Core-owned private checkout reducer; extension payloads do not enter this state. */
function normalizeCoreTotal( payload ) { return Number( payload.total ); }
function renderCoreTotal( payload ) { return normalizeCoreTotal( payload ); }

export function selectTotal( linePrices ) {
    const total = linePrices.reduce( ( sum, price ) => sum + Number( price ), 0 );
    return renderCoreTotal( { total: Number.isFinite( total ) ? total : 0 } );
}
