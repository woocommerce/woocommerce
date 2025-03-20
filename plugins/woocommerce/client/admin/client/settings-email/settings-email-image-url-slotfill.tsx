/**
 * External dependencies
 */
import { createSlotFill } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import { SETTINGS_SLOT_FILL_CONSTANT } from '~/settings/settings-slots';
import { ExistingImage } from './settings-email-image-url-existing-image';
import { NewImage } from './settings-email-image-url-new-image';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

type EmailImageUrlFillProps = {
	inputId: string;
	initImageUrl: string;
};

const EmailImageUrlFill: React.FC< EmailImageUrlFillProps > = ( {
	inputId,
	initImageUrl,
} ) => {
	const [ imageUrl, setImageUrl ] = useState< string >( initImageUrl );
	const hasImage = imageUrl !== '';
	return (
		<Fill>
			{ ! hasImage && (
				<NewImage inputId={ inputId } setImageUrl={ setImageUrl } />
			) }
			{ hasImage && (
				<ExistingImage
					inputId={ inputId }
					imageUrl={ imageUrl }
					setImageUrl={ setImageUrl }
				/>
			) }
		</Fill>
	);
};

export const registerSettingsEmailImageUrlFill = () => {
	const slot_element_id = 'wc_settings_email_image_url_slotfill';
	const slot_element = document.getElementById( slot_element_id );
	const image_url = slot_element?.getAttribute( 'data-image-url' ) || '';
	const input_id = slot_element?.getAttribute( 'data-id' ) || '';
	registerPlugin( 'woocommerce-admin-settings-email-image-url', {
		scope: 'woocommerce-email-image-url-settings',
		render: () => (
			<EmailImageUrlFill
				inputId={ input_id }
				initImageUrl={ image_url }
			/>
		),
	} );
};
