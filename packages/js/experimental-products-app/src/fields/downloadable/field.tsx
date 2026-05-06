/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import { FormFileUpload } from '@wordpress/components';

import type { Field } from '@wordpress/dataviews';

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
	getValue: ( { item } ) => item.downloadable,
	Edit: ( { data, onChange } ) => {
		const downloads = ( data.downloads ?? [] ) as Array<
			UploadedItem | ItemToUpload
		>;

		const filesToUpload = downloads.filter( isItemToUpload );
		const uploadedFiles = downloads.filter( isUploadedItem );

		const handleRemoveDownload = ( fileId: string | number ) => {
			onChange( {
				downloads: downloads.filter(
					( download ) => download.file !== fileId
				),
			} );
		};

		const handleAddDownload = ( file: File ) => {
			const objectUrl = URL.createObjectURL( file );
			onChange( {
				downloads: [
					...downloads,
					{
						file: objectUrl,
						name: file.name,
						type: file.type,
					},
				],
			} );
		};

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
