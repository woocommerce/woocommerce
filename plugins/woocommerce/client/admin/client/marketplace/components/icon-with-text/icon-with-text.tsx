/**
 * External dependencies
 */
import { Icon } from '@wordpress/icons';
import { ReactElement } from 'react';

/**
 * Internal dependencies
 */
import './icon-with-text.scss';

export interface IconWithTextProps {
	icon: JSX.Element;
	title: ReactElement | string;
	description: string;
}

export default function IconWithText( props: IconWithTextProps ): JSX.Element {
	const { icon, title, description } = props;
	return (
		<div className="woocommerce-marketplace__icon-group">
			<div className="woocommerce-marketplace__icon-group-headline">
				<Icon
					icon={ icon }
					size={ 20 }
					className="woocommerce-marketplace__icon-group-icon"
				/>
				<h3 className="woocommerce-marketplace__icon-group-title">
					{ title }
				</h3>
			</div>
			<p className="woocommerce-marketplace__icon-group-description">
				{ description }
			</p>
		</div>
	);
}
