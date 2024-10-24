/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Extension } from '@woocommerce/data';
import { Link } from '@woocommerce/components';
import React from 'react';

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
	React.Children.forEach( children, ( child ) => {
		if (
			React.isValidElement( child ) &&
			child.type === PluginCard.LearnMoreLink
		) {
			learnMoreLink = React.cloneElement( child, {
				// @ts-expect-error -- @types/react is deficient here
				learnMoreLink: learnMoreLinkUrl,
			} );
		}
	} );
	return (
		// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
		<div
			className={ clsx( 'woocommerce-profiler-plugins-plugin-card', {
				'is-installed': installed,
				disabled,
			} ) }
			onClick={ ( event ) => {
				if ( ! disabled ) {
					onChange( event );
				}
			} }
			data-slug={ slug }
		>
			<div className="woocommerce-profiler-plugin-card-top">
				{ ! installed && (
					<CheckboxControl
						id={ `${ pluginKey }-checkbox` }
						className="core-profiler__checkbox"
						disabled={ disabled }
						checked={ checked }
						onChange={ ( event ) => {
							if ( ! disabled ) {
								onChange( event );
							}
						} }
						onClick={ ( e ) => e.stopPropagation() }
					/>
				) }
				{ imageUrl ? <img src={ imageUrl } alt={ pluginKey } /> : null }
				<div
					className={ clsx(
						'woocommerce-profiler-plugins-plugin-card-text-header',
						{
							installed,
						}
					) }
				>
					<label htmlFor={ `${ pluginKey }-checkbox` }>
						<h3>{ title }</h3>
					</label>
					{ installed && (
						<span>{ __( 'Installed', 'woocommerce' ) }</span>
					) }
				</div>
			</div>

			<div
				className={ clsx(
					'woocommerce-profiler-plugins-plugin-card-text',
					{ 'smaller-margin-left': installed }
				) }
			>
				<p dangerouslySetInnerHTML={ sanitizeHTML( description ) } />
				{ learnMoreLink }
			</div>
		</div>
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
				event.stopPropagation();
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
