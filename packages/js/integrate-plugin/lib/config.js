/**
 * External dependencies
 */
const { existsSync, readFileSync } = require( 'fs' );
const { join } = require( 'path' );
const { info, error } = require( '../node_modules/@wordpress/create-block/lib/log' );
const { writeFile } = require( 'fs' ).promises;

const getUniqueItems = ( arr ) => {
    const unique = arr.reduce( ( unique, item ) => {
        unique[ item ] = true;
        return unique;
    }, {} );

    return Object.keys( unique );
}

const updateConfig = async ( {
	modules
} ) => {
	const cwd = join( process.cwd() );
    const config = getPluginConfig();

    const uniqueModules = modules.reduce( ( unique, module ) => {
        unique[ module ] = true;
        return unique;
    }, {} );

    config.modules = Object.keys( uniqueModules );
    
    info( '' );
    info(
        'Updating plugin config file.'
    );

    await writeFile(
		join( cwd, '.woo-plugin.json' ),
		JSON.stringify( config, null, 4 ),
	);
};

const getPluginConfig = () => {
	const cwd = join( process.cwd() );

    if ( ! existsSync( join( cwd, '.woo-plugin.json' ) ) ) {
        return {};
    }

    return JSON.parse(
        readFileSync( join( cwd, '.woo-plugin.json' ), 'utf8' )
    );
};

module.exports = {
    getPluginConfig,
	getUniqueItems,
	updateConfig,
};