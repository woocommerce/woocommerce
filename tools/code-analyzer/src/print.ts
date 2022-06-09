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
		log( '---------------------------------------------------' );
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
			method: string;
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
		let githubCommentContent = '\\n\\n### New schema changes:';
		Object.keys( schemaDiff ).forEach( ( key ) => {
			if ( ! schemaDiff[ key ].areEqual ) {
				githubCommentContent += `\\n* **Schema:** ${ schemaDiff[ key ].method } introduced in ${ version }`;
			}
		} );

		log( `::set-output name=schema::${ githubCommentContent }` );
	} else {
		log( '\n## SCHEMA CHANGES' );
		log( '---------------------------------------------------' );

		Object.keys( schemaDiff ).forEach( ( key ) => {
			if ( ! schemaDiff[ key ].areEqual ) {
				log(
					` NOTICE | Schema changes detected in ${ schemaDiff[ key ].method } as of ${ version }`
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
		const githubCommentContent = `\\n\\n### New database updates:\\n * **${ updateFunctionName }** introduced in ${ updateFunctionVersion }`;
		log( `::set-output name=database::${ githubCommentContent }` );
	} else {
		log( '\n## DATABASE UPDATES' );
		log( '---------------------------------------------------' );
		log(
			` NOTICE | Database update found | ${ updateFunctionName } introduced in ${ updateFunctionVersion }`
		);
		log( '---------------------------------------------------' );
	}
};
