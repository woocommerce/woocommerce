/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	createElement,
	Fragment,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import { MediaItem } from '@wordpress/media-utils';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { ListItem, MediaUploader, Sortable } from '@woocommerce/components';
import { Product, ProductDownload } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { UploadsBlockAttributes } from './types';
import { DownloadsMenu } from './downloads-menu';
import { ProductEditorBlockEditProps } from '../../../types';
import {
	ManageDownloadLimitsModal,
	ManageDownloadLimitsModalProps,
} from '../../../components/manage-download-limits-modal';
import { EditDownloadsModal } from './edit-downloads-modal';
import { UploadImage } from './upload-image';
import { SectionActions } from '../../../components/block-slot-fill';

function getFileName( url?: string ) {
	const [ name ] = url?.split( '/' ).reverse() ?? [];
	return name;
}

function stringifyId< ID >( id?: ID ): string {
	return id ? String( id ) : '';
}

function stringifyEntityId< ID, T extends { id?: ID } >( entity: T ): T {
	return { ...entity, id: stringifyId( entity.id ) };
}

export function DownloadBlockEdit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< UploadsBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const [ downloads, setDownloads ] = useEntityProp< Product[ 'downloads' ] >(
		'postType',
		postType,
		'downloads'
	);
	const [ downloadLimit, setDownloadLimit ] = useEntityProp<
		Product[ 'download_limit' ]
	>( 'postType', postType, 'download_limit' );
	const [ downloadExpiry, setDownloadExpiry ] = useEntityProp<
		Product[ 'download_expiry' ]
	>( 'postType', postType, 'download_expiry' );

	const [ selectedDownload, setSelectedDownload ] =
		useState< ProductDownload | null >();

	const { allowedMimeTypes } = useSelect( ( select ) => {
		const { getEditorSettings } = select( 'core/editor' );
		return getEditorSettings();
	} );

	const allowedTypes = allowedMimeTypes
		? Object.values( allowedMimeTypes )
		: [];

	const { createErrorNotice } = useDispatch( 'core/notices' );

	const [ showManageDownloadLimitsModal, setShowManageDownloadLimitsModal ] =
		useState( false );

	function handleManageLimitsClick() {
		setShowManageDownloadLimitsModal( true );
	}

	function handleManageDownloadLimitsModalClose() {
		setShowManageDownloadLimitsModal( false );
	}

	function handleManageDownloadLimitsModalSubmit(
		value: ManageDownloadLimitsModalProps[ 'initialValue' ]
	) {
		setDownloadLimit( value.downloadLimit as number );
		setDownloadExpiry( value.downloadExpiry as number );
		setShowManageDownloadLimitsModal( false );
	}

	function handleFileUpload( files: MediaItem | MediaItem[] ) {
		if ( ! Array.isArray( files ) ) return;

		const newFiles = files.filter(
			( file ) =>
				! downloads.some( ( download ) => download.file === file.url )
		);

		if ( newFiles.length !== files.length ) {
			createErrorNotice(
				files.length === 1
					? __( 'This file has already been added', 'woocommerce' )
					: __(
							'Some of these files have already been added',
							'woocommerce'
					  )
			);
		}

		if ( newFiles.length ) {
			const uploadedFiles = newFiles.map( ( file ) => ( {
				id: stringifyId( file.id ),
				file: file.url,
				name:
					file.title ||
					file.alt ||
					file.caption ||
					getFileName( file.url ),
			} ) );

			const stringifyIds = downloads.map( stringifyEntityId );

			stringifyIds.push( ...uploadedFiles );

			setDownloads( stringifyIds );
		}
	}

	function handleFileReplace( files: MediaItem | MediaItem[] ) {
		if (
			! Array.isArray( files ) ||
			! files?.length ||
			files[ 0 ]?.id === undefined
		) {
			return;
		}

		const uploadedFile = {
			id: stringifyId( files[ 0 ].id ),
			file: files[ 0 ].url,
			name:
				files[ 0 ].title ||
				files[ 0 ].alt ||
				files[ 0 ].caption ||
				getFileName( files[ 0 ].url ),
		};
		const stringifyIds = downloads.map( ( download ) => {
			if ( download.file === selectedDownload?.file ) {
				return stringifyEntityId( uploadedFile );
			}
			return stringifyEntityId( download );
		} );

		setDownloads( stringifyIds );
		setSelectedDownload( uploadedFile );
	}

	function removeDownload( download: ProductDownload ) {
		const otherDownloads = downloads.reduce< ProductDownload[] >(
			function removeDownloadElement(
				others: ProductDownload[],
				current: ProductDownload
			) {
				if ( current.file === download.file ) {
					return others;
				}
				return [ ...others, stringifyEntityId( current ) ];
			},
			[]
		);

		setDownloads( otherDownloads );
	}

	function removeHandler( download: ProductDownload ) {
		return function handleRemoveClick() {
			removeDownload( download );
		};
	}

	function editHandler( download: ProductDownload ) {
		return function handleEditClick() {
			setSelectedDownload( stringifyEntityId( download ) );
		};
	}

	function handleUploadError( error: unknown ) {
		createErrorNotice(
			typeof error === 'string'
				? error
				: __( 'There was an error uploading files', 'woocommerce' )
		);
	}

	function editDownloadsModalSaveHandler( value: ProductDownload ) {
		return function handleEditDownloadsModalSave() {
			const newDownloads = downloads
				.map( stringifyEntityId )
				.map( ( obj: ProductDownload ) =>
					obj.id === value.id ? value : obj
				);

			setDownloads( newDownloads );
			setSelectedDownload( null );
		};
	}

	return (
		<div { ...blockProps }>
			<SectionActions>
				{ Boolean( downloads.length ) && (
					<Button
						variant="tertiary"
						onClick={ handleManageLimitsClick }
					>
						{ __( 'Manage limits', 'woocommerce' ) }
					</Button>
				) }

				<DownloadsMenu
					allowedTypes={ allowedTypes }
					onUploadSuccess={ handleFileUpload }
					onUploadError={ handleUploadError }
				/>
			</SectionActions>

			<div className="wp-block-woocommerce-product-downloads-field__body">
				<MediaUploader
					label={
						! Boolean( downloads.length ) ? (
							<div className="wp-block-woocommerce-product-downloads-field__drop-zone-content">
								<UploadImage />
								<p className="wp-block-woocommerce-product-downloads-field__drop-zone-label">
									{ createInterpolateElement(
										__(
											'Supported file types: <Types /> and more. <link>View all</link>',
											'woocommerce'
										),
										{
											Types: (
												<Fragment>
													PNG, JPG, PDF, PPT, DOC,
													MP3, MP4
												</Fragment>
											),
											link: (
												// eslint-disable-next-line jsx-a11y/anchor-has-content
												<a
													href="https://codex.wordpress.org/Uploading_Files"
													target="_blank"
													rel="noreferrer"
													onClick={ ( event ) =>
														event.stopPropagation()
													}
												/>
											),
										}
									) }
								</p>
							</div>
						) : (
							''
						)
					}
					buttonText=""
					allowedMediaTypes={ allowedTypes }
					multipleSelect={ 'add' }
					onUpload={ handleFileUpload }
					onFileUploadChange={ handleFileUpload }
					onError={ handleUploadError }
					additionalData={ {
						type: 'downloadable_product',
					} }
				/>

				{ Boolean( downloads.length ) && (
					<Sortable className="wp-block-woocommerce-product-downloads-field__table">
						{ downloads.map( ( download: ProductDownload ) => {
							const nameFromUrl = getFileName( download.file );
							const isUploading =
								download.file.startsWith( 'blob' );

							return (
								<ListItem
									key={ download.file }
									className="wp-block-woocommerce-product-downloads-field__table-row"
								>
									<div className="wp-block-woocommerce-product-downloads-field__table-filename">
										<span>{ download.name }</span>
										{ download.name !== nameFromUrl && (
											<span className="wp-block-woocommerce-product-downloads-field__table-filename-description">
												{ nameFromUrl }
											</span>
										) }
									</div>

									<div className="wp-block-woocommerce-product-downloads-field__table-actions">
										{ isUploading && (
											<Spinner
												aria-label={ __(
													'Uploading file',
													'woocommerce'
												) }
											/>
										) }
										{ ! isUploading && (
											<Button
												onClick={ editHandler(
													download
												) }
												variant="tertiary"
											>
												{ __( 'Edit', 'woocommerce' ) }
											</Button>
										) }
										<Button
											icon={ closeSmall }
											label={ __(
												'Remove file',
												'woocommerce'
											) }
											disabled={ isUploading }
											onClick={ removeHandler(
												download
											) }
										/>
									</div>
								</ListItem>
							);
						} ) }
					</Sortable>
				) }
			</div>

			{ showManageDownloadLimitsModal && (
				<ManageDownloadLimitsModal
					initialValue={ { downloadLimit, downloadExpiry } }
					onSubmit={ handleManageDownloadLimitsModalSubmit }
					onClose={ handleManageDownloadLimitsModalClose }
				/>
			) }
			{ selectedDownload && (
				<EditDownloadsModal
					downloableItem={ { ...selectedDownload } }
					onCancel={ () => setSelectedDownload( null ) }
					onRemove={ () => {
						removeDownload( selectedDownload );
						setSelectedDownload( null );
					} }
					onChange={ ( text: string ) => {
						setSelectedDownload( {
							...selectedDownload,
							name: text,
						} );
					} }
					onSave={ editDownloadsModalSaveHandler( selectedDownload ) }
					onUploadSuccess={ handleFileReplace }
					onUploadError={ handleUploadError }
				/>
			) }
		</div>
	);
}
