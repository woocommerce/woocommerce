/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { debounce } from 'lodash';
import { Placeholder } from '@wordpress/components';

/**
 * Internal dependencies
 */
import EditorBlock from './editor-block.js';
import { getBlockClassName, getSortArgs } from './utils.js';

/**
 * Container of the block rendered in the editor.
 */
class EditorContainerBlock extends Component {
	renderHiddenContentPlaceholder() {
		const { icon, name } = this.props;

		return (
			<Placeholder icon={ icon } label={ name }>
				{ __(
					'The content for this block is hidden due to block settings.',
					'woocommerce'
				) }
			</Placeholder>
		);
	}

	render() {
		const { attributes, className, noReviewsPlaceholder } = this.props;
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

		if ( isAllContentHidden ) {
			return this.renderHiddenContentPlaceholder();
		}

		return (
			<div className={ getBlockClassName( className, attributes ) }>
				<EditorBlock
					attributes={ attributes }
					categoryIds={ categoryIds }
					delayFunction={ ( callback ) => debounce( callback, 400 ) }
					noReviewsPlaceholder={ noReviewsPlaceholder }
					orderby={ orderby }
					order={ order }
					productId={ productId }
					reviewsToDisplay={ reviewsOnPageLoad }
				/>
			</div>
		);
	}
}

EditorContainerBlock.propTypes = {
	attributes: PropTypes.object.isRequired,
	icon: PropTypes.node.isRequired,
	name: PropTypes.string.isRequired,
	noReviewsPlaceholder: PropTypes.func.isRequired,
	className: PropTypes.string,
};

export default EditorContainerBlock;
