/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useContext,
	useState,
} from '@wordpress/element';
import { FormToggle, Icon, Popover } from '@wordpress/components';
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
 * Info button next to the filter label. Opens a popover with the badge
 * explanation and, when the API provides a docs URL, a "Learn more" link.
 * A popover (not a tooltip) so the link stays reachable by pointer and
 * keyboard.
 */
function QualityBadgeInfo( props: {
	label: string;
	tooltip: string;
	docsUrl?: string;
} ) {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ anchor, setAnchor ] = useState< HTMLButtonElement | null >( null );

	return (
		<>
			<button
				ref={ setAnchor }
				type="button"
				className="woocommerce-marketplace__quality-badge-filter__info"
				aria-expanded={ isOpen }
				aria-label={ sprintf(
					// translators: %s: name of the quality badge, supplied by the WooCommerce.com API (e.g. "Excellence Verified").
					__( 'About %s', 'woocommerce' ),
					props.label
				) }
				onClick={ () => setIsOpen( ! isOpen ) }
			>
				<Icon icon={ info } size={ 16 } />
			</button>
			{ isOpen && (
				<Popover
					className="woocommerce-marketplace__quality-badge-filter__popover"
					anchor={ anchor }
					placement="bottom"
					focusOnMount="firstElement"
					onClose={ () => {
						setIsOpen( false );
						anchor?.focus();
					} }
				>
					<p>{ props.tooltip }</p>
					{ props.docsUrl && (
						<a
							href={ props.docsUrl }
							target="_blank"
							rel="noreferrer"
							aria-label={ sprintf(
								// translators: %s: name of the quality badge, supplied by the WooCommerce.com API (e.g. "Excellence Verified").
								__(
									'Learn more about the %s badge',
									'woocommerce'
								),
								props.label
							) }
							onClick={ () =>
								recordEvent(
									'marketplace_quality_badge_learn_more_clicked'
								)
							}
						>
							{ __( 'Learn more', 'woocommerce' ) }
						</a>
					) }
				</Popover>
			) }
		</>
	);
}

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
			<QualityBadgeIcon size={ 18 } />
			<label htmlFor={ toggleId }>
				{ createInterpolateElement(
					sprintf(
						// translators: %s: name of the quality badge, supplied by the WooCommerce.com API (e.g. "Excellence Verified").
						__(
							'Show only <badgeName>%s</badgeName>',
							'woocommerce'
						),
						badge.label
					),
					{
						badgeName: (
							<span className="woocommerce-marketplace__quality-badge-filter__name" />
						),
					}
				) }
			</label>
			{ badge.tooltip && (
				<QualityBadgeInfo
					label={ badge.label }
					tooltip={ badge.tooltip }
					docsUrl={ badge.docs_url }
				/>
			) }
			<FormToggle
				id={ toggleId }
				checked={ isActive }
				onChange={ onChange }
			/>
		</div>
	);
}
