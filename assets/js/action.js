jQuery(function ($) {
  // Active Link Handling
  $("#main-menu-navigation li a")
    .click(function (e) {
      var link = $(this);
      var item = link.parent("li");
      if (item.hasClass("active")) {
        item.removeClass("active");
      } else {
        item.addClass("active");
      }
      if (item.children("li").length > 0) {
        var href = link.attr("href");
        link.attr("href", "#");
        setTimeout(function () {
          link.attr("href", href);
        });
        e.preventDefault();
      }
    })
    .each(function () {
      var link = $(this);
      if (link.get(0).href === location.href) {
        link.addClass("active").parents("li").addClass("active");
        return false;
      }
    });
});

/* ==========================================================================
   SIDEBAR TOGGLE LOGIC (SYNCED & PERSISTENT)
   ========================================================================== */

$(document).ready(function () {
  // 1. Toggle Button Click Handler
  $(document).on("click", ".modern-nav-toggle", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var body = $("body");
    var isCollapsed = false;

    // Toggle logic
    if (body.hasClass("menu-expanded")) {
      body.removeClass("menu-expanded").addClass("menu-collapsed");
      isCollapsed = true;
    } else {
      body.removeClass("menu-collapsed").addClass("menu-expanded");
      isCollapsed = false;
    }

    // ✅ FORCE SYNC: Update both keys to resolve duplication conflict
    var stateString = isCollapsed ? "true" : "false";
    localStorage.setItem("menu-collapsed", stateString); // Our preferred key
    localStorage.setItem("menuCollapsed", stateString); // Legacy/Template key

    // Trigger Resize (Fixes ApexCharts width issues)
    setTimeout(function () {
      window.dispatchEvent(new Event("resize"));
    }, 200);
  });

  // 2. Mobile Overlay & Menu Toggle
  $(document).on("click", ".menu-toggle, .sidenav-overlay", function (e) {
    e.preventDefault();
    var body = $("body");

    if (body.hasClass("menu-open")) {
      body.removeClass("menu-open").addClass("menu-hide");
      $(".sidenav-overlay").removeClass("show");
    } else {
      body.removeClass("menu-hide").addClass("menu-open");
      $(".sidenav-overlay").addClass("show");
    }
  });

  // 3. Expand on Hover (When Collapsed)
  $(".main-menu").hover(
    function () {
      if ($("body").hasClass("menu-collapsed")) {
        $("body").addClass("menu-collapsed-open");
      }
    },
    function () {
      if ($("body").hasClass("menu-collapsed")) {
        $("body").removeClass("menu-collapsed-open");
      }
    }
  );
});

//========================================== Language Cookies =======================

function setCookie(name, value, days) {
  var expires = "";
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

$(document).on("click", ".language a", function () {
  $("#lang_selected").text($(this).text());
  $("#lang_selected").attr("data-code", $(this).attr("data-code"));
  var path = "",
    urlWithOutParameters = "",
    params = "";
  if (window.location.search != "") {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("lang")) urlParams.delete("lang");
    urlWithOutParameters = window.location.origin + window.location.pathname;
    if (urlParams.toString() == "") {
      params = "";
    } else {
      params = "&" + urlParams.toString();
    }
    path = "?lang=" + $(this).attr("data-code") + params;
  } else {
    urlWithOutParameters = window.location.origin + window.location.pathname;
    path = "?lang=" + $(this).attr("data-code");
  }
  window.location = urlWithOutParameters + path;
  setCookie("lang", $(this).attr("data-code"), 30);
  location.reload();
});

// Forget Password Logic
$("#forget_password").on("submit", function (e) {
  e.preventDefault();
  $.ajax({
    url: SITE_URL + "/handlers",
    method: "POST",
    data: { method: "forget_password", email: $("#forgot_email").val() },
    success: function (data) {
      Swal.fire({
        title: "Success",
        text: "Please check your email.",
        icon: "success",
        confirmButtonText: "back",
        customClass: {
          confirmButton: "btn btn-primary",
        },
        buttonsStyling: false,
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = SITE_URL + "/login";
        }
      });
    },
  });
});
