/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';

export const blockAttributes: Record<
	keyof BlockAttributes,
	{
		type: string;
		default: unknown;
	}
> = {
	productId: {
		type: 'number',
		default: 0,
	},
	isDescendentOfQueryLoop: {
		type: 'boolean',
		default: false,
	},
};

export default blockAttributes;
