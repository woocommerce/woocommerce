/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import clsx from 'clsx';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { PrevIcon, NextIcon } from './icons';

const getVerticalAlignmentClass = ( attributes: BlockAttributes ) => {
	const verticalAlignment = attributes?.layout?.verticalAlignment;

	if ( verticalAlignment === 'top' ) {
		return 'aligntop';
	}
	if ( verticalAlignment === 'bottom' ) {
		return 'alignbottom';
	}
	// Default to center.
	return '';
};

const splitClassName = ( className: string ) => {
	const classNamesArray = className.split( ' ' );
	const containerClassName = classNamesArray
		.filter( ( cn ) => ! cn.startsWith( 'has' ) )
		.join( ' ' );
	const buttonClassName = classNamesArray
		.filter( ( cn ) => cn.startsWith( 'has' ) )
		.join( ' ' );
	return { containerClassName, buttonClassName };
};

export const Edit = ( { attributes }: { attributes: BlockAttributes } ) => {
	const verticalAlignmentClass = getVerticalAlignmentClass( attributes );
	const { style, className, ...blockProps } = useBlockProps( {
		className: clsx(
			'wc-block-product-gallery-large-image-next-previous',
			verticalAlignmentClass
		),
	} );

	const { containerClassName, buttonClassName } = splitClassName( className );

	return (
		<div { ...blockProps } className={ containerClassName }>
			<button
				className={ clsx(
					buttonClassName,
					'wc-block-product-gallery-large-image-next-previous__button'
				) }
				style={ style }
				disabled
			>
				<PrevIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--left" />
			</button>
			<button
				style={ style }
				className={
					buttonClassName +
					' wc-block-product-gallery-large-image-next-previous__button'
				}
			>
				<NextIcon className="wc-block-product-gallery-large-image-next-previous__icon wc-block-product-gallery-large-image-next-previous__icon--right" />
			</button>
		</div>
	);
};
