/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Fulfillment } from '../../data/types';
import { PostListIcon } from '../../utils/icons';
import FulfillmentCard from '../user-interface/fulfillments-card/card';
import MetaList from '../user-interface/meta-list/meta-list';

interface MetadataViewerProps {
	fulfillment: Fulfillment;
}

export default function MetadataViewer( { fulfillment }: MetadataViewerProps ) {
	const publicMetadata = fulfillment.meta_data.filter(
		( meta ) => meta.key.startsWith( '_' ) === false
	);

	return (
		<FulfillmentCard
			isCollapsable={ true }
			header={
				<>
					<PostListIcon />
					<h3>{ __( 'Fulfillment details', 'woocommerce' ) }</h3>
				</>
			}
		>
			{ publicMetadata.length === 0 && (
				<p>{ __( 'No metadata available.', 'woocommerce' ) }</p>
			) }
			{ publicMetadata.length > 0 && (
				<MetaList
					metaList={ publicMetadata.map( ( d ) => {
						return { label: d.key, value: d.value as string };
					} ) }
				/>
			) }
		</FulfillmentCard>
	);
}
