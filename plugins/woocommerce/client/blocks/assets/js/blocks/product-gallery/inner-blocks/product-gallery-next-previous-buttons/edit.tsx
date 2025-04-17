/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { PrevIcon, NextIcon } from './icons';

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-product-gallery-large-image-next-previous__button',
	} );

	return (
		<div className="wc-block-product-gallery-large-image-next-previous">
			<button { ...blockProps } disabled>
				<PrevIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--left" />
			</button>
			<button { ...blockProps }>
				<NextIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--right" />
			</button>
		</div>
	);
};
