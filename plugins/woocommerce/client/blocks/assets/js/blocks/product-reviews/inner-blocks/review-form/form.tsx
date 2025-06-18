/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, _x } from '@wordpress/i18n';
import {
	Warning,
	__experimentalGetElementClassName,
} from '@wordpress/block-editor';
import { Button, Disabled } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { productsStore } from '@woocommerce/data';

const CommentsFormPlaceholder = () => {
	const instanceId = useInstanceId( CommentsFormPlaceholder );

	return (
		<div>
			<span
				id="reply-title"
				className="comment-reply-title"
				role="heading"
				aria-level={ 3 }
			>
				{ __( 'Add a review', 'woocommerce' ) }
			</span>
			<form
				noValidate
				className="review-form"
				onSubmit={ ( event ) => event.preventDefault() }
			>
				<div className="comment-form-rating">
					<span>{ __( 'Your rating *', 'woocommerce' ) }</span>
					<p className="wp-block-woocommerce-product-reviews__editor__stars"></p>
				</div>
				<p>
					<label htmlFor={ `review-${ instanceId }` }>
						{ __( 'Your review *', 'woocommerce' ) }
					</label>
					<textarea
						id={ `review-${ instanceId }` }
						name="review"
						cols={ 45 }
						rows={ 8 }
						readOnly
					/>
				</p>
				<p className="form-submit wp-block-button">
					<input
						name="submit"
						type="submit"
						className={ clsx(
							'wp-block-button__link',
							__experimentalGetElementClassName( 'button' )
						) }
						value={ __( 'Submit', 'woocommerce' ) }
						aria-disabled="true"
					/>
				</p>
			</form>
		</div>
	);
};

const CommentsForm = ( {
	postId,
	postType,
}: {
	postId: string;
	postType: string;
} ) => {
	const { updateProduct } = useDispatch( productsStore );
	const product = useSelect(
		( select ) => {
			if ( ! postId ) {
				return null;
			}
			return select( productsStore ).getProduct( Number( postId ) );
		},
		[ postId ]
	);

	const setReviewsAllowed = ( allowed: boolean ) => {
		if ( ! postId ) return;
		updateProduct( Number( postId ), {
			reviews_allowed: allowed,
		} );
	};

	const isSiteEditor = postType === undefined || postId === undefined;

	const postTypeSupportsComments = useSelect(
		( select ) =>
			postType
				? !! select( coreStore ).getPostType( postType )?.supports
						.comments
				: false,
		[ postType ]
	);

	if ( ! isSiteEditor && product && ! product?.reviews_allowed ) {
		const actions = [
			<Button
				__next40pxDefaultSize
				key="enableReviews"
				onClick={ () => setReviewsAllowed( true ) }
				variant="primary"
			>
				{ _x(
					'Enable reviews',
					'action that affects the current product',
					'woocommerce'
				) }
			</Button>,
		];
		return (
			<Warning actions={ actions }>
				{ __(
					'Product Reviews Form block: Reviews are not enabled for this product.',
					'woocommerce'
				) }
			</Warning>
		);
	} else if ( ! postTypeSupportsComments ) {
		return (
			<Warning>
				{ __(
					'Product Reviews Form block: Reviews are not enabled.',
					'woocommerce'
				) }
			</Warning>
		);
	}

	return (
		<Disabled>
			<CommentsFormPlaceholder />
		</Disabled>
	);
};

export default CommentsForm;
