/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import './meta-list.scss';

function MetaValue( { value, href }: { value: string; href?: string } ) {
	if ( isEmpty( String( value ) ) ) {
		return <>{ __( '(empty)', 'woocommerce' ) }</>;
	}

	if ( href ) {
		return (
			<a href={ href } target="_blank" rel="noopener noreferrer">
				{ String( value ) }
			</a>
		);
	}

	return <>{ String( value ) }</>;
}

export default function MetaList( {
	metaList,
}: {
	metaList: Array< {
		label: string;
		value: string;
		href?: string;
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
						<MetaValue value={ meta.value } href={ meta.href } />
					</div>
				</li>
			) ) }
		</ul>
	);
}
