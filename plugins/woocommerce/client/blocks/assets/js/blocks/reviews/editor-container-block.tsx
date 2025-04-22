/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { debounce } from '@woocommerce/base-utils';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { EditorContainerBlockProps } from '@woocommerce/blocks/reviews/types';

/**
 * Internal dependencies
 */
import EditorBlock from './editor-block';
import { getBlockClassName, getSortArgs } from './utils.js';

const EditorContainerBlock = ( {
	attributes,
	icon,
	name,
	noReviewsPlaceholder,
}: EditorContainerBlockProps ) => {
	const {
		categoryIds,
		productId,
		reviewsOnPageLoad,
		showProductName,
		showReviewDate,
		showReviewerName,
		showReviewContent,
		showReviewImage,
		showReviewRating,
	} = attributes;
	const { order, orderby } = getSortArgs( attributes.orderby );
	const isAllContentHidden =
		! showReviewContent &&
		! showReviewRating &&
		! showReviewDate &&
		! showReviewerName &&
		! showReviewImage &&
		! showProductName;

	const blockProps = useBlockProps( {
		className: getBlockClassName( attributes ),
	} );

	if ( isAllContentHidden ) {
		return (
			<Placeholder icon={ icon } label={ name }>
				{ __(
					'The content for this block is hidden due to block settings.',
					'woocommerce'
				) }
			</Placeholder>
		);
	}

	return (
		<div { ...blockProps }>
			<EditorBlock
				attributes={ attributes }
				categoryIds={ categoryIds }
				delayFunction={ ( callback: () => void ) =>
					debounce( callback, 400 )
				}
				noReviewsPlaceholder={ noReviewsPlaceholder }
				orderby={ orderby }
				order={ order }
				productId={ productId }
				reviewsToDisplay={ reviewsOnPageLoad }
			/>
		</div>
	);
};

export default EditorContainerBlock;
