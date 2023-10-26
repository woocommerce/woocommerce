/**
 * External dependencies
 */
import { createElement, createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, help as helpIcon } from '@wordpress/icons';
import { Tooltip } from '@wordpress/components';

interface LabelProps {
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
			{ required
				? createInterpolateElement(
						__( '<label/> <required/>', 'woocommerce' ),
						{
							label: <span>{ label }</span>,
							required: (
								<span className="woocommerce-product-form-label__required">
									{ /* translators: field 'required' indicator */ }
									{ __( '*', 'woocommerce' ) }
								</span>
							),
						}
				  )
				: label }

			{ tooltip && (
				<Tooltip
					text={ <span>{ tooltip }</span> }
					position="top center"
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore Incorrect types.
					className={ 'woocommerce-product-form-label__tooltip' }
					delay={ 0 }
				>
					<span className="woocommerce-product-form-label__icon">
						<Icon icon={ helpIcon } size={ 18 } fill="#949494" />
					</span>
				</Tooltip>
			) }
		</div>
	);
};
