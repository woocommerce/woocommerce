/**
 * External dependencies
 */
import { RawHTML, useMemo } from '@wordpress/element';
import { WordCountType } from '@woocommerce/block-settings';
import type { CSSProperties } from 'react';

/**
 * Internal dependencies
 */
import { generateSummary } from './utils';

interface SummaryProps {
	className?: string;
	source: string;
	maxLength?: number;
	countType?: WordCountType;
	style?: CSSProperties;
	truncateToFirstParagraph?: boolean;
}
/**
 * Summary component.
 *
 * @param {Object}        props                          Component props.
 * @param {string}        props.source                   Source text.
 * @param {number}        props.maxLength                Max length of the summary, using countType.
 * @param {string}        props.countType                One of words, characters_excluding_spaces, or characters_including_spaces.
 * @param {string}        props.className                Class name for rendered component.
 * @param {CSSProperties} props.style                    Style Object for rendered component.
 * @param {boolean}       props.truncateToFirstParagraph Whether source should be truncated to first paragraph or exact word count.
 *
 */
export const Summary = ( {
	source,
	maxLength = 15,
	countType = 'words',
	className = '',
	style = {},
	truncateToFirstParagraph = true,
}: SummaryProps ): JSX.Element => {
	const summaryText = useMemo( () => {
		return generateSummary(
			source,
			maxLength,
			countType,
			truncateToFirstParagraph
		);
	}, [ source, maxLength, countType, truncateToFirstParagraph ] );

	return (
		<RawHTML style={ style } className={ className }>
			{ summaryText }
		</RawHTML>
	);
};

export default Summary;
