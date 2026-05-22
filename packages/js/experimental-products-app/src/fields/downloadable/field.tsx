/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, CheckboxControl, TextControl } from '@wordpress/components';
import { useCallback, useMemo } from '@wordpress/element';
import type { Field } from '@wordpress/dataviews';
import { MediaUpload } from '@wordpress/media-utils';
import type { Attachment } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

type ProductDownload = ProductEntityRecord[ 'downloads' ][ 0 ];

const fieldDefinition = {
	type: 'boolean',
	label: __( 'Downloadable', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

function generateDownloadId() {
	return String( Date.now() );
}

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	type: 'boolean',
	isVisible: ( item ) =>
		item.downloadable === true && item.type !== 'variable',
	getValue: ( { item } ) => item.downloadable,
	Edit: ( { data, onChange } ) => {
		const downloads: ProductDownload[] = useMemo(
			() =>
				data.downloads?.length
					? data.downloads
					: [ { id: generateDownloadId(), name: '', file: '' } ],
			[ data.downloads ]
		);

		const updateDownload = useCallback(
			( index: number, changes: Partial< ProductDownload > ) => {
				const updated = [ ...downloads ];
				updated[ index ] = { ...updated[ index ], ...changes };
				onChange( { downloads: updated } );
			},
			[ downloads, onChange ]
		);

		const addDownload = useCallback( () => {
			onChange( {
				downloads: [
					...downloads,
					{ id: generateDownloadId(), name: '', file: '' },
				],
			} );
		}, [ downloads, onChange ] );

		const removeDownload = useCallback(
			( index: number ) => {
				onChange( {
					downloads: downloads.filter( ( _, i ) => i !== index ),
				} );
			},
			[ downloads, onChange ]
		);

		const limitDownloads = ( data.download_limit ?? -1 ) !== -1;
		const expireDownloads = ( data.download_expiry ?? -1 ) !== -1;

		return (
			<div className="woocommerce-fields-field__downloadable">
				{ downloads.map( ( download, index ) => (
					<div
						key={ download.id || index }
						className="woocommerce-fields-field__downloadable-file"
					>
						<div className="woocommerce-fields-field__downloadable-url-row">
							<TextControl
								__nextHasNoMarginBottom
								label={ __( 'URL', 'woocommerce' ) }
								value={ download.file }
								placeholder="https://"
								onChange={ ( file ) =>
									updateDownload( index, { file } )
								}
							/>
							<MediaUpload
								onSelect={ ( attachment: Attachment ) => {
									updateDownload( index, {
										file: String( attachment.url ?? '' ),
										name:
											download.name ||
											String(
												attachment.title ??
													attachment.alt ??
													''
											),
									} );
								} }
								allowedTypes={ [
									'application',
									'video',
									'audio',
									'image',
								] }
								render={ ( { open }: { open: () => void } ) => (
									<Button
										variant="secondary"
										onClick={ open }
										className="woocommerce-fields-field__downloadable-choose-file"
									>
										{ __( 'Choose file', 'woocommerce' ) }
									</Button>
								) }
							/>
						</div>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Name', 'woocommerce' ) }
							value={ download.name }
							onChange={ ( name ) =>
								updateDownload( index, { name } )
							}
						/>
						{ downloads.length > 1 && (
							<Button
								variant="link"
								isDestructive
								onClick={ () => removeDownload( index ) }
								className="woocommerce-fields-field__downloadable-remove"
							>
								{ __( 'Remove', 'woocommerce' ) }
							</Button>
						) }
					</div>
				) ) }
				<Button
					variant="link"
					onClick={ addDownload }
					className="woocommerce-fields-field__downloadable-add"
				>
					{ __( '+ Add file', 'woocommerce' ) }
				</Button>
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __(
						'Limit downloads per customer',
						'woocommerce'
					) }
					checked={ limitDownloads }
					onChange={ ( checked ) =>
						onChange( { download_limit: checked ? 1 : -1 } )
					}
				/>
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Expire download link', 'woocommerce' ) }
					checked={ expireDownloads }
					onChange={ ( checked ) =>
						onChange( { download_expiry: checked ? 1 : -1 } )
					}
				/>
			</div>
		);
	},
};
