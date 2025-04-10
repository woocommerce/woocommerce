/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';
import { BlockAttributes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { PrevButton, NextButton } from './icons';

const getAlignmentStyle = ( alignment: string ): string => {
	switch ( alignment ) {
		case 'top':
			return 'flex-start';
		case 'center':
			return 'center';
		case 'bottom':
			return 'flex-end';
		default:
			return 'flex-end';
	}
};

export const Edit = ( {
	attributes,
}: {
	attributes: BlockAttributes;
} ): JSX.Element => {
	const blockProps = useBlockProps( {
		style: {
			width: '100%',
			alignItems: getAlignmentStyle(
				attributes.layout?.verticalAlignment
			),
		},
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
