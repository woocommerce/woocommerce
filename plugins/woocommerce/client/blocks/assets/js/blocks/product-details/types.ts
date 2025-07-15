/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

type Context = {
	context: { postId: string; postType: string };
};

type Attributes = {
	blockVersion: string;
};

export type ProductDetailsEditProps = BlockEditProps< Attributes > & Context;
