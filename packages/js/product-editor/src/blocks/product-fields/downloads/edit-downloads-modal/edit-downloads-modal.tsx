/**
 * External dependencies
 */
import { ChangeEvent } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
import { trash } from '@wordpress/icons';
import { useDispatch, useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { ImageGallery, ImageGalleryItem } from '@woocommerce/components';
import { uploadMedia } from '@wordpress/media-utils';
import {
	Button,
	FormFileUpload,
	Modal,
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditDownloadsModalProps } from './types';
import { UnionIcon } from './union-icon';

export interface Image {
	id: number;
	src: string;
	name: string;
	alt: string;
}

export const EditDownloadsModal: React.FC< EditDownloadsModalProps > = ( {
	downloableItem,
	maxUploadFileSize = 10000000,
	onCancel,
	onChange,
	onRemove,
	onSave,
	onUploadSuccess,
	onUploadError,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isCopingToClipboard, setIsCopingToClipboard ] =
		useState< boolean >( false );
	const [ isFileUploading, setIsFileUploading ] =
		useState< boolean >( false );

	const { allowedMimeTypes } = useSelect( ( select ) => {
		const { getEditorSettings } = select( 'core/editor' );
		return getEditorSettings();
	} );

	const allowedTypes = allowedMimeTypes
		? Object.values( allowedMimeTypes )
		: [];

	const { id = 0, file = '', name = '' } = downloableItem;

	const onCopySuccess = () => {
		createNotice(
			'success',
			__( 'URL copied successfully.', 'woocommerce' )
		);
	};

	const isImage = ( filename = '' ) => {
		if ( ! filename ) return;
		const imageExtensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];
		const fileExtension = (
			filename.split( '.' ).pop() || ''
		).toLowerCase();
		return imageExtensions.includes( fileExtension );
	};

	async function copyTextToClipboard( text: string ) {
		if ( 'clipboard' in navigator ) {
			await navigator.clipboard.writeText( text );
		} else {
			const textArea = document.createElement( 'textarea' );
			textArea.value = text;
			document.body.appendChild( textArea );
			textArea.select();
			document.execCommand( 'copy' );
			document.body.removeChild( textArea );
		}
		await onCopySuccess();
	}

	async function handleCopyToClipboard() {
		recordEvent( 'product_downloads_modal_copy_url_to_clipboard' );
		setIsCopingToClipboard( true );
		await copyTextToClipboard( file );
		setIsCopingToClipboard( false );
	}

	async function handleFormFileUploadChange(
		event: ChangeEvent< HTMLInputElement >
	) {
		setIsFileUploading( true );
		const filesList = event.currentTarget.files as FileList;
		await uploadMedia( {
			allowedTypes,
			filesList,
			maxUploadFileSize,
			onFileChange: onUploadSuccess,
			onError: onUploadError,
		} );
		setIsFileUploading( false );
	}

	return (
		<Modal
			title={ sprintf(
				/* translators: %s is the attribute name */
				__( 'Edit %s', 'woocommerce' ),
				name
			) }
			onRequestClose={ (
				event:
					| React.KeyboardEvent< Element >
					| React.MouseEvent< Element >
					| React.FocusEvent< Element >
			) => {
				if ( ! event.isPropagationStopped() && ! isCopingToClipboard ) {
					recordEvent( 'product_downloads_modal_cancel' );
					onCancel();
				}
			} }
			className="woocommerce-edit-downloads-modal"
		>
			<div className="woocommerce-edit-downloads-modal__preview">
				{ isImage( file ) && (
					<ImageGallery allowDragging={ false } columns={ 1 }>
						<ImageGalleryItem
							key={ id }
							alt={ name }
							src={ file }
							id={ `${ id }` }
							isCover={ false }
						/>
					</ImageGallery>
				) }
				<FormFileUpload
					onChange={ handleFormFileUploadChange }
					render={ ( { openFileDialog } ) => (
						<div>
							<p>{ name }</p>
							<Button
								onClick={ openFileDialog }
								isBusy={ isFileUploading }
								disabled={ isFileUploading }
							>
								{ __( 'Replace', 'woocommerce' ) }
							</Button>
						</div>
					) }
				/>
			</div>
			<BaseControl
				id={ 'file-name-help' }
				className="woocommerce-edit-downloads-modal__file-name"
				help={ __(
					'Your customers will see this on the thank-you page and in their order confirmation email.',
					'woocommerce'
				) }
			>
				<InputControl
					id={ 'file-name' }
					label={ __( 'FILE NAME', 'woocommerce' ) }
					name={ 'file-name' }
					value={ name || '' }
					onChange={ onChange }
				/>
			</BaseControl>

			<div className="woocommerce-edit-downloads-modal__file-url">
				<InputControl
					disabled
					id={ 'file-url' }
					label={ __( 'FILE URL', 'woocommerce' ) }
					name={ 'file-url' }
					value={ file || '' }
					suffix={
						<Button
							icon={ <UnionIcon /> }
							onClick={ handleCopyToClipboard }
						/>
					}
				/>
			</div>
			<div className="woocommerce-edit-downloads-modal__buttons">
				<div className="woocommerce-edit-downloads-modal__buttons-left">
					<Button
						icon={ trash }
						isDestructive
						variant="tertiary"
						label={ __( 'Delete', 'woocommerce' ) }
						onClick={ () => {
							recordEvent( 'product_downloads_modal_delete' );
							onRemove();
						} }
					>
						{ __( 'Delete file', 'woocommerce' ) }
					</Button>
				</div>
				<div className="woocommerce-edit-downloads-modal__buttons-right">
					<Button
						label={ __( 'Cancel', 'woocommerce' ) }
						onClick={ () => {
							recordEvent( 'product_downloads_modal_cancel' );
							onCancel();
						} }
						variant="tertiary"
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						label={ __( 'Update', 'woocommerce' ) }
						onClick={ () => {
							recordEvent( 'product_downloads_modal_update' );
							onSave();
						} }
						variant="primary"
					>
						{ __( 'Update', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};
