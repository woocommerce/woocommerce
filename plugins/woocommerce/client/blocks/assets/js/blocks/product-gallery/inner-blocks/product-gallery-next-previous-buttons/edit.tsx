/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';
import type { BlockAttributes } from '@wordpress/blocks';

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

const getAlignClass = ( align?: string ) => {
	if ( align === 'left' ) {
		return 'alignleft';
	}
	if ( align === 'right' ) {
		return 'alignright';
	}
	if ( align === 'center' ) {
		return 'aligncenter';
	}
	// Default to spread.
	return '';
};

export const Edit = ( { attributes }: { attributes: BlockAttributes } ) => {
	const blockProps = useBlockProps( {
		className: 'wc-block-product-gallery-large-image-next-previous__button',
	} );

	// We're passing blockProps to buttons because they're the main block content.
	// However, alignment has to be applied to the container hence we're handling
	// these attributes "manually" here.
	const verticalAlign = attributes?.layout?.verticalAlignment;
	const verticalAlignClass = getVerticalAlignmentClass( verticalAlign );
	const horizontalAlign = attributes?.align;
	const horizontalAlignClass = getAlignClass( horizontalAlign );

	const containerClassName = clsx(
		'wc-block-product-gallery-large-image-next-previous',
		verticalAlignClass,
		horizontalAlignClass
	);

	return (
		<div className={ containerClassName }>
			<button { ...blockProps } disabled>
				<PrevIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--left" />
			</button>
			<button { ...blockProps }>
				<NextIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--right" />
			</button>
		</div>
	);
};
