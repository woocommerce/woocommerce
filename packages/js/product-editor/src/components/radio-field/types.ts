/**
 * External dependencies
 */
import { RadioControl } from '@wordpress/components';

type RadioProps = React.ComponentProps< typeof RadioControl >;

export type RadioFieldProps = Omit< RadioProps, 'label' > & {
	title: string;
	description?: string;
};
