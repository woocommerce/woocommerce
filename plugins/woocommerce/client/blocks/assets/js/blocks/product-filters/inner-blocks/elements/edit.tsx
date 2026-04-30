/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import clsx from 'clsx';
import { decodeHtmlEntities } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { detectElement } from './utils';
import './editor.scss';

const Edit = ( { context }: EditProps ): JSX.Element => {
	const { isLoading = false, items = [] } =
		context[ 'woocommerce/selectableItems' ] ?? {};

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-elements', {
			'is-loading': isLoading,
		} ),
	} );

	const loadingState = useMemo( () => {
		return [ ...Array( 5 ) ].map( ( _, i ) => (
			<div
				key={ i }
				className="wc-block-product-filter-elements__item"
			>
				&nbsp;
			</div>
		) );
	}, [] );

	if ( ! items ) {
		return <></>;
	}

	return (
		<div { ...blockProps }>
			<div className="wc-block-product-filter-elements__items">
				{ isLoading && loadingState }
				{ ! isLoading &&
					items.map( ( item, index ) => {
						const element = detectElement( item.value );
						return (
							<div
								key={ index }
								className={ clsx(
									'wc-block-product-filter-elements__item',
									element && `is-element-${ element }`
								) }
								data-element={ element }
								aria-checked={ !! item.selected }
							>
								<span className="wc-block-product-filter-elements__label">
									<span className="wc-block-product-filter-elements__text">
										{ typeof item.label === 'string'
											? decodeHtmlEntities( item.label )
											: item.label }
									</span>
									{ item.count !== undefined && (
										<span className="wc-block-product-filter-elements__count">
											{ ` (${ item.count })` }
										</span>
									) }
								</span>
								<span
									className="wc-block-product-filter-elements__effect"
									aria-hidden="true"
								/>
							</div>
						);
					} ) }
			</div>
		</div>
	);
};

export default Edit;
