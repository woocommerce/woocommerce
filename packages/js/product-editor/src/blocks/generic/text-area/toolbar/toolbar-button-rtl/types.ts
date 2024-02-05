/**
 * Internal dependencies
 */
import type { TextAreaBlockEditAttributes } from '../../types';

type DirectionProp = TextAreaBlockEditAttributes[ 'direction' ];

export type RTLToolbarButtonProps = {
	/**
	 * Current direction.
	 */
	direction: DirectionProp;

	/**
	 * Callback to update the direction.
	 */
	onChange( direction?: DirectionProp ): void;
};
