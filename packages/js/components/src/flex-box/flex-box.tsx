/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import classNames from 'classnames';
import { unset } from 'lodash';

/**
 * Internal dependencies
 */
export type FlexStyleProps = {
	flexDirection?: 'row' | 'row-reverse' | 'column' | 'column-reverse';
	flexWrap?: 'nowrap' | 'wrap' | 'wrap-reverse';
	justifyContent?:
		| 'flex-start'
		| 'flex-end'
		| 'center'
		| 'space-between'
		| 'space-around'
		| 'space-evenly';
	alignItems?: 'flex-start' | 'flex-end' | 'center' | 'baseline' | 'stretch';
	alignContent?:
		| 'flex-start'
		| 'flex-end'
		| 'center'
		| 'space-between'
		| 'space-around'
		| 'space-evenly'
		| 'stretch';
	rowGap?: string | number;
	columnGap?: string | number;
	width?: string | number;
	height?: string | number;
};
export type FlexBoxProps = FlexStyleProps & {
	className?: string | null;
	children: JSX.Element | JSX.Element[];
};

const defaultClassName = 'woocommerce-flex-box';

const propsToStyle = ( {
	flexDirection = 'row',
	flexWrap,
	justifyContent,
	alignItems,
	alignContent,
	rowGap,
	columnGap,
	width,
	height,
}: FlexStyleProps ) => {
	if (
		! height &&
		( flexDirection === 'column' || flexDirection === 'column-reverse' )
	) {
		height = '100%';
	} else if ( ! width ) {
		width = '100%';
	}
	return {
		flexDirection,
		flexWrap,
		justifyContent,
		alignItems,
		alignContent,
		rowGap,
		columnGap,
		width,
		height,
	};
};
export const FlexBox: React.FC< FlexBoxProps > = ( {
	className,
	children,
	...props
}: FlexBoxProps ) => {
	return (
		<div
			className={ classNames(
				className,
				defaultClassName,
				defaultClassName + '__container'
			) }
			style={ propsToStyle( props ) }
		>
			{ children }
		</div>
	);
};
