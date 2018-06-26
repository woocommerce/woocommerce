/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/utils';
import { Fill } from 'react-slot-fill';
import { isArray, noop } from 'lodash';
import { IconButton } from '@wordpress/components';
import Link from 'components/link';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import WordPressNotices from './wordpress-notices';

const Header = ( { sections, onToggle, isSidebarOpen, isEmbedded } ) => {
	const _sections = isArray( sections ) ? sections : [ sections ];

	const documentTitle = _sections
		.map( section => {
			return isArray( section ) ? section[ 1 ] : section;
		} )
		.reverse()
		.join( ' &lsaquo; ' );

	document.title = decodeEntities(
		sprintf(
			__( '%1$s &lsaquo; %2$s &#8212; WooCommerce', 'woo-dash' ),
			documentTitle,
			wpApiSettings.schema.name
		)
	);

	return (
		<div className="woocommerce-header">
			<h1 className="woocommerce-header__breadcrumbs">
				<span>
					<Link to="/">WooCommerce</Link>
				</span>
				{ _sections.map( ( section, i ) => {
					const sectionPiece = isArray( section ) ? (
						<Link to={ section[ 0 ] } wpAdmin={ isEmbedded }>
							{ section[ 1 ] }
						</Link>
					) : (
						section
					);
					return <span key={ i }>{ sectionPiece }</span>;
				} ) }
			</h1>
			<div className="woocommerce-header__buttons">
				<div className="woocommerce-header__toggle">
					<IconButton
						className="woocommerce-header__button"
						onClick={ onToggle }
						icon="clock"
						label={ __( 'Show Sidebar', 'woo-dash' ) }
						aria-expanded={ isSidebarOpen }
					/>
				</div>
				<WordPressNotices />
			</div>
		</div>
	);
};

Header.propTypes = {
	sections: PropTypes.node.isRequired,
	onToggle: PropTypes.func.isRequired,
	isSidebarOpen: PropTypes.bool,
	isEmbedded: PropTypes.bool,
};

Header.defaultProps = {
	onToggle: noop,
	isEmbedded: false,
};

export default function( props ) {
	return (
		<Fill name="header">
			<Header { ...props } />
		</Fill>
	);
}
