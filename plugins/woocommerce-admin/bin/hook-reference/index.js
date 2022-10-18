const { stat, readdir, writeFile } = require( 'fs' ).promises;
const { resolve } = require( 'path' );
const createData = require( './data' );
const chalk = require( 'chalk' );

async function getFilePaths( dir ) {
	const subdirs = await readdir( dir );
	const files = await Promise.all(
		subdirs.map( async ( subdir ) => {
			const res = resolve( dir, subdir );
			const _stat = await stat( res );
			const isDir = _stat.isDirectory();
			const isNotSourceDir =
				isDir &&
				/\/(build|build-module|build-style|node_modules)$/g.test( res );

			if ( isNotSourceDir ) {
				return;
			}

			return isDir ? getFilePaths( res ) : res;
		} )
	);
	return files
		.filter( ( f ) => !! f )
		.reduce( ( a, f ) => a.concat( f ), [] );
}

async function getAllFilePaths( paths ) {
	const allFiles = await Promise.all(
		paths.map( async ( path ) => {
			return await getFilePaths( path );
		} )
	);

	return allFiles.reduce( ( a, f ) => a.concat( f ), [] );
}

const writeJSONFile = async ( data ) => {
	const fileName = 'bin/hook-reference/data.json';
	const stringifiedData = JSON.stringify( data, null, 4 );
	await writeFile( fileName, stringifiedData + '\n' );

	console.log( '\n' );
	console.log(
		chalk.greenBright(
			'A new Hook Reference data source has been created. See `/bin/hook-reference/data.json` and be sure to commit changes.'
		)
	);
};

console.log( chalk.green( 'Preparing Hook Reference data file' ) );
console.log( '\n' );

getAllFilePaths( [ 'client', 'packages' ] )
	.then( ( paths ) => createData( paths ) )
	.then( ( data ) => writeJSONFile( data ) )
	.catch( ( e ) => console.error( e ) );
