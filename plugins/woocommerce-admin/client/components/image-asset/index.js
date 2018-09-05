/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * A component that loads an image, allowing images to be loaded relative to the main asset/plugin folder.
 * Props are passed through to `<img />`
 */
class ImageAsset extends Component {
	render() {
		const { src, alt, ...restOfProps } = this.props;
		let illustrationSrc = src;

		if ( illustrationSrc.indexOf( '/' ) === 0 ) {
			illustrationSrc = illustrationSrc.substring( 1 );
			illustrationSrc = wcSettings.wcAdminAssetUrl + illustrationSrc;
		}

		return <img src={ illustrationSrc } alt={ alt || '' } { ...restOfProps } />;
	}
}

ImageAsset.propTypes = {
	/**
	 * Image location to pass through to `<img />`.
	 */
	src: PropTypes.string.isRequired,
	/**
	 * Alt text to pass through to `<img />`.
	 */
	alt: PropTypes.string.isRequired,
};

export default ImageAsset;
