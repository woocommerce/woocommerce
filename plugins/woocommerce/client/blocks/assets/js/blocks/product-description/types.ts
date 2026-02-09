/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

type Attributes = {
	tagName: 'div' | 'section' | 'aside';
};

type Context = {
	context: { postId: string; postType: string };
};

export type ProductDescriptionEditProps = BlockEditProps< Attributes > &
	Context;
