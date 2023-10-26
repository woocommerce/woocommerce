/**
 * External dependencies
 */
import { createElement, useState, useLayoutEffect } from '@wordpress/element';

import classNames from 'classnames';
import { Button, Popover } from '@wordpress/components';
import { UseComboboxPropGetters } from 'downshift';
import { ThumbsUpSVG } from './thumbs-up';

type Suggestions = string;

interface AIPopoverProps {
	getMenuProps: UseComboboxPropGetters< Suggestions >[ 'getMenuProps' ];
	widthRef: React.MutableRefObject< HTMLDivElement | null >;
	getItemProps: UseComboboxPropGetters< Suggestions >[ 'getItemProps' ];
	items: Suggestions[];
	highlightedIndex: number | null;
}

export function AIPopover( {
	getMenuProps,
	widthRef,
	getItemProps,
	items,
	highlightedIndex,
}: AIPopoverProps ) {
	const [ boundingRect, setBoundingRect ] = useState< DOMRect >();
	useLayoutEffect( () => {
		if ( widthRef.current && widthRef.current.clientWidth > 0 ) {
			setBoundingRect( widthRef.current.getBoundingClientRect() );
		}
	}, [ widthRef.current, widthRef.current?.clientWidth ] );
	return (
		<Popover>
			<ul style={ { width: boundingRect?.width } } { ...getMenuProps() }>
				{ items.map( ( item, index ) => (
					<li
						key={ item }
						className={ classNames( {
							'woocommerce-product-form-name-ai-suggestions__highlighted':
								highlightedIndex === index,
						} ) }
						{ ...getItemProps( { item, index } ) }
					>
						<div className="woocommerce-product-form-name-ai-suggestions__item">
							<div>
								<p className="woocommerce-product-form-name-ai-suggestions__item-name">
									{ item }
								</p>
								<p className="woocommerce-product-form-name-ai-suggestions__item-description">
									{ item }
								</p>
							</div>
							<div>
								<ThumbsUpSVG />
							</div>
						</div>
					</li>
				) ) }
			</ul>
		</Popover>
	);
}
