/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { MenuItemProps } from './types';
import { QUICK_UPDATE, SINGLE_UPDATE } from './constants';

const getGroupName = (
	group?: string,
	supportsMultipleSelection?: boolean
) => {
	const name = 'woocommerce-actions-menu-slot';
	const nameSuffix = supportsMultipleSelection
		? `_${ QUICK_UPDATE }`
		: `_${ SINGLE_UPDATE }`;
	return group ? `${ name }_${ group }${ nameSuffix }` : name;
};

export const VariationQuickUpdateMenuItem: React.FC< MenuItemProps > & {
	Slot: React.FC<
		Slot.Props & { group: string; supportsMultipleSelection: boolean }
	>;
} = ( { children, order = 1, group = '_main', supportsMultipleSelection } ) => {
	return (
		<Fill name={ getGroupName( group, supportsMultipleSelection ) }>
			{ ( fillProps: Fill.Props ) =>
				createOrderedChildren( children, order, fillProps )
			}
		</Fill>
	);
};

VariationQuickUpdateMenuItem.Slot = ( {
	fillProps,
	group = '_main',
	supportsMultipleSelection,
} ) => {
	return (
		<Slot
			name={ getGroupName( group, supportsMultipleSelection ) }
			fillProps={ fillProps }
		>
			{ ( fills ) => {
				if ( ! sortFillsByOrder ) {
					return null;
				}

				return sortFillsByOrder( fills );
			} }
		</Slot>
	);
};
