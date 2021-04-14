/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { getQuery, updateQueryString } from '@woocommerce/navigation';
import interpolateComponents from 'interpolate-components';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import UsageModal from '~/profile-wizard/steps/usage-modal';

const WCPayUsageModal = () => {
	const query = getQuery();
	const shouldDisplayModal = query[ 'wcpay-connection-success' ] === '1';
	const [ isOpen, setIsOpen ] = useState( shouldDisplayModal );

	if ( ! isOpen ) {
		return null;
	}

	const closeModal = () => {
		setIsOpen( false );
		updateQueryString( { 'wcpay-connection-success': undefined } );
	};

	const title = __(
		'Help us build a better WooCommerce Payments experience',
		'woocommerce-admin'
	);
	const trackingMessage = interpolateComponents( {
		mixedString: __(
			'By agreeing to share non-sensitive {{link}}usage data{{/link}}, you’ll help us improve features and optimize the WooCommerce Payments experience. You can opt out at any time.',
			'woocommerce-admin'
		),
		components: {
			link: (
				<Link
					href="https://woocommerce.com/usage-tracking"
					target="_blank"
					type="external"
				/>
			),
		},
	} );

	return (
		<UsageModal
			isDismissible={ false }
			title={ title }
			message={ trackingMessage }
			acceptActionText={ __( 'I agree', 'woocommerce-admin' ) }
			dismissActionText={ __( 'No thanks', 'woocommerce-admin' ) }
			onContinue={ closeModal }
			onClose={ closeModal }
		/>
	);
};

export default WCPayUsageModal;
