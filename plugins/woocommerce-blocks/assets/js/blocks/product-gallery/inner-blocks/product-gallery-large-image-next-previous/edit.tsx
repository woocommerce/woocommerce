/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { BlockAttributes } from '@wordpress/blocks';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { NextButton, PrevButton } from './icons';
import './editor.scss';
import { ProductGalleryNextPreviousBlockSettings } from './settings';
import { ProductGalleryContext } from '../../types';

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
	context,
}: {
	attributes: BlockAttributes;
	context: ProductGalleryContext;
} ): JSX.Element => {
	const blockProps = useBlockProps( {
		style: {
			width: '100%',
			alignItems: getAlignmentStyle(
				attributes.layout?.verticalAlignment
			),
		},
	} );

	const suffixClass = useMemo( () => {
		switch ( context.nextPreviousButtonsPosition ) {
			case 'insideTheImage':
				return 'inside-image';
			case 'outsideTheImage':
				return 'outside-image';
			case 'off':
				return 'off';
			default:
				return 'off';
		}
	}, [ context.nextPreviousButtonsPosition ] );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ProductGalleryNextPreviousBlockSettings context={ context } />
			</InspectorControls>
			<div
				className={ classNames(
					'wc-block-product-gallery-large-image-next-previous-container',
					`wc-block-product-gallery-large-image-next-previous--${ suffixClass }`
				) }
			>
				<PrevButton suffixClass={ suffixClass } />
				<NextButton suffixClass={ suffixClass } />
			</div>
		</div>
	);
};
