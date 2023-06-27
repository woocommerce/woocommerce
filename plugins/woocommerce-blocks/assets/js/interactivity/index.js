import registerDirectives from './directives';
import { init } from './router';
export { store } from './store';
export { navigate } from './router';

/**
 * Initialize the Interactivity API.
 */
document.addEventListener( 'DOMContentLoaded', async () => {
	registerDirectives();
	await init();
	// eslint-disable-next-line no-console
	console.log( 'Interactivity API started' );
} );
