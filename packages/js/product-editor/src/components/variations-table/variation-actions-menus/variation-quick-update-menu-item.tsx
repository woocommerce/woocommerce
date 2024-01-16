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
import {
	MULTIPLE_UPDATE,
	SINGLE_UPDATE,
	VARIATION_ACTIONS_SLOT_NAME,
} from './constants';

const DEFAULT_ORDER = 20;

export const getGroupName = (
	group?: string,
	isMultipleSelection?: boolean
) => {
	const nameSuffix = isMultipleSelection
		? `_${ MULTIPLE_UPDATE }`
		: `_${ SINGLE_UPDATE }`;
	return group
		? `${ VARIATION_ACTIONS_SLOT_NAME }_${ group }${ nameSuffix }`
		: VARIATION_ACTIONS_SLOT_NAME;
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
				{ [ MULTIPLE_UPDATE, SINGLE_UPDATE ].map( ( updateType ) => (
					<Fill
						key={ updateType }
						name={ getGroupName(
							group,
							updateType === MULTIPLE_UPDATE
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
