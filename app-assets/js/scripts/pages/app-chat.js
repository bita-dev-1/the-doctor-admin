"use strict";
var sidebarToggle = $(".sidebar-toggle"),
  overlay = $(".body-content-overlay"),
  sidebarContent = $(".sidebar-content");

// Chat sidebar toggle
function sidebarToggleFunction() {
  if (sidebarToggle.length) {
    sidebarToggle.on("click", function () {
      sidebarContent.addClass("show");
      overlay.addClass("show");
    });
  }
}

// Window Resize
$(window).on("resize", function () {
  sidebarToggleFunction();
  if ($(window).width() > 992) {
    if ($(".chat-application .body-content-overlay").hasClass("show")) {
      $(".app-content .sidebar-left").removeClass("show");
      $(".chat-application .body-content-overlay").removeClass("show");
    }
  }

  // Chat sidebar toggle
  if ($(window).width() < 991) {
    if (
      !$(".chat-application .chat-profile-sidebar").hasClass("show") ||
      !$(".chat-application .sidebar-content").hasClass("show")
    ) {
      $(".sidebar-content").removeClass("show");
      $(".body-content-overlay").removeClass("show");
    }
  }
});

$(document).ready(function () {
  var chatUsersListWrapper = $(".chat-application .chat-user-list-wrapper"),
    profileSidebar = $(".chat-application .chat-profile-sidebar"),
    profileSidebarArea = $(".chat-application .profile-sidebar-area"),
    userProfileSidebar = $(".user-profile-sidebar"),
    userChats = $(".user-chats"),
    chatsUserList = $(".chat-users-list"),
    chatList = $(".chat-list"),
    contactList = $(".contact-list"),
    sidebarCloseIcon = $(".chat-application .sidebar-close-icon"),
    menuToggle = $(".chat-application .menu-toggle"),
    chatSearch = $(".chat-application #chat-search");

  // init ps if it is not touch device
  if (!$.app.menu.is_touch_device()) {
    // Chat user list
    if (chatUsersListWrapper.length > 0) {
      var chatUserList = new PerfectScrollbar(chatUsersListWrapper[0]);
    }

    // Admin profile left
    if (userProfileSidebar.find(".user-profile-sidebar-area").length > 0) {
      var userScrollArea = new PerfectScrollbar(
        userProfileSidebar.find(".user-profile-sidebar-area")[0]
      );
    }

    // Chat area
    if (userChats.length > 0) {
      var chatsUser = new PerfectScrollbar(userChats[0], {
        wheelPropagation: false,
      });
    }

    // User profile right area
    if (profileSidebarArea.length > 0) {
      var user_profile = new PerfectScrollbar(profileSidebarArea[0]);
    }
  } else {
    chatUsersListWrapper.css("overflow", "scroll");
    userProfileSidebar
      .find(".user-profile-sidebar-area")
      .css("overflow", "scroll");
    userChats.css("overflow", "scroll");
    profileSidebarArea.css("overflow", "scroll");

    // on user click sidebar close in touch devices
    $(chatsUserList)
      .find("li")
      .on("click", function () {
        $(sidebarContent).removeClass("show");
        $(overlay).removeClass("show");
      });
  }

  // On sidebar close click
  if (sidebarCloseIcon.length) {
    sidebarCloseIcon.on("click", function () {
      sidebarContent.removeClass("show");
      overlay.removeClass("show");
    });
  }

  // On overlay click
  if (overlay.length) {
    overlay.on("click", function () {
      sidebarContent.removeClass("show");
      overlay.removeClass("show");
      profileSidebar.removeClass("show");
      userProfileSidebar.removeClass("show");
    });
  }

  // auto scroll to bottom of Chat area
  chatsUserList.find("li").on("click", function () {
    userChats.animate({ scrollTop: userChats[0].scrollHeight }, 400);
  });

  // Main menu toggle should hide app menu
  if (menuToggle.length) {
    menuToggle.on("click", function (e) {
      sidebarContent.removeClass("show");
      overlay.removeClass("show");
      profileSidebar.removeClass("show");
      userProfileSidebar.removeClass("show");
    });
  }

  if ($(window).width() < 992) {
    sidebarToggleFunction();
  }

  // Filter
  if (chatSearch.length) {
    chatSearch.on("keyup", function () {
      var value = $(this).val().toLowerCase();
      if (value !== "") {
        // filter chat list
        chatList.find("li:not(.no-results)").filter(function () {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });

        var chat_tbl_row = chatList.find("li:not(.no-results):visible").length;

        // check if chat row available
        if (chat_tbl_row == 0) {
          chatList.find(".no-results").addClass("show");
        } else {
          if (chatList.find(".no-results").hasClass("show")) {
            chatList.find(".no-results").removeClass("show");
          }
        }
      } else {
        // If filter box is empty
        chatsUserList.find("li").show();
        if (chatUsersListWrapper.find(".no-results").hasClass("show")) {
          chatUsersListWrapper.find(".no-results").removeClass("show");
        }
      }
    });
  }

  if ($(".chats .chat").length) {
    $(".user-chats").scrollTop($(".user-chats > .chats").height());
  }

  $(document).on("keyup", ".chat-app-form input.message", function () {
    if ($(this).val().length != 0)
      $(".chat-app-form .send").attr("disabled", false);
    else $(".chat-app-form .send").attr("disabled", true);
  });

  $(document).on("change", ".chat-app-form-files .codexInputFile", function () {
    $(".chat-app-form .send").attr("disabled", false);
    $(".chat-app-form input.message").attr("disabled", true);
  });

  var request;
  $(document).on("click", ".chat-list li", function (e) {
    e.preventDefault();
    e.stopPropagation();

    if (request) request.abort();

    let self = $(this),
      id = self.attr("data-express"),
      username = self.find(".chat-info h5").text(),
      image = self.find(".avatar img").attr("src");

    if (
      username != $(".active-chat .chat-header .current-conversation").text()
    ) {
      $(".chat-list li").removeClass("active");
      self.addClass("active");

      if (self.hasClass("active")) {
        $(".start-chat-area").addClass("d-none");
        $(".active-chat").removeClass("d-none");
      } else {
        $(".start-chat-area").removeClass("d-none");
        $(".active-chat").addClass("d-none");
      }

      $(".active-chat .chat-header .current-conversation").text(username);
      $(".active-chat .chat-header .avatar img").attr("src", image);
      $(".active-chat .chat-header").attr("data-express", id);

      history.pushState(null, null, SITE_URL + "/messages/" + id);

      request = $.ajax({
        url: SITE_URL + "/handlers",
        method: "POST",
        data: { method: "chat", conversation: id },
        dataType: "json",
        success: function (data) {
          renderMessages(data);
        },
      });
    }
  });

  // Send Message
  $(document).on("submit", ".chat-app-form", function (e) {
    e.preventDefault();
    e.stopPropagation();

    if (request) request.abort();

    var chatInput = $(".chat-app-form .message");
    var filePathInput = $("#file-path-input");
    var message = chatInput.val();
    var filePath = filePathInput.val();

    if (message.trim().length > 0 || filePath.trim().length > 0) {
      var data = {
        method: "send_msg",
        conversation: $(".active-chat .chat-header").attr("data-express"),
        last: $(".user-chats .chat:last-child").attr("data-express") || 0,
      };

      if (filePath.trim().length > 0) {
        data.file = true;
        data.file_path = filePath;
      } else {
        data.message = message;
      }

      request = $.ajax({
        url: SITE_URL + "/handlers",
        method: "POST",
        data: data,
        dataType: "json",
        beforeSend: function () {
          let svg =
            '<svg class="seloader ms-1" width="18" viewBox="1 0 34 34" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" height="18" fill="none" stroke-width="4"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"></circle><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"></animateTransform></path></g></g></svg>';
          $(".send").attr("disabled", "disabled").append(svg);
        },
        success: function (res) {
          if (res.state == "true") {
            renderMessages(res.data, true);

            // Reset form
            chatInput.val("").prop("disabled", false);
            filePathInput.val("");
            $(".chat-app-form .codexMultiPreviewImage").html("");
            $(".chat-app-form .send").prop("disabled", true);
            if (data.hasOwnProperty("file")) {
              $(".chat-app-form .collapse").collapse("hide");
            }
          } else {
            Swal.fire({
              title: res.message || "An error occurred",
              icon: "warning",
              showConfirmButton: false,
              buttonsStyling: false,
              timer: 3000,
              timerProgressBar: true,
            });
          }
        },
        complete: function () {
          $(".seloader").remove();
          // Re-enable send button if there's text, disable otherwise
          if (chatInput.val().trim().length > 0) {
            $(".send").prop("disabled", false);
          } else {
            $(".send").prop("disabled", true);
          }
        },
      });
    }
  });
  $(document).on("submit", ".post-conversation", function (e) {
    e.preventDefault();
    e.stopPropagation();

    if (request) request.abort();

    let self = $(this),
      subarray = [];

    $.each(self.find("#id_particib").serializeArray(), function () {
      subarray.push(this.value);
    });

    let data = {
      method: "post_conversation",
      participants: subarray,
      name: self.find("#name").val(),
      csrf: self.find("input[name=csrf]").val(),
    };
    request = $.ajax({
      url: SITE_URL + "/handlers",
      method: "POST",
      data: data,
      dataType: "json",
      beforeSend: function () {
        let svg =
          '<svg class="seloader ms-1" width="18" viewBox="1 0 34 34" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" height="18" fill="none" stroke-width="4"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"></circle><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"></animateTransform></path></g></g></svg>';
        self.find('button[type="submit"]').attr("disabled", true);
        self.find('button[type="submit"]').append(svg);
      },
      success: function (res) {
        if (res.state == "true") {
          $(".offcanvas").offcanvas("hide");
          $(".post-conversation")[0].reset();
          $("#id_particib").val(null).trigger("change");

          self.find('button[type="submit"]').attr("disabled", false);
        } else
          Swal.fire({
            title: res.message,
            icon: "warning",
            showConfirmButton: false,
            buttonsStyling: false,
            timer: 3000,
            timerProgressBar: true,
          });
      },
      complete: function () {
        $(".seloader").remove();
      },
    });
  });

  function renderMessages(data, append = false) {
    let html_data = "",
      profile_id = $(".sidebar-profile-toggle").attr("data-profile"),
      profile_image = $(".sidebar-profile-toggle").attr("data-image");
    $.each(data, function (index, value) {
      html_data +=
        `
          <div class="chat ` +
        (value.id_sender == profile_id &&
        (value.my_particib == profile_id || value.id_particib == profile_id)
          ? ""
          : "chat-left") +
        `" data-express="` +
        value.id +
        `">
              <div class="chat-avatar">
                  <span class="avatar box-shadow-1 cursor-pointer">
                      <img src="` +
        (value.id_sender == profile_id &&
        (value.my_particib == profile_id || value.id_particib == profile_id) &&
        $.trim(profile_image) !== null
          ? $.trim(profile_image)
          : "/assets/images/default_User.png") +
        `" alt="avatar" height="36" width="36" />
                  </span>
              </div>
              <div class="chat-body">
                <div class="chat-content">
                  ` +
        (value.type == 1
          ? `<div class="attachement_item downloadable d-flex w-auto" data-file="http://localhost/` +
            value.message +
            `">
                            <img class="img-fluid" src=` +
            value.message.replace("http://localhost/", "") +
            ` />
                        </div>`
          : value.type == 2
          ? `<div class="attachement_item downloadable d-flex pe-3 mt-1 mb-1 w-auto" data-file="` +
            value.message +
            `">
                                  <span class="attachement_type">` +
            value.message.split(".").pop() +
            `</span>
                                  <p class="m-0">` +
            value.message.replace("http://localhost/uploads/", "") +
            `</p>
                              </div>`
          : "<p>" + value.message + "</p>") +
        `
                </div>
              </div>
          </div>
          `;
    });

    if (append) {
      $(".user-chats > .chats").append(html_data);
      $(".user-chats .ps__rail-y").css("top", 0);
      $(".user-chats").scrollTop($(".user-chats > .chats").height());
    } else {
      $(".user-chats > .chats").html(html_data);
      $(".user-chats .ps__rail-y").css("top", 0);
      $(".user-chats").scrollTop($(".user-chats > .chats").height());
    }
  }

  setInterval(acountState, 5000);
  function acountState() {
    // Save current scroll position
    saveScrollPosition();
    var data = { method: "acountState" };

    if (
      $(".active-chat .chat-header").length &&
      $(".active-chat .chat-header").attr("data-express") != ""
    ) {
      data.conversation = $(".active-chat .chat-header").attr("data-express");
      data.last = $(".active-chat .chat:last-child").attr("data-express");
    }

    $.ajax({
      url: SITE_URL + "/handlers",
      type: "POST",
      data: data,
      dataType: "json",
      success: function (data) {
        if (data.length != 0) {
          if (data.chat_list.length) {
            let list_items = "",
              conversationId = $(".active-chat .chat-header").attr(
                "data-express"
              );

            $.each(data.chat_list, function (index, value) {
              let participantsArray = $.isArray(value.participants)
                ? value.participants.map((item) => item.username)
                : [];
              list_items +=
                `
                  <li data-express="` +
                value.id +
                `" ` +
                (conversationId == value.id ? 'class="active"' : "") +
                `>
                    <span class="avatar">
                        <img src="/assets/images/default_User.png" height="42" width="42" alt="" />
                    </span>
                    <div class="chat-info flex-grow-1">
                        <h5 class="mb-0">` +
                value.participants[0].user +
                `</h5>
                        <p class="card-text text-truncate">
                          ` +
                (!jQuery.isEmptyObject(value.last_msg)
                  ? value.last_msg.type == 1
                    ? "vous a envoyé une photo"
                    : value.last_msg.type == 2
                    ? "vous a envoyé une fichier"
                    : value.last_msg.message
                  : "") +
                `
                        </p>
                    </div>
                    <div class="chat-meta text-nowrap">
                        <small class="float-end mb-25 chat-time"></small>
                    </div>
                  </li>
              `;
            });

            $("ul.chat-list").html(list_items);
          }

          if (
            ($.isArray(data.data) && data.data.length) ||
            !jQuery.isEmptyObject(data.data)
          )
            renderMessages(data.data.messages, true);
          restoreScrollPosition();
        }
      },
    });
  }

  $(document).on("click", ".downloadable", function (e) {
    e.preventDefault();

    var url = "http://localhost/";

    if (
      $(this).attr("data-file").toString().toLowerCase().indexOf("admin") >= 0
    )
      url = "https://api.localhost/";

    if ($(this).find("img.img-fluid").length)
      window.open($(this).find("img")[0].src, "imgWindow");
    else {
      var link = document.createElement("a");
      link.setAttribute(
        "href",
        "/" + $(this).attr("data-file").replace(url, "")
      );
      link.setAttribute(
        "download",
        $(this)
          .attr("data-file")
          .replace(url + "uploads/", "")
      );
      link.click();
      link.remove();
    }
  });

  // Variable to store the scroll position
  var chatScrollPosition = 0;

  // Function to save the scroll position
  function saveScrollPosition() {
    chatScrollPosition = $(".user-chats").scrollTop();
  }

  // Function to restore the scroll position
  function restoreScrollPosition() {
    $(".user-chats").scrollTop(chatScrollPosition);
  }

  // Function to reset the scroll position to top
  function resetScrollPosition() {
    $(".user-chats").scrollTop(0);
  }
});
