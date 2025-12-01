/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';
import {
	AlignmentToolbar,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { REVIEWS_STORE_NAME } from '@woocommerce/data';

export default function Edit( {
	// commentId is the ID of the review.
	context: { commentId: reviewId },
	attributes,
	setAttributes,
}: BlockEditProps< {
	textAlign: string;
} > & {
	context: { commentId: string };
} ) {
	const { textAlign } = attributes;
	const className = clsx( 'wc-block-product-review-rating', {
		[ `has-text-align-${ textAlign }` ]: textAlign,
	} );
	const blockProps = useBlockProps( {
		className,
	} );
	const rating = useSelect(
		( select ) => {
			const { getReview } = select( REVIEWS_STORE_NAME );
			const review = reviewId ? getReview( Number( reviewId ) ) : null;
			return review?.rating ?? 4;
		},
		[ reviewId ]
	);

	const starStyle = {
		width: ( rating / 5 ) * 100 + '%',
	};

	const ratingText = sprintf(
		/* translators: %f is referring to the average rating value */
		__( 'Rated %f out of 5', 'woocommerce' ),
		rating
	);

	const ratingHTML = {
		__html: sprintf(
			/* translators: %s is the rating value wrapped in HTML strong tags. */
			__( 'Rated %s out of 5', 'woocommerce' ),
			sprintf( '<strong class="rating">%f</strong>', rating )
		),
	};

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ attributes.textAlign }
					onChange={ ( newAlign ) => {
						setAttributes( { textAlign: newAlign || '' } );
					} }
				/>
			</BlockControls>
			<div { ...blockProps }>
				<div
					className="wc-block-product-review-rating__stars"
					role="img"
					aria-label={ ratingText }
				>
					<span
						style={ starStyle }
						dangerouslySetInnerHTML={ ratingHTML }
					/>
				</div>
			</div>
		</>
	);
}
