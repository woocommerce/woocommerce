/**
 * External dependencies
 */
import React, { useState, useEffect } from '@wordpress/element';
import { Guide } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import interpolateComponents from '@automattic/interpolate-components';
import classNames from 'classnames';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { LineChartIllustration } from '../welcome-modal/illustrations/line-chart';
import { PageContent } from '../welcome-modal/page-content';
import './style.scss';

const page = {
	image: <LineChartIllustration />,
	content: (
		<PageContent
			title={ __(
				'Welcome to your new store management experience',
				'woocommerce'
			) }
			body={ interpolateComponents( {
				mixedString: __(
					"We've designed your navigation and home screen to help you focus on the things that matter most in managing your online store. {{link}}Learn more{{/link}} about these changes – or explore on your own.",
					'woocommerce'
				),
				components: {
					link: (
						<Link
							href="https://wordpress.com/support/new-woocommerce-experience-on-wordpress-dot-com/"
							type="external"
							target="_blank"
						/>
					),
				},
			} ) }
		/>
	),
};

export default function WelcomeFromCalypsoModal( { onClose } ) {
	const [ guideIsOpen, setGuideIsOpen ] = useState( true );

	useEffect( () => {
		recordEvent( 'welcome_from_calypso_modal_open' );
	}, [] );

	if ( ! guideIsOpen ) {
		return null;
	}

	const guideClassNames = classNames(
		'woocommerce__welcome-modal',
		'woocommerce__welcome-from-calypso-modal'
	);

	return (
		<Guide
			onFinish={ () => {
				if ( onClose ) {
					onClose();
				}

				setGuideIsOpen( false );
				recordEvent( 'welcome_from_calypso_modal_close' );
			} }
			className={ guideClassNames }
			finishButtonText={ __( "Let's go", 'woocommerce' ) }
			pages={ [ page ] }
		/>
	);
}
