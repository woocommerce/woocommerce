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

function ProgressBar( props: ProgressBarProps ): JSX.Element {
	const {
		className = '',
		percent = 0,
		color = '#674399',
		bgcolor = 'var(--wp-admin-theme-color)',
	} = props;

	const containerStyles = {
		backgroundColor: bgcolor,
	};

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
					style={ fillerStyles }
				/>
			</div>
		</div>
	);
}

export default ProgressBar;
