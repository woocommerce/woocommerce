/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

const getGroupName = ( group?: string, type?: string ) => {
	const name = 'woocommerce-actions-menu-slot';
	const nameSuffix = type ? `_${ type }` : '';
	return group ? `${ name }_${ group }${ nameSuffix }` : name;
};

const ACTION_MENUS = [ 'quick-update', 'single-variation' ];

export const VariationActionsMenuItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
	group?: string;
	type?: string;
} > & {
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
			{ ACTION_MENUS.map( ( actionType ) => (
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
