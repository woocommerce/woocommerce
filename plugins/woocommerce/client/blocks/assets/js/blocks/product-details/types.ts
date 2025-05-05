/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

type Context = {
	context: { postId: string; postType: string };
};

export type ProductDetailsEditProps = BlockEditProps<
	Record< string, never >
> &
	Context;
