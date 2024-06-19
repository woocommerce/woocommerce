/**
 * External dependencies
 */
import { createElement, HTMLAttributes } from 'react';

type ProgressBarProps = {
	/** Component classname */
	className?: string;
	/** Progress percentage (0 to 100) */
	percent?: number;
	/** Color of the progress bar */
	color?: string;
	/** Background color of the progress container */
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

	const fillerStyles: HTMLAttributes< HTMLDivElement >[ 'style' ] = {
		backgroundColor: color,
		width: `${ percent }%`,
		display: percent === 0 ? 'none' : 'inherit',
	};

	return (
		<div className={ `woocommerce-onboarding-progress-bar ${ className }` }>
			<div
				className="woocommerce-onboarding-progress-bar__container"
				style={ containerStyles }
			>
				<div
					className="woocommerce-onboarding-progress-bar__filler"
					style={ fillerStyles }
				/>
			</div>
		</div>
	);
};

export default ProgressBar;
