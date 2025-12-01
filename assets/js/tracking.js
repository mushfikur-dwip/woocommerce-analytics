/**
 * UTM Tracking Script
 *
 * Captures UTM parameters and stores them in cookies
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Get URL parameters
    function getUrlParameter(name) {
      name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
      var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
      var results = regex.exec(location.search);
      return results === null
        ? ""
        : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    // Set cookie
    function setCookie(name, value, days) {
      var expires = "";
      if (days) {
        var date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = "; expires=" + date.toUTCString();
      }
      document.cookie =
        name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
    }

    // Get cookie expiry from localized data
    var cookieExpiry = wcAnalyticsTracking.cookieExpiry || 30;

    // Capture UTM parameters if present
    var utmSource = getUrlParameter("utm_source");
    var utmMedium = getUrlParameter("utm_medium");
    var utmCampaign = getUrlParameter("utm_campaign");
    var utmTerm = getUrlParameter("utm_term");
    var utmContent = getUrlParameter("utm_content");

    // Save UTM parameters to cookies
    if (utmSource) {
      setCookie("wc_analytics_utm_source", utmSource, cookieExpiry);
    }
    if (utmMedium) {
      setCookie("wc_analytics_utm_medium", utmMedium, cookieExpiry);
    }
    if (utmCampaign) {
      setCookie("wc_analytics_utm_campaign", utmCampaign, cookieExpiry);
    }
    if (utmTerm) {
      setCookie("wc_analytics_utm_term", utmTerm, cookieExpiry);
    }
    if (utmContent) {
      setCookie("wc_analytics_utm_content", utmContent, cookieExpiry);
    }

    // Capture referrer on first visit
    if (document.referrer && !getCookie("wc_analytics_referrer")) {
      setCookie("wc_analytics_referrer", document.referrer, cookieExpiry);
    }

    // Capture landing page on first visit
    if (!getCookie("wc_analytics_landing")) {
      setCookie("wc_analytics_landing", window.location.href, cookieExpiry);
    }

    // Get cookie helper
    function getCookie(name) {
      var nameEQ = name + "=";
      var ca = document.cookie.split(";");
      for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === " ") c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0)
          return c.substring(nameEQ.length, c.length);
      }
      return null;
    }
  });
})(jQuery);
