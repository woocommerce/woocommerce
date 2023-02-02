/**
 * External dependencies
 */
import { ControllerFieldState } from 'react-hook-form';

export const getErrorMessageProps = ( fieldState: ControllerFieldState ) => {
	return fieldState.error && fieldState.isTouched
		? {
				className: 'has-error',
				help: fieldState.error.message,
		  }
		: {};
};
