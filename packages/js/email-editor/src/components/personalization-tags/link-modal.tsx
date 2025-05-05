/**
 * External dependencies
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

const LinkModal = ( { onInsert, isOpened, closeCallback, tag } ) => {
	const [ linkText, setLinkText ] = useState( __( 'Link', 'woocommerce' ) );
	if ( ! isOpened ) {
		return null;
	}

	return (
		<Modal
			size="small"
			title={ __( 'Insert Link', 'woocommerce' ) }
			onRequestClose={ closeCallback }
			className="woocommerce-personalization-tags-modal"
		>
			<TextControl
				label={ __( 'Link Text', 'woocommerce' ) }
				value={ linkText }
				onChange={ setLinkText }
			/>
			<Button
				isPrimary
				onClick={ () => {
					if ( onInsert ) {
						onInsert( tag.token, linkText );
					}
				} }
			>
				{ __( 'Insert', 'woocommerce' ) }
			</Button>
		</Modal>
	);
};

export { LinkModal };
