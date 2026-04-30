/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { decodeHtmlEntities } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import './style.scss';
import { EditProps } from './types';

const NUGGET_POSITIONS = [
	{ x: 15, y: 25 },
	{ x: 45, y: 15 },
	{ x: 75, y: 35 },
	{ x: 25, y: 55 },
	{ x: 60, y: 45 },
	{ x: 85, y: 20 },
	{ x: 35, y: 40 },
	{ x: 70, y: 60 },
	{ x: 10, y: 45 },
	{ x: 50, y: 30 },
	{ x: 20, y: 70 },
	{ x: 65, y: 70 },
	{ x: 40, y: 65 },
	{ x: 80, y: 55 },
	{ x: 55, y: 75 },
];

const SIZES: Array< 'small' | 'medium' | 'large' > = [
	'medium',
	'large',
	'small',
	'medium',
	'large',
	'small',
	'medium',
	'small',
	'large',
	'medium',
	'small',
	'medium',
	'large',
	'small',
	'medium',
];

const GoldMinerEdit = ( { context }: EditProps ): JSX.Element => {
	const selectableItems = context?.[ 'woocommerce/selectableItems' ] ?? {};
	const isLoading = selectableItems.isLoading ?? false;
	const items = Array.isArray( selectableItems.items )
		? selectableItems.items
		: [];

	const blockProps = useBlockProps( {
		className: 'wc-block-product-filter-gold-miner',
	} );

	const loadingState = useMemo( () => {
		return [ ...Array( 5 ) ].map( ( _, i ) => (
			<div
				className="wc-block-product-filter-gold-miner__nugget wc-block-product-filter-gold-miner__nugget--loading"
				key={ i }
				style={ {
					left: `${ NUGGET_POSITIONS[ i ].x }%`,
					top: `${ NUGGET_POSITIONS[ i ].y }%`,
				} }
			/>
		) );
	}, [] );

	return (
		<div { ...blockProps }>
			<Disabled>
				<div className="wc-block-product-filter-gold-miner__sky">
					<div className="wc-block-product-filter-gold-miner__miner">
						<div className="wc-block-product-filter-gold-miner__claw-pivot">
							<div className="wc-block-product-filter-gold-miner__claw-arm">
								<div className="wc-block-product-filter-gold-miner__claw-hook" />
							</div>
						</div>
					</div>
					<div className="wc-block-product-filter-gold-miner__instruction">
						{ __( 'Click to drop the claw!', 'woocommerce' ) }
					</div>
				</div>
				<div className="wc-block-product-filter-gold-miner__ground">
					{ isLoading && loadingState }
					{ ! isLoading &&
						items.map( ( item, index ) => (
							<div
								key={ index }
								className={ `wc-block-product-filter-gold-miner__nugget wc-block-product-filter-gold-miner__nugget--${ SIZES[ index % SIZES.length ] } ${ item.selected ? 'is-selected' : '' }` }
								style={ {
									left: `${ NUGGET_POSITIONS[ index % NUGGET_POSITIONS.length ].x }%`,
									top: `${ NUGGET_POSITIONS[ index % NUGGET_POSITIONS.length ].y }%`,
								} }
								title={
									typeof item.label === 'string'
										? decodeHtmlEntities( item.label )
										: ''
								}
							>
								<span className="wc-block-product-filter-gold-miner__nugget-label">
									{ typeof item.label === 'string'
										? decodeHtmlEntities( item.label )
										: item.label }
								</span>
							</div>
						) ) }
				</div>
			</Disabled>
		</div>
	);
};

export default GoldMinerEdit;
