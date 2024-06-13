/**
 * External dependencies
 */
import { Dropdown, MenuGroup } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { MediaItem } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { MediaLibraryMenuItem } from '../../menu-items/media-library-menu-item';
import {
	UploadFilesMenuItem,
	UploadFilesMenuItemErrorCallback,
} from '../../menu-items/upload-files-menu-item';
import { mapUploadImageToImage } from '../../../utils/map-upload-image-to-image';
import { VariationQuickUpdateMenuItem } from '../variation-actions-menus';
import type { ImageActionsMenuProps } from './types';

export function ImageActionsMenu( {
	selection,
	onChange,
	onDelete,
	...props
}: ImageActionsMenuProps ) {
	const [ isUploading, setIsUploading ] = useState( false );

	const { createErrorNotice } = useDispatch( 'core/notices' );

	function uploadSuccessHandler( onClose: () => void ) {
		return function handleUploadSuccess( files: MediaItem[] ) {
			const image =
				( files.length && mapUploadImageToImage( files[ 0 ] ) ) ||
				undefined;
			const variation = {
				id: selection[ 0 ].id,
				image,
			};

			setIsUploading( false );

			onChange( [ variation ], false );
			onClose();
		};
	}

	const uploadErrorHandler: UploadFilesMenuItemErrorCallback = function (
		error
	) {
		createErrorNotice(
			sprintf(
				/* translators: %1$s is a line break, %2$s is the detailed error message */
				__( 'Error uploading file:%1$s%2$s', 'woocommerce' ),
				'\n',
				error.message
			)
		);
	};

	function mediaLibraryMenuItemSelectHandler( onClose: () => void ) {
		return function handleMediaLibraryMenuItemSelect( media: never ) {
			const variation = {
				id: selection[ 0 ].id,
				image: mapUploadImageToImage( media ) || undefined,
			};
			onChange( [ variation ], false );
			onClose();
		};
	}

	return (
		<Dropdown
			{ ...props }
			// @ts-expect-error missing prop in types.
			popoverProps={ {
				placement: 'bottom-end',
			} }
			renderToggle={ ( toggleProps ) =>
				props.renderToggle( { ...toggleProps, isBusy: isUploading } )
			}
			className="woocommerce-image-actions-menu"
			contentClassName="woocommerce-image-actions-menu__menu-content"
			renderContent={ ( { onClose } ) => (
				<div className="components-dropdown-menu__menu">
					<MenuGroup>
						<UploadFilesMenuItem
							allowedTypes={ [ 'image' ] }
							accept="image/*"
							multiple={ false }
							info={ __(
								'1000 pixels wide or larger',
								'woocommerce'
							) }
							onUploadProgress={ () => {
								setIsUploading( true );
								onClose();
							} }
							onUploadSuccess={ uploadSuccessHandler( onClose ) }
							onUploadError={ ( error ) => {
								uploadErrorHandler( error );
								setIsUploading( false );
								onClose();
							} }
						/>

						<MediaLibraryMenuItem
							allowedTypes={ [ 'image' ] }
							multiple={ false }
							value={ selection[ 0 ].id }
							onSelect={ mediaLibraryMenuItemSelectHandler(
								onClose
							) }
						/>
					</MenuGroup>

					<VariationQuickUpdateMenuItem.Slot
						group={ 'image-actions-menu' }
						onChange={ onChange }
						onClose={ onClose }
						selection={ selection }
						supportsMultipleSelection={ false }
					/>
				</div>
			) }
		/>
	);
}
