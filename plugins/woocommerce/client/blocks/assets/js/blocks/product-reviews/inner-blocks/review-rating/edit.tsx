/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import {
	AlignmentToolbar,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useStyleProps } from '@woocommerce/base-hooks';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Renders the `woocommerce/product-review-rating` block on the editor.
 *
 * @param   {Object}  props                    React props.
 * @param   {Object}  props.attributes         Block attributes.
 * @param   {Object}  props.setAttributes      Function to set block attributes.
 * @param   {Object}  props.context           Inherited context.
 * @param   {string}  props.context.commentId The comment ID.
 *
 * @return {JSX.Element} React element.
 */
export default function Edit( {
	context: { commentId },
	attributes,
	setAttributes,
}: BlockEditProps< {
	textAlign: string;
} > & {
	context: { commentId: string };
} ) {
	const { textAlign } = attributes;
	const styleProps = useStyleProps( attributes );
	const className = clsx(
		styleProps.className,
		'wc-block-components-product-review-rating',
		{
			[ `has-text-align-${ textAlign }` ]: textAlign,
		}
	);
	const blockProps = useBlockProps( {
		className,
	} );
	let [ rating ] = useEntityProp( 'root', 'comment', 'rating', commentId );
	rating = rating ?? 4;

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
					className={
						'wc-block-components-product-review-rating__stars'
					}
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
