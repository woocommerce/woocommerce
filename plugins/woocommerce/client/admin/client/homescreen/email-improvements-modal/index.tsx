/**
 * External dependencies
 */
import { Guide, Button, Icon } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { updateQueryString } from '@woocommerce/navigation';
import { useSearchParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import './style.scss';
import Illustration from './email-improvements.png';

interface EmailImprovementsModalProps {
	type: 'enabled' | 'try';
}

export const EmailImprovementsModal = ( {
	type,
}: EmailImprovementsModalProps ) => {
	const [ guideIsOpen, setGuideIsOpen ] = useState( false );
	const [ searchParams ] = useSearchParams();

	let title = __( 'Your emails have a new look!', 'woocommerce' );

	if ( type === 'try' ) {
		title = __(
			'Introducing new email templates for your store!',
			'woocommerce'
		);
	}

	useEffect( () => {
		if ( searchParams.get( 'emailImprovementsModal' ) ) {
			setGuideIsOpen( true );
		} else {
			setGuideIsOpen( false );
		}
	}, [ searchParams ] );

	const clearQueryString = () => {
		updateQueryString(
			{
				emailImprovementsModal: undefined,
			},
			undefined,
			Object.fromEntries( searchParams.entries() )
		);
	};

	const onFinish = () => {
		clearQueryString();
		setGuideIsOpen( false );
	};

	return (
		<>
			{ guideIsOpen && (
				<Guide
					onFinish={ onFinish }
					contentLabel=""
					className="woocommerce__email-improvements-modal"
					pages={ [
						{
							content: (
								<div className="email-improvements-modal-layout">
									<div className="email-improvements-modal-content">
										<div className="email-improvements-modal-content-image">
											<img
												src={ Illustration }
												alt=""
												width={ 250 }
												height={ 240 }
											/>
										</div>
										<div>
											<h1>{ title }</h1>
											<p>
												{ __(
													'We’re excited to introduce our refreshed email templates designed to enhance your customers shopping experience. Preview and customize your emails in Settings.',
													'woocommerce'
												) }
											</p>
										</div>
										<div className="email-improvements-modal-footer">
											<Button
												variant="tertiary"
												href="https://developer.woocommerce.com/2025/04/09/woocommerce-9-8-modernized-designs-and-email-previews/"
												target="_blank"
											>
												{ __(
													'Learn more',
													'woocommerce'
												) }
											</Button>
											{ type === 'try' ? (
												<Button
													variant="primary"
													href="?page=wc-settings&tab=email&try-new-templates"
												>
													{ __(
														'Try new templates',
														'woocommerce'
													) }
												</Button>
											) : (
												<Button
													variant="primary"
													href="?page=wc-settings&tab=email"
												>
													{ __(
														'Manage emails',
														'woocommerce'
													) }
												</Button>
											) }
										</div>
									</div>
									<Button
										variant="tertiary"
										className="email-improvements-modal-close-button"
										label={ __( 'Close', 'woocommerce' ) }
										icon={
											<Icon
												icon={ closeSmall }
												viewBox="6 4 12 14"
											/>
										}
										iconSize={ 24 }
										onClick={ onFinish }
									></Button>
								</div>
							),
						},
					] }
				/>
			) }
		</>
	);
};
