/**
 * External dependencies
 */
import { Dropdown, MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronRight } from '@wordpress/icons';
import { MediaUpload } from '@wordpress/media-utils';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { VariationActionsMenuItemProps } from '../types';
import { handlePrompt } from '../../../utils/handle-prompt';

function convertMediaFileToDownloadFile( value: Record< string, any > ) {
	return { id: `${ value.id }`, name: value.name, file: value.url };
}

export function DownloadsMenuItem( {
	selection,
	onChange,
	onClose,
}: VariationActionsMenuItemProps ) {
	const ids = Array.isArray( selection )
		? selection.map( ( { id } ) => id )
		: selection.id;

	function handleMediaUploadSelect(
		value: Record< string, any > | Record< string, any >[]
	) {
		const downloads = Array.isArray( value )
			? value.map( convertMediaFileToDownloadFile )
			: convertMediaFileToDownloadFile( value );

		const partialVariation = {
			downloadable: true,
			downloads,
		};

		if ( Array.isArray( selection ) ) {
			onChange(
				selection.map( ( { id } ) => ( {
					...partialVariation,
					id,
				} ) )
			);
		} else {
			onChange( partialVariation );
		}

		recordEvent( 'product_variations_menu_downloads_update', {
			source: TRACKS_SOURCE,
			action: 'downloads_set',
			variation_id: ids,
		} );

		onClose();
	}

	function uploadFilesClickHandler( openMediaUploadModal: () => void ) {
		return function handleUploadFilesClick() {
			recordEvent( 'product_variations_menu_downloads_select', {
				source: TRACKS_SOURCE,
				action: 'downloads_set',
				variation_id: ids,
			} );

			openMediaUploadModal();
		};
	}

	function menuItemClickHandler( name: string, message: string ) {
		return function handleMenuItemClick() {
			recordEvent( 'product_variations_menu_downloads_select', {
				source: TRACKS_SOURCE,
				action: `${ name }_set`,
				variation_id: ids,
			} );
			handlePrompt( {
				message,
				onOk( value ) {
					if ( Array.isArray( selection ) ) {
						onChange(
							selection.map( ( { id } ) => ( {
								id,
								downloadable: true,
								[ name ]: value,
							} ) )
						);
					} else {
						onChange( {
							downloadable: true,
							[ name ]: value,
						} );
					}
					recordEvent( 'product_variations_menu_downloads_update', {
						source: TRACKS_SOURCE,
						action: `${ name }_set`,
						variation_id: ids,
					} );
				},
			} );
			onClose();
		};
	}

	return (
		<Dropdown
			position="middle right"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<MenuItem
					onClick={ () => {
						recordEvent( 'product_variations_menu_shipping_click', {
							source: TRACKS_SOURCE,
							variation_id: ids,
						} );
						onToggle();
					} }
					aria-expanded={ isOpen }
					icon={ chevronRight }
					iconPosition="right"
				>
					{ __( 'Downloads', 'woocommerce' ) }
				</MenuItem>
			) }
			renderContent={ () => (
				<div className="components-dropdown-menu__menu">
					<MediaUpload
						// @ts-expect-error multiple also accepts string.
						multiple={ 'add' }
						onSelect={ handleMediaUploadSelect }
						render={ ( { open } ) => (
							<MenuItem
								onClick={ uploadFilesClickHandler( open ) }
							>
								{ __( 'Upload files', 'woocommerce' ) }
							</MenuItem>
						) }
					/>

					<MenuItem
						onClick={ menuItemClickHandler(
							'download_limit',
							__(
								'Leave blank for unlimited re-downloads',
								'woocommerce'
							)
						) }
					>
						{ __( 'Set download limit', 'woocommerce' ) }
					</MenuItem>

					<MenuItem
						onClick={ menuItemClickHandler(
							'download_expiry',
							__(
								'Enter the number of days before a download link expires, or leave blank',
								'woocommerce'
							)
						) }
					>
						{ __( 'Set download expiry', 'woocommerce' ) }
					</MenuItem>
				</div>
			) }
		/>
	);
}
