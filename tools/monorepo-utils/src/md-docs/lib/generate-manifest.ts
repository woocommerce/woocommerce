/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';
import { glob } from 'glob';
import crypto from 'crypto';

/**
 * Internal dependencies
 */
import { generatePostFrontMatter } from './generate-frontmatter';
import { generateFileUrl } from './generate-urls';

export interface Category {
	[ key: string ]: unknown;
	posts?: Post[];
	categories?: Category[];
}

export interface Post {
	[ key: string ]: unknown;
}

export function generatePostId( filePath: string, prefix = '' ) {
	const hash = crypto.createHash( 'sha1' );
	hash.update( `${ prefix }/${ filePath }` );
	return hash.digest( 'hex' );
}

function filenameMatches( filename: string, hayStack: string[] ) {
	const found = hayStack.filter( ( item ) => filename.match( item ) );
	return found.length > 0;
}

async function processDirectory(
	rootDirectory: string,
	subDirectory: string,
	projectName: string,
	baseUrl: string,
	baseEditUrl: string,
	fullPathToDocs: string,
	exclude: string[],
	checkReadme = true
): Promise< Category > {
	const category: Category = {};

	// Process README.md (if exists) for the category definition.
	const readmePath = path.join( subDirectory, 'README.md' );

	if ( checkReadme ) {
		if ( fs.existsSync( readmePath ) ) {
			const readmeContent = fs.readFileSync( readmePath, 'utf-8' );
			const frontMatter = generatePostFrontMatter( readmeContent, true );
			category.content = frontMatter.content;
			category.category_slug = frontMatter.category_slug;
			category.category_title = frontMatter.category_title;
			category.menu_title = frontMatter.menu_title;
		}
		// derive the category title from the directory name, capitalize first letter of each word.
		const categoryFolder = path.basename( subDirectory );
		const categoryTitle = categoryFolder
			.split( '-' )
			.map(
				( slugPart ) =>
					slugPart.charAt( 0 ).toUpperCase() + slugPart.slice( 1 )
			)
			.join( ' ' );
		category.category_slug = category.category_slug ?? categoryFolder;
		category.category_title = category.category_title ?? categoryTitle;
	}

	const markdownFiles = glob
		.sync( path.join( subDirectory, '*.md' ) )
		.filter(
			( markdownFile ) => ! filenameMatches( markdownFile, exclude )
		);
	// If there are markdown files in this directory, add a posts array to the category. Otherwise, assume its a top level category that will contain subcategories.
	if ( markdownFiles.length > 0 ) {
		category.posts = [];
	}

	markdownFiles.forEach( ( filePath ) => {
		if ( filePath !== readmePath || ! checkReadme ) {
			// Skip README.md which we have already processed.
			const fileContent = fs.readFileSync( filePath, 'utf-8' );
			const fileFrontmatter = generatePostFrontMatter( fileContent );

			if ( baseUrl.includes( 'github' ) ) {
				fileFrontmatter.edit_url = generateFileUrl(
					baseEditUrl,
					rootDirectory,
					subDirectory,
					filePath
				);
			}

			const post: Post = { ...fileFrontmatter };

			// Generate hash of the post contents.
			post.hash = crypto
				.createHash( 'sha256' )
				.update( JSON.stringify( fileContent ) )
				.digest( 'hex' );

			// get the folder name of rootDirectory.
			const relativePath = path.relative( fullPathToDocs, filePath );

			category.posts.push( {
				...post,
				url: generateFileUrl(
					baseUrl,
					rootDirectory,
					subDirectory,
					filePath
				),
				filePath,
				id: generatePostId( relativePath, projectName ),
			} );
		}
	} );

	// Recursively process subdirectories.
	category.categories = [];
	const subdirectories = fs
		.readdirSync( subDirectory, { withFileTypes: true } )
		.filter( ( dirent ) => dirent.isDirectory() )
		.filter( ( dirent ) => ! filenameMatches( dirent.name, exclude ) )
		.map( ( dirent ) => path.join( subDirectory, dirent.name ) );
	for ( const subdirectory of subdirectories ) {
		const subcategory = await processDirectory(
			rootDirectory,
			subdirectory,
			projectName,
			baseUrl,
			baseEditUrl,
			fullPathToDocs,
			exclude
		);

		category.categories.push( subcategory );
	}

	return category;
}

export async function generateManifestFromDirectory(
	rootDirectory: string,
	subDirectory: string,
	projectName: string,
	baseUrl: string,
	baseEditUrl: string
) {
	const fullPathToDocs = subDirectory;
	const manifestIgnore = path.join( subDirectory, '.manifestignore' );
	let ignoreList;

	if ( fs.existsSync( manifestIgnore ) ) {
		ignoreList = fs
			.readFileSync( manifestIgnore, 'utf-8' )
			.split( '\n' )
			.map( ( item ) => item.trim() )
			.filter( ( item ) => item.length > 0 )
			.filter( ( item ) => item.substring( 0, 1 ) !== '#' );
	}

	const manifest = await processDirectory(
		rootDirectory,
		subDirectory,
		projectName,
		baseUrl,
		baseEditUrl,
		fullPathToDocs,
		ignoreList ?? [],
		false
	);

	// Generate hash of the manifest contents.
	const hash = crypto
		.createHash( 'sha256' )
		.update( JSON.stringify( manifest ) )
		.digest( 'hex' );

	return { ...manifest, hash };
}
