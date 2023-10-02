/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import { Button, Spinner } from '@wordpress/components';
import {
	createElement,
	Fragment,
	createInterpolateElement,
	useState,
	useEffect,
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
import { DownloadableFileItem, UploadsBlockAttributes } from './types';
import { UploadImage } from './upload-image';

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
	const [ fileItems, setFileItems ] = useState< DownloadableFileItem[] >(
		[]
	);

	useEffect( () => {
		setFileItems( ( currentItems ) => {
			const downloadsMap = downloads.reduce<
				Record< string, ProductDownload >
			>(
				( current, download ) => ( {
					...current,
					[ download.id ]: download,
				} ),
				{}
			);

			const fileItemsInDownloads = currentItems.reduce<
				DownloadableFileItem[]
			>( function keepPresentDownload( items, item ) {
				if ( item.download.id === '' ) {
					items.push( item );
					return items;
				}
				if ( item.download.id && item.download.id in downloadsMap ) {
					const download = downloadsMap[ item.download.id ];
					delete downloadsMap[ item.download.id ];
					items.push( {
						...item,
						download,
					} );
					return items;
				}
				return items;
			}, [] );

			return Object.values( downloadsMap ).reduce<
				DownloadableFileItem[]
			>( function addAbsentDownload( items, download ) {
				items.push( {
					key: String( download.id ),
					download,
				} );
				return items;
			}, fileItemsInDownloads );
		} );
	}, [ downloads ] );

	function handleFileUpload( files: MediaItem[] ) {
		const { uploadedFiles, items } = files.reduce< {
			uploadedFiles: Product[ 'downloads' ];
			items: DownloadableFileItem[];
		} >(
			( current, file, index ) => {
				const download = {
					id: file.id ? String( file.id ) : '',
					file: file.url,
					name: getFileName( file.url ),
				};
				const item = {
					key: `_${ index }`,
					download,
					uploading: ! Boolean( file.id ),
				};

				if ( download.id ) {
					current.uploadedFiles.push( download );
				}
				current.items.push( item );

				return current;
			},
			{ uploadedFiles: [], items: [] }
		);

		if ( uploadedFiles.length ) {
			if ( ! downloads.length ) {
				setDownloadable( true );
			}
			setDownloads( [ ...downloads, ...uploadedFiles ] );
		}

		setFileItems( items );
	}

	function removeHandler( fileItem: DownloadableFileItem ) {
		return function handleRemoveClick() {
			const otherDownloads = downloads.reduce< ProductDownload[] >(
				function removeDownload( others, current ) {
					if (
						String( current.id ) === String( fileItem.download.id )
					) {
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

	return (
		<div { ...blockProps }>
			{ Boolean( fileItems.length ) ? (
				<Sortable className="wp-block-woocommerce-product-downloads-field__table">
					{ fileItems.map( ( fileItem ) => {
						const nameFromUrl = getFileName(
							fileItem.download.file
						);

						return (
							<ListItem key={ String( fileItem.key ) }>
								<div className="wp-block-woocommerce-product-downloads-field__table-filename">
									<span>{ fileItem.download.name }</span>
									{ fileItem.download.name !==
										nameFromUrl && (
										<span className="wp-block-woocommerce-product-downloads-field__table-filename-description">
											{ nameFromUrl }
										</span>
									) }
								</div>

								<div className="wp-block-woocommerce-product-downloads-field__table-actions">
									{ fileItem.uploading && (
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
										disabled={ fileItem.uploading }
										onClick={ removeHandler( fileItem ) }
									/>
								</div>
							</ListItem>
						);
					} ) }
				</Sortable>
			) : (
				<MediaUploader
					label={
						<>
							<UploadImage />
							<p className="woocommerce-product-form__remove-files-drop-zone-label">
								{ createInterpolateElement(
									__(
										'Supported file types: <Types /> and more. <link>View all</link>',
										'woocommerce'
									),
									{
										Types: (
											<Fragment>
												PNG, JPG, PDF, PPT, DOC, MP3,
												MP4
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
						</>
					}
					buttonText=""
					allowedMediaTypes={ [ '*' ] }
					multipleSelect={ 'add' }
					onUpload={ handleFileUpload }
					onFileUploadChange={ handleFileUpload }
					onError={ () => null }
				/>
			) }
		</div>
	);
}
