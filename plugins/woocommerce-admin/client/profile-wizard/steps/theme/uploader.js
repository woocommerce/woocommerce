/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import {
	Card,
	DropZoneProvider,
	DropZone,
	FormFileUpload,
} from '@wordpress/components';
import CloudUploadIcon from 'gridicons/dist/cloud-upload';
import { noop } from 'lodash';
import PropTypes from 'prop-types';
import { withDispatch } from '@wordpress/data';
import { H, Spinner } from '@woocommerce/components';
import { isWpVersion } from '@woocommerce/settings';

/**
 * NOTE: This can be removed after WP version 6.0 and replaced with a div.
 *
 * @param {Object} props             React props.
 * @param {Node}   [props.children]  Children of react component.
 * @param {string} [props.className] Additional class name to style the component.
 */
const DropZoneWrapper = ( { children, className } ) => {
	const isDropzoneProviderDepreciated = isWpVersion( '5.8', '>=' );

	if ( isDropzoneProviderDepreciated ) {
		return <div className={ className }>{ children }</div>;
	}
	return (
		<DropZoneProvider>
			<div className={ className }>{ children }</div>
		</DropZoneProvider>
	);
};

class ThemeUploader extends Component {
	constructor() {
		super();

		this.state = {
			isUploading: false,
		};

		this.handleFilesUpload = this.handleFilesUpload.bind( this );
		this.handleFilesDrop = this.handleFilesDrop.bind( this );
	}

	handleFilesDrop( files ) {
		const file = files[ 0 ];
		this.uploadTheme( file );
	}

	handleFilesUpload( e ) {
		const file = e.target.files[ 0 ];
		this.uploadTheme( file );
	}

	uploadTheme( file ) {
		const { createNotice, onUploadComplete } = this.props;
		this.setState( { isUploading: true } );

		const body = new window.FormData();
		body.append( 'pluginzip', file );

		return apiFetch( { path: '/wc-admin/themes', method: 'POST', body } )
			.then( ( response ) => {
				onUploadComplete( response );
				this.setState( { isUploading: false } );
				createNotice( response.status, response.message );
			} )
			.catch( ( error ) => {
				this.setState( { isUploading: false } );
				if ( error && error.message ) {
					createNotice( 'error', error.message );
				}
			} );
	}

	render() {
		const { className } = this.props;
		const { isUploading } = this.state;

		const classes = classnames( 'woocommerce-theme-uploader', className, {
			'is-uploading': isUploading,
		} );

		return (
			<Card className={ classes }>
				<DropZoneWrapper className="woocommerce-theme-uploader__dropzone-wrapper">
					{ ! isUploading ? (
						<Fragment>
							<FormFileUpload
								accept=".zip"
								onChange={ this.handleFilesUpload }
							>
								<CloudUploadIcon />
								<H className="woocommerce-theme-uploader__title">
									{ __( 'Upload a theme', 'woocommerce' ) }
								</H>
								<p>
									{ __(
										'Drop a theme zip file here to upload',
										'woocommerce'
									) }
								</p>
							</FormFileUpload>
							<DropZone
								label={ __(
									'Drop your theme zip file here',
									'woocommerce'
								) }
								onFilesDrop={ this.handleFilesDrop }
							/>
						</Fragment>
					) : (
						<Fragment>
							<Spinner />
							<H className="woocommerce-theme-uploader__title">
								{ __( 'Uploading theme', 'woocommerce' ) }
							</H>
							<p>
								{ __(
									'Your theme is being uploaded',
									'woocommerce'
								) }
							</p>
						</Fragment>
					) }
				</DropZoneWrapper>
			</Card>
		);
	}
}

ThemeUploader.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * Function called when an upload has finished.
	 */
	onUploadComplete: PropTypes.func,
};

ThemeUploader.defaultProps = {
	onUploadComplete: noop,
};

export default compose(
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		return { createNotice };
	} )
)( ThemeUploader );
