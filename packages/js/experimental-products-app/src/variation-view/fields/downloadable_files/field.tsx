/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, TextControl } from '@wordpress/components';
import { trash } from '@wordpress/icons';
import { useCallback, useRef, useState } from '@wordpress/element';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import type { UploadedItem } from './utils';

declare global {
	interface Window {
		wp?: {
			media?: ( config: {
				title?: string;
				button?: { text?: string };
				multiple?: boolean;
			} ) => WPMediaFrame;
		};
	}
}

interface WPMediaFrame {
	on( event: 'select', callback: () => void ): WPMediaFrame;
	open(): void;
	state(): {
		get( key: 'selection' ): {
			first(): {
				toJSON(): {
					url: string;
					title: string;
					filename: string;
					id: number;
				};
			};
		};
	};
}

function openMediaLibrary(
	onSelect: ( url: string, name: string, id?: string ) => void
) {
	if ( ! window.wp?.media ) {
		return;
	}
	const frame = window.wp.media( {
		title: __( 'Choose a file', 'woocommerce' ),
		button: { text: __( 'Choose file', 'woocommerce' ) },
		multiple: false,
	} );
	frame.on( 'select', () => {
		const attachment = frame.state().get( 'selection' ).first().toJSON();
		onSelect(
			attachment.url,
			attachment.title || attachment.filename || '',
			attachment.id ? String( attachment.id ) : undefined
		);
	} );
	frame.open();
}

function DownloadableFilesEdit( {
	data,
	onChange,
}: {
	data: ProductEntityRecord;
	onChange: ( changes: Partial< ProductEntityRecord > ) => void;
} ) {
	type KeyedItem = UploadedItem & { _key: number };

	const savedDownloads = ( data.downloads ?? [] ) as UploadedItem[];
	const keyCounter = useRef( Math.max( savedDownloads.length, 1 ) );

	// State is initialised from saved data once per mount.
	// The drawer unmounts on close/cancel, so re-opening always starts fresh.
	const [ downloads, setDownloads ] = useState< KeyedItem[] >(
		savedDownloads.length > 0
			? savedDownloads.map( ( d, i ) => ( { ...d, _key: i } ) )
			: [ { file: '', name: '', _key: 0 } ]
	);

	const commit = useCallback(
		( next: KeyedItem[] ) => {
			setDownloads( next );
			// Only persist entries that have a URL.
			onChange( {
				downloads: next
					.filter( ( d ) => d.file.trim() !== '' )
					.map( ( { _key: _, ...d } ) => ( {
						...d,
						id: d.id ?? '',
					} ) ),
			} );
		},
		[ onChange ]
	);

	const updateEntry = ( index: number, changes: Partial< KeyedItem > ) => {
		commit(
			downloads.map( ( d, i ) =>
				i === index ? { ...d, ...changes } : d
			)
		);
	};

	const removeEntry = ( index: number ) => {
		commit( downloads.filter( ( _, i ) => i !== index ) );
	};

	const canDelete = downloads.length > 1;

	return (
		<div className="woocommerce-fields-downloadable-files">
			{ downloads.map( ( download, index ) => (
				<div
					key={ download._key }
					className="woocommerce-fields-downloadable-files__entry"
				>
					{ index > 0 && (
						<hr className="woocommerce-fields-downloadable-files__separator" />
					) }

					<div className="woocommerce-fields-downloadable-files__url-row">
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'URL', 'woocommerce' ) }
							type="url"
							placeholder="https://"
							value={ download.file }
							onChange={ ( val ) =>
								updateEntry( index, { file: val } )
							}
						/>
						<Button
							className="woocommerce-fields-downloadable-files__choose-button"
							variant="secondary"
							__next40pxDefaultSize
							onClick={ () =>
								openMediaLibrary( ( url, name, id ) => {
									updateEntry( index, {
										file: url,
										name: download.name || name,
										...( id ? { id } : {} ),
									} );
								} )
							}
						>
							{ __( 'Choose file', 'woocommerce' ) }
						</Button>
					</div>

					<div className="woocommerce-fields-downloadable-files__name-row">
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Name', 'woocommerce' ) }
							value={ download.name }
							onChange={ ( val ) =>
								updateEntry( index, { name: val } )
							}
						/>
						{ canDelete && (
							<Button
								className="woocommerce-fields-downloadable-files__delete-button"
								variant="secondary"
								__next40pxDefaultSize
								icon={ trash }
								label={ __( 'Remove file', 'woocommerce' ) }
								onClick={ () => removeEntry( index ) }
							/>
						) }
					</div>
				</div>
			) ) }

			<Button
				className="woocommerce-fields-downloadable-files__add-button"
				variant="tertiary"
				__next40pxDefaultSize
				onClick={ () =>
					commit( [
						...downloads,
						{ file: '', name: '', _key: keyCounter.current++ },
					] )
				}
			>
				{ __( '+ Add file', 'woocommerce' ) }
			</Button>
		</div>
	);
}

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	label: __( 'Downloadable files', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
	isVisible: ( item ) => !! item.downloadable,
	getValue: ( { item } ) => item.downloads ?? [],
	Edit: DownloadableFilesEdit,
};
