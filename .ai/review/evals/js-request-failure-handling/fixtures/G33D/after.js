/* Private request helper; loadSuggestions is its only caller. URL is a fixed same-origin endpoint. */
async function requestSuggestions( url ) { const response = await fetch( url ); if ( ! response.ok ) { throw new Error( 'Request failed' ); } return response.json(); }
export async function loadSuggestions( url, setSuggestions, restoreManualEntry ) { try { setSuggestions( await requestSuggestions( url ) ); } catch ( error ) { restoreManualEntry(); } }
