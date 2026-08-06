/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';
import { FormToggle, Icon, Tooltip } from '@wordpress/components';
import { info } from '@wordpress/icons';
import { useInstanceId } from '@wordpress/compose';
import { recordEvent } from '@woocommerce/tracks';
import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './quality-badge.scss';
import { QualityBadgeIcon } from './quality-badge';
import { MarketplaceContext } from '../../contexts/marketplace-context';

/**
 * "Show only <badge>" toggle for product listings. Renders nothing unless the
 * WooCommerce.com API reports the quality badge as enabled. The filter state
 * lives in the `quality_badge` URL query parameter, which is forwarded to the
 * search API.
 */
export default function QualityBadgeFilter() {
	const { iamSettings } = useContext( MarketplaceContext );
	const query = useQuery();
	const toggleId = useInstanceId(
		QualityBadgeFilter,
		'woocommerce-marketplace-quality-badge-filter'
	) as string;

	const badge = iamSettings?.quality_badge;
	if ( ! badge?.enabled || ! badge.label ) {
		return null;
	}

	const isActive = query.quality_badge === '1';

	const onChange = () => {
		recordEvent( 'marketplace_quality_badge_filter_toggled', {
			state: isActive ? 'off' : 'on',
		} );
		navigateTo( {
			url: getNewPath( { quality_badge: isActive ? undefined : '1' } ),
		} );
	};

	return (
		<div className="woocommerce-marketplace__quality-badge-filter">
			<QualityBadgeIcon size={ 20 } />
			<label htmlFor={ toggleId }>
				{ sprintf(
					// translators: %s: name of the quality badge, supplied by the WooCommerce.com API (e.g. "Excellence Verified").
					__( 'Show only %s', 'woocommerce' ),
					badge.label
				) }
			</label>
			{ badge.tooltip && (
				<Tooltip text={ badge.tooltip }>
					<button
						type="button"
						className="woocommerce-marketplace__quality-badge-filter__info"
						aria-label={ sprintf(
							// translators: %s: name of the quality badge, supplied by the WooCommerce.com API (e.g. "Excellence Verified").
							__( 'About %s', 'woocommerce' ),
							badge.label
						) }
					>
						<Icon icon={ info } size={ 16 } />
					</button>
				</Tooltip>
			) }
			<FormToggle
				id={ toggleId }
				checked={ isActive }
				onChange={ onChange }
			/>
		</div>
	);
}
