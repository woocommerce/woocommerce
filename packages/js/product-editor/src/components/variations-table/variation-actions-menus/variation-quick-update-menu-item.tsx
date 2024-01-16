/**
 * External dependencies
 */
import { Slot, Fill, MenuItem } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { MenuItemProps } from './types';
import { QUICK_UPDATE, SINGLE_UPDATE } from './constants';

const DEFAULT_ORDER = 20;

export const getGroupName = (
	group?: string,
	isMultipleSelection?: boolean
) => {
	const name = 'woocommerce-actions-menu-slot';
	const nameSuffix = isMultipleSelection
		? `_${ QUICK_UPDATE }`
		: `_${ SINGLE_UPDATE }`;
	return group ? `${ name }_${ group }${ nameSuffix }` : name;
};

export const getMenuItem = (
	children: React.ReactNode,
	onClick: () => void
) => <MenuItem onClick={ onClick }>{ children }</MenuItem>;

export const VariationQuickUpdateMenuItem: React.FC< MenuItemProps > & {
	Slot: React.FC<
		Slot.Props & {
			group: string;
			supportsMultipleSelection: boolean;
			onClick?: () => void;
		}
	>;
} = ( {
	children,
	order = DEFAULT_ORDER,
	group = 'top-level',
	supportsMultipleSelection,
	onClick = () => {},
} ) => {
	if ( supportsMultipleSelection ) {
		return (
			<>
				{ [ QUICK_UPDATE, SINGLE_UPDATE ].map( ( updateType ) => (
					<Fill
						key={ updateType }
						name={ getGroupName(
							group,
							updateType === QUICK_UPDATE
						) }
					>
						{ ( fillProps: Fill.Props ) =>
							createOrderedChildren(
								getMenuItem( children, onClick ),
								order,
								fillProps
							)
						}
					</Fill>
				) ) }
			</>
		);
	}
	return (
		<Fill name={ getGroupName( group, supportsMultipleSelection ) }>
			{ ( fillProps: Fill.Props ) =>
				createOrderedChildren(
					getMenuItem( children, onClick ),
					order,
					fillProps
				)
			}
		</Fill>
	);
};

VariationQuickUpdateMenuItem.Slot = ( {
	fillProps,
	group = 'top-level',
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
