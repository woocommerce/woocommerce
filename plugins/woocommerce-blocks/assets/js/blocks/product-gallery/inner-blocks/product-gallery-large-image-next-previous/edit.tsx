/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { BlockAttributes } from '@wordpress/blocks';
import { PanelBody } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */

import './editor.scss';
import { ProductGalleryNextPreviousBlockSettings } from './settings';
import { ProductGalleryContext } from '../../types';
import { getNextPreviousImagesWithClassName } from './utils';

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
		className: classNames(
			'wc-block-editor-product-gallery-large-image-next-previous',
			'wc-block-product-gallery-large-image-next-previous'
		),
	} );

	const previousNextImage = useMemo( () => {
		return getNextPreviousImagesWithClassName(
			context.nextPreviousButtonsPosition
		);
	}, [ context.nextPreviousButtonsPosition ] );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody>
					<ProductGalleryNextPreviousBlockSettings
						context={ context }
					/>
				</PanelBody>
			</InspectorControls>
			<div
				className={ classNames(
					'wc-block-product-gallery-large-image-next-previous-container',
					`wc-block-product-gallery-large-image-next-previous--${ previousNextImage?.classname }`
				) }
			>
				{ previousNextImage?.PrevButtonImage && (
					<previousNextImage.PrevButtonImage />
				) }
				{ previousNextImage?.NextButtonImage && (
					<previousNextImage.NextButtonImage />
				) }
			</div>
		</div>
	);
};
