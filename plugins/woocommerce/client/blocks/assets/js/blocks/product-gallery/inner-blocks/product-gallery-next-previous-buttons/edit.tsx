/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { PrevIcon, NextIcon } from './icons';

export const Edit = (): JSX.Element => {
	const { style, ...blockProps } = useBlockProps( {
		className: 'wc-block-product-gallery-large-image-next-previous',
	} );

	return (
		<div { ...blockProps }>
			<div className="wc-block-product-gallery-large-image-next-previous-container">
				<button
					style={ style }
					className="wc-block-product-gallery-large-image-next-previous__button"
				>
					<PrevIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--left" />
				</button>
				<button
					style={ style }
					className="wc-block-product-gallery-large-image-next-previous__button"
				>
					<NextIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--right" />
				</button>
			</div>
		</div>
	);
};
