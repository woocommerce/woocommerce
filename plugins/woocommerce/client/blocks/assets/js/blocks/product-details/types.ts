/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

type Context = {
	context: { postId: string; postType: string };
};

export type Attributes = {
	hideTabTitle: boolean;
};

export type ProductDetailsEditProps = BlockEditProps< Attributes > & Context;
