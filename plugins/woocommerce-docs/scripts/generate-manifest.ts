/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';
import matter from 'gray-matter';
import { glob } from 'glob';
import crypto from 'crypto';

interface Category {
	[ key: string ]: unknown;
}

interface Page {
	[ key: string ]: unknown;
}

function generatePageId( filePath: string, prefix = '' ) {
	const hash = crypto.createHash( 'sha1' );
	hash.update( prefix + filePath );
	return hash.digest( 'hex' );
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
	}

	// Process markdown files in the directory.
	category.pages = [];
	const markdownFiles = glob.sync( path.join( directory, '*.md' ) );
	markdownFiles.forEach( ( filePath ) => {
		if ( filePath !== readmePath || ! checkReadme ) {
			// Skip README.md which we have already processed.
			const fileContent = fs.readFileSync( filePath, 'utf-8' );
			const fileFrontmatter = matter( fileContent ).data;
			const page: Page = { ...fileFrontmatter };
			// @ts-ignore
			category.pages.push( {
				...page,
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
	// Call processDirectory with false for checkReadme for the top-level directory.
	return processDirectory( directory, projectName, false );
}

// Use the processRootDirectory function.
processRootDirectory( path.join( __dirname, '../example-docs' ), 'test-docs' )
	.then( ( root ) => {
		console.log( JSON.stringify( root, null, 2 ) );
	} )
	.catch( ( err ) => {
		console.error( err );
	} );
