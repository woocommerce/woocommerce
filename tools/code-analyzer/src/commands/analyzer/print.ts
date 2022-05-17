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
