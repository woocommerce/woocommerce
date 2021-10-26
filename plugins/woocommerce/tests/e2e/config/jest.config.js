const path = require( 'path' );
const { useE2EJestConfig, getAppRoot } = require( '@woocommerce/e2e-environment' );

console.log('<<< Logging >>>>\n');

console.log( "process.cwd():\t " + process.cwd()  );

console.log("__dirname:\t " + __dirname + '\n');

const jestConfig = useE2EJestConfig( {
	roots: [ path.resolve( __dirname, '../specs' ) ],
} );
console.log( jestConfig );
module.exports = jestConfig;
