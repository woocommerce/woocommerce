/**
 * External dependencies
 */
import {
	createElement,
	createInterpolateElement,
	isValidElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, help as helpIcon } from '@wordpress/icons';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';
import { sanitizeHTML } from '@woocommerce/sanitize';

/**
 * Internal dependencies
 */

export interface LabelProps {
	label: string;
	labelId?: string;
	required?: boolean;
	note?: string;
	tooltip?: string;
	onClick?: ( event: React.MouseEvent ) => void;
}

export const Label = ( {
	label,
	labelId,
	required,
	tooltip,
	note,
	onClick,
}: LabelProps ) => {
	let labelElement: JSX.Element | string = label;

	if ( required ) {
		if ( note?.length ) {
			labelElement = createInterpolateElement(
				__( '<label/> <note /> <required/>', 'woocommerce' ),
				{
					label: (
						<span
							dangerouslySetInnerHTML={ {
								__html: sanitizeHTML( label ),
							} }
						></span>
					),
					note: (
						<span className="woocommerce-product-form-label__note">
							{ note }
						</span>
					),
					required: (
						<span
							aria-hidden="true"
							className="woocommerce-product-form-label__required"
						>
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
						<span
							aria-hidden="true"
							className="woocommerce-product-form-label__required"
						>
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

	const spanAdditionalProps =
		typeof labelElement === 'string'
			? { dangerouslySetInnerHTML: { __html: sanitizeHTML( label ) } }
			: {};

	return (
		<div className="woocommerce-product-form-label__label">
			{ /* eslint-disable-next-line jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */ }
			<span id={ labelId } onClick={ onClick } { ...spanAdditionalProps }>
				{ isValidElement( labelElement ) ? labelElement : null }
			</span>

			{ tooltip && (
				<Tooltip
					text={
						<span
							dangerouslySetInnerHTML={ {
								__html: sanitizeHTML( tooltip ),
							} }
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
