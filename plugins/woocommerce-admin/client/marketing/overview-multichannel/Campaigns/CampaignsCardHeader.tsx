/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, CardHeader, Modal, Icon } from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { CardHeaderTitle } from '~/marketing/components';

export const CampaignsCardHeader = () => {
	const [ open, setOpen ] = useState( false );
	const [ collapsed, setCollapsed ] = useState( true );
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
			{ open && (
				<Modal
					className="woocommerce-marketing-create-campaign-modal"
					title={ __( 'Create a new campaign', 'woocommerce' ) }
					onRequestClose={ closeModal }
				>
					<div className="woocommerce-marketing-create-campaign-modal__question-label">
						{ __(
							'Where would you like to promote your products?',
							'woocommerce'
						) }
					</div>
					{ /* TODO: list of campaign type here. */ }
					<Button
						variant="link"
						onClick={ () => setCollapsed( ! collapsed ) }
					>
						{ __(
							'Add channels for other campaign types',
							'woocommerce'
						) }
						<Icon
							icon={ collapsed ? chevronDown : chevronUp }
							size={ 24 }
						/>
					</Button>
					{ /* TODO: list of recommended channels here. */ }
				</Modal>
			) }
		</CardHeader>
	);
};
