/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { useState, useEffect } from '@wordpress/element';
import {
	CheckboxControl,
	ValidationInputError,
} from '@woocommerce/blocks-components';
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

	const { error, validationErrorHtmlId } = useSelect(
		( select ) => {
			const store = select( validationStore );

			return {
				error: store.getValidationError( validationErrorId ),
				validationErrorHtmlId:
					store.getValidationErrorId( validationErrorId ),
			};
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
			void clearValidationError( validationErrorId );
		} else {
			void setValidationErrors( {
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
			void clearValidationError( validationErrorId );
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
							aria-describedby={
								hasError ? validationErrorHtmlId : undefined
							}
							disabled={ isDisabled }
						>
							<span
								className="wc-block-components-checkbox__label"
								dangerouslySetInnerHTML={ {
									__html: text || termsCheckboxDefaultText,
								} }
							/>
						</CheckboxControl>
						<ValidationInputError
							propertyName={ validationErrorId }
							elementId={ validationErrorId }
						/>
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
