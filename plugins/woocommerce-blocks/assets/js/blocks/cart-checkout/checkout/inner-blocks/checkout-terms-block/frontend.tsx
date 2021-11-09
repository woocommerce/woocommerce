/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useState, useEffect } from '@wordpress/element';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { withInstanceId } from '@wordpress/compose';
import type { ValidationData } from '@woocommerce/type-defs/contexts';
/**
 * Internal dependencies
 */
import { termsConsentDefaultText, termsCheckboxDefaultText } from './constants';
import './style.scss';

const FrontendBlock = ( {
	text,
	checkbox,
	instanceId,
	validation,
	className,
}: {
	text: string;
	checkbox: boolean;
	instanceId: string;
	validation: ValidationData;
	className?: string;
} ): JSX.Element => {
	const [ checked, setChecked ] = useState( false );

	const { isDisabled } = useCheckoutSubmit();

	const validationErrorId = 'terms-and-conditions-' + instanceId;
	const {
		getValidationError,
		setValidationErrors,
		clearValidationError,
	} = validation;

	const error = getValidationError( validationErrorId ) || {};
	const hasError = error.message && ! error.hidden;

	// Track validation errors for this input.
	useEffect( () => {
		if ( ! checkbox ) {
			return;
		}
		if ( checked ) {
			clearValidationError( validationErrorId );
		} else {
			setValidationErrors( {
				[ validationErrorId ]: {
					message: __(
						'Please read and accept the terms and conditions.',
						'woo-gutenberg-products-block'
					),
					hidden: true,
				},
			} );
		}
		return () => {
			clearValidationError( validationErrorId );
		};
	}, [
		checkbox,
		checked,
		validationErrorId,
		clearValidationError,
		setValidationErrors,
	] );

	return (
		<div
			className={ classnames(
				'wc-block-checkout__terms',
				{
					'wc-block-checkout__terms--disabled': isDisabled,
				},
				className
			) }
		>
			{ checkbox ? (
				<>
					<CheckboxControl
						id="terms-and-conditions"
						checked={ checked }
						onChange={ () => setChecked( ( value ) => ! value ) }
						hasError={ hasError }
						disabled={ isDisabled }
					>
						<span
							dangerouslySetInnerHTML={ {
								__html: text || termsCheckboxDefaultText,
							} }
						/>
					</CheckboxControl>
				</>
			) : (
				<span
					dangerouslySetInnerHTML={ {
						__html: text || termsConsentDefaultText,
					} }
				/>
			) }
		</div>
	);
};

export default withInstanceId( FrontendBlock );
