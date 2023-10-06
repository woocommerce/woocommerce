/**
 * External dependencies
 */
import { join, resolve } from 'path';
import glob from 'fast-glob';
import { mkdtemp, readFile } from 'fs/promises';
import { command } from 'execa';
import CLIError from '@wordpress/create-block/lib/cli-error';
import { existsSync } from 'fs';
import { tmpdir } from 'os';
import npmPackageArg from 'npm-package-arg';
import { rimrafSync } from 'rimraf';

/**
 * Internal dependencies
 */
import {
	ComposerJSONData,
	PluginConfig,
	PluginTemplate,
	PluginTemplateDefaults,
	TemplateVariant,
} from './types';
import { info } from './log';

const predefinedPluginTemplates: Record< string, PluginTemplate > = {
	es5: {
		defaultValues: {
			slug: 'example-static-es5',
			title: 'Example Static (ES5)',
			description:
				'Example block scaffolded with Create Block tool – no build step required.',
			dashicon: 'smiley',
			supports: {
				html: false,
			},
			wpScripts: false,
			editorScript: null,
			editorStyle: null,
			style: null,
			viewScript: 'file:./view.js',
			example: {},
		},
		templatesPath: join( __dirname, 'templates', 'es5' ),
		variants: {
			static: {},
			dynamic: {
				slug: 'example-dynamic-es5',
				title: 'Example Dynamic (ES5)',
				render: 'file:./render.php',
			},
		},
	},
	standard: {
		defaultValues: {
			slug: 'example-static',
			title: 'Example Static',
			description: 'Example block scaffolded with Create Block tool.',
			dashicon: 'smiley',
			supports: {
				html: false,
			},
			viewScript: 'file:./view.js',
			example: {},
		},
		variants: {
			static: {},
			dynamic: {
				slug: 'example-dynamic',
				title: 'Example Dynamic',
				render: 'file:./render.php',
			},
		},
	},
};

const getOutputTemplates = async ( outputTemplatesPath: string ) => {
	const outputTemplatesFiles = await glob( '**/*.mustache', {
		cwd: outputTemplatesPath,
		dot: true,
	} );
	return Object.fromEntries(
		await Promise.all(
			outputTemplatesFiles.map( async ( outputTemplateFile ) => {
				const outputFile = outputTemplateFile.replace(
					/\.mustache$/,
					''
				);
				const outputTemplate = await readFile(
					join( outputTemplatesPath, outputTemplateFile ),
					'utf8'
				);
				return [ outputFile, outputTemplate ];
			} )
		)
	);
};

const getOutputAssets = async ( outputAssetsPath: string ) => {
	const outputAssetFiles = await glob( '**/*', {
		cwd: outputAssetsPath,
		dot: true,
	} );
	return Object.fromEntries(
		await Promise.all(
			outputAssetFiles.map( async ( outputAssetFile ) => {
				const outputAsset = await readFile(
					join( outputAssetsPath, outputAssetFile )
				);
				return [ outputAssetFile, outputAsset ];
			} )
		)
	);
};

const externalTemplateExists = async ( templateName: string ) => {
	try {
		await command( `npm view ${ templateName }` );
	} catch ( error ) {
		return false;
	}
	return true;
};

const configToTemplate = async ( {
	pluginTemplatesPath,
	blockTemplatesPath,
	includesTemplatesPath,
	srcTemplatesPath,
	assetsPath,
	defaultValues,
	variants = {},
	modules = [],
	onComplete,
	composerDependencies = [],
	composerDevDependencies = [],
}: Partial< ComposerJSONData > & Partial< PluginConfig > & PluginTemplate ) => {
	if ( defaultValues === null || typeof defaultValues !== 'object' ) {
		throw new CLIError( 'Template found but invalid definition provided.' );
	}

	pluginTemplatesPath =
		pluginTemplatesPath || join( __dirname, 'templates', 'plugin' );
	blockTemplatesPath =
		blockTemplatesPath || join( __dirname, 'templates', 'block' );
	includesTemplatesPath =
		includesTemplatesPath || join( __dirname, 'templates', 'includes' );
	srcTemplatesPath =
		srcTemplatesPath || join( __dirname, 'templates', 'src' );

	return {
		blockOutputTemplates: blockTemplatesPath
			? await getOutputTemplates( blockTemplatesPath )
			: {},
		pluginOutputTemplates: await getOutputTemplates( pluginTemplatesPath ),
		includesOutputTemplates: await getOutputTemplates(
			includesTemplatesPath
		),
		srcOutputTemplates: await getOutputTemplates( srcTemplatesPath ),
		outputAssets: assetsPath ? await getOutputAssets( assetsPath ) : {},
		defaultValues,
		variants,
		modules,
		onComplete,
		composerDependencies,
		composerDevDependencies,
	};
};

const getPluginTemplate = async (
	templateName: keyof typeof predefinedPluginTemplates
) => {
	if ( predefinedPluginTemplates[ templateName ] ) {
		return await configToTemplate(
			predefinedPluginTemplates[ templateName ]
		);
	}

	try {
		if ( existsSync( resolve( templateName ) ) ) {
			// eslint-disable-next-line @typescript-eslint/no-var-requires
			return await configToTemplate( require( resolve( templateName ) ) );
		}
		// eslint-disable-next-line @typescript-eslint/no-var-requires
		return await configToTemplate( require( templateName ) );
	} catch ( error ) {
		if ( error instanceof CLIError ) {
			throw error;
		} else if (
			( error as { code: string; message: string } ).code !==
			'MODULE_NOT_FOUND'
		) {
			throw new CLIError(
				`Invalid block template loaded. Error: ${
					( error as { code: string; message: string } ).message
				}`
			);
		}
	}

	if ( ! ( await externalTemplateExists( templateName ) ) ) {
		throw new CLIError(
			`Invalid plugin template type name: "${ templateName }". Allowed values: ` +
				Object.keys( predefinedPluginTemplates )
					.map( ( name ) => `"${ name }"` )
					.join( ', ' ) +
				', or an existing npm package name.'
		);
	}

	let tempCwd;

	try {
		info( '' );
		info( 'Downloading template files. It might take some time...' );

		tempCwd = await mkdtemp( join( tmpdir(), 'wp-create-block-' ) );

		await command( `npm install ${ templateName } --no-save`, {
			cwd: tempCwd,
		} );

		const { name } = npmPackageArg( templateName );
		if ( name ) {
			return await configToTemplate(
				// eslint-disable-next-line @typescript-eslint/no-var-requires
				require( require.resolve( name, {
					paths: [ tempCwd ],
				} ) )
			);
		}
	} catch ( error ) {
		if ( error instanceof CLIError ) {
			throw error;
		} else {
			throw new CLIError(
				`Invalid plugin template downloaded. Error: ${
					( error as { message: string } ).message
				}`
			);
		}
	} finally {
		if ( tempCwd ) {
			rimrafSync( tempCwd );
		}
	}
};

const getVariantVars = (
	variants: Record< string, TemplateVariant | Record< string, void > >,
	variant?: string
) => {
	const variantVars: Record< string, boolean > = {};
	const variantNames = Object.keys( variants );
	if ( variantNames.length === 0 ) {
		return variantVars;
	}

	const currentVariant = variant ?? variantNames[ 0 ];
	for ( const variantName of variantNames ) {
		const key =
			variantName.charAt( 0 ).toUpperCase() + variantName.slice( 1 );
		variantVars[ `is${ key }Variant` ] =
			currentVariant === variantName ?? false;
	}

	return variantVars;
};

function getDefaultValues(
	pluginTemplate: PluginTemplate,
	variant?: string
): PluginTemplateDefaults {
	const templateVariant =
		variant && pluginTemplate ? pluginTemplate.variants?.[ variant ] : {};
	return {
		$schema: 'https://schemas.wp.org/trunk/block.json',
		apiVersion: 3,
		namespace: 'create-block',
		category: 'widgets',
		author: 'The WordPress Contributors',
		license: 'GPL-2.0-or-later',
		licenseURI: 'https://www.gnu.org/licenses/gpl-2.0.html',
		version: '0.1.0',
		wpScripts: true,
		customScripts: {},
		wpEnv: false,
		npmDependencies: [],
		// folderName: './src',
		editorScript: 'file:./index.js',
		editorStyle: 'file:./index.css',
		style: 'file:./style-index.css',
		...pluginTemplate.defaultValues,
		...templateVariant,
		variantVars: getVariantVars( pluginTemplate?.variants || {}, variant ),
		includesDir: 'includes/',
		srcDir: 'src/',
		modules: [],
	};
}

export { getPluginTemplate, getDefaultValues };
