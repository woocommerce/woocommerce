/**
 * External dependencies
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const LinkModal = ( { onInsert, isOpened, closeCallback, tag } ) => {
	const [ linkText, setLinkText ] = useState(
		__( 'Link', __i18n_text_domain__ )
	);
	if ( ! isOpened ) {
		return null;
	}

	return (
		<Modal
			size="small"
			title={ __( 'Insert Link', __i18n_text_domain__ ) }
			onRequestClose={ closeCallback }
			className="woocommerce-personalization-tags-modal"
		>
			<TextControl
				label={ __( 'Link Text', __i18n_text_domain__ ) }
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
				{ __( 'Insert', __i18n_text_domain__ ) }
			</Button>
		</Modal>
	);
};

export { LinkModal };
