/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

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
				<button className="wc-block-product-gallery-large-image-next-previous-left">
					&lt;
				</button>
				<button className="wc-block-product-gallery-large-image-next-previous-right">
					&gt;
				</button>
			</div>
		</div>
	);
};
