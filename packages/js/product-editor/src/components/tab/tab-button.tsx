/**
 * External dependencies
 */
import { Button, Fill } from '@wordpress/components';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TABS_SLOT_NAME } from '../tabs/constants';
import { TabsFillProps } from '../tabs';

export function TabButton( {
	children,
	className,
	id,
}: {
	children: string | JSX.Element;
	className?: string;
	id: string;
} ) {
	const classes = classnames(
		'wp-block-woocommerce-product-tab__button',
		className
	);

	return (
		<Fill name={ TABS_SLOT_NAME }>
			{ ( fillProps: TabsFillProps ) => {
				const { onClick } = fillProps;
				return (
					<Button
						className={ classes }
						onClick={ () => onClick( id ) }
					>
						{ children }
					</Button>
				);
			} }
		</Fill>
	);
}
