/* Private extension adapter. Field definitions are serialized by the extension, not the WooCommerce Additional Fields API. */
const extensionResponse = JSON.parse( '{"fields":[{"id":"reference","hidden":1},{"id":"note","hidden":0}]}' );
export function visibleAdditionalFields( additionalFields ) {
    return additionalFields.filter( ( field ) => ! field.hidden );
}
export function renderExtensionFields( render ) {
    visibleAdditionalFields( extensionResponse.fields ).forEach( ( field ) => render( field.id ) );
}
