/**
 * Internal dependencies
 */
import type { TextAreaBlockEditAttributes } from '../../types';

export type RTLToolbarButtonProps = Pick<
	TextAreaBlockEditAttributes,
	'direction'
> & {
	onChange( direction?: TextAreaBlockEditAttributes[ 'direction' ] ): void;
};
