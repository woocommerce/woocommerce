/* Private request helper; loadSuggestions is its only caller. URL is a fixed same-origin endpoint. */
export async function loadSuggestions( url, setSuggestions, restoreManualEntry ) { try { const response = await fetch( url ); if ( ! response.ok ) { throw new Error( 'Request failed' ); } setSuggestions( await response.json() ); } catch ( error ) { restoreManualEntry(); } }
