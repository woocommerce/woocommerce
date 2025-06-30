/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { RadioControl } from '@wordpress/components';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';
import { RadioFieldProps } from './types';

export function RadioField( {
	title,
	description,
	className,
	...props
}: RadioFieldProps ) {
	return (
		<RadioControl
			{ ...props }
			className={ clsx( className, 'woocommerce-radio-field' ) }
			label={
				<>
					<span className="woocommerce-radio-field__title">
						{ title }
					</span>
					{ description && (
						<span
							className="woocommerce-radio-field__description"
							dangerouslySetInnerHTML={ sanitizeHTML(
								description
							) }
						/>
					) }
				</>
			}
		/>
	);
}
