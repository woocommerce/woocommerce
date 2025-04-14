/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { PrevButton, NextButton } from './icons';

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
				<PrevButton />
				<NextButton />
			</div>
		</div>
	);
};
