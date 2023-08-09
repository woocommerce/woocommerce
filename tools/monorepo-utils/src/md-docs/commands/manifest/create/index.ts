/**
 * External dependencies
 */
import { writeFile } from 'fs';
import { Command } from '@commander-js/extra-typings';
import path from 'path';

/**
 * Internal dependencies
 */
import { generateManifestFromDirectory } from '../../../lib/generate-manifest';
import { Logger } from '../../../../core/logger';
import { processMarkdownLinks } from '../../../lib/markdown-links';

export const generateManifestCommand = new Command( 'create' )
	.description(
		'Create a manifest file representing the contents of a markdown directory.'
	)
	.argument(
		'<directory>',
		'Path to directory of Markdown files to generate the manifest from.'
	)
	.argument(
		'<projectName>',
		'Name of the project to generate the manifest for, used to uniquely identify manifest entries.'
	)
	.option(
		'-o --outputFilePath <outputFilePath>',
		'Full path and filename of where to output the manifest.',
		`${ process.cwd() }/manifest.json`
	)
	.option(
		'-b --baseUrl <baseUrl>',
		'Base url to resolve markdown file URLs to in the manifest.',
		'https://raw.githubusercontent.com/woocommerce/woocommerce/trunk'
	)
	.option(
		'-r --rootDir <rootDir>',
		'Root directory of the markdown files, used to generate URLs.',
		process.cwd()
	)
	.option(
		'-be --baseEditUrl <baseEditUrl>',
		'Base url to provide edit links to. This option will be ignored if your baseUrl is not a GitHub URL.',
		'https://github.com/woocommerce/woocommerce/edit/trunk'
	)
	.action( async ( dir, projectName, options ) => {
		const { outputFilePath, baseUrl, rootDir, baseEditUrl } = options;

		// determine if the rootDir is absolute or relative
		const absoluteRootDir = path.isAbsolute( rootDir )
			? rootDir
			: path.join( process.cwd(), rootDir );

		const absoluteSubDir = path.isAbsolute( dir )
			? dir
			: path.join( process.cwd(), dir );

		const absoluteOutputFilePath = path.isAbsolute( outputFilePath )
			? outputFilePath
			: path.join( process.cwd(), outputFilePath );

		Logger.startTask( 'Generating manifest' );

		const manifest = await generateManifestFromDirectory(
			absoluteRootDir,
			absoluteSubDir,
			projectName,
			baseUrl,
			baseEditUrl
		);

		const manifestWithLinks = await processMarkdownLinks(
			manifest,
			absoluteRootDir,
			absoluteSubDir,
			projectName
		);

		Logger.endTask();

		Logger.startTask( 'Writing manifest' );

		await writeFile(
			absoluteOutputFilePath,
			JSON.stringify( manifestWithLinks, null, 2 ),
			( err ) => {
				if ( err ) {
					Logger.error( err );
				}
			}
		);

		Logger.endTask();
		Logger.notice( `Manifest output at ${ outputFilePath }` );
	} );
