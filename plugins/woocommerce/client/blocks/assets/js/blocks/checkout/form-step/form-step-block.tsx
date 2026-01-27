/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { PlainText, useBlockProps } from '@wordpress/block-editor';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';

/**
 * Internal dependencies
 */
import FormStepHeading from './form-step-heading';

export interface FormStepBlockProps {
	attributes: { title: string; description: string };
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	className?: string;
	children?: React.ReactNode;
	lock?: { move: boolean; remove: boolean };
}

/**
 * Form Step Block for use in the editor.
 */
export const FormStepBlock = ( {
	attributes,
	setAttributes,
	className = '',
	children,
}: FormStepBlockProps ): JSX.Element => {
	const { showFormStepNumbers } = useCheckoutBlockContext();

	const { title = '', description = '' } = attributes;
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-components-checkout-step', className, {
			'wc-block-components-checkout-step--with-step-number':
				showFormStepNumbers,
		} ),
	} );
	return (
		<div { ...blockProps }>
			<div className="wc-block-components-checkout-step__heading-container">
				<FormStepHeading>
					<PlainText
						className={ '' }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						style={ { backgroundColor: 'transparent' } }
					/>
				</FormStepHeading>
				<p className="wc-block-components-checkout-step__description">
					<PlainText
						className={
							! description
								? 'wc-block-components-checkout-step__description-placeholder'
								: ''
						}
						value={ description }
						placeholder={ __(
							'Optional text for this form step.',
							'woocommerce'
						) }
						onChange={ ( value ) =>
							setAttributes( {
								description: value,
							} )
						}
						style={ { backgroundColor: 'transparent' } }
					/>
				</p>
			</div>
			<div className="wc-block-components-checkout-step__content">
				{ children }
			</div>
		</div>
	);
};
