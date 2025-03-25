/**
 * External dependencies
 */
import { Button, Fill } from '@wordpress/components';
import classnames from 'classnames';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TABS_SLOT_NAME } from '../../../components/tabs/constants';

export const DEFAULT_TAB_ORDER = 100;

const OrderedWrapper = ( {
	children,
}: {
	order: number;
	children: JSX.Element | JSX.Element[];
} ) => <>{ children }</>;

export function TabButton( {
	children,
	className,
	id,
	order = DEFAULT_TAB_ORDER,
	selected = false,
}: {
	children: string | JSX.Element;
	className?: string;
	id: string;
	selected?: boolean;
	order?: number;
} ) {
	const classes = classnames(
		'wp-block-woocommerce-product-tab__button',
		className,
		{ 'is-selected': selected }
	);

	return (
		<Fill name={ TABS_SLOT_NAME }>
			{ ( fillProps ) => {
				const { onClick } = fillProps;
				return (
					<OrderedWrapper order={ order }>
						<Button
							key={ id }
							className={ classes }
							onClick={ () => onClick( id ) }
							id={ `woocommerce-product-tab__${ id }` }
							aria-controls={ `woocommerce-product-tab__${ id }-content` }
							aria-selected={ selected }
							tabIndex={ selected ? undefined : -1 }
							role="tab"
						>
							{ children }
						</Button>
					</OrderedWrapper>
				);
			} }
		</Fill>
	);
}
