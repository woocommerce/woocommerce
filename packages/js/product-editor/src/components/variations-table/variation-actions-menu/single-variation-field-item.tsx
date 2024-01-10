/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { VariationActionsMenuItem } from '../variation-actions/variation-actions-menu-item';

type SingleVariationMenuItemProps = {
	children?: React.ReactNode;
	order?: number;
	group?: string;
	type?: string;
};

export function SingleVariationMenuItem( {
	group,
	...props
}: SingleVariationMenuItemProps ) {
	return (
		<VariationActionsMenuItem
			{ ...props }
			group={ group }
			type={ 'single-variation' }
		/>
	);
}
