/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../data';

export const LOAD_TEMPLATE_VERSION_ACTION_NAME = 'loadTemplateVersion';

/**
 * Load Template Version component.
 *
 * @return {Object} The component
 */
export const LoadTemplateVersion = () => {
	const [ templates, setTemplates ] = useState( [] );
	const [ versions, setVersions ] = useState( [] );
	const [ isLoadingTemplates, setIsLoadingTemplates ] = useState( false );
	const [ isLoadingVersions, setIsLoadingVersions ] = useState( false );

	const { updateCommandParams } = useDispatch( store );

	const params = useSelect(
		( select ) =>
			select( store ).getCommandParams()[
				LOAD_TEMPLATE_VERSION_ACTION_NAME
			] || {},
		[]
	);

	const selectedTemplate = params.template_name || '';
	const selectedVersion = params.version || '';

	// Load templates on component mount
	useEffect( () => {
		loadTemplates();
	}, [] );

	// Load versions when template changes
	useEffect( () => {
		if ( selectedTemplate ) {
			loadVersions( selectedTemplate );
		} else {
			setVersions( [] );
			updateCommandParams( LOAD_TEMPLATE_VERSION_ACTION_NAME, {
				template_name: selectedTemplate,
				version: '',
			} );
		}
	}, [ selectedTemplate ] );

	/**
	 * Load available templates
	 */
	const loadTemplates = async () => {
		setIsLoadingTemplates( true );
		try {
			const response = await apiFetch( {
				path: '/wc-admin-test-helper/tools/get-available-templates',
				method: 'GET',
			} );

			const options = Object.entries( response ).map(
				( [ value, label ] ) => ( {
					value,
					label,
				} )
			);

			setTemplates( options );
			setIsLoadingTemplates( false );
		} catch ( error ) {
			console.error( 'Error loading templates:', error );
			setIsLoadingTemplates( false );
		}
	};

	/**
	 * Load available versions for a template
	 *
	 * @param {string} templateName Template name
	 */
	const loadVersions = async ( templateName ) => {
		setIsLoadingVersions( true );
		try {
			const response = await apiFetch( {
				path: `/wc-admin-test-helper/tools/get-available-versions?template_name=${ templateName }`,
				method: 'GET',
			} );

			const options = response.map( ( version ) => ( {
				value: version,
				label: version,
			} ) );

			setVersions( options );
			if ( options.length > 0 ) {
				updateCommandParams( LOAD_TEMPLATE_VERSION_ACTION_NAME, {
					template_name: templateName,
					version: options[ 0 ].value,
				} );
			}
			setIsLoadingVersions( false );
		} catch ( error ) {
			console.error( 'Error loading versions:', error );
			setIsLoadingVersions( false );
		}
	};

	/**
	 * Handle template selection change
	 *
	 * @param {string} value Selected template
	 */
	const handleTemplateChange = ( value ) => {
		updateCommandParams( LOAD_TEMPLATE_VERSION_ACTION_NAME, {
			template_name: value,
			version: '',
		} );
	};

	/**
	 * Handle version selection change
	 *
	 * @param {string} value Selected version
	 */
	const handleVersionChange = ( value ) => {
		updateCommandParams( LOAD_TEMPLATE_VERSION_ACTION_NAME, {
			template_name: selectedTemplate,
			version: value,
		} );
	};

	return (
		<div>
			<p>
				Load a specific version of a WooCommerce template for testing.
			</p>
			<div style={ { marginBottom: '10px' } }>
				<SelectControl
					label="Template"
					value={ selectedTemplate }
					options={ [
						{
							value: '',
							label: 'Select a template',
							disabled: true,
						},
						...templates,
					] }
					onChange={ handleTemplateChange }
					disabled={ isLoadingTemplates }
				/>
			</div>
			{ selectedTemplate && (
				<div style={ { marginBottom: '10px' } }>
					<SelectControl
						label="Version"
						value={ selectedVersion }
						options={
							versions.length > 0
								? versions
								: [
										{
											value: '',
											label: 'No versions available',
										},
								  ]
						}
						onChange={ handleVersionChange }
						disabled={ isLoadingVersions || versions.length === 0 }
					/>
				</div>
			) }
		</div>
	);
};

export default LoadTemplateVersion;
