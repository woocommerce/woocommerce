import { runPackageBuilder } from '@woocommerce/internal-build';

await runPackageBuilder( {
	entryPoints: 'src/**/*.{ts,tsx,js,jsx}',
	assets: [ 'src/**/block.json' ],
} );
