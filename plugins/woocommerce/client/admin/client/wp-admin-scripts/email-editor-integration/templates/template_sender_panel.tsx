/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelRow, TextControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useCallback, useRef } from '@wordpress/element';

type TemplateSenderPanelProps = {
	debouncedRecordEvent: (
		name: string,
		data?: Record< string, unknown >
	) => void;
};

function TemplateSenderPanel( {
	debouncedRecordEvent,
}: TemplateSenderPanelProps ) {
	const [ woocommerce_template_data, setWoocommerceTemplateData ] =
		useEntityProp( 'postType', 'wp_template', 'woocommerce_data' );
	const emailInputRef = useRef< HTMLInputElement >( null );

	const handleFromNameChange = useCallback(
		( value: string ) => {
			setWoocommerceTemplateData( {
				...woocommerce_template_data,
				sender_settings: {
					...woocommerce_template_data?.sender_settings,
					from_name: value,
				},
			} );
			debouncedRecordEvent( 'email_from_name_input_updated', { value } );
		},
		[ woocommerce_template_data, setWoocommerceTemplateData ]
	);
	const handleFromAddressChange = useCallback(
		( value: string ) => {
			setWoocommerceTemplateData( {
				...woocommerce_template_data,
				sender_settings: {
					...woocommerce_template_data?.sender_settings,
					from_address: value,
				},
			} );

			// Use HTML5 validation
			if ( emailInputRef.current ) {
				emailInputRef.current.checkValidity();
				emailInputRef.current.reportValidity();
			}
			debouncedRecordEvent( 'email_from_address_input_updated', {
				value,
			} );
		},
		[ woocommerce_template_data, setWoocommerceTemplateData ]
	);

	return (
		<>
			<h2>{ __( 'Sender Options', 'woocommerce' ) }</h2>
			<PanelRow>
				<p>
					{ __(
						'This is how your sender name and email address would appear in outgoing WooCommerce emails.',
						'woocommerce'
					) }
				</p>
			</PanelRow>

			<PanelRow>
				<TextControl
					className="woocommerce-email-sidebar-template-settings-sender-options-input"
					/* translators: Label for the sender's `“from” name` in email settings. */
					label={ __( '“from” name', 'woocommerce' ) }
					name="from_name"
					type="text"
					value={
						woocommerce_template_data?.sender_settings?.from_name ||
						''
					}
					onChange={ handleFromNameChange }
				/>
			</PanelRow>

			<PanelRow>
				<TextControl
					ref={ emailInputRef }
					className="woocommerce-email-sidebar-template-settings-sender-options-input"
					/* translators: Label for the sender's `“from” email` in email settings. */
					label={ __( '“from” email', 'woocommerce' ) }
					name="from_email"
					type="email"
					value={
						woocommerce_template_data?.sender_settings
							?.from_address || ''
					}
					onChange={ handleFromAddressChange }
					required
				/>
			</PanelRow>
		</>
	);
}

export { TemplateSenderPanel };
