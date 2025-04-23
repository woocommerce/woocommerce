/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { PrevIcon, NextIcon } from './icons';

const getVerticalAlignmentClass = ( verticalAlignment?: string ) => {
	if ( verticalAlignment === 'top' ) {
		return 'aligntop';
	}
	if ( verticalAlignment === 'bottom' ) {
		return 'alignbottom';
	}
	// Default to center.
	return '';
};

export const Edit = ( {
	attributes,
}: {
	attributes: { layout: { verticalAlignment: string } };
} ) => {
	const verticalAlignment = attributes?.layout?.verticalAlignment;
	const verticalAlignmentClass =
		getVerticalAlignmentClass( verticalAlignment );

	const { style, ...blockProps } = useBlockProps( {
		className: clsx(
			'wc-block-product-gallery-large-image-next-previous',
			verticalAlignmentClass
		),
	} );

	return (
		<div { ...blockProps }>
			<button
				className="wc-block-product-gallery-large-image-next-previous__button"
				style={ style }
				disabled
			>
				<PrevIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--left" />
			</button>
			<button
				className="wc-block-product-gallery-large-image-next-previous__button"
				style={ style }
			>
				<NextIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--right" />
			</button>
		</div>
	);
};
