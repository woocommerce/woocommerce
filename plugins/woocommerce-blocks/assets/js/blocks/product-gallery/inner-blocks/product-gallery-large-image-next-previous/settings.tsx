/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import {
	PanelBody,
	// @ts-expect-error `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error `__experimentalToggleGroupControlOption` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { NextPreviousButtonSettingValues } from './types';
import { InsideTheImage, OutsideTheImage } from './icons';
import { Context } from '../../types';

const NextPreviousButtonIcons = {
	[ NextPreviousButtonSettingValues.insideTheImage ]: <InsideTheImage />,
	[ NextPreviousButtonSettingValues.outsideTheImage ]: <OutsideTheImage />,
};

const getHelpText = ( buttonPosition: NextPreviousButtonSettingValues ) => {
	switch ( buttonPosition ) {
		case NextPreviousButtonSettingValues.insideTheImage:
			return __(
				'Next and previous buttons will appear inside the large image.',
				'woo-gutenberg-products-block'
			);
		case NextPreviousButtonSettingValues.outsideTheImage:
			return __(
				'Next and previous buttons will appear on outside the large image.',
				'woo-gutenberg-products-block'
			);
		case 'off':
			return __(
				'No next or previous button will be displayed.',
				'woo-gutenberg-products-block'
			);
		default:
			return __(
				'No next or previous button will be displayed.',
				'woo-gutenberg-products-block'
			);
	}
};

export const ProductGalleryNextPreviousBlockSettings = ( {
	context,
}: {
	context: Context;
} ) => {
	const { productGalleryClientId, nextPreviousButtonsPosition } = context;
	// @ts-expect-error @wordpress/block-editor/store types not provided
	const { updateBlockAttributes } = useDispatch( blockEditorStore );
	return (
		<PanelBody
			className="wc-block-editor-product-gallery-large-image-next-previous-settings"
			title={ __( 'Next/Prev Buttons', 'woo-gutenberg-products-block' ) }
		>
			<ToggleGroupControl
				style={ {
					width: '100%',
				} }
				onChange={ ( value: NextPreviousButtonSettingValues ) =>
					updateBlockAttributes( productGalleryClientId, {
						nextPreviousButtonsPosition: value,
					} )
				}
				help={ getHelpText( nextPreviousButtonsPosition ) }
				value={ nextPreviousButtonsPosition }
			>
				<ToggleGroupControlOption
					value={ NextPreviousButtonSettingValues.off }
					label={ __( 'Off', 'woo-gutenberg-products-block' ) }
				/>
				<ToggleGroupControlOption
					value={ NextPreviousButtonSettingValues.insideTheImage }
					label={ NextPreviousButtonIcons.insideTheImage }
				/>
				<ToggleGroupControlOption
					value={ NextPreviousButtonSettingValues.outsideTheImage }
					label={ NextPreviousButtonIcons.outsideTheImage }
				/>
			</ToggleGroupControl>
		</PanelBody>
	);
};
