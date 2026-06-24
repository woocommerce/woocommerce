/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormFileUpload } from '@wordpress/components';
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import type { Field } from '@wordpress/dataviews';
import { uploadMedia } from '@wordpress/media-utils';
import type { Attachment } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { ListItem } from '../components/list-item';

import {
	GenericThumbnail,
	getLastPathFromUrl,
	isImageFromLink,
	isItemToUpload,
	isUploadedItem,
	type ItemToUpload,
	type UploadedItem,
} from './utils';

const uploadingLabel = __( 'uploading…', 'woocommerce' );

const getAttachmentTextValue = ( value: unknown ) => {
	return typeof value === 'string' ? value : '';
};

const toUploadedDownload = (
	attachment: Attachment,
	fallbackFile: File
): UploadedItem | undefined => {
	const file = getAttachmentTextValue( attachment.url );

	if ( ! file ) {
		return undefined;
	}

	return {
		id: attachment.id ? String( attachment.id ) : undefined,
		file,
		name:
			getAttachmentTextValue( attachment.title ) ||
			getAttachmentTextValue( attachment.alt ) ||
			getAttachmentTextValue( attachment.caption ) ||
			fallbackFile.name ||
			getLastPathFromUrl( file ),
	};
};

const fieldDefinition = {
	type: 'boolean',
	label: __( 'Downloadable', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	type: 'boolean',
	isVisible: ( item ) =>
		item.downloadable === true && item.type !== 'variable',
	getValue: ( { item } ) => item.downloadable,
	Edit: ( { data, onChange } ) => {
		const dataDownloads = useMemo(
			() =>
				( data.downloads ?? [] ) as Array<
					UploadedItem | ItemToUpload
				>,
			[ data.downloads ]
		);
		const [ downloads, setDownloads ] = useState( dataDownloads );
		const downloadsRef = useRef( dataDownloads );

		useEffect( () => {
			downloadsRef.current = dataDownloads;
			setDownloads( dataDownloads );
		}, [ dataDownloads ] );

		const setVisibleDownloads = useCallback(
			( nextDownloads: Array< UploadedItem | ItemToUpload > ) => {
				downloadsRef.current = nextDownloads;
				setDownloads( nextDownloads );
			},
			[]
		);

		const commitDownloads = useCallback(
			( nextDownloads: Array< UploadedItem | ItemToUpload > ) => {
				setVisibleDownloads( nextDownloads );
				onChange( {
					downloads: nextDownloads,
				} );
			},
			[ onChange, setVisibleDownloads ]
		);

		const filesToUpload = downloads.filter( isItemToUpload );
		const uploadedFiles = downloads.filter( isUploadedItem );

		const handleRemoveDownload = useCallback(
			( fileId: string | number ) => {
				commitDownloads(
					downloadsRef.current.filter(
						( download ) => download.file !== fileId
					)
				);
			},
			[ commitDownloads ]
		);

		const handleAddDownload = useCallback(
			( file: File ) => {
				const objectUrl = URL.createObjectURL( file );
				const placeholderDownload: ItemToUpload = {
					file: objectUrl,
					name: file.name,
					type: file.type,
				};

				setVisibleDownloads( [
					...downloadsRef.current,
					placeholderDownload,
				] );

				uploadMedia( {
					filesList: [ file ],
					onFileChange( attachments ) {
						const attachment = attachments[ 0 ] as
							| Attachment
							| undefined;

						const uploadedDownload = attachment?.id
							? toUploadedDownload( attachment, file )
							: undefined;

						if ( ! uploadedDownload ) {
							URL.revokeObjectURL( objectUrl );
							setVisibleDownloads(
								downloadsRef.current.filter(
									( download ) => download.file !== objectUrl
								)
							);
							return;
						}

						const hasPlaceholder = downloadsRef.current.some(
							( download ) => download.file === objectUrl
						);

						if ( ! hasPlaceholder ) {
							URL.revokeObjectURL( objectUrl );
							return;
						}

						const currentDownloads = downloadsRef.current.filter(
							( download ) => download.file !== objectUrl
						);
						const isAlreadyAdded = currentDownloads.some(
							( download ) =>
								download.file === uploadedDownload.file
						);

						URL.revokeObjectURL( objectUrl );
						commitDownloads(
							isAlreadyAdded
								? currentDownloads
								: [ ...currentDownloads, uploadedDownload ]
						);
					},
					onError() {
						URL.revokeObjectURL( objectUrl );
						setVisibleDownloads(
							downloadsRef.current.filter(
								( download ) => download.file !== objectUrl
							)
						);
					},
				} );
			},
			[ commitDownloads, setVisibleDownloads ]
		);

		const items = [
			...uploadedFiles.map( ( file ) => {
				const thumbnail = isImageFromLink( file.file ) ? (
					file.file
				) : (
					<GenericThumbnail />
				);
				return {
					id: file.file,
					title: file.name,
					thumbnail,
					meta: getLastPathFromUrl( file.file ),
					altText: file.name,
				};
			} ),
			...filesToUpload.map( ( file ) => {
				const thumbnail = file.type?.startsWith( 'image/' ) ? (
					file.file
				) : (
					<GenericThumbnail />
				);
				return {
					id: file.file,
					title: `${ file.name } - ${ uploadingLabel }`,
					thumbnail,
					meta: getLastPathFromUrl( file.file ),
					altText: file.name,
				};
			} ),
		];

		return (
			<div className="woocommerce-fields-field__downloadable">
				{ items.length > 0 && (
					<ListItem
						items={ items }
						onRemove={ ( item ) => handleRemoveDownload( item.id ) }
						showRemoveButton={ true }
					/>
				) }
				<FormFileUpload
					__next40pxDefaultSize
					className="woocommerce-fields-field__downloadable-upload-button"
					onChange={ ( event ) => {
						const file = event?.currentTarget.files?.[ 0 ];
						if ( file ) {
							handleAddDownload( file );
						}
					} }
				>
					<span>
						{ __( '+ Upload another file', 'woocommerce' ) }
					</span>
				</FormFileUpload>
			</div>
		);
	},
};
