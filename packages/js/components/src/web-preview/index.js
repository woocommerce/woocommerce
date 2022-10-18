/**
 * External dependencies
 */
import classnames from 'classnames';
import { createElement, Component, createRef } from '@wordpress/element';
import { noop } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Spinner from '../spinner';

/**
 * WebPreview component to display an iframe of another page.
 */
class WebPreview extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			isLoading: true,
		};

		this.iframeRef = createRef();
		this.setLoaded = this.setLoaded.bind( this );
	}

	componentDidMount() {
		this.iframeRef.current.addEventListener( 'load', this.setLoaded );
	}

	setLoaded() {
		this.setState( { isLoading: false } );
		this.props.onLoad();
	}

	render() {
		const { className, loadingContent, src, title } = this.props;
		const { isLoading } = this.state;

		const classes = classnames( 'woocommerce-web-preview', className, {
			'is-loading': isLoading,
		} );

		return (
			<div className={ classes }>
				{ isLoading && loadingContent }
				<div className="woocommerce-web-preview__iframe-wrapper">
					<iframe
						ref={ this.iframeRef }
						title={ title }
						src={ src }
					/>
				</div>
			</div>
		);
	}
}

WebPreview.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * Content shown when iframe is still loading.
	 */
	loadingContent: PropTypes.node,
	/**
	 * Function to fire when iframe content is loaded.
	 */
	onLoad: PropTypes.func,
	/**
	 * Iframe src to load.
	 */
	src: PropTypes.string.isRequired,
	/**
	 * Iframe title.
	 */
	title: PropTypes.string.isRequired,
};

WebPreview.defaultProps = {
	loadingContent: <Spinner />,
	onLoad: noop,
};

export default WebPreview;
