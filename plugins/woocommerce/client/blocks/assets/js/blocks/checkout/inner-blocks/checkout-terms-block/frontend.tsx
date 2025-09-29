/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { useState, useEffect } from '@wordpress/element';
import { CheckboxControl } from '@woocommerce/blocks-components';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { withInstanceId } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { termsConsentDefaultText, termsCheckboxDefaultText } from './constants';

const FrontendBlock = ( {
	text,
	checkbox,
	instanceId,
	className,
	showSeparator,
}: {
	text: string;
	checkbox: boolean;
	showSeparator: string | boolean;
	instanceId: string;
	className?: string;
} ): JSX.Element => {
	const [ checked, setChecked ] = useState( false );

	const { isDisabled } = useCheckoutSubmit();

	const validationErrorId = 'terms-and-conditions-' + instanceId;
	const { setValidationErrors, clearValidationError } =
		useDispatch( validationStore );

	const error = useSelect(
		( select ) => {
			return select( validationStore ).getValidationError(
				validationErrorId
			);
		},
		[ validationErrorId ]
	);
	const hasError = !! ( error?.message && ! error?.hidden );

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
						'woocommerce'
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
		<>
			<div
				className={ clsx(
					'wc-block-checkout__terms',
					{
						'wc-block-checkout__terms--disabled': isDisabled,
						'wc-block-checkout__terms--with-separator':
							showSeparator !== 'false' &&
							showSeparator !== false,
					},
					className
				) }
			>
				{ checkbox ? (
					<>
						<CheckboxControl
							id="terms-and-conditions"
							checked={ checked }
							onChange={ () =>
								setChecked( ( value ) => ! value )
							}
							hasError={ hasError }
							disabled={ isDisabled }
						>
							<span
								className="wc-block-components-checkbox__label"
								dangerouslySetInnerHTML={ {
									__html: text || termsCheckboxDefaultText,
								} }
							/>
						</CheckboxControl>
					</>
				) : (
					<span
						className="wc-block-components-checkbox__label"
						dangerouslySetInnerHTML={ {
							__html: text || termsConsentDefaultText,
						} }
					/>
				) }
			</div>
		</>
	);
};

export default withInstanceId( FrontendBlock );
