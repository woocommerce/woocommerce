/**
 * External dependencies
 */

import { HTMLAttributes, createElement } from 'react';
/**
 * Internal dependencies
 */

type ProgressBarProps = {
	className?: string;
	percent?: number;
	color?: string;
	bgcolor?: string;
};

export const ProgressBar = ( {
	className = '',
	percent = 0,
	color = '#674399',
	bgcolor = 'var(--wp-admin-theme-color)',
}: ProgressBarProps ) => {
	const containerStyles = {
		backgroundColor: bgcolor,
	};

	const fillerStyles: HTMLAttributes< HTMLDivElement >[ 'style' ] = {
		backgroundColor: color,
		width: `${ percent }%`,
		display: percent === 0 ? 'none' : 'inherit',
	};

	return (
		<div className={ `woocommerce-progress-bar ${ className }` }>
			<div
				className="woocommerce-progress-bar__container"
				style={ containerStyles }
			>
				<div
					className="woocommerce-progress-bar__filler"
					style={ fillerStyles }
				/>
			</div>
		</div>
	);
};
