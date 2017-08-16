//Contributed by Thewpexperts for color swatch
jQuery(document).ready(function ($) {
    'use strict';
    var $body = $('body');

    for (var i = 1; i <= 3; i++)
    {
        var colorPicker = $('#term-color-picker-' + i).wpColorPicker();
        $(colorPicker).parent().closest('div').addClass('color-picker-' + i);
        $('.color-picker-' + i).hide();
    }
    $body.on('click', '.select_comb_radio', function () {
        var combVal = $(this).val();
        for (var j = 3; j > 0; j--)
        {
            if (combVal < j)
            {
                $('.color-picker-' + j).hide();
            } else
            {
                $('.color-picker-' + j).show();
            }

        }

    });
    var currentUrl = window.location.href;
    if (currentUrl.indexOf('term.php?taxonomy=pa_') > -1)
    {
        var checkedValue = $('input[name="term-color-comb"]:checked').val();
        for (var k = 3; k > 0; k--)
        {
            if (checkedValue < k)
            {
                $('.color-picker-' + k).hide();
            } else
            {
                $('.color-picker-' + k).show();
            }

        }
    }

});

