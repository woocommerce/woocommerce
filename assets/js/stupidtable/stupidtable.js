// Stupid jQuery table plugin.

// Call on a table
// sortFns: Sort functions for your datatypes.
(function($) {

  $.fn.stupidtable = function(sortFns) {
    return this.each(function() {
      var $table = $(this);
      sortFns = sortFns || {};

      // Merge sort functions with some default sort functions.
      sortFns = $.extend({}, $.fn.stupidtable.default_sort_fns, sortFns);


      // ==================================================== //
      //                  Begin execution!                    //
      // ==================================================== //

      // Do sorting when THs are clicked
      $table.on("click.stupidtable", "thead th", function() {
        var $this = $(this);
        var th_index = 0;
        var dir = $.fn.stupidtable.dir;

        // Account for colspans
        $this.parents("tr").find("th").slice(0, $this.index()).each(function() {
          var cols = $(this).attr("colspan") || 1;
          th_index += parseInt(cols,10);
        });

        // Determine (and/or reverse) sorting direction, default `asc`
        var sort_dir = $this.data("sort-default") || dir.ASC;
        if ($this.data("sort-dir"))
           sort_dir = $this.data("sort-dir") === dir.ASC ? dir.DESC : dir.ASC;

        // Choose appropriate sorting function.
        var type = $this.data("sort") || null;

        // Prevent sorting if no type defined
        if (type === null) {
          return;
        }

        // Trigger `beforetablesort` event that calling scripts can hook into;
        // pass parameters for sorted column index and sorting direction
        $table.trigger("beforetablesort", {column: th_index, direction: sort_dir});
        // More reliable method of forcing a redraw
        $table.css("display");

        // Run sorting asynchronously on a timeout to force browser redraw after
        // `beforetablesort` callback. Also avoids locking up the browser too much.
        setTimeout(function() {
          // Gather the elements for this column
          var sortMethod = sortFns[type];

          $table.children("tbody").each(function(index,tbody){
              var column = [];
              var $tbody = $(tbody);
              var trs = $tbody.children("tr").not('[data-sort-ignore]');

              // Extract the data for the column that needs to be sorted and pair it up
              // with the TR itself into a tuple
              trs.each(function(index,tr) {
                var $e = $(tr).children().eq(th_index);
                var sort_val = $e.data("sort-value");
                var order_by = typeof(sort_val) !== "undefined" ? sort_val : $e.text();
                column.push([order_by, tr]);
              });

              // Sort by the data-order-by value
              column.sort(function(a, b) { return sortMethod(a[0], b[0]); });
              if (sort_dir != dir.ASC)
                column.reverse();

              // Replace the content of tbody with the sorted rows. Strangely (and
              // conveniently!) enough, .append accomplishes this for us.
              trs = $.map(column, function(kv) { return kv[1]; });
              $tbody.append(trs);
          });

          // Reset siblings
          $table.find("th").data("sort-dir", null).removeClass("sorting-desc sorting-asc");
          $this.data("sort-dir", sort_dir).addClass("sorting-"+sort_dir);

          // Trigger `aftertablesort` event. Similar to `beforetablesort`
          $table.trigger("aftertablesort", {column: th_index, direction: sort_dir});
          // More reliable method of forcing a redraw
          $table.css("display");
        }, 10);
      });
    });
  };

  // Enum containing sorting directions
  $.fn.stupidtable.dir = {ASC: "asc", DESC: "desc"};

  $.fn.stupidtable.default_sort_fns = {
    "int": function(a, b) {
      return parseInt(a, 10) - parseInt(b, 10);
    },
    "float": function(a, b) {
      return parseFloat(a) - parseFloat(b);
    },
    "string": function(a, b) {
      return a.localeCompare(b);
    },
    "string-ins": function(a, b) {
      a = a.toLocaleLowerCase();
      b = b.toLocaleLowerCase();
      return a.localeCompare(b);
    }
  };

})(jQuery);
