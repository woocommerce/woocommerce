/**
 * External dependencies
 */
import { cli } from '@woocommerce/e2e-utils';
import path from 'path';

const createPluginFromPHPFile = async (
	phpFilePath: string,
	jsFilePath = ''
) => {
	const absolutePath = path.resolve( phpFilePath );
	const directory = path.dirname( absolutePath );
	const phpFileName = path.basename( phpFilePath );
	const jsFileName = path.basename( jsFilePath );
	const fileNameZip = phpFileName.replace( '.php', '' );
	console.log( phpFileName, jsFileName );
	await cli(
		`cd ${ directory } && zip ${ fileNameZip }.zip ${ phpFileName } ${ jsFileName } && mv ${ fileNameZip }.zip ${ __dirname }`
	);
};

export const installPluginFromPHPFile = async (
	phpFilePath: string,
	jsFilePath?: string
) => {
	await createPluginFromPHPFile( phpFilePath, jsFilePath );
	const fileName = path.basename( phpFilePath ).replace( '.php', '' );
	await cli(
		`npm run wp-env run tests-cli -- wp plugin install /var/www/html/custom-plugins/${ fileName }.zip --activate`
	);
};

export const uninstallPluginFromPHPFile = async ( phpFilePath: string ) => {
	const fileName = path.basename( phpFilePath ).replace( '.php', '' );
	await cli(
		`npm run wp-env run tests-cli -- wp plugin delete ${ fileName }`
	);
	await cli( `rm ${ __dirname }/${ fileName }.zip` );
};
