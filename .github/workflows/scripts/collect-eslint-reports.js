const fs = require( 'fs' );
const path = require( 'path' );

const rootDirectory = path.resolve( __dirname );
const packagesDirectory = path.join( rootDirectory, 'packages' ); // Adjust the path accordingly

const allReports = [];

function collectReports( directory ) {
	const files = fs.readdirSync( directory );
	for ( const file of files ) {
		const fullPath = path.join( directory, file );
		const isDirectory = fs.statSync( fullPath ).isDirectory();

		if ( isDirectory && file !== 'node_modules' && file !== 'vendor' ) {
			const reportPath = path.join( fullPath, 'eslint_report.json' );
			if ( fs.existsSync( reportPath ) ) {
				const report = require( reportPath );
				allReports.push( report );
			}

			collectReports( fullPath );
		}
	}
}

collectReports( packagesDirectory );

fs.writeFileSync(
	'combined_eslint_report.json',
	JSON.stringify( allReports, null, 2 )
);
