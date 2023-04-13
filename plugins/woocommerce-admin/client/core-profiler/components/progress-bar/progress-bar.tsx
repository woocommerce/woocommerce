/**
<<<<<<< HEAD
 * External dependencies
 */

import { HTMLAttributes } from 'react';
/**
=======
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
 * Internal dependencies
 */

import './progress-bar.scss';

type ProgressBarProps = {
	className?: string;
	percent?: number;
	color?: string;
	bgcolor?: string;
};

<<<<<<< HEAD
const ProgressBar = ( {
	className = '',
	percent = 0,
	color = '#674399',
	bgcolor = 'var(--wp-admin-theme-color)',
}: ProgressBarProps ) => {
=======
function ProgressBar( props: ProgressBarProps ): JSX.Element {
	const {
		className = '',
		percent = 0,
		color = '#674399',
		bgcolor = 'var(--wp-admin-theme-color)',
	} = props;

>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
	const containerStyles = {
		backgroundColor: bgcolor,
	};

<<<<<<< HEAD
	const fillerStyles: HTMLAttributes< HTMLDivElement >[ 'style' ] = {
		backgroundColor: color,
		width: `${ percent }%`,
		display: percent === 0 ? 'none' : 'inherit',
	};

	return (
		<div className={ `woocommerce-profiler-progress-bar ${ className }` }>
			<div
				className="woocommerce-profiler-progress-bar__container"
				style={ containerStyles }
			>
				<div
					className="woocommerce-profiler-progress-bar__filler"
=======
	let fillerStyles: Record< string, string > = {
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
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
					style={ fillerStyles }
				/>
			</div>
		</div>
	);
<<<<<<< HEAD
};
=======
}
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)

export default ProgressBar;
