/**
 * External dependencies
 */
import { RawHTML, useMemo } from '@wordpress/element';
import { WordCountType } from '@woocommerce/block-settings';
import { sanitizeHTML } from '@woocommerce/sanitize';
import type { CSSProperties, ReactElement } from 'react';

/**
 * Internal dependencies
 */
import { generateSummary } from './utils';

export interface SummaryProps {
	className?: string;
	source: string;
	maxLength?: number;
	countType?: WordCountType;
	style?: CSSProperties;
}

const allowedTags = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'ul',
	'ol',
	'li',
	'h1',
	'h2',
	'h3',
	'h4',
	'h5',
	'h6',
	'pre',
	'blockquote',
	'img',
];

const allowedAttributes = [
	'target',
	'href',
	'rel',
	'name',
	'download',
	'src',
	'class',
	'alt',
	'style',
];

const allowedAttributeSet = new Set( allowedAttributes );

// Re-check parsed attributes before RawHTML renders the summary, because malformed product HTML can survive string sanitization with event handlers attached.
const enforceAllowedAttributes = ( html: string ) => {
	const element = document.createElement( 'div' );

	element.innerHTML = html;
	element.querySelectorAll( '*' ).forEach( ( node ) => {
		Array.from( node.attributes ).forEach( ( attribute ) => {
			if ( ! allowedAttributeSet.has( attribute.name.toLowerCase() ) ) {
				node.removeAttribute( attribute.name );
			}
		} );
	} );

	return element.innerHTML;
};

/**
 * Summary component.
 *
 * @param {Object}        props           Component props.
 * @param {string}        props.source    Source text.
 * @param {number}        props.maxLength Max length of the summary, using countType.
 * @param {string}        props.countType One of words, characters_excluding_spaces, or characters_including_spaces.
 * @param {string}        props.className Class name for rendered component.
 * @param {CSSProperties} props.style     Style Object for rendered component.
 */
export const Summary = ( {
	source,
	maxLength = 15,
	countType = 'words',
	className = '',
	style = {},
}: SummaryProps ): ReactElement => {
	const summaryText = useMemo( () => {
		return generateSummary( source, maxLength, countType );
	}, [ source, maxLength, countType ] );
	const sanitizedSummary = useMemo( () => {
		return enforceAllowedAttributes(
			sanitizeHTML( summaryText, {
				tags: allowedTags,
				attr: allowedAttributes,
			} )
		);
	}, [ summaryText ] );

	return (
		<RawHTML style={ style } className={ className }>
			{ sanitizedSummary }
		</RawHTML>
	);
};

export default Summary;
