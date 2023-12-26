/**
 * External dependencies
 */
import { existsSync, readFileSync, promises } from 'fs';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { info } from './log';

const { writeFile } = promises;
function getUniqueItems( arr: string[] ) {
	const uniqueObject = arr.reduce( ( unique, item ) => {
		unique[ item ] = true;
		return unique;
	}, {} as Record< string, boolean > );

	return Object.keys( uniqueObject );
}

const getPluginConfig = () => {
	const cwd = join( process.cwd() );

	if ( ! existsSync( join( cwd, '.woo-plugin.json' ) ) ) {
		return {};
	}

	return JSON.parse(
		readFileSync( join( cwd, '.woo-plugin.json' ), 'utf8' )
	);
};

const updateConfig = async ( { modules }: { modules: string[] } ) => {
	const cwd = join( process.cwd() );
	const config = getPluginConfig();

	const uniqueModules = modules.reduce( ( unique, module ) => {
		unique[ module ] = true;
		return unique;
	}, {} as Record< string, boolean > );

	config.modules = Object.keys( uniqueModules );

	info( '' );
	info( 'Updating plugin config file.' );

	await writeFile(
		join( cwd, '.woo-plugin.json' ),
		JSON.stringify( config, null, 4 )
	);
};

export { getPluginConfig, getUniqueItems, updateConfig };
