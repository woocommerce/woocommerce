/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import { Button, Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	createElement,
	Fragment,
	createInterpolateElement,
} from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import { MediaItem } from '@wordpress/media-utils';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { ListItem, MediaUploader, Sortable } from '@woocommerce/components';
import { Product, ProductDownload } from '@woocommerce/data';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { UploadsBlockAttributes } from './types';
import { UploadImage } from './upload-image';
import { DownloadsMenu } from './downloads-menu';

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

export function Edit( {
	attributes,
	clientId,
}: BlockEditProps< UploadsBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { children, ...innerBlockProps } = useInnerBlocksProps( blockProps, {
		templateLock: 'all',
		attributes: { isOpen: true },
	} );

	const [ id ] = useEntityProp< Product[ 'id' ] >(
		'postType',
		'product',
		'id'
	);

	const [ , setDownloadable ] = useEntityProp< Product[ 'downloadable' ] >(
		'postType',
		'product',
		'downloadable'
	);

	const [ downloads, setDownloads ] = useEntityProp< Product[ 'downloads' ] >(
		'postType',
		'product',
		'downloads'
	);

	const { allowedMimeTypes } = useSelect( ( select ) => {
		const { getEditorSettings } = select( 'core/editor' );
		return getEditorSettings();
	} );

	const [ dialogClientId ] = useSelect(
		( select ) => {
			const { getBlockOrder } = select( 'core/block-editor' );
			return getBlockOrder( clientId );
		},
		[ clientId ]
	);

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const allowedTypes = allowedMimeTypes
		? Object.values( allowedMimeTypes )
		: [];

	const { createErrorNotice } = useDispatch( 'core/notices' );

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
			if ( ! downloads.length ) {
				setDownloadable( true );
			}

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

	function removeHandler( download: ProductDownload ) {
		return function handleRemoveClick() {
			const otherDownloads = downloads.reduce< ProductDownload[] >(
				function removeDownload( others, current ) {
					if ( current.file === download.file ) {
						return others;
					}
					return [ ...others, stringifyEntityId( current ) ];
				},
				[]
			);

			if ( ! otherDownloads.length ) {
				setDownloadable( false );
			}

			setDownloads( otherDownloads );
		};
	}

	function handleUploadError( error: unknown ) {
		createErrorNotice(
			typeof error === 'string'
				? error
				: __( 'There was an error uploading files', 'woocommerce' )
		);
	}

	return (
		<div { ...innerBlockProps }>
			<div className="wp-block-woocommerce-product-downloads-field__header">
				<Button
					variant="tertiary"
					onClick={ () => {
						updateBlockAttributes( dialogClientId, {
							isOpen: true,
							onClose() {
								alert( 'Closeing modal!!!' );
							},
						} );
					} }
				>
					{ __( 'Manage limits', 'woocommerce' ) }
				</Button>

				<DownloadsMenu
					allowedTypes={ allowedTypes }
					onUploadSuccess={ handleFileUpload }
					onUploadError={ handleUploadError }
				/>
			</div>

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
				/>

				{ Boolean( downloads.length ) && (
					<Sortable className="wp-block-woocommerce-product-downloads-field__table">
						{ downloads.map( ( download ) => {
							const nameFromUrl = getFileName( download.file );
							const isUploading =
								download.file.startsWith( 'blob' );

							return (
								<ListItem key={ download.file }>
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

			{ children }
		</div>
	);
}
