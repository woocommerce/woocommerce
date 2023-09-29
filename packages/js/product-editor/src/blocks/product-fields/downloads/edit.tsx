/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import classnames from 'classnames';
import { createElement, Fragment } from '@wordpress/element';
import { MediaItem } from '@wordpress/media-utils';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { MediaUploader } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { UploadsBlockAttributes } from './types';
import { UploadImage } from './upload-image';

export function Edit( {
	attributes,
}: BlockEditProps< UploadsBlockAttributes > ) {
	const [ downloadable, setDownloadable ] = useEntityProp<
		Product[ 'downloadable' ]
	>( 'postType', 'product', 'downloadable' );
	const [ downloads, setDownloads ] = useEntityProp< Product[ 'downloads' ] >(
		'postType',
		'product',
		'downloads'
	);

	const blockProps = useWooBlockProps( attributes, {
		className: classnames( {
			'has-downloads': downloads.length > 0,
		} ),
	} );

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
			<MediaUploader
				label={
					<>
						<UploadImage />
						<p className="woocommerce-product-form__remove-files-drop-zone-label">
							Supported file types: PNG, JPG, PDF, PPT, DOC, MP3,
							and MP4.{ ' ' }
							<a href="#" target="_blank" rel="noreferrer">
								Learn more
							</a>
						</p>
					</>
				}
				buttonText=""
				multipleSelect={ 'add' }
				onError={ () => null }
				onUpload={ handleFileUpload }
			/>
		</div>
	);
}
