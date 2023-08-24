import registerDirectives from './directives';
import { init } from './router';
import { rawStore, afterLoads } from './store';

export { navigate } from './router';
export { store } from './store';

/**
 * Initialize the Interactivity API.
 */
document.addEventListener( 'DOMContentLoaded', async () => {
	registerDirectives();
	await init();
	afterLoads.forEach( ( afterLoad ) => afterLoad( rawStore ) );
} );
