/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, CardHeader } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CardHeaderTitle } from '~/marketing/components';
import { CreateNewCampaignModal } from './CreateNewCampaignModal';

export const CampaignsCardHeader = () => {
	const [ open, setOpen ] = useState( false );

	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	return (
		<CardHeader>
			<CardHeaderTitle>
				{ __( 'Campaigns', 'woocommerce' ) }
			</CardHeaderTitle>
			<Button variant="secondary" onClick={ openModal }>
				{ __( 'Create new campaign', 'woocommerce' ) }
			</Button>
			{ open && <CreateNewCampaignModal onRequestClose={ closeModal } /> }
		</CardHeader>
	);
};
