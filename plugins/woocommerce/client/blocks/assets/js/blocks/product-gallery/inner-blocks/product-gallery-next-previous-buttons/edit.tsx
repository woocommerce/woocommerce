/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { PrevIcon, NextIcon } from './icons';

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: clsx(
			'wc-block-editor-product-gallery-large-image-next-previous',
			'wc-block-product-gallery-large-image-next-previous'
		),
	} );

	return (
		<div { ...blockProps }>
			<div
				className={ clsx(
					'wc-block-product-gallery-large-image-next-previous-container'
				) }
			>
				<button className="wc-block-product-gallery-large-image-next-previous__button">
					<PrevIcon className="wc-block-product-gallery-large-image-next-previous-left__icon" />
				</button>
				<button className="wc-block-product-gallery-large-image-next-previous__button">
					<NextIcon className="wc-block-product-gallery-large-image-next-previous-right__icon" />
				</button>
			</div>
		</div>
	);
};
