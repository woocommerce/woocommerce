/* global wc_enhanced_select_params */
/* global wpApiSettings */
jQuery( function( $ ) {

    function getEnhancedSelectFormatString() {
        return {
            'language': {
                errorLoading: function() {
                    // Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
                    return wc_enhanced_select_params.i18n_searching;
                },
                inputTooLong: function( args ) {
                    var overChars = args.input.length - args.maximum;

                    if ( 1 === overChars ) {
                        return wc_enhanced_select_params.i18n_input_too_long_1;
                    }

                    return wc_enhanced_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
                },
                inputTooShort: function( args ) {
                    var remainingChars = args.minimum - args.input.length;

                    if ( 1 === remainingChars ) {
                        return wc_enhanced_select_params.i18n_input_too_short_1;
                    }

                    return wc_enhanced_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
                },
                loadingMore: function() {
                    return wc_enhanced_select_params.i18n_load_more;
                },
                maximumSelected: function( args ) {
                    if ( args.maximum === 1 ) {
                        return wc_enhanced_select_params.i18n_selection_too_long_1;
                    }

                    return wc_enhanced_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
                },
                noResults: function() {
                    return wc_enhanced_select_params.i18n_no_matches;
                },
                searching: function() {
                    return wc_enhanced_select_params.i18n_searching;
                }
            }
        };
    }

    try {
        $( document.body )
            .on( 'wc-enhanced-select-init', function() {
                // Ajax category search boxes
                $( ':input.wc-brands-search' ).filter( ':not(.enhanced)' ).each( function() {
                    var select2_args = $.extend( {
                        allowClear        : $( this ).data( 'allow_clear' ) ? true : false,
                        placeholder       : $( this ).data( 'placeholder' ),
                        minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : 3,
                        escapeMarkup      : function( m ) {
                            return m;
                        },
                        ajax: {
                            url:         wpApiSettings.root + 'wc/v3/products/brands',
                            dataType:    'json',
                            delay:       250,
                            headers: {
                                'X-WP-Nonce': wpApiSettings.nonce
                            },
                            data:        function( params ) {
                                return {
                                    hide_empty: 1,
                                    search:     params.term
                                };
                            },
                            processResults: function( data ) {
                                const results = data
                                    .map( term => ({ id: term.slug, text: term.name + ' (' + term.count + ')' }) )
                                return {
                                    results
                                };
                            },
                            cache: true
                        }
                    }, getEnhancedSelectFormatString() );

                    $( this ).selectWoo( select2_args ).addClass( 'enhanced' );
                });
            })
            .trigger( 'wc-enhanced-select-init' );
    } catch( err ) {
        // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
        window.console.log( err );
    }
});