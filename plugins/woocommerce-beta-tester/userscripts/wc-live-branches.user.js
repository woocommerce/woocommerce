// ==UserScript==
// @name         WooCommerce Live Branches
// @namespace    https://wordpress.com/
// @version      1.1
// @description  Adds links to PRs pointing to Jurassic Ninja sites for live-testing a changeset
// @grant        GM_xmlhttpRequest
// @connect      jurassic.ninja
// @require      https://code.jquery.com/jquery-3.3.1.min.js
// @match        https://github.com/woocommerce/woocommerce/pull/*
// ==/UserScript==

// Need to declare "jQuery" for linting within TamperMonkey, but in the monorepo it's already declared.
// eslint-disable-next-line no-redeclare
/* global jQuery */

( function () {
	const $ = jQuery.noConflict();
	const markdownBodySelector = '.pull-discussion-timeline .markdown-body';
	const pluginsList = null;

	// Watch for relevant DOM changes that indicate we need to re-run `doit()`:
	// - Adding a new `.markdown-body`.
	// - Removing `#woocommerce-live-branches`.
	const observer = new MutationObserver( ( list ) => {
		for ( const m of list ) {
			for ( const n of m.addedNodes ) {
				if (
					( n.matches && n.matches( markdownBodySelector ) ) ||
					( n.querySelector &&
						n.querySelector( markdownBodySelector ) )
				) {
					doit();
					return;
				}
			}
			for ( const n of m.removedNodes ) {
				if (
					n.id === 'woocommerce-live-branches' ||
					( n.querySelector &&
						n.querySelector( '#woocommerce-live-branches' ) )
				) {
					doit();
					return;
				}
			}
		}
	} );
	observer.observe( document, { subtree: true, childList: true } );

	// Run it on load too.
	doit();

	/**
	 * Determine the current repo.
	 *
	 * Currently looks at the URL, expecting it to match a `@match` pattern from the script header.
	 *
	 * @return {string|null} Repo name.
	 */
	function determineRepo() {
		const m = location.pathname.match( /^\/([^/]+\/[^/]+)\/pull\// );
		return m && m[ 1 ] ? decodeURIComponent( m[ 1 ] ) : null;
	}

	/** Function. */
	function doit() {
		const markdownBody =
			document.querySelectorAll( markdownBodySelector )[ 0 ];
		if (
			! markdownBody ||
			markdownBody.querySelector( '#woocommerce-live-branches' )
		) {
			// No body or Live Branches is already there, no need to do it again.
			return;
		}

		const host = 'https://jurassic.ninja';
		const currentBranch = jQuery( '.head-ref:first' ).text();
		const branchIsForked = currentBranch.includes( ':' );
		const branchStatus = $( '.gh-header-meta .State' ).text().trim();
		const repo = determineRepo();

		if ( branchStatus === 'Merged' ) {
			const contents = `
				<p><strong>This branch is already merged.</strong></p>
				<p><a target="_blank" rel="nofollow noopener" href="${ getLink() }">
					Test with <code>trunk</code> branch instead.
				</a></p>
			`;
			appendHtml( markdownBody, contents );
		} else if ( branchStatus === 'Draft' ) {
			appendHtml(
				markdownBody,
				'<p><strong>This branch is a draft. You can open live branches only from open pull requests.</strong></p>'
			);
		} else if ( branchIsForked ) {
			appendHtml(
				markdownBody,
				"<p><strong>This branch can't be tested live because it comes from a forked version of this repo.</strong></p>"
			);
		} else if ( ! repo ) {
			appendHtml(
				markdownBody,
				'<p><strong>Cannot determine the repository for this PR.</strong></p>'
			);
		} else {
			// TODO: Fetch the list of feature flags dynamically from the API or something.
			const featureFlags = [
				'async-product-editor-category-field',
				'launch-your-store',
				'minified-js',
				'new-product-management-experience',
				'product-custom-fields',
				'settings',
			];

			const contents = `
					<details>
						<summary>Expand for JN site options:</summary>
						<h4>Settings</h4>
						${ getOptionsList(
							[
								{
									label: 'A shortlived site',
									name: 'shortlived',
								},
								{
									checked: true,
									label: '<code>WP_DEBUG</code> and <code>WP_DEBUG_LOG</code> set to true',
									name: 'wp-debug-log',
								},
								{
									label: 'Multisite based on subdomains',
									name: 'subdomain_multisite',
								},
								{
									label: 'Multisite based on subdirectories',
									name: 'subdir_multisite',
								},
								{
									label: 'Pre-generate content',
									name: 'content',
								},
								{
									label: '<code>xmlrpc.php</code> unavailable',
									name: 'blockxmlrpc',
								},
							],
							100
						) }
						<h4>Install additional plugins</h4>
						${ getOptionsList(
							[
								{
									label: 'WooCommerce Smooth Generator',
									name: 'wc-smooth-generator',
								},
								{
									label: 'Jetpack',
									name: 'nojetpack',
									invert: true,
								},
								{
									label: 'WordPress Beta Tester',
									name: 'wordpress-beta-tester',
								},
								{
									label: 'Gutenberg',
									name: 'gutenberg',
								},
								{
									label: 'Classic Editor',
									name: 'classic-editor',
								},
								{
									label: 'AMP',
									name: 'amp',
								},
								{
									label: 'Config Constants',
									name: 'config-constants',
								},
								{
									label: 'Code Snippets',
									name: 'code-snippets',
								},
								{
									label: 'WP Rollback',
									name: 'wp-rollback',
								},
								{
									label: 'WP Downgrade',
									name: 'wp-downgrade',
								},
								{
									label: 'WP Super Cache',
									name: 'wp-super-cache',
								},
								{
									label: 'WP Job Manager',
									name: 'wp-job-manager',
								},
							],
							33
						) }
						<h4>Enable additional feature flags</h4>
						${ getOptionsList(
							featureFlags.map( ( flag ) => ( {
								label: flag,
								name: 'woocommerce-beta-tester-feature-flags',
								value: flag,
							} ) ),
							50
						) }
					</details>
					<p>
						<a id="woocommerce-beta-branch-link" target="_blank" rel="nofollow noopener" href="#">…</a>
					</p>
					`;
			appendHtml( markdownBody, contents );
			updateLink();
		}

		/**
		 * Encode necessary HTML entities in a string.
		 *
		 * @param {string} s - String to encode.
		 * @return {string} Encoded string.
		 */
		function encodeHtmlEntities( s ) {
			return s.replace(
				/[&<>"']/g,
				( m ) => `&#${ m.charCodeAt( 0 ) };`
			);
		}

		/**
		 * Build the JN create URI.
		 *
		 * @return {string} URI.
		 */
		function getLink() {
			const query = [
				'woocommerce-beta-tester',
				`woocommerce-beta-tester-live-branch=${ currentBranch }`,
			];

			const enabledFeatureFlags = [];

			$(
				'#woocommerce-live-branches input[type=checkbox]:checked:not([data-invert]), #woocommerce-live-branches input[type=checkbox][data-invert]:not(:checked)'
			).each( ( i, input ) => {
				if ( input.name === 'woocommerce-beta-tester-feature-flags' ) {
					enabledFeatureFlags.push( input.value );
					return;
				}

				if ( input.value ) {
					query.push(
						encodeURIComponent( input.name ) +
							'=' +
							encodeURIComponent( input.value )
					);
				} else {
					query.push( encodeURIComponent( input.name ) );
				}
			} );

			if ( enabledFeatureFlags.length ) {
				query.push(
					`woocommerce-beta-tester-feature-flags=${ enabledFeatureFlags.join(
						','
					) }`
				);
			}

			// prettier-ignore
			return `${ host }/create?${ query.join( '&' ).replace( /%(2F|5[BD])/g, m => decodeURIComponent( m ) ) }`;
		}

		/**
		 * Build HTML for a single option checkbox.
		 *
		 * @param {Object}  opts            - Options.
		 * @param {string}  opts.label      - Checkbox label HTML.
		 * @param {string}  opts.name       - Checkbox name.
		 * @param {string}  [opts.value]    - Checkbox value, if any.
		 * @param {boolean} [opts.checked]  - Whether the checkbox is default checked.
		 * @param {boolean} [opts.disabled] - Whether the checkbox is disabled.
		 * @param {boolean} [opts.invert]   - Whether the sense of the checkbox is inverted.
		 * @param {number}  columnWidth     - Column width.
		 * @return {string} HTML.
		 */
		function getOption(
			{
				disabled = false,
				checked = false,
				invert = false,
				value = '',
				label,
				name,
			},
			columnWidth
		) {
			// prettier-ignore
			return `
			<li style="min-width: ${ columnWidth }%">
				<label style="font-weight: inherit; ">
					<input type="checkbox" name="${ encodeHtmlEntities( name ) }" value="${ encodeHtmlEntities( value ) }"${ checked ? ' checked' : '' }${ disabled ? ' disabled' : '' }${ invert ? ' data-invert' : '' }>
					${ label }
				</label>
			</li>
			`;
		}

		/**
		 * Build HTML for a set of option checkboxes.
		 *
		 * @param {object[]} options     - Array of options for `getOption()`.
		 * @param {number}   columnWidth - Column width.
		 * @return {string} HTML.
		 */
		function getOptionsList( options, columnWidth ) {
			return `
				<ul style="list-style: none; padding-left: 0; margin-top: 24px; display: flex; flex-wrap: wrap;">
					${ options
						.map( ( option ) => {
							return getOption( option, columnWidth );
						} )
						.join( '' ) }
				</ul>
			`;
		}

		/**
		 * Append HTML to the element.
		 *
		 * Also registers `onInputChanged()` as a change handler for all checkboxes in the HTML.
		 *
		 * @param {HTMLElement} el       - Element.
		 * @param {string}      contents - HTML to append.
		 */
		function appendHtml( el, contents ) {
			const $el = $( el );
			const liveBranches = $(
				'<div id="woocommerce-live-branches" />'
			).append( `<h2>WooCommerce Live Branches</h2> ${ contents }` );
			$( '#woocommerce-live-branches' ).remove();
			$el.append( liveBranches );
			liveBranches
				.find( 'input[type=checkbox]' )
				.each( () =>
					this.addEventListener( 'change', onInputChanged )
				);
		}

		/**
		 * Change handler. Updates the link.
		 *
		 * @param {Event} e - Event object.
		 */
		function onInputChanged( e ) {
			e.stopPropagation();
			e.preventDefault();
			if ( e.target.checked ) {
				e.target.setAttribute( 'checked', true );
			} else {
				e.target.removeAttribute( 'checked' );
			}
			updateLink();
		}

		/**
		 * Update the link.
		 */
		function updateLink() {
			const $link = $( '#woocommerce-beta-branch-link' );
			const url = getLink();

			$link
				.attr( 'href', url )
				.text( 'Create Jurassic Ninja site for this branch.' );
		}
	}
} )();
