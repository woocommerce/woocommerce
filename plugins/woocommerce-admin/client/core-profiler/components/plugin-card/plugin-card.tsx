/**
 * External dependencies
 */
import { ReactNode } from 'react';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import sanitizeHTML from '~/lib/sanitize-html';
import './plugin-card.scss';

export const PluginCard = ( {
	installed = false,
	icon,
	title,
	onChange,
	checked = false,
	description,
	learnMoreLink,
}: {
	// Checkbox will be hidden if true
	installed?: boolean;
	key?: string;
	icon: ReactNode;
	title: string | ReactNode;
	description: string | ReactNode;
	checked?: boolean;
	onChange?: () => void;
	learnMoreLink?: ReactNode;
} ) => {
	return (
		<div className="woocommerce-profiler-plugins-plugin-card">
			{ ! installed && (
				<CheckboxControl
					checked={ checked }
					onChange={ onChange ? onChange : () => {} }
				/>
			) }
			{ icon }
			<div className="woocommerce-profiler-plugins-plugin-card-text">
				<div
					className={ classnames(
						'woocommerce-profiler-plugins-plugin-card-text-header',
						{
							installed,
						}
					) }
				>
					<h3>{ title }</h3>
					{ installed && (
						<span>{ __( 'Installed', 'woocommerce' ) }</span>
					) }
				</div>
				<p dangerouslySetInnerHTML={ sanitizeHTML( description ) } />
				{ learnMoreLink }
			</div>
		</div>
	);
};
