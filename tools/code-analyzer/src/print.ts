/**
 * Print template results
 *
 * @param {Map<string, string[]>} data   Raw data.
 * @param {string}                output Output style.
 * @param {string}                title  Section title.
 * @param {Function}              log    print method.
 */
export const printTemplateResults = (
	data: Map< string, string[] >,
	output: string,
	title: string,
	log: ( s: string ) => void
): void => {
	if ( output === 'github' ) {
		let opt = '\\n\\n### Template changes:';
		for ( const [ key, value ] of data ) {
			opt += `\\n* **file:** ${ key }`;
			opt += `\\n  * ${ value[ 0 ].toUpperCase() }: ${ value[ 2 ] }`;
			log(
				`::${ value[ 0 ] } file=${ key },line=1,title=${ value[ 1 ] }::${ value[ 2 ] }`
			);
		}

		log( `::set-output name=templates::${ opt }` );
	} else {
		log( `\n## ${ title }:` );
		for ( const [ key, value ] of data ) {
			log( 'FILE: ' + key );
			log( '---------------------------------------------------' );
			log(
				` ${ value[ 0 ].toUpperCase() } | ${ value[ 1 ] } | ${
					value[ 2 ]
				}`
			);
			log( '---------------------------------------------------' );
		}
	}
};

/**
 * Print hook results
 *
 * @param {Map}      data   Raw data.
 * @param {string}   output Output style.
 * @param {string}   title  Section title.
 * @param {Function} log    print method.
 */
export const printHookResults = (
	data: Map< string, Map< string, string[] > >,
	output: string,
	title: string,
	log: ( s: string ) => void
): void => {
	if ( output === 'github' ) {
		let opt = '\\n\\n### New hooks:';
		for ( const [ key, value ] of data ) {
			if ( value.size ) {
				opt += `\\n* **file:** ${ key }`;
				for ( const [ k, v ] of value ) {
					opt += `\\n  * ${ v[ 0 ].toUpperCase() }: ${ v[ 2 ] }`;
					log(
						`::${ v[ 0 ] } file=${ key },line=1,title=${ v[ 1 ] } - ${ k }::${ v[ 2 ] }`
					);
				}
			}
		}

		log( `::set-output name=wphooks::${ opt }` );
	} else {
		log( `\n## ${ title }:` );
		for ( const [ key, value ] of data ) {
			if ( value.size ) {
				log( 'FILE: ' + key );
				log( '---------------------------------------------------' );
				for ( const [ k, v ] of value ) {
					log( 'HOOK: ' + k );
					log(
						'---------------------------------------------------'
					);
					log(
						` ${ v[ 0 ].toUpperCase() } | ${ v[ 1 ] } | ${ v[ 2 ] }`
					);
					log(
						'---------------------------------------------------'
					);
				}
			}
		}
	}
};

/**
 *  Print Schema change results.
 *
 * @param {Object}   schemaDiff Schema diff object
 * @param {string}   version    Version change was introduced.
 * @param {string}   output     Output style.
 * @param {Function} log        Print method.
 */
export const printSchemaChange = (
	schemaDiff: {
		[ key: string ]: {
			description: string;
			base: string;
			compare: string;
			areEqual: boolean;
		};
	} | void,
	version: string,
	output: string,
	log: ( s: string ) => void
): void => {
	if ( ! schemaDiff ) {
		return;
	}
	if ( output === 'github' ) {
		// Add Github output here.
	} else {
		log( '\n## SCHEMA CHANGES' );
		log( '---------------------------------------------------' );

		Object.keys( schemaDiff ).forEach( ( key ) => {
			if ( ! schemaDiff[ key ].areEqual ) {
				log(
					` NOTICE | Schema changes detected in ${ schemaDiff[ key ].description } as of ${ version }`
				);
				log( '---------------------------------------------------' );
			}
		} );
	}
};

/**
 *
 * @param {Object}   databaseUpdates                       Database update info.
 * @param {string}   databaseUpdates.updateFunctionName    Database upodate function name.
 * @param {string}   databaseUpdates.updateFunctionVersion Database update version.
 * @param {string}   output                                Output style.
 * @param {Function} log                                   Print method.
 */
export const printDatabaseUpdates = (
	{
		updateFunctionName,
		updateFunctionVersion,
	}: { updateFunctionName: string; updateFunctionVersion: string },
	output: string,
	log: ( s: string ) => void
): void => {
	if ( output === 'github' ) {
		// Add Github output here.
	} else {
		log( '\n## DATABASE UPDATES' );
		log( '---------------------------------------------------' );
		log(
			` NOTICE | Database update found | ${ updateFunctionName } introduced in ${ updateFunctionVersion }`
		);
		log( '---------------------------------------------------' );
	}
};
