/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import './meta-list.scss';

export default function MetaList( {
	metaList,
}: {
	metaList: Array< {
		label: string;
		value: string;
	} >;
} ) {
	return (
		<ul className="woocommerce-fulfillment-meta-list">
			{ metaList.map( ( meta, index ) => (
				<li
					key={ index }
					className="woocommerce-fulfillment-meta-list__item"
				>
					<div className="woocommerce-fulfillment-meta-list__item-label">
						{ meta.label }
					</div>
					<div className="woocommerce-fulfillment-meta-list__item-value">
						{ isEmpty( String( meta.value ) )
							? __( '(empty)', 'woocommerce' )
							: String( meta.value ) }
					</div>
				</li>
			) ) }
		</ul>
	);
}
