/* Aggregate best-effort telemetry; fixed trusted URL. Callers do not consume a result. */
export function recordSuggestionOpened( url ) { return fetch( url, { method: 'POST' } ).catch( () => {} ); }
