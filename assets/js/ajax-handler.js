/**
 * AJAX Handler for WooCommerce Analytics
 */

(function ($) {
  "use strict";

  var wcAnalyticsAjax = {
    /**
     * Initialize
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Bind events
     */
    bindEvents: function () {
      // Refresh LTV calculations
      $("#wc-analytics-refresh-ltv").on("click", this.refreshLTV);

      // Update campaign spend
      $(".wc-analytics-update-spend").on("click", this.updateCampaignSpend);

      // Load more data
      $(".wc-analytics-load-more").on("click", this.loadMore);
    },

    /**
     * Refresh all customer LTV calculations
     */
    refreshLTV: function (e) {
      e.preventDefault();

      if (!confirm("This will recalculate LTV for all customers. Continue?")) {
        return;
      }

      var $button = $(this);
      $button.prop("disabled", true).text("Calculating...");

      $.ajax({
        url: wcAnalytics.ajaxUrl,
        type: "POST",
        data: {
          action: "wc_analytics_refresh_ltv",
          nonce: wcAnalytics.nonce,
        },
        success: function (response) {
          if (response.success) {
            alert(
              "Successfully recalculated LTV for " +
                response.data.count +
                " customers."
            );
            location.reload();
          } else {
            alert("Error: " + response.data.message);
          }
        },
        error: function () {
          alert(wcAnalytics.strings.error);
        },
        complete: function () {
          $button.prop("disabled", false).text("Refresh All LTV");
        },
      });
    },

    /**
     * Update campaign marketing spend
     */
    updateCampaignSpend: function (e) {
      e.preventDefault();

      var $form = $(this).closest("form");
      var data = $form.serialize();
      data += "&action=wc_analytics_update_spend&nonce=" + wcAnalytics.nonce;

      $.ajax({
        url: wcAnalytics.ajaxUrl,
        type: "POST",
        data: data,
        success: function (response) {
          if (response.success) {
            alert("Campaign spend updated successfully.");
            location.reload();
          } else {
            alert("Error: " + response.data.message);
          }
        },
        error: function () {
          alert(wcAnalytics.strings.error);
        },
      });
    },

    /**
     * Load more table data
     */
    loadMore: function (e) {
      e.preventDefault();

      var $button = $(this);
      var table = $button.data("table");
      var offset = $button.data("offset");

      $button.prop("disabled", true).text(wcAnalytics.strings.loading);

      $.ajax({
        url: wcAnalytics.ajaxUrl,
        type: "POST",
        data: {
          action: "wc_analytics_load_more",
          table: table,
          offset: offset,
          nonce: wcAnalytics.nonce,
        },
        success: function (response) {
          if (response.success) {
            $(table + " tbody").append(response.data.html);
            $button.data("offset", offset + response.data.loaded);

            if (!response.data.has_more) {
              $button.hide();
            }
          }
        },
        error: function () {
          alert(wcAnalytics.strings.error);
        },
        complete: function () {
          $button.prop("disabled", false).text("Load More");
        },
      });
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    wcAnalyticsAjax.init();
  });
})(jQuery);
