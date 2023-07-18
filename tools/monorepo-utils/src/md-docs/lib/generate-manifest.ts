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

interface Post {
	[ key: string ]: unknown;
}

export function generatePageId( filePath: string, prefix = '' ) {
	const hash = crypto.createHash( 'sha1' );
	hash.update( prefix + filePath );
	return hash.digest( 'hex' );
}

async function processDirectory(
	rootDirectory: string,
	subDirectory: string,
	projectName: string,
	baseUrl: string,
	baseEditUrl: string,
	checkReadme = true
): Promise< Category > {
	const category: Category = {};

	// Process README.md (if exists) for the category definition.
	const readmePath = path.join( subDirectory, 'README.md' );

	if ( checkReadme && fs.existsSync( readmePath ) ) {
		const readmeContent = fs.readFileSync( readmePath, 'utf-8' );
		const frontMatter = generatePostFrontMatter( readmeContent );
		Object.assign( category, frontMatter );
	} else if ( checkReadme ) {
		// derive the category title from the directory name, capitalize first letter
		const categoryTitle = path.basename( subDirectory );
		category.category_title =
			categoryTitle.charAt( 0 ).toUpperCase() + categoryTitle.slice( 1 );
	}

	const markdownFiles = glob.sync( path.join( subDirectory, '*.md' ) );

	markdownFiles.forEach( ( filePath ) => {
		if ( filePath !== readmePath || ! checkReadme ) {
			// Skip README.md which we have already processed.
			const fileContent = fs.readFileSync( filePath, 'utf-8' );
			const fileFrontmatter = generatePostFrontMatter( fileContent );
			category.posts = [];

			if ( baseUrl.includes( 'github' ) ) {
				fileFrontmatter.edit_url = generateFileUrl(
					baseEditUrl,
					rootDirectory,
					subDirectory,
					filePath
				);
			}

			const post: Post = { ...fileFrontmatter };

			category.posts.push( {
				...post,
				url: generateFileUrl(
					baseUrl,
					rootDirectory,
					subDirectory,
					filePath
				),
				id: generatePageId( filePath, projectName ),
			} );
		}
	} );

	// Recursively process subdirectories.
	category.categories = [];
	const subdirectories = fs
		.readdirSync( subDirectory, { withFileTypes: true } )
		.filter( ( dirent ) => dirent.isDirectory() )
		.map( ( dirent ) => path.join( subDirectory, dirent.name ) );
	for ( const subdirectory of subdirectories ) {
		const subcategory = await processDirectory(
			rootDirectory,
			subdirectory,
			projectName,
			baseUrl,
			baseEditUrl
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
	const manifest = await processDirectory(
		rootDirectory,
		subDirectory,
		projectName,
		baseUrl,
		baseEditUrl,
		false
	);

	// Generate hash of the manifest contents.
	const hash = crypto
		.createHash( 'sha256' )
		.update( JSON.stringify( manifest ) )
		.digest( 'hex' );

	return { ...manifest, hash };
}
