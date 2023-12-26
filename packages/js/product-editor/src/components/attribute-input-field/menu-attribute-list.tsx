/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { Icon } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __experimentalSelectControlMenuItem as MenuItem } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import {
	MenuAttributeListProps,
	NarrowedQueryAttribute,
	UseComboboxGetMenuPropsOptions,
} from './types';

function isNewAttributeListItem( attribute: NarrowedQueryAttribute ): boolean {
	return attribute.id === -99;
}

function sanitizeSlugName( slug: string | undefined ): string {
	return slug && slug.startsWith( 'pa_' ) ? slug.substring( 3 ) : '';
}

export const MenuAttributeList: React.FC< MenuAttributeListProps > = ( {
	disabledAttributeMessage = '',
	renderItems,
	highlightedIndex,
	getItemProps,
} ) => {
	if ( renderItems.length > 0 ) {
		return (
			<Fragment>
				{ renderItems.map( ( item, index: number ) => (
					<MenuItem
						key={ item.id }
						index={ index }
						isActive={ highlightedIndex === index }
						item={ item }
						getItemProps={ (
							options: UseComboboxGetMenuPropsOptions
						) => ( {
							...getItemProps( options ),
							disabled: item.isDisabled || undefined,
						} ) }
						tooltipText={
							item.isDisabled
								? disabledAttributeMessage
								: sanitizeSlugName( item.slug )
						}
					>
						{ isNewAttributeListItem( item ) ? (
							<div className="woocommerce-attribute-input-field__add-new">
								<Icon
									icon={ plus }
									size={ 20 }
									className="woocommerce-attribute-input-field__add-new-icon"
								/>
								<span>
									{ sprintf(
										/* translators: The name of the new attribute term to be created */
										__( 'Create "%s"', 'woocommerce' ),
										item.name
									) }
								</span>
							</div>
						) : (
							item.name
						) }
					</MenuItem>
				) ) }
			</Fragment>
		);
	}
	return (
		<div className="woocommerce-attribute-input-field__no-results">
			{ __( 'Nothing yet. Type to create.', 'woocommerce' ) }
		</div>
	);
};
