/**
 * External dependencies
 */
import * as IsShallowEqualModule from '@wordpress/is-shallow-equal';

type IsShallowEqualFn = ( a: unknown, b: unknown ) => boolean;

// Gutenberg's new esbuild-based bundling pipeline (PR
// WordPress/gutenberg#72140) appends a `wp.X = Object.assign({}, wp.X)`
// footer to every `window.wp.*` global. `Object.assign` only copies
// enumerable own properties, so the non-enumerable `__esModule: true`
// flag is dropped — which breaks webpack's default-import interop
// (`__webpack_require__.n` returns the whole namespace object instead
// of `.default`). The upstream Gutenberg source has started adding
// named exports as a migration aid, but the npm package only exposes
// `default`, so we fall back to that for WordPress core's bundled
// version. See: https://github.com/WordPress/gutenberg/issues/78697.
const moduleExports = IsShallowEqualModule as unknown as {
	default?: unknown;
	isShallowEqual?: unknown;
};

// Pick the first candidate that is actually callable. Guarding against
// non-function values avoids a delayed crash if some future build of
// `@wordpress/is-shallow-equal` ships a non-callable `default`/named
// export but still passes truthy checks.
const candidates: unknown[] = [
	moduleExports.default,
	moduleExports.isShallowEqual,
	IsShallowEqualModule,
];
const callable = candidates.find(
	( candidate ): candidate is IsShallowEqualFn =>
		typeof candidate === 'function'
);

if ( ! callable ) {
	throw new Error(
		'WooCommerce: `@wordpress/is-shallow-equal` did not expose a callable export. ' +
			'This usually means the runtime build of the package has changed in an incompatible way.'
	);
}

const isShallowEqual: IsShallowEqualFn = callable;

export default isShallowEqual;
