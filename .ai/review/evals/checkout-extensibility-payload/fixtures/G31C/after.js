/* Scenario context: This module is private checkout implementation, not a published package API. The input is created exclusively by a core reducer with numeric finite total; extensions cannot write this state and no PHP serialization boundary is involved. The export is only for local module imports. */
function normalizeCoreTotal( payload ) { return typeof payload.total === 'number' && Number.isFinite( payload.total ) ? payload.total : 0; }
export function renderCoreTotal( payload ) { return normalizeCoreTotal( payload ); }
