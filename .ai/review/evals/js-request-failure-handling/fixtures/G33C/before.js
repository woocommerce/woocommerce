/* Scenario context: Private helper sends aggregate telemetry to a fixed trusted endpoint. Caller never awaited or used its return. Event delivery is explicitly best-effort and does not change orders, settings, or checkout state. Reporting failure is intentionally silent. */
export function recordSuggestionOpened( url ) { return fetch( url, { method: 'POST' } ).catch( () => {} ); }
