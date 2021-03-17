const { readFile } = require( 'fs' ).promises;
const exec = require( 'await-exec' );
const { parse } = require( 'comment-parser/lib' );
const { relative, resolve } = require( 'path' );
const chalk = require( 'chalk' );

const getHooks = ( parsedData ) =>
	parsedData.filter( ( docBlock ) =>
		docBlock.tags.some( ( tag ) => tag.tag === 'hook' )
	);

const getSourceFile = ( file, commit, { source } ) => {
	const first = source[ 0 ].number + 1;
	const last = source[ source.length - 1 ].number + 1;

	return `https://github.com/woocommerce/woocommerce-admin/blob/${ commit }/${ file }#L${ first }-L${ last }`;
};

const logProgress = ( fileName, { tags } ) => {
	const hook = tags.find( ( tag ) => tag.tag === 'hook' );
	console.log(
		chalk.cyan( `${ hook.name } ` ) +
			chalk.yellow( 'generated in ' ) +
			chalk.yellow.underline( fileName )
	);
};

const addSourceFiles = async ( hooks, fileName ) => {
	const { stdout } = await exec( 'git log --pretty="format:%H" -1' );
	const commit = stdout.trim();

	return hooks.map( ( hook ) => {
		logProgress( fileName, hook );
		hook.sourceFile = getSourceFile( fileName, commit, hook );
		return hook;
	} );
};

const prepareHooks = async ( path ) => {
	const data = await readFile( path, 'utf-8' ).catch( ( err ) =>
		console.error( 'Failed to read file', err )
	);
	const fileName = relative( resolve( __dirname, '../../' ), path );

	const parsedData = parse( data );
	const rawHooks = getHooks( parsedData );
	return await addSourceFiles( rawHooks, fileName );
};

const makeDocObjects = async ( path ) => {
	const hooks = await prepareHooks( path );
	return hooks.map( ( { description, tags, sourceFile } ) => {
		const example = tags.find( ( tag ) => tag.tag === 'example' );
		const hook = tags.find( ( tag ) => tag.tag === 'hook' );
		return {
			description,
			sourceFile,
			name: hook ? hook.name : '',
			example: example ? example.description : '',
		};
	} );
};

const createData = async ( paths ) => {
	const data = await Promise.all(
		paths.map( async ( path ) => {
			return await makeDocObjects( path );
		} )
	);
	return data.flat();
};

module.exports = createData;
