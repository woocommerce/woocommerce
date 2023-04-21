/**
 * External dependencies
 */
import { Button, Fill } from '@wordpress/components';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TABS_SLOT_NAME } from '../../components/tabs/constants';
import { TabsFillProps } from '../../components/tabs';

export function TabButton( {
	children,
	className,
	id,
	selected = false,
}: {
	children: string | JSX.Element;
	className?: string;
	id: string;
	selected?: boolean;
} ) {
	const classes = classnames(
		'wp-block-woocommerce-product-tab__button',
		className,
		{ 'is-selected': selected }
	);

	return (
		<Fill name={ TABS_SLOT_NAME }>
			{ ( fillProps: TabsFillProps ) => {
				const { onClick } = fillProps;
				return (
					<Button
						key={ id }
						className={ classes }
						onClick={ () => onClick( id ) }
						id={ `woocommerce-product-tab__${ id }` }
						aria-controls={ `woocommerce-product-tab__${ id }-content` }
						aria-selected={ selected }
					>
						{ children }
					</Button>
				);
			} }
		</Fill>
	);
}
