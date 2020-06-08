/**
 * External dependencies
 */
import { SVG } from 'wordpress-components';
import classnames from 'classnames';

const arrowDownAlt2 = ( { className, size, ...extraProps } ) => {
	const iconClass = classnames(
		'dashicon',
		'dashicons-arrow-down-alt2',
		className
	);
	return (
		<SVG
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 20 20"
			className={ iconClass }
			width={ size }
			height={ size }
			{ ...extraProps }
		>
			<path d="M5 6l5 5 5-5 2 1-7 7-7-7z" />
		</SVG>
	);
};

export default arrowDownAlt2;
