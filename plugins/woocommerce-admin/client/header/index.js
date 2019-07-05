/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, findDOMNode } from '@wordpress/element';
import classnames from 'classnames';
import { decodeEntities } from '@wordpress/html-entities';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanel from './activity-panel';

class Header extends Component {
	constructor() {
		super();
		this.state = {
			isScrolled: false,
		};

		this.onWindowScroll = this.onWindowScroll.bind( this );
		this.updateIsScrolled = this.updateIsScrolled.bind( this );
	}

	componentDidMount() {
		this.threshold = findDOMNode( this ).offsetTop;
		window.addEventListener( 'scroll', this.onWindowScroll );
		this.updateIsScrolled();
	}

	componentWillUnmount() {
		window.removeEventListener( 'scroll', this.onWindowScroll );
		window.cancelAnimationFrame( this.handle );
	}

	onWindowScroll() {
		this.handle = window.requestAnimationFrame( this.updateIsScrolled );
	}

	updateIsScrolled() {
		const isScrolled = window.pageYOffset > this.threshold - 20;
		if ( isScrolled !== this.state.isScrolled ) {
			this.setState( {
				isScrolled: isScrolled,
			} );
		}
	}

	render() {
		const { sections, isEmbedded } = this.props;
		const { isScrolled } = this.state;
		const _sections = Array.isArray( sections ) ? sections : [ sections ];

		const documentTitle = _sections
			.map( section => {
				return Array.isArray( section ) ? section[ 1 ] : section;
			} )
			.reverse()
			.join( ' &lsaquo; ' );

		document.title = decodeEntities(
			sprintf(
				__( '%1$s &lsaquo; %2$s &#8212; WooCommerce', 'woocommerce-admin' ),
				documentTitle,
				wcSettings.siteTitle
			)
		);

		const className = classnames( 'woocommerce-layout__header', {
			'is-scrolled': isScrolled,
		} );

		return (
			<div className={ className }>
				<h1 className="woocommerce-layout__header-breadcrumbs">
					<span>
						<Link href={ 'admin.php?page=wc-admin' } type={ isEmbedded ? 'wp-admin' : 'wc-admin' }>
							WooCommerce
						</Link>
					</span>
					{ _sections.map( ( section, i ) => {
						const sectionPiece = Array.isArray( section ) ? (
							<Link
								href={ isEmbedded ? section[ 0 ] : getNewPath( {}, section[ 0 ], {} ) }
								type={ isEmbedded ? 'wp-admin' : 'wc-admin' }
							>
								{ section[ 1 ] }
							</Link>
						) : (
							section
						);
						return <span key={ i }>{ sectionPiece }</span>;
					} ) }
				</h1>
				{ window.wcAdminFeatures[ 'activity-panels' ] && <ActivityPanel /> }
			</div>
		);
	}
}

Header.propTypes = {
	sections: PropTypes.node.isRequired,
	isEmbedded: PropTypes.bool,
};

Header.defaultProps = {
	isEmbedded: false,
};

export default Header;
