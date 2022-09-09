/**
 * External dependencies
 */
import ejs from 'ejs';
import { join } from 'path';

const TEMPLATE_DIR = join( __dirname, '..', 'templates' );

export const renderTemplate = (
	templateFile: string,
	templateData: unknown
) => {
	return new Promise< string >( ( resolve, reject ) => {
		ejs.renderFile(
			join( TEMPLATE_DIR, templateFile ),
			templateData,
			{},
			function ( err: Error, str: string ) {
				if ( err ) {
					reject( err );
				} else {
					resolve( str );
				}
			}
		);
	} );
};
