/**
 * External dependencies
 */
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, postContent } from '@wordpress/icons';
import { applyFilters } from '@wordpress/hooks';

const TypeInfoIcon = applyFilters(
	'woocommerce_email_editor_sidebar_email_type_info_icon',
	() => <Icon icon={ postContent } />
) as () => JSX.Element;

const TypeInfoContent = applyFilters(
	'woocommerce_email_editor_sidebar_email_type_info_content',
	() => (
		<>
			<h2>{ __( 'Email content', 'woocommerce' ) }</h2>
			<span>
				{ __(
					'This block represents the main content of your email, such as the invoice or order details. When the email is sent, it will be replaced with the actual email content.',
					'woocommerce'
				) }
			</span>
		</>
	)
) as () => JSX.Element;

export function EmailTypeInfo() {
	return (
		<>
			<Panel className="woocommerce-email-sidebar-email-type-info">
				<PanelBody>
					<PanelRow>
						<span className="woocommerce-email-type-info-icon">
							<TypeInfoIcon />
						</span>
						<div className="woocommerce-email-type-info-content">
							<TypeInfoContent />
						</div>
					</PanelRow>
				</PanelBody>
			</Panel>
		</>
	);
}
