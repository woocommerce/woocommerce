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
	posts?: Post[];
	categories?: Category[];
}

interface Post {
	[ key: string ]: unknown;
}

function generatePageId( filePath: string, prefix = '' ) {
	const hash = crypto.createHash( 'sha1' );
	hash.update( prefix + filePath );
	return hash.digest( 'hex' );
}

/**
 *	Generates a file url relative to the root directory provided.
 *
 * @param baseUrl          The base url to use for the file url.
 * @param rootDirectory    The root directory where the file resides.
 * @param subDirectory     The sub-directory where the file resides.
 * @param absoluteFilePath The absolute path to the file.
 * @return The file url.
 */
export const generateFileUrl = (
	baseUrl: string,
	rootDirectory: string,
	subDirectory: string,
	absoluteFilePath: string
) => {
	// check paths are absolute
	for ( const filePath of [
		rootDirectory,
		subDirectory,
		absoluteFilePath,
	] ) {
		if ( ! path.isAbsolute( filePath ) ) {
			throw new Error(
				`File URLs cannot be generated without absolute paths. ${ filePath } is not absolute.`
			);
		}
	}
	// Generate a path from the subdirectory to the file path.
	const relativeFilePath = path.resolve( subDirectory, absoluteFilePath );

	// Determine the relative path from the rootDirectory to the filePath.
	const relativePath = path.relative( rootDirectory, relativeFilePath );

	return `${ baseUrl }/${ relativePath }`;
};

async function processDirectory(
	rootDirectory: string,
	subDirectory: string,
	projectName: string,
	baseUrl: string,
	checkReadme = true
): Promise< Category > {
	let category: Category = {};

	// Process README.md (if exists) for the category definition.
	const readmePath = path.join( subDirectory, 'README.md' );

	if ( checkReadme && fs.existsSync( readmePath ) ) {
		const readmeContent = fs.readFileSync( readmePath, 'utf-8' );
		const readmeFrontmatter = matter( readmeContent ).data;
		category = { ...readmeFrontmatter };
		category.posts = [];
	}

	const markdownFiles = glob.sync( path.join( subDirectory, '*.md' ) );

	markdownFiles.forEach( ( filePath ) => {
		if ( filePath !== readmePath || ! checkReadme ) {
			// Skip README.md which we have already processed.
			const fileContent = fs.readFileSync( filePath, 'utf-8' );
			const fileFrontmatter = matter( fileContent ).data;
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
			baseUrl
		);

		category.categories.push( subcategory );
	}

	return category;
}

export async function generateManifestFromDirectory(
	rootDirectory: string,
	subDirectory: string,
	projectName: string,
	baseUrl: string
) {
	const manifest = await processDirectory(
		rootDirectory,
		subDirectory,
		projectName,
		baseUrl,
		false
	);

	// Generate hash of the manifest contents.
	const hash = crypto
		.createHash( 'sha256' )
		.update( JSON.stringify( manifest ) )
		.digest( 'hex' );

	return { ...manifest, hash };
}
