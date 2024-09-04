/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { wordpress } from '@wordpress/icons';
import { store as coreDataStore } from '@wordpress/core-data';
import classNames from 'classnames';

type SiteIconProps = {
	className: string;
};

function SiteIcon( { className }: SiteIconProps ) {
	const { isRequestingSite, siteIconUrl } = useSelect( ( select ) => {
		const { getEntityRecord } = select( coreDataStore );
		const siteData: { site_icon_url?: string } = getEntityRecord(
			'root',
			'__unstableBase',
			undefined
		);

		return {
			isRequestingSite: ! siteData,
			siteIconUrl: siteData?.site_icon_url,
		};
	}, [] );

	if ( isRequestingSite && ! siteIconUrl ) {
		return <div className="edit-site-site-icon__image" />;
	}

	const icon = siteIconUrl ? (
		<img
			className="edit-site-site-icon__image"
			alt={ __( 'Site Icon', 'woocommerce' ) }
			src={ siteIconUrl }
		/>
	) : (
		<Icon
			className="edit-site-site-icon__icon"
			icon={ wordpress }
			size={ 48 }
		/>
	);

	return (
		<div className={ classNames( className, 'edit-site-site-icon' ) }>
			{ icon }
		</div>
	);
}

export default SiteIcon;
