/**
 * External dependencies
 */
import { RawHTML, useMemo } from '@wordpress/element';
import { WordCountType } from '@woocommerce/block-settings';
import { sanitizeHTML } from '@woocommerce/sanitize';
import type { CSSProperties } from 'react';

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

/**
 * Summary component.
 *
 * @param {Object}        props           Component props.
 * @param {string}        props.source    Source text.
 * @param {number}        props.maxLength Max length of the summary, using countType.
 * @param {string}        props.countType One of words, characters_excluding_spaces, or characters_including_spaces.
 * @param {string}        props.className Class name for rendered component.
 * @param {CSSProperties} props.style     Style Object for rendered component.
 *
 */
export const Summary = ( {
	source,
	maxLength = 15,
	countType = 'words',
	className = '',
	style = {},
}: SummaryProps ): JSX.Element => {
	const summaryText = useMemo( () => {
		return generateSummary( source, maxLength, countType );
	}, [ source, maxLength, countType ] );

	return (
		<RawHTML style={ style } className={ className }>
			{ sanitizeHTML( summaryText, {
				tags: allowedTags,
				attr: allowedAttributes,
			} ) }
		</RawHTML>
	);
};

export default Summary;
