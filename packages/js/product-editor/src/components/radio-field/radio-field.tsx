/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { RadioControl } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';
import { RadioFieldProps } from './types';

export function RadioField< T = string >( {
	title,
	description,
	className,
	...props
}: RadioFieldProps< T > ) {
	return (
		<RadioControl
			{ ...props }
			className={ classNames( className, 'woocommerce-radio-field' ) }
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
