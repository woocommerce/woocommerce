/**
 * Internal dependencies
 */
import { SummaryAttributes } from '../types';

export type ParagraphRTLControlProps = Pick<
	SummaryAttributes,
	'direction'
> & {
	onChange( direction?: SummaryAttributes[ 'direction' ] ): void;
};
