/**
 * External dependencies
 */
import { RadioControl } from '@wordpress/components';

export type RadioFieldProps< T > = Omit< RadioControl.Props< T >, 'label' > & {
	title: string;
	description?: string;
};
