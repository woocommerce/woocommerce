/**
 * External dependencies
 */
import { Button, Popover, Card, CardBody } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import Phone from 'gridicons/dist/phone';
import classNames from 'classnames';

export const LoginQR = () => {
	// Todo: Add Jetpack installed & connected logic
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );

	const click = () => {
		setIsPopoverVisible( true );
		// Todo: recordEvent()
	};

	return (
		<div>
			<Button
				className={ classNames(
					'woocommerce-layout__activity-panel-tab',
					isPopoverVisible ? 'is-opened' : ''
				) }
				onClick={ click }
			>
				<Phone />
				{ __( 'App login', 'woocommerce' ) }
			</Button>
			{ isPopoverVisible && (
				<Popover
					focusOnMount="container"
					position="bottom center"
					onClose={ () => setIsPopoverVisible( false ) }
				>
					<Card>
						<CardBody>
							<img
								alt="QRCode"
								src="data:image/png;base64, iVBORw0KGgoAAAANSUhEUgAAAZAAAAGQAQMAAAC6caSPAAAABlBMVEX///8AAABVwtN+AAACFUlEQVR4nO3bO3KDMBDG8fWkoOQIOQpHg6P5KBwhpQsGRY9dSRA7r4nkFP9tEmT9VH2DHoAIRbWu0eXa/eV8S83zJq/avErp4i8gkB5k0YBOe/A3mapfRV4C0XHiBQTShbyEDrdAfIIt0aEtjOMSieMsEMgTyJj/pkZfm+8DgTyT+Brc9ZK7xvoq/BBICyKpUoI9iVc6zjeWChBIA5LLyMW9pV1SiHUkpSCQHuROFeLjvd3vA4G0JJbksmlPSdZGXynROg4E0oOITu3WVaf4nGSnN9jdekIgPyaz7W70ON3iWWKZ15ZWEEhbUrY8yRvVZeQU/55vsBBIW2In50NJ8l7P7NYrnBotAoH0I+UMaNb5Px4QhXG2dIOdjvM+BNKepF9d6BorJnmV6oDoej/8EEgTMuawii0C8k48NNm5Zbz7bhBIF6KVWmOC7YCoeroznMIPgbQl+kw8X8SHjxpvOZwpjafH6BBISyL6a9kd2XLUSscpSYZA2hMRm/erDZHtkiI5LhUgkF+RGMvF4mkLTnuJUjO7QiAdyKHK2jKV7sRFTp9FQCBtic3brrylpgvO9eM7vdXAEEhTov/Ur5pXb6lp3m0xsEIgfUj9Yc4y5AMi98lSAQLpSUKvULN99L3leDsI5HlkKgdEQ/3dmHkIpAdJ2a0375I/zHExvI/ehYNAWpFchyRLvsEafbSxgkD+nFDUv6t37pg7IDeGw/cAAAAASUVORK5CYII="
							></img>
							<div style={ { textAlign: 'center' } }>
								You should scan this to get free WooCommerce
								store points!
							</div>
						</CardBody>
					</Card>
				</Popover>
			) }
		</div>
	);
};
