/**
 * External dependencies
 */
import classNames from 'classnames';
import { navigateTo, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { Category } from './types';

export default function CategoryLink( props: Category ): JSX.Element {
	function updateCategorySelection(
		event: React.MouseEvent< HTMLButtonElement >
	) {
		const slug = event.currentTarget.value;

		if ( ! slug ) {
			return;
		}

		navigateTo( {
			url: getNewPath( { category: slug } ),
		} );
	}

	const classes = classNames(
		'woocommerce-marketplace__category-item-button',
		{
			'woocommerce-marketplace__category-item-button--selected':
				props.selected,
		}
	);

	return (
		<button
			className={ classes }
			onClick={ updateCategorySelection }
			value={ props.slug }
		>
			{ props.label }
		</button>
	);
}
