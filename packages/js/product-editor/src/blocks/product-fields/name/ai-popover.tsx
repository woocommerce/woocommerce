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
import { Suggestion as AISuggestion } from '../../../hooks/use-ai-product-field';

type Suggestion = AISuggestion;

interface AIPopoverProps {
	getMenuProps: UseComboboxPropGetters< Suggestion >[ 'getMenuProps' ];
	widthRef: React.MutableRefObject< HTMLDivElement | null >;
	getItemProps: UseComboboxPropGetters< Suggestion >[ 'getItemProps' ];
	items: Suggestion[];
	highlightedIndex: number | null;
	onGetMoreSuggestions: () => void;
}

export function AIPopover( {
	getMenuProps,
	widthRef,
	getItemProps,
	items,
	highlightedIndex,
	onGetMoreSuggestions,
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
					style={ {
						width: boundingRect?.width,
						marginBottom: '0',
						marginTop: '0',
					} }
					{ ...getMenuProps() }
				>
					{ items.map( ( item, index ) => (
						<li
							key={ item.content }
							className={ classNames( {
								'woocommerce-product-form-name-ai-suggestions__highlighted':
									highlightedIndex === index,
							} ) }
							{ ...getItemProps( {
								item,
								index,
								style: {
									paddingTop: '10px',
								},
							} ) }
						>
							<div className="woocommerce-product-form-name-ai-suggestions__item">
								<div>
									<p className="woocommerce-product-form-name-ai-suggestions__item-name">
										{ item.content }
									</p>
									<p className="woocommerce-product-form-name-ai-suggestions__item-description">
										{ item.reason }
									</p>
								</div>
								<div>
									<ThumbsUpSVG />
									<ThumbsUpSVG rotate />
								</div>
							</div>
						</li>
					) ) }
				</ul>
				<Button
					{ ...getItemProps( {
						// workaround for now
						item: {
							content: 'get_more_suggestions',
							reason: '',
						},
						index: items.length + 1,
						className:
							'woocommerce-product-form-name-ai-suggestions__button',
					} ) }
					onClick={ () => {
						onGetMoreSuggestions();
					} }
				>
					<div className="woocommerce-product-form-name-ai-suggestions__flex">
						<div
							style={ { display: 'flex', alignItems: 'center' } }
						>
							<p style={ { marginTop: 0, marginBottom: 0 } }>
								Get more suggestions
							</p>
						</div>
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
