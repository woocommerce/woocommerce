/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { Category } from './category-selector';

export default function CategoryLink( props: Category ): JSX.Element {
	const classes = classNames(
		'woocommerce-marketplace__category-item-button',
		{
			'woocommerce-marketplace__category-item-button--selected':
				props.selected,
		}
	);

	return <button className={ classes }>{ props.label }</button>;
}
