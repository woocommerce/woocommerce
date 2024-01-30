/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, help as helpIcon } from '@wordpress/icons';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';

export interface LabelProps {
	label: string;
	required?: boolean;
	tooltip?: string;
}

export const Label: React.FC< LabelProps > = ( {
	label,
	required,
	tooltip,
} ) => {
	return (
		<div className="woocommerce-product-form-label__label">
			{ label }
			{ required && (
				<span className="woocommerce-product-form-label__required">
					{ /* translators: field 'required' indicator */ }
					{ __( '*', 'woocommerce' ) }
				</span>
			) }

			{ tooltip && (
				<Tooltip
					text={
						<span
							dangerouslySetInnerHTML={ sanitizeHTML( tooltip ) }
						></span>
					}
					position="top center"
					className="woocommerce-product-form-label__tooltip"
				>
					<span className="woocommerce-product-form-label__icon">
						<Icon icon={ helpIcon } size={ 18 } fill="#949494" />
					</span>
				</Tooltip>
			) }
		</div>
	);
};
