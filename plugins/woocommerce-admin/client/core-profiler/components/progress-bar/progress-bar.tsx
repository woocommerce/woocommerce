/**
 * External dependencies
 */

import { HTMLAttributes } from 'react';
/**
 * Internal dependencies
 */

import './progress-bar.scss';

type ProgressBarProps = {
	className?: string;
	percent?: number;
	color?: string;
	bgcolor?: string;
};

const ProgressBar = ( {
	className = '',
	percent = 0,
	color = '#674399',
	bgcolor = 'var(--wp-admin-theme-color)',
}: ProgressBarProps ) => {
	const containerStyles = {
		backgroundColor: bgcolor,
	};

	let fillerStyles: HTMLAttributes< HTMLDivElement >[ 'style' ] = {
		backgroundColor: color,
		width: `${ percent }%`,
	};

	if ( percent === 0 ) {
		fillerStyles = {
			display: 'none',
		};
	}

	return (
		<div className={ `wc-progress-bar ${ className }` }>
			<div
				className="wc-progress-bar__container"
				style={ containerStyles }
			>
				<div
					className="wc-progress-bar__filler"
					style={ fillerStyles }
				/>
			</div>
		</div>
	);
};

export default ProgressBar;
