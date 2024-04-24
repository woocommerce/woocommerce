/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Lightbulb from './Icons/lightbulb';

interface Props {
	color: 'purple' | 'blue' | 'gray' | 'yellow';
	className?: string;
}
const TipBox: React.FC< Props > = ( { color, className, children } ) => {
	return (
		<div
			className={ classNames(
				'wcpay-component-tip-box',
				color,
				className
			) }
		>
			<Lightbulb />
			<div className="wcpay-component-tip-box__content">{ children }</div>
		</div>
	);
};

export default TipBox;
