/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { VariationActionsMenuItem } from '../variation-actions-menu-item';

type VariationQuickUpdateMenuItemProps = {
	children?: React.ReactNode;
	order?: number;
	group?: string;
	type?: string;
};

export function VariationQuickUpdateMenuItem( {
	group,
	...props
}: VariationQuickUpdateMenuItemProps ) {
	return (
		<VariationActionsMenuItem
			{ ...props }
			group={ group }
			type={ 'quick-update' }
		/>
	);
}
