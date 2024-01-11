/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
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

const getGroupName = ( group?: string, type?: string ) => {
	const name = 'woocommerce-actions-menu-slot';
	const nameSuffix = type ? `_${ type }` : '';
	return group ? `${ name }_${ group }${ nameSuffix }` : name;
};

export const VariationActionsMenuItem: React.FC< MenuItemProps > & {
	Slot: React.FC< Slot.Props & { group: string; type: string } >;
} = ( { children, order = 1, group = '_main', type } ) => {
	if ( type ) {
		return (
			<Fill name={ getGroupName( group, type ) }>
				{ ( fillProps: Fill.Props ) =>
					createOrderedChildren( children, order, fillProps )
				}
			</Fill>
		);
	}

	return (
		<>
			{ [ QUICK_UPDATE, SINGLE_UPDATE ].map( ( actionType ) => (
				<Fill
					key={ actionType }
					name={ getGroupName( group, actionType ) }
				>
					{ ( fillProps: Fill.Props ) =>
						createOrderedChildren( children, order, fillProps )
					}
				</Fill>
			) ) }
		</>
	);
};

VariationActionsMenuItem.Slot = ( { fillProps, group = '_main', type } ) => {
	return (
		<Slot name={ getGroupName( group, type ) } fillProps={ fillProps }>
			{ ( fills ) => {
				if ( ! sortFillsByOrder ) {
					return null;
				}

				return sortFillsByOrder( fills );
			} }
		</Slot>
	);
};

export const VariationSingleUpdateMenuItem = ( {
	group,
	...props
}: MenuItemProps ) => {
	return (
		<VariationActionsMenuItem
			{ ...props }
			group={ group }
			type={ SINGLE_UPDATE }
		/>
	);
};

export const VariationQuickUpdateMenuItem = ( {
	group,
	...props
}: MenuItemProps ) => {
	return (
		<VariationActionsMenuItem
			{ ...props }
			group={ group }
			type={ QUICK_UPDATE }
		/>
	);
};
