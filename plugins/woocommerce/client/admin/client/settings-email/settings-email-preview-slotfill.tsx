/**
 * External dependencies
 */
import { createSlotFill, Spinner } from '@wordpress/components';
import { SelectControlSingleSelectionProps } from '@wordpress/components/build-types/select-control/types';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';
import { SETTINGS_SLOT_FILL_CONSTANT } from '~/settings/settings-slots';
import {
	EmailPreviewDeviceType,
	DEVICE_TYPE_DESKTOP,
} from './settings-email-preview-device-type';
import { EmailPreviewHeader } from './settings-email-preview-header';
import { EmailPreviewIframe } from './settings-email-preview-iframe';
import { EmailPreviewSend } from './settings-email-preview-send';
import { EmailPreviewType } from './settings-email-preview-type';
import { EmailCesFeedback } from './settings-email-ces-feedback';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

export type EmailTypes = NonNullable<
	SelectControlSingleSelectionProps[ 'options' ]
>;

type EmailPreviewFillProps = {
	emailTypes: EmailTypes;
	previewUrl: string;
	settingsIds: string[];
};

const wpMenuWidth = document.getElementById( 'adminmenu' )?.clientWidth || 160;
// Calculation: WP menu + email settings + email preview + padding
const FLOATING_PREVIEW_WIDTH_LIMIT = wpMenuWidth + 666 + 684 + 40;

const EmailPreviewFill: React.FC< EmailPreviewFillProps > = ( {
	emailTypes,
	previewUrl,
	settingsIds,
} ) => {
	const [ deviceType, setDeviceType ] =
		useState< string >( DEVICE_TYPE_DESKTOP );
	const isSingleEmail = emailTypes.length === 1;
	const [ emailType, setEmailType ] = useState< string >(
		isSingleEmail
			? emailTypes[ 0 ].value
			: 'WC_Email_Customer_Processing_Order'
	);
	const [ isLoading, setIsLoading ] = useState< boolean >( false );
	const [ isWide, setIsWide ] = useState< boolean >(
		! isSingleEmail && window.innerWidth > FLOATING_PREVIEW_WIDTH_LIMIT
	);
	const finalPreviewUrl = `${ previewUrl }&type=${ emailType }`;

	useEffect( () => {
		if ( isSingleEmail ) {
			return;
		}
		const handleResize = debounce( () => {
			setIsWide( window.innerWidth > FLOATING_PREVIEW_WIDTH_LIMIT );
		}, 400 );
		window.addEventListener( 'resize', handleResize );
		return () => {
			window.removeEventListener( 'resize', handleResize );
		};
	}, [ isSingleEmail ] );

	const cesQuestion = __(
		'I am able to customize my email designs to match my store’s brand.',
		'woocommerce'
	);

	return (
		<Fill>
			{ ! isWide && <h2>{ __( 'Email preview', 'woocommerce' ) }</h2> }
			<div
				className={ `wc-settings-email-preview-container ${
					isWide ? 'wc-settings-email-preview-container-floating' : ''
				}` }
			>
				<div className="wc-settings-email-preview-controls">
					{ ! isSingleEmail && (
						<EmailPreviewType
							emailTypes={ emailTypes }
							emailType={ emailType }
							setEmailType={ ( newEmailType: string ) => {
								setIsLoading( true );
								setEmailType( newEmailType );
							} }
						/>
					) }
					<div className="wc-settings-email-preview-spinner">
						{ isLoading && <Spinner /> }
					</div>
					<div style={ { flexGrow: 1 } } />
					<EmailPreviewDeviceType
						deviceType={ deviceType }
						setDeviceType={ setDeviceType }
					/>
					<EmailPreviewSend type={ emailType } />
				</div>
				<div
					className={ `wc-settings-email-preview wc-settings-email-preview-${ deviceType }` }
				>
					<EmailPreviewHeader emailType={ emailType } />
					<EmailPreviewIframe
						src={ finalPreviewUrl }
						isLoading={ isLoading }
						setIsLoading={ setIsLoading }
						settingsIds={ settingsIds }
					/>
					<div className="wc-settings-email-preview-ces-feedback">
						<EmailCesFeedback
							action="email_improvements_feedback"
							question={ cesQuestion }
						/>
					</div>
				</div>
			</div>
		</Fill>
	);
};

export const registerSettingsEmailPreviewFill = (
	isFeatureEnabled: boolean
) => {
	const slotElementId = 'wc_settings_email_preview_slotfill';
	const slotElement = document.getElementById( slotElementId );
	if ( ! slotElement ) {
		return null;
	}
	if ( ! isFeatureEnabled ) {
		slotElement.className = 'wc-settings-email-improvements-disabled';
	}
	const previewUrl = slotElement.getAttribute( 'data-preview-url' );
	if ( ! previewUrl ) {
		return null;
	}
	const emailTypesData = slotElement.getAttribute( 'data-email-types' );
	let emailTypes: EmailTypes = [];
	try {
		emailTypes = JSON.parse( emailTypesData || '' );
	} catch ( e ) {}
	const settingsIdsData = slotElement.getAttribute(
		'data-email-setting-ids'
	);
	let settingsIds: string[] = [];
	try {
		settingsIds = JSON.parse( settingsIdsData || '' );
	} catch ( e ) {}

	registerPlugin( 'woocommerce-admin-settings-email-preview', {
		scope: 'woocommerce-email-preview-settings',
		render: () => (
			<EmailPreviewFill
				settingsIds={ settingsIds }
				emailTypes={ emailTypes }
				previewUrl={ previewUrl }
			/>
		),
	} );
};
