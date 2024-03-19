/**
 * External dependencies
 */
import { createElement, createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, help as helpIcon } from '@wordpress/icons';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';

export interface LabelProps {
	id?: string;
	label: string;
	required?: boolean;
	note?: string;
	tooltip?: string;
}

export const Label: React.FC< LabelProps > = ( {
	id,
	label,
	required,
	tooltip,
	note,
} ) => {
	let labelElement: JSX.Element | string = label;

	if ( required ) {
		if ( note?.length ) {
			labelElement = createInterpolateElement(
				__( '<label/> <note /> <required/>', 'woocommerce' ),
				{
					label: <span>{ label }</span>,
					note: (
						<span className="woocommerce-product-form-label__note">
							{ note }
						</span>
					),
					required: (
						<span className="woocommerce-product-form-label__required">
							{ /* translators: field 'required' indicator */ }
							{ __( '*', 'woocommerce' ) }
						</span>
					),
				}
			);
		} else {
			labelElement = createInterpolateElement(
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
			);
		}
	} else if ( note?.length ) {
		labelElement = createInterpolateElement(
			__( '<label/> <note />', 'woocommerce' ),
			{
				label: <span>{ label }</span>,
				note: (
					<span className="woocommerce-product-form-label__note">
						{ note }
					</span>
				),
			}
		);
	}

	return (
		<div id={ id } className="woocommerce-product-form-label__label">
			{ labelElement }

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
