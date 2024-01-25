/**
 * Internal dependencies
 */
import type { TextAreaBlockEdit } from '../../types';

export type RTLToolbarButtonProps = Pick< TextAreaBlockEdit, 'direction' > & {
	onChange( direction?: TextAreaBlockEdit[ 'direction' ] ): void;
};
