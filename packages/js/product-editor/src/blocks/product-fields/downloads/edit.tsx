/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import classnames from 'classnames';
import {
	createElement,
	Fragment,
	createInterpolateElement,
} from '@wordpress/element';
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

function getFileName( download: ProductDownload ) {
	if ( download.name ) {
		return download.name;
	}

	const [ name ] = download.file.split( '/' ).reverse();
	return name;
}

export function Edit( {
	attributes,
}: BlockEditProps< UploadsBlockAttributes > ) {
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

	const blockProps = useWooBlockProps( attributes );

	function handleFileUpload( files: MediaItem[] ) {
		const uploadedFiles = files
			.filter( ( file ) => file.id )
			.map( ( file ) => ( {
				id: String( file.id ),
				file: file.url,
				name: '',
			} ) );

		if ( uploadedFiles.length ) {
			if ( ! downloads.length ) {
				setDownloadable( true );
			}
			setDownloads( [ ...downloads, ...uploadedFiles ] );
		}
	}

	return (
		<div { ...blockProps }>
			{ Boolean( downloads.length ) ? (
				<Sortable className="wp-block-woocommerce-product-downloads-field__table">
					{ downloads.map( ( download ) => (
						<ListItem key={ String( download.id ) }>
							<span>{ getFileName( download ) }</span>
						</ListItem>
					) ) }
				</Sortable>
			) : (
				<MediaUploader
					label={
						<>
							<UploadImage />
							<p className="woocommerce-product-form__remove-files-drop-zone-label">
								{ createInterpolateElement(
									__(
										'Supported file types: <Types /> and <LastType/>. <link>Learn more</link>'
									),
									{
										Types: (
											<Fragment>
												PNG, JPG, PDF, PPT, DOC, MP3
											</Fragment>
										),
										LastType: <Fragment>MP4</Fragment>,
										link: (
											<a
												href="#"
												target="_blank"
												rel="noreferrer"
											/>
										),
									}
								) }
							</p>
						</>
					}
					buttonText=""
					multipleSelect={ 'add' }
					onError={ () => null }
					onUpload={ handleFileUpload }
				/>
			) }
		</div>
	);
}
