import { runPackageBuilder } from '@woocommerce/internal-build';

await runPackageBuilder( {
	entryPoints: 'src/**/*.{ts,tsx,js,jsx}',
	// SVG imports are left untouched by the (non-bundling) transpile, so the
	// asset files must be copied alongside the emitted modules.
	assets: [ 'src/**/*.svg' ],
} );
