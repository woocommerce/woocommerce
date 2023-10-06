/**
 * External dependencies
 */
import { join } from 'path';
import { writeOutputTemplate } from '@wordpress/create-block/lib/output';

/**
 * Internal dependencies
 */
import { PluginTemplate } from './types';
import { info } from './log';

export async function initTemplates( view: PluginTemplate ) {
	const {
		includesOutputTemplates,
		pluginOutputTemplates,
		srcOutputTemplates,
		defaultValues,
	} = view;

	info( '' );
	info( 'Creating plugin files from template "package.json".' );

	const { includesDir, srcDir, slug } = defaultValues;
	if ( includesOutputTemplates && includesDir ) {
		await Promise.all(
			Object.keys( includesOutputTemplates ).map(
				async ( outputFile ) =>
					await writeOutputTemplate(
						includesOutputTemplates[ outputFile ],
						join( includesDir, outputFile ),
						{
							slug,
						}
					)
			)
		);
	}

	if ( srcDir && srcOutputTemplates ) {
		await Promise.all(
			Object.keys( srcOutputTemplates ).map(
				async ( outputFile ) =>
					await writeOutputTemplate(
						srcOutputTemplates[ outputFile ],
						join( srcDir, outputFile ),
						{
							slug,
						}
					)
			)
		);
	}

	if ( pluginOutputTemplates && defaultValues?.slug ) {
		await Promise.all(
			Object.keys( pluginOutputTemplates ).map(
				async ( outputFile ) =>
					await writeOutputTemplate(
						pluginOutputTemplates[ outputFile ],
						outputFile,
						{
							slug: defaultValues.slug,
						}
					)
			)
		);
	}
}
