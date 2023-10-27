/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useState,
	useLayoutEffect,
	createInterpolateElement,
} from '@wordpress/element';

import classNames from 'classnames';
import { Button, Popover } from '@wordpress/components';
import { UseComboboxPropGetters } from 'downshift';
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
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
		<Popover focusOnMount={ false }>
			<>
				<ul
					style={ { width: boundingRect?.width, marginBottom: '0' } }
					{ ...getMenuProps() }
				>
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
				<Button className="woocommerce-product-form-name-ai-suggestions__button">
					<div className="woocommerce-product-form-name-ai-suggestions__flex">
						<p>Get more suggestions</p>
						<div
							style={ { display: 'flex', alignItems: 'center' } }
						>
							{ createInterpolateElement(
								__( '<p/> <link/>', 'woocommerce' ),
								{
									p: (
										<p className="woocommerce-product-form-name-ai-suggestions__item-description">
											Powered by experimental AI.
										</p>
									),
									link: (
										<Link
											href="https://automattic.com/ai-guidelines"
											target="_blank"
											rel="noopener noreferrer"
											type="external"
										>
											{ __(
												'Learn more',
												'woocommerce'
											) }
										</Link>
									),
								}
							) }
						</div>
					</div>
				</Button>
			</>
		</Popover>
	);
}
