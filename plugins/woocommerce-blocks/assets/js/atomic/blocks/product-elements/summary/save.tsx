/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

type Props = {
	attributes: Record< string, unknown > & {
		className?: string;
	};
};

export const Save = ( { attributes }: Props ): JSX.Element => {
	return (
		<div
			{ ...useBlockProps.save( {
				className: clsx( 'is-loading', attributes.className ),
			} ) }
		/>
	);
};
