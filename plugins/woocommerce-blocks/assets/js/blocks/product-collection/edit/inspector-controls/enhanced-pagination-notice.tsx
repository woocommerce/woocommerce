/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useUnsupportedBlocks } from './utils';
import { ProductCollectionAttributes } from '../../types';

type EnhancedPaginationNoticeProps = {
	clientId: string;
	attributes: ProductCollectionAttributes;
	setAttributes: () => void;
};

const EnhancedPaginationNotice = ( {
	clientId,
	attributes,
	setAttributes,
}: EnhancedPaginationNoticeProps ) => {
	const unsupportedBlocks = useUnsupportedBlocks( clientId );

	if ( ! unsupportedBlocks.length ) {
		return null;
	}

	const uniqueUnsupportedBlocks = [ ...new Set( unsupportedBlocks ) ];
	const numberOfUnsupportedBlocks = uniqueUnsupportedBlocks.length;

	return (
		<Notice status="warning" isDismissible={ false }>
			{ sprintf(
				/* translators: %d: number of incompatible blocks */
				_n(
					/* translators: %d: number of incompatible blocks */
					'Browsing between pages will cause a full page reload because there is %d block in Product Collection:',
					'Browsing between pages will cause a full page reload because there are %d incompatible block in Product Collection:',
					numberOfUnsupportedBlocks,
					'woocommerce'
				),
				numberOfUnsupportedBlocks
			) }
			<ul>
				{
					// Limit the number of displayed blocks to 5
					uniqueUnsupportedBlocks
						.slice( 0, 5 )
						.map( ( blockName ) => (
							<li key={ blockName }>- { blockName }</li>
						) )
				}
			</ul>
		</Notice>
	);
};

export default EnhancedPaginationNotice;
