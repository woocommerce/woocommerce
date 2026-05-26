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
// version. See: https://github.com/WordPress/gutenberg/issues/XXXXX.
const moduleExports = IsShallowEqualModule as unknown as {
	default?: IsShallowEqualFn;
	isShallowEqual?: IsShallowEqualFn;
};

const isShallowEqual: IsShallowEqualFn =
	moduleExports.default ??
	moduleExports.isShallowEqual ??
	( IsShallowEqualModule as unknown as IsShallowEqualFn );

export default isShallowEqual;
