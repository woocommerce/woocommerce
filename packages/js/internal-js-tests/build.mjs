import { runPackageBuilder } from '@woocommerce/internal-build';

await runPackageBuilder( {
	entryPoints: 'src/**/*.{ts,tsx,js,jsx}',
	ignore: [ 'src/setup-*.js', 'src/mocks/**' ],
} );
