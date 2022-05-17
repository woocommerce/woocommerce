/**
 * Print template results
 *
 * @param {Map<string, string[]>} data   Raw data.
 * @param {string}                output Output style.
 * @param {string}                title  Section title.
 * @param {Function}              log    print method.
 */
export const printTemplateResults = async (
	data: Map< string, string[] >,
	output: string,
	title: string,
	log: ( s: string ) => void
): Promise< void > => {
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
export const printHookResults = async (
	data: Map< string, Map< string, string[] > >,
	output: string,
	title: string,
	log: ( s: string ) => void
): Promise< void > => {
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
