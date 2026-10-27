/**
 * Internal dependencies
 */
import defaultConfig from '../../playwright.config';

process.env.USE_WP_ENV = 'true';

// SQLite-backed object cache serialises writes; concurrent API workers cause
// lock contention that makes the server return SQLite error text instead of
// JSON. Run API tests one at a time to prevent this.
export default {
	...defaultConfig,
	projects: defaultConfig.projects?.map( ( p ) =>
		p.name === 'api' ? { ...p, workers: 1 } : p
	),
};
