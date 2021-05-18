/**
 * Internal dependencies
 */
import { TextField } from './field-text';
import { ControlProps } from '../types';

export const PasswordField: React.FC< ControlProps > = ( props ) => {
	return <TextField { ...props } type="password" />;
};
