/*
 * Simple Placeholder by @marcgg under MIT License
 * Report bugs or contribute on Gihub: https://github.com/marcgg/Simple-Placeholder
*/

(function($) {
  $.simplePlaceholder = {
    placeholderClass: null,

    hidePlaceholder: function(){
      var $this = $(this);
      if($this.val() == $this.attr('placeholder') && $this.data($.simplePlaceholder.placeholderData)){
        $this
          .val("")
          .removeClass($.simplePlaceholder.placeholderClass)
          .data($.simplePlaceholder.placeholderData, false);
      }
    },

    showPlaceholder: function(){
      var $this = $(this);
      if($this.val() == ""){
        $this
          .val($this.attr('placeholder'))
          .addClass($.simplePlaceholder.placeholderClass)
          .data($.simplePlaceholder.placeholderData, true);
      }
    },

    preventPlaceholderSubmit: function(){
      $(this).find(".simple-placeholder").each(function(e){
        var $this = $(this);
        if($this.val() == $this.attr('placeholder') && $this.data($.simplePlaceholder.placeholderData)){
          $this.val('');
        }
      });
      return true;
    }
  };

  $.fn.simplePlaceholder = function(options) {
    if(document.createElement('input').placeholder == undefined){
      var config = {
        placeholderClass : 'placeholding',
        placeholderData : 'simplePlaceholder.placeholding'
      };

      if(options) $.extend(config, options);
      $.extend($.simplePlaceholder, config);

      this.each(function() {
        var $this = $(this);
        $this.focus($.simplePlaceholder.hidePlaceholder);
        $this.blur($.simplePlaceholder.showPlaceholder);
        $this.data($.simplePlaceholder.placeholderData, false);
        if($this.val() == '') {
          $this.val($this.attr("placeholder"));
          $this.addClass($.simplePlaceholder.placeholderClass);
          $this.data($.simplePlaceholder.placeholderData, true);
        }
        $this.addClass("simple-placeholder");
        $(this.form).submit($.simplePlaceholder.preventPlaceholderSubmit);
      });
    }

    return this;
  };

})(jQuery);