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

export function Edit( {
	attributes,
}: BlockEditProps< UploadsBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
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

	const allowedTypes = allowedMimeTypes
		? Object.values( allowedMimeTypes )
		: [];

	const { createErrorNotice } = useDispatch( 'core/notices' );

	function handleFileUpload( files: MediaItem | MediaItem[] ) {
		if ( ! Array.isArray( files ) ) return;

		const uploadedFiles = files
			.filter(
				( file ) =>
					! file.id ||
					! downloads.some(
						( download ) =>
							download.id === String( file.id ) ||
							download.file === file.url
					)
			)
			.map( ( file ) => ( {
				id: file.id ? String( file.id ) : '',
				file: file.url,
				name: getFileName( file.url ),
			} ) );

		if ( uploadedFiles.length ) {
			if ( ! downloads.length ) {
				setDownloadable( true );
			}
			setDownloads( [ ...downloads, ...uploadedFiles ] );
		}
	}

	function removeHandler( download: ProductDownload ) {
		return function handleRemoveClick() {
			const otherDownloads = downloads.reduce< ProductDownload[] >(
				function removeDownload( others, current ) {
					if ( current.file === download.file ) {
						return others;
					}
					return [ ...others, current ];
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
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-product-downloads-field__header">
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
		</div>
	);
}
