/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { EditProps } from './types';

const Edit = ( props: EditProps ): JSX.Element => {
	const { context } = props;
	const { filterData } = context;
	const { isLoading, items } = filterData;

	const blockProps = useBlockProps();

	const loadingState = useMemo( () => {
		return [ ...Array( 5 ) ].map( ( _, i ) => (
			<li
				key={ i }
				style={ {
					/* stylelint-disable */
					width: Math.floor( Math.random() * ( 100 - 25 ) ) + '%',
				} }
			>
				&nbsp;
			</li>
		) );
	}, [] );

	if ( ! items ) {
		return <></>;
	}

	return (
		<>
			<div { ...blockProps }>
				<ul className="wc-block-product-filter-chips__list">
					{ isLoading && loadingState }
					{ ! isLoading &&
						items.map( ( item, index ) => (
							<li
								key={ index }
								className="wc-block-product-filter-chips__item"
							>
								<label
									htmlFor={ `interactive-checkbox-${ index }` }
									className=" wc-block-product-filter-chips__label"
								>
									<span className="wc-block-product-filter-chips__input-wrapper">
										<input
											name={ `interactive-checkbox-${ index }` }
											type="checkbox"
											className="wc-block-product-filter-chips__input"
											defaultChecked={ !! item.selected }
										/>
									</span>
									<span className="wc-block-product-filter-chips__text">
										{ item.label }
									</span>
								</label>
							</li>
						) ) }
				</ul>
			</div>
		</>
	);
};
export default Edit;
