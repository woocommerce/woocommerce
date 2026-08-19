'use strict';

/**
 * Convert the small subset of HTML the hook generator emits (via Parsedown)
 * into plain markdown, so the generated docs contain no inline HTML.
 *
 * Handled: <p>, <ul>/<li>, <pre><code>, inline <code>, <a>, <strong>/<b>,
 * <em>/<i>, <br>. Any other leftover <tag> is rendered as inline code, which
 * covers literal placeholders like `<hook-name>` used in docblock prose.
 */

const decodeEntities = ( text ) =>
	text
		.replace( /&lt;/g, '<' )
		.replace( /&gt;/g, '>' )
		.replace( /&quot;/g, '"' )
		.replace( /&#0?39;/g, "'" )
		.replace( /&amp;/g, '&' );

const convertInline = ( text ) =>
	text
		.replace(
			/<code[^>]*>([\s\S]*?)<\/code>/g,
			( _, code ) => `\`${ decodeEntities( code ) }\``
		)
		.replace( /<a\s+href="([^"]*)"[^>]*>([\s\S]*?)<\/a>/g, '[$2]($1)' )
		.replace( /<\/?(strong|b)>/g, '**' )
		.replace( /<\/?(em|i)>/g, '*' )
		.replace( /<br\s*\/?>/g, '\n' );

const htmlToMarkdown = ( html ) => {
	if ( ! html ) {
		return html;
	}

	const blocks = [];

	let markdown = html
		// Fenced code blocks first, so nothing inside them is rewritten.
		.replace(
			/<pre><code(?:\s+class="language-([^"]*)")?>([\s\S]*?)<\/code><\/pre>/g,
			( _, language, code ) => {
				blocks.push(
					`\`\`\`${ language || '' }\n${ decodeEntities(
						code
					).trim() }\n\`\`\``
				);
				return `@@CODE_BLOCK_${ blocks.length - 1 }@@`;
			}
		)
		.replace( /<ul[^>]*>([\s\S]*?)<\/ul>/g, ( _, items ) => {
			const list = [ ...items.matchAll( /<li[^>]*>([\s\S]*?)<\/li>/g ) ]
				.map( ( m ) => `- ${ m[ 1 ].trim() }` )
				.join( '\n' );
			return `\n\n${ list }\n\n`;
		} )
		.replace( /<p[^>]*>([\s\S]*?)<\/p>/g, '\n\n$1\n\n' );

	markdown = convertInline( markdown );

	// Decode entities BEFORE the catch-all below, so entity-escaped tags
	// (e.g. `&lt;div&gt;`) get neutralized too instead of landing in the
	// output as raw HTML.
	markdown = decodeEntities( markdown );

	// Whatever still looks like a tag is a literal placeholder — show it as code.
	markdown = markdown.replace( /<([^<>\s][^<>]*)>/g, '`<$1>`' );

	// Restore fenced code blocks.
	markdown = markdown.replace(
		/@@CODE_BLOCK_(\d+)@@/g,
		( _, i ) => blocks[ Number( i ) ]
	);

	return (
		markdown
			// Spaces between the original HTML tags leave indented or
			// whitespace-only lines behind; clean them up.
			.replace( /^[ \t]+(```)/gm, '$1' )
			.replace( /^[ \t]+$/gm, '' )
			.replace( /\n{3,}/g, '\n\n' )
			.trim()
	);
};

module.exports = { htmlToMarkdown };
