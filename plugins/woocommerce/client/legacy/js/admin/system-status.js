/* global jQuery, woocommerce_admin_system_status, wcSetClipboard, wcClearClipboard, wp */
jQuery( function ( $ ) {
	/**
	 * Users country and state fields
	 */
	var wcSystemStatus = {
		toolsPollTimer: null,
		toolsPollInProgress: false,

		init: function () {
			$( document.body )
				.on(
					'click',
					'a.help_tip, a.woocommerce-help-tip, woocommerce-product-type-tip',
					this.preventTipTipClick
				)
				.on( 'click', 'a.debug-report', this.requestReport )
				.on( 'click', '#copy-for-support', this.copyReport )
				.on( 'click', '#copy-for-github', this.copyGithubReport )
				.on( 'aftercopy', '#copy-for-support, #copy-for-github', this.copySuccess )
				.on( 'aftercopyfailure', '#copy-for-support, #copy-for-github', this.copyFail )
				.on( 'click', '#download-for-support', this.downloadReport );

			const loadCountsButton = document.getElementById( 'wc-status-load-post-counts' );
			if ( loadCountsButton ) {
				loadCountsButton.addEventListener( 'click', () => this.loadPostCounts().catch( () => {} ) );
			}
			const withoutCountsButton = document.getElementById( 'wc-status-report-without-counts' );
			if ( withoutCountsButton ) {
				withoutCountsButton.addEventListener( 'click', () => {
					const body = document.querySelector( '#wc-status-post-counts tbody' );
					body.replaceChildren( this.postCountRow( wp.i18n.__( 'Post counts', 'woocommerce' ),
						wp.i18n.__( 'Unavailable — count request failed.', 'woocommerce' ) ) );
					this.reportWithoutCounts = true;
					this.generateReport.call( document.querySelector( 'a.debug-report' ) );
					withoutCountsButton.style.display = 'none';
				} );
			}
			this.maybePollTools();
		},

		postCountsRequest: null,
		reportWithoutCounts: false,

		/**
		 * Build a report row without interpreting post type names as HTML.
		 */
		postCountRow: function( type, count ) {
			const row = document.createElement( 'tr' );
			[ type, '', count ].forEach( ( value, index ) => {
				const cell = document.createElement( 'td' );
				cell.textContent = value;
				if ( index === 1 ) {
					cell.className = 'help';
				}
				row.appendChild( cell );
			} );
			return row;
		},

		/**
		 * Load exact counts once per page, sharing an in-flight request with report generation.
		 */
		loadPostCounts: function() {
			const table = document.getElementById( 'wc-status-post-counts' );
			if ( ! table || table.dataset.loaded === 'true' ) {
				return Promise.resolve();
			}
			if ( this.postCountsRequest ) {
				return this.postCountsRequest;
			}

			const button = document.getElementById( 'wc-status-load-post-counts' );
			const status = document.getElementById( 'wc-status-post-counts-message' );
			button.disabled = true;
			status.textContent = wp.i18n.__( 'Counting posts… You can continue viewing the rest of this page.', 'woocommerce' );
			table.setAttribute( 'aria-busy', 'true' );

			this.postCountsRequest = wp.apiFetch( { path: '/wc/v3/system_status?_fields=post_type_counts' } )
				.then( ( response ) => {
					if ( ! Array.isArray( response.post_type_counts ) || ! response.post_type_counts.every( ( item ) =>
						item && typeof item.type === 'string' && /^\d+$/.test( String( item.count ) ) ) ) {
						throw new Error( 'Invalid post counts response.' );
					}
					const body = table.querySelector( 'tbody' );
					const rows = response.post_type_counts.map( ( item ) => this.postCountRow( item.type, item.count ) );
					if ( ! rows.length ) {
						rows.push( this.postCountRow(
							wp.i18n.__( 'Post counts', 'woocommerce' ), wp.i18n.__( 'No posts found.', 'woocommerce' )
						) );
					}
					body.replaceChildren( ...rows );
					table.dataset.loaded = 'true';
					table.setAttribute( 'aria-busy', 'false' );
					button.style.display = 'none';
					document.getElementById( 'wc-status-report-without-counts' ).style.display = 'none';
					status.textContent = wp.i18n.__( 'Counts collected just now.', 'woocommerce' );
					if ( this.reportWithoutCounts ) {
						this.reportWithoutCounts = false;
						this.generateReport.call( document.querySelector( 'a.debug-report' ) );
					}
				} )
				.catch( ( error ) => {
					this.postCountsRequest = null;
					table.setAttribute( 'aria-busy', 'false' );
					button.disabled = false;
					button.textContent = wp.i18n.__( 'Try again', 'woocommerce' );
					status.textContent = wp.i18n.__(
						'Post counts could not be loaded. The rest of your system information is available.', 'woocommerce'
					);
					throw error;
				} );
			return this.postCountsRequest;
		},

		/**
		 * Wait for post counts before offering a complete report for copying or download.
		 */
		requestReport: function( event ) {
			event.preventDefault();
			const button = this;
			if ( button.getAttribute( 'aria-disabled' ) === 'true' ) {
				return;
			}
			const originalLabel = button.textContent;
			const withoutCountsButton = document.getElementById( 'wc-status-report-without-counts' );
			withoutCountsButton.style.display = 'none';
			button.setAttribute( 'aria-disabled', 'true' );
			button.textContent = wp.i18n.__( 'Preparing report…', 'woocommerce' );
			wcSystemStatus.loadPostCounts().then( () => {
				button.removeAttribute( 'aria-disabled' );
				button.textContent = originalLabel;
				wcSystemStatus.generateReport.call( button );
			}, () => {
				button.removeAttribute( 'aria-disabled' );
				button.textContent = wp.i18n.__( 'Retry system report', 'woocommerce' );
				withoutCountsButton.style.display = '';
			} );
		},

		/**
		 * Start polling the tools page if a background tool is running.
		 */
		maybePollTools: function() {
			if (
				this.toolsPollTimer ||
				! woocommerce_admin_system_status.tools_url ||
				! this.shouldPollTools()
			) {
				return;
			}

			this.toolsPollTimer = window.setInterval(
				$.proxy( this.pollTools, this ),
				parseInt( woocommerce_admin_system_status.tools_poll_interval, 10 ) || 10000
			);
		},

		/**
		 * Check whether any tool rows still need polling.
		 *
		 * @return {Bool}
		 */
		shouldPollTools: function() {
			return $( '.wc_status_table--tools tr.requires-refresh' ).is( function() {
				var $row = $( this );
				return $row.find( '.run-tool-status' ).length > 0 || $row.find( '.run-tool .button:disabled' ).length > 0;
			});
		},

		/**
		 * Refresh background tool rows from the tools page.
		 */
		pollTools: function() {
			if ( this.toolsPollInProgress ) {
				return;
			}

			this.toolsPollInProgress = true;

			var self = this;
			var toolsUrl = woocommerce_admin_system_status.tools_url;
			var pollUrl = toolsUrl + ( toolsUrl.indexOf( '?' ) === -1 ? '?' : '&' ) + 'wc_status_tools_poll=' + Date.now();

			$.get( pollUrl )
				.done( function( response ) {
					var $response = $( '<div>' ).append( $.parseHTML( response ) );

					$( '.wc_status_table--tools tr.requires-refresh' ).each( function() {
						var $currentRow = $( this );
						var action      = $currentRow.data( 'tool-action' );

						if ( ! action ) {
							return;
						}

						var $updatedRow = $response.find( '.wc_status_table--tools tr[data-tool-action="' + action + '"]' );

						if ( $updatedRow.length ) {
							$currentRow.replaceWith( $updatedRow );
						}
					});

					if ( ! self.shouldPollTools() ) {
						window.clearInterval( self.toolsPollTimer );
						self.toolsPollTimer = null;
					}
				})
				.fail( function() {
					window.clearInterval( self.toolsPollTimer );
					self.toolsPollTimer = null;
				})
				.always( function() {
					self.toolsPollInProgress = false;
				});
		},

		/**
		 * Prevent anchor behavior when click on TipTip.
		 *
		 * @return {Bool}
		 */
		preventTipTipClick: function() {
			return false;
		},

		/**
		 * Generate system status report.
		 *
		 * @return {Bool}
		 */
		generateReport: function() {
			var report = '';

			$( '.wc_status_table thead, .wc_status_table tbody' ).each( function() {
				if ( $( this ).is( 'thead' ) ) {
					var label = $( this ).find( 'th:eq(0)' ).data( 'exportLabel' ) || $( this ).text();
					report = report + '\n### ' + label.trim() + ' ###\n\n';
				} else {
					$( 'tr', $( this ) ).each( function() {
						var label       = $( this ).find( 'td:eq(0)' ).data( 'exportLabel' ) || $( this ).find( 'td:eq(0)' ).text();
						var the_name    = label.trim().replace( /(<([^>]+)>)/ig, '' ); // Remove HTML.

						// Find value
						var $value_html = $( this ).find( 'td:eq(2)' ).clone();
						$value_html.find( '.private' ).remove();
						$value_html.find( '.dashicons-yes' ).replaceWith( '&#10004;' );
						$value_html.find( '.dashicons-no-alt, .dashicons-warning' ).replaceWith( '&#10060;' );

						// Format value
						var the_value   = $value_html.text().trim();
						var value_array = the_value.split( ', ' );

						if ( value_array.length > 1 ) {
							// If value have a list of plugins ','.
							// Split to add new line.
							var temp_line ='';
							$.each( value_array, function( key, line ) {
								temp_line = temp_line + line + '\n';
							});

							the_value = temp_line;
						}

						if ( the_name || the_value ) {
							report = report + '' + the_name + ': ' + the_value + '\n';
						} else {
							report = report + '\n';
						}
					});
				}
			});

			try {
				$( '#debug-report' ).slideDown();
				$( '#debug-report' ).find( 'textarea' ).val( '`' + report + '`' ).trigger( 'focus' ).trigger( 'select' );
				$( this ).fadeOut();
				return false;
			} catch ( e ) {
				/* jshint devel: true */
				window.console.log( e );
			}

			return false;
		},

		/**
		 * Copy for report.
		 *
		 * @param {Object} evt Copy event.
		 */
		copyReport: function( evt ) {
			wcClearClipboard();
			wcSetClipboard( $( '#debug-report' ).find( 'textarea' ).val(), $( this ) );
			evt.preventDefault();
		},
		/**
		 * Apply redactions
		 */
		applyRedactions( report ) {
			var redactions = [
				{
					regex: /(WordPress address \(URL\):)[^\n]*/,
					replacement: "$1 [Redacted]"
				},
				{
					regex: /(Site address \(URL\):)[^\n]*/,
					replacement: "$1 [Redacted]"
				},
				{
					regex: /(### Database ###\n)([\s\S]*?)(\n### Post Type Counts ###)/,
					replacement: "$1\n[REDACTED]\n$3"
				}
			];

			redactions.forEach( function( redaction ) {
				report = report.replace( redaction.regex, redaction.replacement );
			});
			return report;
		},
		/**
		 * Copy for GitHub report.
		 *
		 * @param {Object} event Copy event.
		 */
		copyGithubReport: function( event ) {
			wcClearClipboard();
			var reportValue = $( '#debug-report' ).find( 'textarea' ).val();
			var redactedReport = wcSystemStatus.applyRedactions( reportValue );

			var reportForGithub = '<details><summary>System Status Report</summary>\n\n``' + redactedReport + '``\n</details>';

			wcSetClipboard( reportForGithub, $( this ) );
			event.preventDefault();
		},

		/**
		 * Display a "Copied!" tip when success copying
		 */
		copySuccess: function( event ) {
			$( event.target ).tipTip({
				'attribute':  'data-tip',
				'activation': 'focus',
				'fadeIn':     50,
				'fadeOut':    50,
				'delay':      0
			}).trigger( 'focus' );
		},

		/**
		 * Displays the copy error message when failure copying.
		 */
		copyFail: function() {
			$( '.copy-error' ).removeClass( 'hidden' );
			$( '#debug-report' ).find( 'textarea' ).trigger( 'focus' ).trigger( 'select' );
		},

		downloadReport: function() {
			var ssr_text = new Blob( [ $( '#debug-report' ).find( 'textarea' ).val() ], { type: 'text/plain' } );

			var domain = window.location.hostname;
			var datetime = new Date().toISOString().slice( 0, 19 ).replace( /:/g, '-' );

			var a = document.createElement( 'a' );
			a.download = 'SystemStatusReport_' + domain + '_' + datetime + '.txt';
			a.href = window.URL.createObjectURL( ssr_text );
			a.textContent = 'Download ready';
			a.style='display:none';
			a.click();
			a.remove();
		}
	};

	wcSystemStatus.init();

	$( '.wc_status_table' ).on( 'click', '.run-tool input.button', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( woocommerce_admin_system_status.run_tool_confirmation );
	});

	$( '#log-viewer-select' ).on( 'click', 'h2 a.page-title-action', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( woocommerce_admin_system_status.delete_log_confirmation );
	});
});
