/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import { useState, createElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME,
	ProductTag,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';
import { CreateTagModalProps } from './types';

export const CreateTagModal: React.FC< CreateTagModalProps > = ( {
	initialTagName,
	onCancel,
	onCreate,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isCreating, setIsCreating ] = useState( false );
	const { createProductTag, invalidateResolutionForStoreSelector } =
		useDispatch( EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME );
	const [ tagName, setTagName ] = useState( initialTagName || '' );

	const onSave = async () => {
		recordEvent( 'product_tag_add', {
			source: TRACKS_SOURCE,
		} );
		setIsCreating( true );
		try {
			const newTag: ProductTag = await createProductTag( {
				name: tagName,
			} );
			invalidateResolutionForStoreSelector( 'getProductTags' );
			setIsCreating( false );
			onCreate( newTag );
		} catch ( e ) {
			createNotice(
				'error',
				__( 'Failed to create tag.', 'woocommerce' )
			);
			setIsCreating( false );
			onCancel();
		}
	};

	return (
		<Modal
			title={ __( 'Create tag', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-create-new-tag-modal"
		>
			<div className="woocommerce-create-new-tag-modal__wrapper">
				<TextControl
					label={ __( 'Name', 'woocommerce' ) }
					name="Tops"
					value={ tagName }
					onChange={ setTagName }
				/>
				<div className="woocommerce-create-new-tag-modal__buttons">
					<Button
						isSecondary
						onClick={ () => onCancel() }
						disabled={ isCreating }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						isPrimary
						disabled={ tagName.length === 0 || isCreating }
						isBusy={ isCreating }
						onClick={ () => {
							onSave();
						} }
					>
						{ __( 'Save', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};
