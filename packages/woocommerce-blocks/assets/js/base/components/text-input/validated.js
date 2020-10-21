/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useValidationContext } from '@woocommerce/base-context';
import { ValidationInputError } from '@woocommerce/base-components/validation';
import { withInstanceId } from '@woocommerce/base-hocs/with-instance-id';

/**
 * Internal dependencies
 */
import TextInput from './index';
import './style.scss';

const ValidatedTextInput = ( {
	className,
	instanceId,
	id,
	ariaDescribedBy,
	errorId,
	validateOnMount = true,
	focusOnMount = false,
	onChange,
	showError = true,
	...rest
} ) => {
	const inputRef = useRef();
	const {
		getValidationError,
		hideValidationError,
		setValidationErrors,
		clearValidationError,
		getValidationErrorId,
	} = useValidationContext();

	const textInputId = id || 'textinput-' + instanceId;
	errorId = errorId || textInputId;

	const validateInput = ( errorsHidden = true ) => {
		if ( inputRef.current.checkValidity() ) {
			clearValidationError( errorId );
		} else {
			setValidationErrors( {
				[ errorId ]: {
					message:
						inputRef.current.validationMessage ||
						__( 'Invalid value.', 'woocommerce' ),
					hidden: errorsHidden,
				},
			} );
		}
	};

	useEffect( () => {
		if ( focusOnMount ) {
			inputRef.current.focus();
		}
	}, [ focusOnMount ] );

	useEffect( () => {
		if ( validateOnMount ) {
			validateInput();
		}
	}, [ validateOnMount ] );

	// Remove validation errors when unmounted.
	useEffect( () => {
		return () => {
			clearValidationError( errorId );
		};
	}, [ errorId ] );

	const errorMessage = getValidationError( errorId ) || {};
	const hasError = errorMessage.message && ! errorMessage.hidden;
	const describedBy =
		showError && hasError && getValidationErrorId( errorId )
			? getValidationErrorId( errorId )
			: ariaDescribedBy;

	return (
		<TextInput
			className={ classnames( className, {
				'has-error': hasError,
			} ) }
			id={ textInputId }
			onBlur={ () => {
				validateInput( false );
			} }
			feedback={
				showError && <ValidationInputError propertyName={ errorId } />
			}
			ref={ inputRef }
			onChange={ ( val ) => {
				hideValidationError( errorId );
				onChange( val );
			} }
			ariaDescribedBy={ describedBy }
			{ ...rest }
		/>
	);
};

ValidatedTextInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	id: PropTypes.string,
	value: PropTypes.string,
	ariaDescribedBy: PropTypes.string,
	errorId: PropTypes.string,
	validateOnMount: PropTypes.bool,
	focusOnMount: PropTypes.bool,
	showError: PropTypes.bool,
};

export default withInstanceId( ValidatedTextInput );
