/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export type BadgeProps = {
	count: number;
} & React.HTMLAttributes< HTMLSpanElement >;

export const Badge: React.FC< BadgeProps > = ( {
	count,
	className = '',
	...props
}: BadgeProps ) => {
	return (
		<span className={ `woocommerce-badge ${ className }` } { ...props }>
			{ count }
		</span>
	);
};
