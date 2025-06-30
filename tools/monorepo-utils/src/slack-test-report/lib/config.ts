/**
 * External dependencies
 */
import fs from 'fs';
import { makeRe } from 'minimatch';
import path from 'path';

/**
 * Internal dependencies
 */
import { Logger } from '../../core/logger';

interface Route {
	channels: string[];
	checkType?: string;
	refName?: string;
	excludeDefaultChannel?: boolean;
}

export interface ReporterConfig {
	defaultChannel: string;
	routes: Route[];
}

/**
 * Loads and parses a config file
 *
 * @param {string} configPath - Path to the config file
 * @return {ReporterConfig} The parsed and validated config object
 * @throws {Error} If the config file cannot be read or is invalid
 */
export function loadConfig( configPath: string ): any {
	let rawData: string;
	try {
		rawData = fs.readFileSync( path.resolve( configPath ), 'utf8' );
	} catch ( error ) {
		throw new Error( `Failed to read config file: ${ error.message }` );
	}

	let parsedData: any;
	try {
		parsedData = JSON.parse( rawData );
	} catch ( error ) {
		throw new Error( `Failed to parse config file: ${ error.message }` );
	}

	Logger.notice( `Loaded config from ${ configPath }` );
	return parsedData;
}

/**
 * Parses a config object and validates it against the expected schema
 *
 * @param {string} rawConfig - raw config data
 * @return {ReporterConfig} The parsed and validated config object
 * @throws {Error} If the config file cannot be read or is invalid
 */
export function parseConfig( rawConfig: unknown ): ReporterConfig {
	if ( ! rawConfig || typeof rawConfig !== 'object' ) {
		throw new Error(
			`Failed to parse config file: config needs to be an Object`
		);
	}

	const parsedConfig = rawConfig as ReporterConfig;

	if (
		! parsedConfig.defaultChannel ||
		typeof parsedConfig.defaultChannel !== 'string'
	) {
		throw new Error(
			'Failed to parse config file: defaultChannel must be a non-empty string'
		);
	}

	if ( ! parsedConfig.routes ) {
		return { defaultChannel: parsedConfig.defaultChannel, routes: [] };
	}

	if ( ! Array.isArray( parsedConfig.routes ) ) {
		throw new Error(
			'Failed to parse config file: routes must be an array'
		);
	}

	for ( const route of parsedConfig.routes ) {
		if ( typeof route !== 'object' ) {
			throw new Error(
				`Failed to parse config file: route needs to be an Object`
			);
		}

		if (
			! route.channels ||
			! Array.isArray( route.channels ) ||
			! route.channels.every( ( channel ) => typeof channel === 'string' )
		) {
			throw new Error(
				'Failed to parse config file: channels must be an array of strings'
			);
		}

		if (
			( ! route.checkType || typeof route.checkType !== 'string' ) &&
			( ! route.refName || typeof route.refName !== 'string' )
		) {
			throw new Error(
				'Failed to parse config file: route must have at least one of checkType or refName as a non-empty string'
			);
		}

		if (
			'excludeDefaultChannel' in route &&
			typeof route.excludeDefaultChannel !== 'boolean'
		) {
			throw new Error(
				'Failed to parse config file: excludeDefaultChannel must be a boolean when present'
			);
		}

		// Set excludeDefaultChannel to false if not present
		if ( ! ( 'excludeDefaultChannel' in route ) ) {
			route.excludeDefaultChannel = false;
		}
	}

	return parsedConfig;
}

/**
 * Get channels for a specific ref or check name from the config
 *
 * @param {ReporterConfig} config    - The parsed config object
 * @param {string}         refName   - The name of the ref
 * @param {string}         checkName - The name of the check
 * @return {string[]} Array of channel IDs where the notification should be sent
 */
export function getConfiguredChannels(
	config: ReporterConfig | undefined,
	refName: string,
	checkName: string
): string[] {
	if ( ! config ) {
		throw new Error( 'Config must be provided to get configured channels' );
	}

	const channels = new Set< string >();

	for ( const route of config.routes ) {
		const refRegex = route.refName ? makeRe( route.refName ) : null;
		const checkRegex = route.checkType ? makeRe( route.checkType ) : null;

		const matchesRef =
			'refName' in route &&
			route.refName &&
			refRegex &&
			refRegex.test( refName );

		const matchesCheck =
			'checkType' in route &&
			route.checkType &&
			checkRegex &&
			checkRegex.test( checkName );

		if ( matchesRef || matchesCheck ) {
			route.channels.forEach( ( channel ) => channels.add( channel ) );
			if ( ! route.excludeDefaultChannel ) {
				channels.add( config.defaultChannel );
			}
		}
	}

	if ( channels.size === 0 ) {
		Logger.notice(
			`Found no channels configured for refName: ${ refName }, checkName: ${ checkName }`
		);
		Logger.notice( 'Using default channel' );
		channels.add( config.defaultChannel );
	}

	Logger.notice(
		`Returning ${ channels.size } channel(s) for refName: ${ refName }, checkName: ${ checkName }`
	);
	return Array.from( channels );
}

/**
 * Resolves an array of channel environment variable names to their actual values.
 *
 * @param  channelVars - Array of environment variable names that contain channel IDs
 * @return Array of resolved channel IDs
 * @throws {Error} If any of the environment variables are not defined
 */
export function resolveChannels( channelVars: string[] ): string[] {
	const undefinedVars = channelVars.filter(
		( varName ) => ! ( varName in process.env )
	);

	if ( undefinedVars.length > 0 ) {
		throw new Error(
			`Missing required environment variables: ${ undefinedVars.join(
				', '
			) }`
		);
	}

	return channelVars.map( ( varName ) => process.env[ varName ] as string );
}
