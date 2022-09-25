const { readFile } = require( 'fs' ).promises;
const exec = require( 'await-exec' );
const { parse } = require( 'comment-parser/lib' );
const { relative, resolve } = require( 'path' );
const chalk = require( 'chalk' );

const dataTypes = [ 'action', 'filter', 'slotFill' ];

const getHooks = ( parsedData ) =>
	parsedData.filter( ( docBlock ) =>
		docBlock.tags.some( ( tag ) => dataTypes.includes( tag.tag ) )
	);

const getSourceFile = ( file, commit, { source } ) => {
	const first = source[ 0 ].number + 1;
	const last = source[ source.length - 1 ].number + 1;

	return `https://github.com/woocommerce/woocommerce-admin/blob/${ commit }/${ file }#L${ first }-L${ last }`;
};

const logProgress = ( fileName, { tags } ) => {
	const hook = tags.find( ( tag ) => dataTypes.includes( tag.tag ) );
	console.log(
		chalk.green( `@${ hook.tag } ` ) +
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
		const tag = tags.find( ( tag ) => dataTypes.includes( tag.tag ) );

		paramTags = tags.reduce(
			( result, { tag, name, type, description } ) => {
				if ( tag === 'param' ) {
					result.push( {
						name,
						type,
						description,
					} );
				}
				return result;
			},
			[]
		);

		const docObject = {
			description,
			sourceFile,
			name: tag ? tag.name : '',
			type: tag.tag,
			params: paramTags,
		};

		if ( tag.tag === 'slotFill' ) {
			const scopeTab = tags.find( ( tag ) => tag.tag === 'scope' );
			if ( scopeTab ) {
				docObject.scope = scopeTab.name;
			} else {
				console.warn(
					`Failed to find "scope" tag for slotFill "${ tag.name }" doc.`
				);
			}
		}

		return docObject;
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
