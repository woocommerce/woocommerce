/**
 * WordPress dependencies
 */
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import {
	Children,
	cloneElement,
	createElement,
	Fragment,
	isValidElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { MediaUploadComponentType } from './types';
import { MediaItem, MediaUpload } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */

const POPOVER_PROPS = {
	className: 'woocommerce-image-gallery__toolbar-dropdown-popover',
	placement: 'bottom-start',
};

type ImageGalleryToolbarDropdownProps = {
	onReplace: ( media: { id: number } & MediaItem ) => void;
	onRemove: () => void;
	canRemove?: boolean;
	removeBlockLabel?: string;
	MediaUploadComponent: MediaUploadComponentType;
};

export function ImageGalleryToolbarDropdown( {
	children,
	onReplace,
	onRemove,
	canRemove,
	removeBlockLabel,
	MediaUploadComponent = MediaUpload,
	...props
}: React.PropsWithChildren< ImageGalleryToolbarDropdownProps > ) {
	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ __( 'Options', 'woocommerce' ) }
			className="woocommerce-image-gallery__toolbar-dropdown"
			popoverProps={ POPOVER_PROPS }
			menuProps={ {
				/**
				 * @param {KeyboardEvent} event
				 */
				onKeyDown( event ) {
					if ( event.defaultPrevented ) return;
				},
			} }
			{ ...props }
		>
			{ ( { onClose } ) => (
				<>
					<MenuGroup>
						<MediaUploadComponent
							onSelect={ ( media ) => {
								onReplace( media as MediaItem );
								onClose();
							} }
							allowedTypes={ [ 'image' ] }
							render={ ( { open } ) => (
								<MenuItem
									onClick={ () => {
										// onClose();
										open();
									} }
								>
									{ __( 'Replace', 'woocommerce' ) }
								</MenuItem>
							) }
						/>
					</MenuGroup>
					{ typeof children === 'function'
						? children( { onClose } )
						: Children.map(
								children,
								( child ) =>
									isValidElement( child ) &&
									cloneElement( child, { onClose } )
						  ) }
					{ canRemove && (
						<MenuGroup>
							<MenuItem
								onClick={ () => {
									onClose();
									onRemove();
								} }
							>
								{ removeBlockLabel ||
									__( 'Remove', 'woocommerce' ) }
							</MenuItem>
						</MenuGroup>
					) }
				</>
			) }
		</DropdownMenu>
	);
}
