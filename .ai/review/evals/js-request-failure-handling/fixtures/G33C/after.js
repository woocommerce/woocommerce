/* Aggregate best-effort telemetry; fixed trusted URL. Callers do not consume a result. */
export function recordSuggestionOpened( url ) { void fetch( url, { method: 'POST', keepalive: true } ).catch( () => {} ); }
