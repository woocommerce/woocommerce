/**
 * Internal dependencies
 */
import { SchemaDiff } from './git';
import { HookChangeDescription } from './lib/hook-changes';
import { TemplateChangeDescription } from './lib/template-changes';

/**
 * Print template results
 *
 * @param {Map<string, string[]>} data   Raw data.
 * @param {string}                output Output style.
 * @param {string}                title  Section title.
 * @param {Function}              log    print method.
 */
export const printTemplateResults = (
	data: TemplateChangeDescription[],
	output: string,
	title: string,
	log: ( s: string ) => void
): void => {
	if ( output === 'github' ) {
		let opt = '\\n\\n### Template changes:';
		for ( const { filePath, code, message } of data ) {
			opt += `\\n* **File:** ${ filePath }`;
			opt += `\\n  * ${ code.toUpperCase() }: ${ message }`;
			log(
				`::${ code } file=${ filePath },line=1,title=${ title }::${ message }`
			);
		}

		log( `::set-output name=templates::${ opt }` );
	} else {
		log( `\n## ${ title }:` );
		for ( const { filePath, code, message } of data ) {
			log( 'FILE: ' + filePath );
			log( '---------------------------------------------------' );
			log( ` ${ code.toUpperCase() } | ${ title } | ${ message }` );
			log( '---------------------------------------------------' );
		}
	}
};

/**
 * Print hook results
 *
 * @param {Map}      data         Raw data.
 * @param {string}   output       Output style.
 * @param {string}   sectionTitle Section title.
 * @param {Function} log          print method.
 */
export const printHookResults = (
	data: HookChangeDescription[],
	output: string,
	sectionTitle: string,
	log: ( s: string ) => void
) => {
	if ( output === 'github' ) {
		let opt = '\\n\\n### New hooks:';
		for ( const {
			filePath,
			name,
			version,
			description,
			hookType,
			changeType
		} of data ) {
			opt += `\\n* **File:** ${ filePath }`;

			const cliMessage = `**${ name }** introduced in ${ version }`;
			const ghMessage = `\\'${ name }\\' introduced in ${ version }`;
			const message = output === 'github' ? ghMessage : cliMessage;
			const title = `${ changeType } ${ hookType } found`;

			opt += `\\n  * NOTICE - ${ message }: ${ description }`;
			log(
				`::NOTICE file=${ filePath },line=1,title=${ title } - ${ name }::${ message }`
			);
		}

		log( `::set-output name=wphooks::${ opt }` );
	} else {
		log( `\n## ${ sectionTitle }:` );
		log( '---------------------------------------------------' );
		for ( const {
			filePath,
			name,
			version,
			description,
			hookType,
			changeType,
			ghLink,
		} of data ) {
			const cliMessage = `**${ name }** introduced in ${ version }`;
			const ghMessage = `\\'${ name }\\' introduced in ${ version }`;
			const message = output === 'github' ? ghMessage : cliMessage;
			const title = `${ changeType } ${ hookType } found`;

			log( 'FILE: ' + filePath );
			log( '---------------------------------------------------' );
			log( `HOOK: ${ name }: ${ description }` );
			log( '---------------------------------------------------' );
			log( `GITHUB: ${ ghLink }` );
			log( '---------------------------------------------------' );
			log( `NOTICE | ${ title } | ${ message }` );
			log( '---------------------------------------------------\n' );
		}
	}
};

/**
 *  Print Schema change results.
 *
 * @param {Object}   schemaDiffs Schema diff object
 * @param {string}   version     Version change was introduced.
 * @param {string}   output      Output style.
 * @param {Function} log         Print method.
 */
export const printSchemaChange = (
	schemaDiffs: SchemaDiff[],
	version: string,
	output: string,
	log: ( s: string ) => void
) => {
	if ( output === 'github' ) {
		let githubCommentContent = '\\n\\n### New schema changes:';
		schemaDiffs.forEach( ( schemaDiff ) => {
			if ( ! schemaDiff.areEqual ) {
				githubCommentContent += `\\n* **Schema:** ${ schemaDiff.method } introduced in ${ version }`;
			}
		} );

		log( `::set-output name=schema::${ githubCommentContent }` );
	} else {
		log( '\n## SCHEMA CHANGES' );
		log( '---------------------------------------------------' );
		schemaDiffs.forEach( ( schemaDiff ) => {
			if ( ! schemaDiff.areEqual ) {
				log(
					` NOTICE | Schema changes detected in ${ schemaDiff.method } as of ${ version }`
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
