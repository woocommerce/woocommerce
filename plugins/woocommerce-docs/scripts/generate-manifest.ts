/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';
import matter from 'gray-matter';
import { glob } from 'glob';
import crypto from 'crypto';
import process from 'process';

const branch = process.argv[ 2 ] || 'trunk'; // Use 'trunk' as the default branch if no argument is provided

interface Category {
	[ key: string ]: unknown;
}

interface Post {
	[ key: string ]: unknown;
}

function generatePageId( filePath: string, prefix = '' ) {
	const hash = crypto.createHash( 'sha1' );
	hash.update( prefix + filePath );
	return hash.digest( 'hex' );
}

function generateHashOfString( str: string ) {
	return crypto.createHash( 'sha256' ).update( str ).digest( 'hex' );
}

function generateRawGithubFileUrl(
	owner: string,
	repo: string,
	gitBranch: string,
	repoPath: string,
	filePath: string
): string {
	const relativePath = path.relative( repoPath, filePath );
	const githubUrl = `https://raw.githubusercontent.com/${ owner }/${ repo }/${ gitBranch }/${ relativePath }`;
	return githubUrl.replace( /\\/g, '/' ); // Ensure we use forward slashes in the URL.
}
async function processDirectory(
	directory: string,
	projectName: string,
	checkReadme = true
): Promise< Category > {
	let category: Category = {};

	// Process README.md (if exists) for the category definition.
	const readmePath = path.join( directory, 'README.md' );
	if ( checkReadme && fs.existsSync( readmePath ) ) {
		const readmeContent = fs.readFileSync( readmePath, 'utf-8' );
		const readmeFrontmatter = matter( readmeContent ).data;
		category = { ...readmeFrontmatter };
		category.posts = [];
	}

	const markdownFiles = glob.sync( path.join( directory, '*.md' ) );
	markdownFiles.forEach( ( filePath ) => {
		if ( filePath !== readmePath || ! checkReadme ) {
			// Skip README.md which we have already processed.
			const fileContent = fs.readFileSync( filePath, 'utf-8' );
			const fileFrontmatter = matter( fileContent ).data;
			const post: Post = { ...fileFrontmatter };
			// @ts-ignore
			category.posts.push( {
				...post,
				url: generateRawGithubFileUrl(
					'woocommerce',
					'woocommerce',
					branch,
					path.join( __dirname, '../../../' ),
					filePath
				),
				id: generatePageId( filePath, projectName ),
			} );
		}
	} );

	// Recursively process subdirectories.
	category.categories = [];
	const subdirectories = fs
		.readdirSync( directory, { withFileTypes: true } )
		.filter( ( dirent ) => dirent.isDirectory() )
		.map( ( dirent ) => path.join( directory, dirent.name ) );
	for ( const subdirectory of subdirectories ) {
		const subcategory = await processDirectory( subdirectory, projectName );
		//  @ts-ignore
		category.categories.push( subcategory );
	}

	return category;
}

async function processRootDirectory( directory: string, projectName: string ) {
	return processDirectory( directory, projectName, false );
}

// Use the processRootDirectory function.
processRootDirectory( path.join( __dirname, '../example-docs' ), 'test-docs' )
	.then( ( root ) => {
		const rootHash = generateHashOfString( JSON.stringify( root ) );

		// Add the root hash to the root object.
		root.hash = rootHash;

		// write it to a file in this directory
		fs.writeFileSync(
			path.join( __dirname, 'manifest.json' ),
			JSON.stringify( root, null, 2 )
		);
	} )
	.catch( ( err ) => {
		console.error( err );
	} );
