/**
 * Admin JavaScript for WooCommerce Analytics
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Initialize tooltips
    $(".wc-analytics-tooltip").hover(
      function () {
        $(this).attr("title", $(this).data("tooltip"));
      },
      function () {
        $(this).removeAttr("title");
      }
    );

    // Table row highlighting
    $(".wp-list-table tbody tr").hover(
      function () {
        $(this).css("background-color", "#f5f5f5");
      },
      function () {
        $(this).css("background-color", "");
      }
    );

    // Confirm before export
    $(".wc-analytics-export-btn").on("click", function (e) {
      var confirmed = confirm("Export data to CSV?");
      if (!confirmed) {
        e.preventDefault();
      }
    });

    // Auto-refresh data (optional)
    if (
      typeof wcAnalyticsAutoRefresh !== "undefined" &&
      wcAnalyticsAutoRefresh.enabled
    ) {
      setInterval(function () {
        location.reload();
      }, wcAnalyticsAutoRefresh.interval * 1000);
    }

    // Date range validation
    $("form.wc-analytics-date-filter").on("submit", function (e) {
      var startDate = $("#start_date").val();
      var endDate = $("#end_date").val();

      if (startDate && endDate) {
        var start = new Date(startDate);
        var end = new Date(endDate);

        if (start > end) {
          alert("Start date must be before end date.");
          e.preventDefault();
          return false;
        }
      }
    });

    // Copy to clipboard functionality
    $(".wc-analytics-copy-btn").on("click", function (e) {
      e.preventDefault();
      var text = $(this).data("copy");

      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function () {
          alert("Copied to clipboard!");
        });
      } else {
        // Fallback
        var temp = $("<input>");
        $("body").append(temp);
        temp.val(text).select();
        document.execCommand("copy");
        temp.remove();
        alert("Copied to clipboard!");
      }
    });

    // Filter tables
    $(".wc-analytics-table-filter").on("keyup", function () {
      var value = $(this).val().toLowerCase();
      var table = $(this).data("table");

      $(table + " tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });

    // Expandable rows
    $(".wc-analytics-expandable").on("click", function () {
      $(this).next(".wc-analytics-expanded-content").slideToggle();
      $(this)
        .find(".dashicons")
        .toggleClass("dashicons-arrow-down dashicons-arrow-up");
    });

    // Number formatting
    $(".wc-analytics-number").each(function () {
      var num = parseFloat($(this).text());
      if (!isNaN(num)) {
        $(this).text(num.toLocaleString());
      }
    });

    // Percentage color coding
    $(".wc-analytics-percentage").each(function () {
      var value = parseFloat($(this).text());
      if (!isNaN(value)) {
        if (value >= 70) {
          $(this).css("color", "#46b450");
        } else if (value >= 40) {
          $(this).css("color", "#f56e28");
        } else {
          $(this).css("color", "#dc3232");
        }
      }
    });

    // Loading indicator for AJAX
    $(document)
      .ajaxStart(function () {
        $(".wc-analytics-content").addClass("wc-analytics-loading");
      })
      .ajaxStop(function () {
        $(".wc-analytics-content").removeClass("wc-analytics-loading");
      });
  });
})(jQuery);
