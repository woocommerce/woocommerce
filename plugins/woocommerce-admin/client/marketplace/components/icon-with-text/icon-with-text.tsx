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
	title: ReactElement | JSX.Element | string;
	description: string;
}

export default function IconWithText( props: IconWithTextProps ): JSX.Element {
	const { icon, title, description } = props;
	return (
		<div className="icon-group">
			<div className="icon-group__headline">
				<Icon icon={ icon } size={ 20 } className="icon-group__icon" />
				<h3 className="icon-group__title">{ title }</h3>
			</div>
			<p className="icon-group__description">{ description }</p>
		</div>
	);
}
