/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

type Attributes = {
	textAlign: string;
	showProductTitle: boolean;
	showReviewsCount: boolean;
	level: number;
	levelOptions: { label: string; value: number }[];
};

type Context = {
	context: { postId: string; postType: string };
};

export type ProductReviewsTitleEditProps = BlockEditProps< Attributes > &
	Context;
