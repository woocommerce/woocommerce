/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow, TextControl } from '@wordpress/components';

function TemplateSenderPanel() {
	return (
		<Panel className="woocommerce-email-sidebar-template-settings-sender-options">
			<PanelBody>
				<PanelRow>
					<div>
						<h2>{ __( 'Sender Options', 'woocommerce' ) }</h2>
						<p>
							{ __(
								'This is how your sender name and email address would appear in outgoing WooCommerce emails.',
								'woocommerce'
							) }
						</p>
					</div>
				</PanelRow>
				<PanelRow>
					<TextControl
						className="woocommerce-email-sidebar-template-settings-sender-options-input"
						label={ __( '“from” name', 'woocommerce' ) }
						name="from_name"
						value={ 'Store name' }
						onChange={ ( value: string ) =>
							console.log( 'value', value )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						className="woocommerce-email-sidebar-template-settings-sender-options-input"
						label={ __( '“from” email', 'woocommerce' ) }
						name="from_email"
						value={ 'sender@example.com' }
						onChange={ ( value: string ) =>
							console.log( 'value', value )
						}
					/>
				</PanelRow>
			</PanelBody>
		</Panel>
	);
}

export { TemplateSenderPanel };
