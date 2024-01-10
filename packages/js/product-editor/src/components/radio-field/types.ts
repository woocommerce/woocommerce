/**
 * External dependencies
 */
import { RadioControl } from '@wordpress/components';

type RadioProps = typeof RadioControl extends (
	props: infer P
) => JSX.Element | null
	? P
	: never;

export type RadioFieldProps = Omit< RadioProps, 'label' > & {
	title: string;
	description?: string;
};
