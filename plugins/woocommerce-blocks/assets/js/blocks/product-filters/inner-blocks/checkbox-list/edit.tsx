/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
import { EditProps } from './types';

const Edit = ( { context, isSelected }: EditProps ) => {
	const { filterData, isFilterDataLoading, isParentSelected } = context;
	const blockProps = useBlockProps();

	if ( ! filterData.collection ) {
		return null;
	}
	if ( isFilterDataLoading ) {
		return 'Loading…';
	}

	const threshold = 15;
	const isLongList = filterData.collection.length > threshold;

	const Block = () => (
		<div { ...blockProps }>
			<ul className="wc-block-product-filter-checkbox-list__list">
				{ ( isLongList
					? filterData.collection.slice( 0, threshold )
					: filterData.collection
				).map( ( item, index ) => (
					<li
						key={ index }
						className="wc-block-product-filter-checkbox-list__item"
					>
						<label
							htmlFor={ `interactive-checkbox-${ index }` }
							className=" wc-block-product-filter-checkbox-list__label"
						>
							<span className="wc-block-interactive-components-checkbox-list__input-wrapper">
								<span className="wc-block-product-filter-checkbox-list__input-wrapper">
									<input
										name={ `interactive-checkbox-${ index }` }
										type="checkbox"
										className="wc-block-product-filter-checkbox-list__input"
										// Harded coded some checked items for styling purpose.
										defaultChecked={ [ 1, 3, 4 ].includes(
											index
										) }
									/>
									<svg
										className="wc-block-product-filter-checkbox-list__mark"
										viewBox="0 0 10 8"
										fill="none"
										xmlns="http://www.w3.org/2000/svg"
									>
										<path
											d="M9.25 1.19922L3.75 6.69922L1 3.94922"
											stroke="currentColor"
											strokeWidth="1.5"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
									</svg>
								</span>
							</span>
							<span className="wc-block-product-filter-checkbox-list__text">
								{ item.label }
							</span>
						</label>
					</li>
				) ) }
			</ul>
			{ isLongList && (
				<span className="wc-block-product-filter-checkbox-list__show-more">
					<small>{ __( 'Show more…', 'woocommerce' ) }</small>
				</span>
			) }
		</div>
	);
	if ( isParentSelected === false && ! isSelected ) {
		return (
			<Disabled>
				<Block />
			</Disabled>
		);
	}
	return <Block />;
};

export default Edit;
