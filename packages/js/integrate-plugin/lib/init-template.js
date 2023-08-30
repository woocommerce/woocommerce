/**
 * External dependencies
 */
const { join } = require( 'path' );
const { info } = require( '../node_modules/@wordpress/create-block/lib/log' );
const { writeOutputTemplate } = require( '../node_modules/@wordpress/create-block/lib/output' );

module.exports = async ( view ) => {
    const {
        includesOutputTemplates,
        includesDir,
        srcOutputTemplates,
        srcDir,
    } = view;
	const cwd = join( process.cwd() );

	info( '' );
	info( 'Creating plugin files from template "package.json".' );

    await Promise.all(
        Object.keys( includesOutputTemplates ).map(
            async ( outputFile ) => 
                await writeOutputTemplate(
                    includesOutputTemplates[ outputFile ],
                    join( includesDir, outputFile ),
                    view
                )
        )
    );

    await Promise.all(
        Object.keys( srcOutputTemplates ).map(
            async ( outputFile ) => 
                await writeOutputTemplate(
                    srcOutputTemplates[ outputFile ],
                    path.join( srcDir, outputFile ),
                    view
                )
        )
    );
};
