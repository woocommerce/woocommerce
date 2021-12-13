/**
 * External Dependencies
 */
const config = require( 'config' );

/**
 * Since the 'config' object is not extensible, we create an empty 'e2eConfig' object and map
 * its prototype to the 'config' object. This allows us to extend the new 'e2eConfig' object
 * while still having access to all the data and methods of 'config'
 */
const e2eConfig = Object.create( config );

/**
 *
 * @param property { string } - the property to be fetched from the config file
 * @param defaultValue { string | number | boolean | null } - default value if 'property' is not found
 * @returns { string | number | boolean }
 * @throws Error
 */
e2eConfig.get = ( property, defaultValue = null ) => {
	if ( config.has( property ) ) {
		return config.get( property );
	} else if ( defaultValue ) {
		return defaultValue;
	}

	throw new Error(
		`Configuration property "${ property }" is not defined and no defaultValue has been provided`
	);
};

module.exports = e2eConfig;
