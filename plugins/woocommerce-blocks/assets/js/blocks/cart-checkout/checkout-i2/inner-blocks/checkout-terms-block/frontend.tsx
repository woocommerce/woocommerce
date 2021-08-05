/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useState, useEffect } from '@wordpress/element';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import { useValidationContext } from '@woocommerce/base-context';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { termsConsentDefaultText, termsCheckboxDefaultText } from './constants';
import './style.scss';

const FrontendBlock = ( {
	text,
	checkbox,
	instanceId,
}: {
	text: string;
	checkbox: boolean;
	instanceId: string;
} ): JSX.Element => {
	const [ checked, setChecked ] = useState( false );

	// @todo Checkout i2 - Pass validation context to Inner Blocks to avoid exporting in a public package.
	const { isDisabled } = useCheckoutSubmit();
	const {
		getValidationError,
		setValidationErrors,
		clearValidationError,
	} = useValidationContext();

	const validationErrorId = 'terms-and-conditions-' + instanceId;
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
			className={ classnames( 'wc-block-checkout__terms', {
				'wc-block-checkout__terms--disabled': isDisabled,
			} ) }
		>
			{ checkbox ? (
				<>
					<CheckboxControl
						id="terms-and-conditions"
						checked={ checked }
						onChange={ () => setChecked( ( value ) => ! value ) }
						className={ classnames( {
							'has-error': hasError,
						} ) }
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
