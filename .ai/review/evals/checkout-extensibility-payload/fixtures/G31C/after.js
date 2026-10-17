/* Core-owned private checkout reducer; extension payloads do not enter this state. */
function normalizeCoreTotal( payload ) { return typeof payload.total === 'number' && Number.isFinite( payload.total ) ? payload.total : 0; }
function renderCoreTotal( payload ) { return normalizeCoreTotal( payload ); }

export function selectTotal( linePrices ) {
    const total = linePrices.reduce( ( sum, price ) => sum + Number( price ), 0 );
    return renderCoreTotal( { total: Number.isFinite( total ) ? total : 0 } );
}
