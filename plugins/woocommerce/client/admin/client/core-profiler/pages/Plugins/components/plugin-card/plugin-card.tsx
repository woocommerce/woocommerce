/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Extension } from '@woocommerce/data';
import { Link } from '@woocommerce/components';
import {
	useMemo,
	Children,
	isValidElement,
	cloneElement,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import sanitizeHTML from '~/lib/sanitize-html';
import './plugin-card.scss';

export const PluginCard = ( {
	plugin: {
		is_activated: installed = false,
		image_url: imageUrl,
		key: pluginKey,
		label: title,
		description,
		learn_more_link: learnMoreLinkUrl,
	},
	onChange = () => {},
	disabled = false,
	checked = false,
	children,
}: {
	plugin: Pick<
		Extension,
		| 'is_activated'
		| 'image_url'
		| 'key'
		| 'label'
		| 'description'
		| 'learn_more_link'
	>;
	installed?: boolean;
	onChange?: ( arg0: unknown ) => void;
	disabled?: boolean;
	checked?: boolean;
	children?: React.ReactNode;
} ) => {
	let learnMoreLink = null;
	const slug = pluginKey.replace( ':alt', '' );

	Children.forEach( children, ( child ) => {
		if (
			isValidElement( child ) &&
			child.type === PluginCard.LearnMoreLink
		) {
			learnMoreLink = cloneElement( child, {
				// @ts-expect-error -- @types/react is deficient here
				learnMoreLink: learnMoreLinkUrl,
			} );
		}
	} );

	const descriptionText = useMemo( () => {
		const descriptionElement = document.createElement( 'div' );
		descriptionElement.innerHTML = description;
		return descriptionElement.textContent || '';
	}, [ description ] );

	return (
		<label
			className={ clsx( 'woocommerce-profiler-plugins-plugin-card', {
				'is-installed': installed,
				disabled,
			} ) }
			data-slug={ slug }
			htmlFor={ `${ pluginKey }-checkbox` }
		>
			{ /* this label element acts as the catchment area for the checkbox */ }
			{ ! installed && (
				<CheckboxControl
					id={ `${ pluginKey }-checkbox` }
					className="woocommerce-profiler__checkbox"
					disabled={ disabled }
					checked={ checked }
					onChange={ ( event ) => {
						if ( ! disabled ) {
							onChange( event );
						}
					} }
				/>
			) }
			<div className="woocommerce-profiler-plugins-plugin-card-main">
				{ imageUrl ? (
					<img
						className="woocommerce-profiler-plugins-plugin-card-logo"
						src={ imageUrl }
						alt={ pluginKey }
					/>
				) : null }

				<div className="woocommerce-profiler-plugins-plugin-card-content">
					<div
						className={ clsx(
							'woocommerce-profiler-plugins-plugin-card-text-header',
							{
								installed,
							}
						) }
					>
						<h3 className="woocommerce-profiler-plugins-plugin-card-title">
							{ title }
						</h3>
						{ installed && (
							<span>{ __( 'Installed', 'woocommerce' ) }</span>
						) }
					</div>

					<div className="woocommerce-profiler-plugins-plugin-card-text">
						<p
							dangerouslySetInnerHTML={ sanitizeHTML(
								description
							) }
							title={ descriptionText }
						/>
						{ learnMoreLink }
					</div>
				</div>
			</div>
		</label>
	);
};

PluginCard.LearnMoreLink = ( {
	learnMoreLink,
	onClick,
}: {
	learnMoreLink?: Extension[ 'learn_more_link' ];
	onClick?: React.MouseEventHandler< HTMLAnchorElement >;
} ) => (
	<Link
		onClick={ ( event ) => {
			if ( typeof onClick === 'function' ) {
				onClick( event );
			}
		} }
		href={ learnMoreLink ?? '' }
		target="_blank"
		type="external"
	>
		{ __( 'Learn More', 'woocommerce' ) }
	</Link>
);
