/* Address suggestion widget hides the manual-entry panel while loading. Caller starts loadSuggestions without awaiting it. */
export async function loadSuggestions( url, setSuggestions, restoreManualEntry ) { try { const response = await fetch( url ); if ( ! response.ok ) { throw new Error( 'Request failed' ); } setSuggestions( await response.json() ); } finally { /* request settled */ } }
