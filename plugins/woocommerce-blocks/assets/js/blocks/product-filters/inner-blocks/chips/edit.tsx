/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import './editor.scss';

const Edit = ( props: EditProps ): JSX.Element => {
	const { context } = props;
	const { filterData } = context;
	const { isLoading, items } = filterData;

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-chips', {
			'is-loading': isLoading,
		} ),
	} );

	const loadingState = useMemo( () => {
		return [ ...Array( 10 ) ].map( ( _, i ) => (
			<div
				className="wc-block-product-filter-chips__item"
				key={ i }
				style={ {
					/* stylelint-disable */
					width: Math.floor( Math.random() * ( 100 - 25 ) ) + '%',
				} }
			>
				&nbsp;
			</div>
		) );
	}, [] );

	if ( ! items ) {
		return <></>;
	}

	return (
		<>
			<div { ...blockProps }>
				<div className="wc-block-product-filter-chips__items">
					{ isLoading && loadingState }
					{ ! isLoading &&
						items.map( ( item, index ) => (
							<div
								key={ index }
								className="wc-block-product-filter-chips__item"
								aria-checked={ !! item.selected }
							>
								<span className="wc-block-product-filter-chips__label">
									{ item.label }
								</span>
							</div>
						) ) }
				</div>
			</div>
		</>
	);
};
export default Edit;
