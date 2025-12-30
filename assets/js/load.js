"use strict";

const SITE_URL = $(".SITE_URL").val(),
  API_URL = $(".API_URL").val();

/********************[Begin]: Select2 & Picker ********************/
$(document).ready(function () {
  $(".select2").each(function () {
    var self = $(this);

    self.wrap('<div class="position-relative codexSelect2"></div>');
    if (
      typeof self.attr("data-express") === "undefined" ||
      self.attr("data-express") === false
    ) {
      self
        .select2({
          dropdownParent: self.parent(),
          placeholder: self.attr("placeholder"),
        })
        .change(function () {
          self.valid();
        });
    } else {
      if (
        typeof self.attr("his_parent") === "undefined" ||
        self.attr("his_parent") === false
      ) {
        self
          .select2({
            dropdownParent: self.parent(),
            placeholder: self.attr("placeholder"),
            ajax: {
              type: "post",
              dataType: "json",
              url: SITE_URL + "/data",
              delay: 250,
              data: function (params) {
                var query = {
                  searchTerm: params.term,
                  method: "select2Data",
                  token: self.data("express"),
                };
                return query;
              },
              processResults: function (data) {
                return {
                  results: data,
                };
              },
              cache: true,
            },
          })
          .change(function () {
            let child = $(document).find(
              `.select2[his_parent="#${self.attr("name")}"]`
            );
            if (child.length) {
              child.empty();
            }
            self.valid();
          });
      } else {
        if ($(self.attr("his_parent")).find("option").length) {
          self
            .select2({
              dropdownParent: self.parent(),
              placeholder: self.attr("placeholder"),
              ajax: {
                type: "post",
                dataType: "json",
                url: SITE_URL + "/data",
                delay: 250,
                data: function (params) {
                  var query = {
                    searchTerm: params.term,
                    method: "select2Data",
                    token: self.data("express"),
                    parent: $(self.attr("his_parent")).val(),
                  };
                  return query;
                },
                processResults: function (data) {
                  return {
                    results: data,
                  };
                },
                cache: true,
              },
            })
            .change(function () {
              self.valid();
            });
        } else {
          self
            .select2({
              dropdownParent: self.parent(),
              placeholder: self.attr("placeholder"),
            })
            .change(function () {
              self.valid();
            });
        }
        $(document).on("select2:select", self, function (e) {
          e.preventDefault();
          self
            .select2({
              dropdownParent: self.parent(),
              placeholder: self.attr("placeholder"),
              ajax: {
                type: "post",
                dataType: "json",
                url: SITE_URL + "/data",
                delay: 250,
                data: function (params) {
                  var query = {
                    searchTerm: params.term,
                    method: "select2Data",
                    token: self.data("express"),
                    parent: $(self.attr("his_parent")).val(),
                  };
                  return query;
                },
                processResults: function (data) {
                  return {
                    results: data,
                  };
                },
                cache: true,
              },
            })
            .change(function () {
              self.valid();
            });
        });
      }
    }
  });

  if ($(".picker").length) {
    $(".picker").flatpickr({
      allowInput: true,
      onReady: function (selectedDates, dateStr, instance) {
        if (instance.isMobile) {
          $(instance.mobileInput).attr("step", null);
        }
      },
    });
  }
});
/********************[End]: Select2 & Picker ********************/

/********************[Begin]: DataTable ********************/
$("input.picker").on("change", function (e) {
  e.preventDefault();
  e.stopPropagation();
  $(this)
    .parents("body")
    .find(".table.dataTable#codexTable")
    .DataTable()
    .ajax.reload();
});

function call_data_table(data) {
  // --- LOGGING STEP 2 ---
  console.log(
    "[LOG 2] Initializing DataTable with data:",
    JSON.parse(JSON.stringify(data))
  );

  var dataButton = "",
    buttons = [];
  if (data.hasOwnProperty("button")) {
    dataButton = data.button;
    delete data["button"];

    var singleBtn = {};
    $.each(dataButton, function (index, value) {
      // --- LOGGING STEP 3 ---
      console.log("[LOG 3] Processing button definition:", value);

      singleBtn = {};
      if (!value.hasOwnProperty("collection")) {
        if (value.hasOwnProperty("text")) singleBtn.text = value["text"];
        if (value.hasOwnProperty("class")) singleBtn.className = value["class"];

        // --- LOGGING STEP 4 ---
        if (value.hasOwnProperty("attr")) {
          // DataTables expects 'attr' to be an object, not a string. Let's parse it.
          let attrObject = {};
          // Simple parser for "key=value key2='value 2'"
          const attributes = value["attr"].match(
            /([a-zA-Z0-9_-]+)=["']([^"']+)["']/g
          );
          if (attributes) {
            attributes.forEach((attr) => {
              const parts = attr.split("=");
              const key = parts[0];
              const val = parts[1].replace(/["']/g, ""); // remove quotes
              attrObject[key] = val;
            });
          }
          singleBtn.attr = attrObject;
          console.log("[LOG 4] Applying attributes:", attrObject);
        }

        if (value.hasOwnProperty("url")) {
          singleBtn.action = function (e, dt, button, config) {
            window.location = value["url"];
          };
        } else if (value.hasOwnProperty("action") && value.action === "popup") {
          // For popup buttons, we don't need a JS action,
          // the data-bs-* attributes handled by Bootstrap's JS are enough.
          // We define a null action to prevent DataTables default behavior.
          singleBtn.action = function (e, dt, node, config) {
            // This function intentionally left blank.
            // The modal is triggered by Bootstrap's data attributes.
          };
          console.log(
            "[LOG 5] Popup button identified. Action is set to null function."
          );
        }
      } else {
        singleBtn.extend = "collection";
        if (value.hasOwnProperty("class")) singleBtn.className = value["class"];
        if (value.hasOwnProperty("text"))
          singleBtn.text =
            feather.icons["external-link"].toSvg({
              class: "font-small-4 me-50",
            }) + value["text"];
        singleBtn.buttons = [];
        $.each(value["collection"], function (index, value) {
          singleBtn.buttons.push(dataTable_exportItem(value));
        });
      }
      buttons.push(singleBtn);
    });
  }

  if (typeof data.method === "undefined" && data.method !== false) {
    data.method = "data_table";
  }

  var select = $("#codexTable").parents("#ajax-datatable").find("#selectstate");
  $(select).on("change", function () {
    $(this)
      .parents("#ajax-datatable")
      .find(".dataTable.table")
      .DataTable()
      .ajax.reload();
  });

  var selecttype = $("#codexTable")
    .parents("#ajax-datatable")
    .find("#selecType");
  $(selecttype).on("change", function () {
    $(this)
      .parents("#ajax-datatable")
      .find(".dataTable.table")
      .DataTable()
      .ajax.reload();
  });
  let langDataTable = getCookie("lang");

  $("#codexTable").DataTable({
    processing: true,
    serverSide: true,
    // --- التعديلات هنا ---
    responsive: false, // تعطيل التجاوب الذكي (الذي يخفي الأعمدة)
    scrollX: true, // تفعيل التمرير الأفقي
    autoWidth: false, // منع الحساب التلقائي للعرض لضمان الامتلاء
    // -------------------
    stateSave: true,
    stateDuration: -1,
    ordering: true,
    order: [[0, "desc"]],
    language: {
      url:
        SITE_URL +
        "/assets/js/dataTableLangue/" +
        (langDataTable.length ? langDataTable : "fr") +
        ".json",
    },
    ajax: {
      url: SITE_URL + "/data",
      type: "POST",
      data: function (c) {
        if (select.length) {
          if (select.val() != "") {
            data.status = select.val();
          } else {
            data.status = "";
          }
        }
        if (selecttype.length) {
          if (selecttype.val() != "") {
            data.selectype = selecttype.val();
          } else {
            data.selectype = "";
          }
        }

        return $.extend({}, c, data);
      },
    },
    dom:
      '<"d-flex justify-content-between align-items-center header-actions mx-auto row mt-75"' +
      '<"col-sm-12 col-lg-4 d-flex justify-content-center justify-content-lg-start" l>' +
      '<"col-sm-12 col-lg-8 ps-xl-75 ps-0"<"dt-action-buttons d-flex align-items-center justify-content-center justify-content-lg-end flex-lg-nowrap flex-wrap"<"me-1"f>B>>' +
      ">t" +
      '<"d-flex justify-content-between mx-2 row mb-1"' +
      '<"col-sm-12 col-md-6"i>' +
      '<"col-sm-12 col-md-6"p>' +
      ">",
    buttons: buttons,
  });
}

$(document).on("click", ".delete-record", function (e) {
  e.preventDefault();
  var self = $(this),
    option_swal = {
      title: "Are you sure you want to delete this item?",
      html: "",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "YES",
      cancelButtonText: "NO",
      customClass: {
        confirmButton: "btn btn-success",
        cancelButton: "btn btn-outline-danger me-1 ms-1",
      },
      buttonsStyling: false,
    };

  var data = {
    method: "deleteItem_table",
    table: self.parents(".table.dataTable").data("express"),
    id: self.data("id"),
  };

  Swal.fire(option_swal).then(function (result) {
    if (result.value) {
      $.ajax({
        type: "POST",
        url: SITE_URL + "/data",
        data: data,
        dataType: "json",
        success: function (data) {
          if (data["state"]) {
            Swal.fire({
              title: "Deleted successfully",
              icon: "success",
              confirmButtonText: "back",
              customClass: {
                confirmButton: "btn btn-primary",
              },
              buttonsStyling: false,
            }).then((result) => {
              if (result.isConfirmed) {
                self.parents(".table.dataTable").DataTable().ajax.reload();
              }
            });
          } else {
            Swal.fire({
              title: "something is wrong!",
              icon: "error",
              confirmButtonText: "back",
              customClass: {
                confirmButton: "btn btn-primary",
              },
              buttonsStyling: false,
            });
            self.parents(".table.dataTable").DataTable().ajax.reload();
          }
        },
      });
    }
  });
});

function dataTable_exportItem(data) {
  var item = { className: "dropdown-item" };
  switch (data["role"]) {
    case "csv":
      item.extend = data["role"];
      item.text =
        feather.icons["file-text"].toSvg({ class: "font-small-4 me-50" }) +
        data["text"];
      item.exportOptions = data["exportOptions"];
      break;
    case "excel":
      item.extend = data["role"];
      item.text =
        feather.icons["file"].toSvg({ class: "font-small-4 me-50" }) +
        data["text"];
      item.exportOptions = data["exportOptions"];
      break;
    case "pdf":
      item.extend = data["role"];
      item.text =
        feather.icons["clipboard"].toSvg({ class: "font-small-4 me-50" }) +
        data["text"];
      item.exportOptions = data["exportOptions"];
      break;
    case "copy":
      item.extend = data["role"];
      item.text =
        feather.icons["copy"].toSvg({ class: "font-small-4 me-50" }) +
        data["text"];
      item.exportOptions = data["exportOptions"];
      break;
    case "print":
    default:
      item.extend = data["role"];
      item.text =
        feather.icons["printer"].toSvg({ class: "font-small-4 me-50" }) +
        data["text"];
      item.exportOptions = data["exportOptions"];
      break;
  }
  return item;
}
/********************[End]: DataTable ********************/

/********************[Begin]: PostForm ********************/
$(document).on("submit", "#codexForm, .codexForm", function (e) {
  e.preventDefault();
  e.stopPropagation();
  var self = $(this);

  var data = {
    class: self.data("express"),
    data: $(this).find(":not(.excluded)").serializeArray(),
  };

  if (
    typeof self.attr("data-update") === "undefined" ||
    self.attr("data-update") === false ||
    self.attr("data-update") == "" ||
    self.attr("data-update") == "UW0="
  )
    data.method = "postForm";
  else {
    data.method = "updatForm";
    data.object = $(this).attr("data-update");
    data.attachemment = $(this).attr("data-sub-express");
  }

  if (
    typeof self.attr("data-codex-id") !== "undefined" &&
    self.attr("data-codex-id") !== false
  ) {
    data.codex_id = self.attr("data-codex-id");
  }

  self.find(".form-switch .form-check-input:not(:checked)").each(function () {
    if (!isKeyInArray(data.data, this.name)) {
      data.data.push({ name: this.name, value: 0 });
    }
  });

  if (self.hasClass("codexGallery")) data.multi = [{ name: "multi" }];

  self.find(".codexFileUp .codexFileData:not(.excluded)").each(function () {
    if (self.hasClass("codexGallery")) {
      var term = $(this).attr("data-name");
      let obj = {};
      if ($(this).val() != "") {
        obj["id_post"] = self.attr("data-codex-id");
        obj[term] = $(this).val();
        data.multi.push(obj);
      }
    } else data.data.push({ name: $(this).attr("data-name"), value: $(this).val() });
  });

  $.ajax({
    type: "POST",
    url: SITE_URL + "/data",
    data: data,
    dataType: "json",
    beforeSend: function () {
      var svg =
        '<svg class="seloader" width="16" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="#fff"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"/><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/></path></g></g></svg>';
      self.find('button[type="submit"]').attr("disabled", "disabled");
      self.find('button[type="submit"]').append(svg);
    },
    success: function (data) {
      if (data.state != "false") {
        Swal.fire({
          title: data.message,
          icon: "success",
          showConfirmButton: false,
          buttonsStyling: false,
          timer: 1500,
          timerProgressBar: true,
        }).then((result) => {
          if (self.parents(".table.dataTable").length) {
            self.parents(".table.dataTable").DataTable().ajax.reload();
          }

          if (self.parents(".modal").length) {
            self.parents(".modal").modal("toggle");

            if (self.parents("body").find(".table.dataTable").length) {
              self
                .parents("body")
                .find(".table.dataTable")
                .DataTable()
                .ajax.reload();
            }
          } else {
            history.back(-1);
          }
        });
      } else {
        Swal.fire({
          title: "something is wrong!",
          icon: "error",
          confirmButtonText: "back",
          customClass: {
            confirmButton: "btn btn-primary",
          },
          buttonsStyling: false,
        });
        if (self.parents(".table.dataTable").length) {
          self.parents(".table.dataTable").DataTable().ajax.reload();
        }
      }
    },
    complete: function () {
      self.find('button[type="submit"]').removeAttr("disabled");
      $(".seloader").remove();
    },
  });
});
/********************[End]: PostForm ********************/

function isKeyInArray(array, key) {
  return array.some((obj) => obj.hasOwnProperty(key));
}

function getValueFromKeyInArray(array, key) {
  return array.find((obj) => obj[key])?.[key];
}

/********************[Begin]: Upload Files ********************/
if ($(".codexFileUp").length) {
  const fileInput = document.querySelector(".codexFileUp input[type=file]");

  $(document).on("change", ".codexInputFile", function (e) {
    var formData = new FormData();
    if (
      typeof $(this).attr("multiple") === "undefined" ||
      $(this).attr("multiple") === false
    ) {
      const file = e.target.files[0];
      formData.append("file[]", file);
    } else {
      const file = e.target.files;
      for (var i = 0; i < file.length; i++) {
        formData.append("file[]", file[i]);
      }
    }

    moveUploadedFile($(this), formData);
  });

  if ($("#codexUploadArea").length) {
    const uploadArea = document.querySelector("#codexUploadArea");
    const dropZoon = document.querySelector(".codexInputFile");
    const loadingText = document.querySelector("#loadingText");
    const previewImage = document.querySelector("#codexPreviewImage");
    const fileDetails = document.querySelector("#codexFileDetails");
    const uploadedFile = document.querySelector("#codexUploadedFile");
    const uploadedFileInfo = document.querySelector("#codexuploadedFileInfo");
    const uploadedFileName = document.querySelector(".uploaded-file__name");

    dropZoon.addEventListener("dragover", function (e) {
      e.preventDefault();
      dropZoon.classList.add("drop-zoon--over");
    });

    dropZoon.addEventListener("dragleave", function (event) {
      dropZoon.classList.remove("drop-zoon--over");
    });

    dropZoon.addEventListener("drop", function (e) {
      e.preventDefault();
      dropZoon.classList.remove("drop-zoon--over");

      var formData = new FormData();
      if (
        typeof $(this).attr("multiple") === "undefined" ||
        $(this).attr("multiple") === false
      ) {
        const file = e.dataTransfer.files[0];
        formData.append("file[]", file);
      } else {
        const file = e.dataTransfer.files;

        for (var i = 0; i < file.length; i++) {
          formData.append("file[]", file[i]);
        }
      }
      moveUploadedFile($(this), formData);
    });
  }
}

// This is inside assets/js/load.js

function moveUploadedFile(self, formData) {
  formData.append("method", "moveUploadedFile");
  $.ajax({
    url: SITE_URL + "/data",
    type: "POST",
    data: formData,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend: function () {},
    success: function (data) {
      data = JSON.parse(data);
      if (data.state == "true") {
        // --- START: MODIFIED LOGIC FOR CHAT ---
        if (self.closest(".chat-app-form-files").length) {
          // This is the chat uploader
          var filePath = data.path[0].new;
          $("#file-path-input").val(filePath);

          // Show a preview inside the collapse area
          var previewHtml = `<div class="attachement_item d-flex align-items-center p-1 border rounded">
                                 <span class="attachement_type">${filePath
                                   .split(".")
                                   .pop()}</span>
                                 <p class="m-0 ms-1 text-truncate">${
                                   data.path[0].old
                                 }</p>
                               </div>`;
          $(".chat-app-form-files")
            .find(".codexMultiPreviewImage")
            .html(previewHtml);

          // Enable send button
          $(".btn.send").prop("disabled", false);
          return; // Stop further execution for chat
        }
        // --- END: MODIFIED LOGIC FOR CHAT ---

        if (
          typeof self
            .parents(".codexFileUp")
            .find(".codexInputFile")
            .attr("multiple") === "undefined" ||
          self
            .parents(".codexFileUp")
            .find(".codexInputFile")
            .attr("multiple") === false
        ) {
          if ($(".codexAttachementMessage").length) {
            var file =
              `<div class="attachement_item d-flex">
                        <span class="attachement_type">` +
              data.path[0].new.split(".").pop() +
              `</span>
                        <span class="removePic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ff4141" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></span>
                        <p class="m-0" src="` +
              data.path[0].new +
              `">` +
              data.path[0].old +
              `</p></div>`;
            $(".codexAttachementMessage").html(file);
          } else if (
            self.parents(".codexFiles").find("#codexPreviewImage").length
          ) {
            var dataName = self
              .parents(".codexFileUp")
              .find(".codexInputFile ")
              .attr("name");

            var img =
              '<div class="col-lg-3 col-md-4 col-sm-6 col-12"><span class="removePic">X</span><img src="' +
              data.path[0].new +
              '" alt="Preview Image">';
            img +=
              "<p>" +
              data.path[0].old +
              '</p><input type="hidden" class="codexFileData" data-name="' +
              dataName +
              '" value="' +
              data.path[0].new +
              '" /></div>  ';
            self
              .parents(".codexFileUp")
              .find(".codexMultiPreviewImage")
              .html(img);
          } else if (
            self.parents(".codexFileUp").find("#codexPreviewImage").length
          ) {
            self
              .parents(".codexFileUp")
              .find("#codexPreviewImage")
              .attr("src", data.path[0].new);
            self.parents(".codexFileUp").find("#codexPreviewImage").show();
          }
          self
            .parents(".codexFileUp")
            .find(".codexFileData")
            .val(data.path[0].new);
        } else {
          var dataName = self
              .parents(".codexFileUp")
              .find(".codexInputFile ")
              .attr("name"),
            img = "";
          $.each(data.path, function (index, item) {
            img =
              '<div class="col-lg-3 col-md-4 col-sm-6 col-12"><span class="removePic">X</span><img src="' +
              SITE_URL +
              "/" +
              item.new +
              '" alt="Preview Image">';
            img +=
              "<p>" +
              item.old +
              '</p><input type="hidden" class="codexFileData" data-name="' +
              dataName +
              '" value="' +
              item.new +
              '" /></div>  ';
            self
              .parents(".codexFileUp")
              .find(".codexMultiPreviewImage")
              .append(img);
          });
        }
      } else {
        Swal.fire({
          title: data.message,
          icon: "error",
          confirmButtonText: "back",
          customClass: {
            confirmButton: "btn btn-primary",
          },
          buttonsStyling: false,
        });
      }
    },
    error: function (error) {
      Swal.fire({
        title: "something is wrong!",
        icon: "error",
        confirmButtonText: "back",
        customClass: {
          confirmButton: "btn btn-primary",
        },
        buttonsStyling: false,
      });
    },
  });
}

$(document).ready(function () {
  $(document).on("click", ".removePic", function (e) {
    e.preventDefault();
    e.stopPropagation();
    var self = $(this),
      option_swal = {
        title: "Are you sure you want to delete this item?",
        html: "",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "yes",
        cancelButtonText: "back",
        customClass: {
          confirmButton: "btn btn-success",
          cancelButton: "btn btn-outline-danger me-1 ms-1",
        },
        buttonsStyling: false,
      };

    var data = { method: "removeUploadedFile", path: self.next().attr("src") };

    Swal.fire(option_swal).then(function (result) {
      if (result.value) {
        $.ajax({
          type: "POST",
          url: SITE_URL + "/data",
          data: data,
          dataType: "json",
          success: function (data) {
            if (data.state) {
              Swal.fire({
                title: "Deleted successfully",
                icon: "success",
                confirmButtonText: "back",
                customClass: {
                  confirmButton: "btn btn-primary",
                },
                buttonsStyling: false,
              }).then((result) => {
                if (result.isConfirmed) {
                  self.parent().remove();
                }
              });
            } else {
              Swal.fire({
                title: "something is wrong!",
                icon: "error",
                confirmButtonText: "back",
                customClass: {
                  confirmButton: "btn btn-primary",
                },
                buttonsStyling: false,
              });
            }
          },
        });
      }
    });
  });
});

/********************[End]: Upload Files ********************/

/********************[Begin]: Authentication ********************/

$(document).ready(function () {
  /********************[Begin]: signUp ********************/
  if ($("#codexSignUp").length) {
    $("#codexSignUp").validate({
      rules: {
        type: {
          required: true,
        },
        country_id: {
          required: true,
        },
        username: {
          required: true,
        },
        email: {
          required: true,
          email: true,
          remote: {
            url: SITE_URL + "/data",
            type: "POST",
            data: {
              method: "checkUnique",
              name: "email",
              value: function () {
                return $("#codexSignUp #email").val();
              },
              class: function () {
                return $("#codexSignUp").attr("data-express");
              },
            },
          },
        },
        password: {
          required: true,
          minlength: 8,
        },
        cpassword: {
          required: true,
          equalTo: "#password",
        },
        privacy: {
          required: true,
        },
      },
      messages: {
        email: {
          required: "Please enter the email address ",
          remote: "Email address already Exist",
        },
        password: {
          minlength: "Username must be at least 8 characters",
        },
      },
      ignore: [],
      errorPlacement: function (error, element) {
        if (element.parent(".input-group").length) {
          error.insertAfter(element.parent()); // radio/checkbox?
        } else if (element.hasClass("select2-hidden-accessible")) {
          error.insertAfter(element.next("span")); // select2
          element.next("span").addClass("error").removeClass("valid");
        } else {
          error.insertAfter(element); // default
        }
      },
    });
  }
  $(document).on("submit", "#codexSignUp", function (e) {
    e.preventDefault();
    var self = $(this);

    var data = {
      method: "signUp",
      data: $(this).find(":not(.excluded)").serializeArray(),
    };

    $(".form-switch .form-check-input:not(:checked)").each(function () {
      if (!isKeyInArray(data.data, this.name)) {
        data.data.push({ name: this.name, value: 0 });
      }
    });

    $(".codexFileUp .codexFileData:not(.excluded)").each(function () {
      if ($(this).val() != "") {
        data.data.push({
          name: $(this).attr("data-name"),
          value: $(this).val(),
        });
      }
    });

    $.ajax({
      type: "POST",
      url: SITE_URL + "/data",
      data: data,
      dataType: "json",
      success: function (data) {
        if (data.state != "false") {
          let timerInterval;
          Swal.fire({
            title: data.message,
            icon: "success",
            showConfirmButton: false,
            buttonsStyling: false,
            timer: 1500,
            timerProgressBar: true,
          }).then((result) => {
            location.reload();
          });
        } else {
          Swal.fire({
            title: "something is wrong!",
            icon: "error",
            confirmButtonText: "back",
            customClass: {
              confirmButton: "btn btn-primary",
            },
            buttonsStyling: false,
          });
        }
      },
    });
  });
  /********************[End]: signUp ********************/

  if ($("#codexFormLogin").length) {
    $("#codexFormLogin").validate({
      rules: {
        username: {
          required: true,
        },
        password: {
          required: true,
        },
      },
    });
  }

  $("#codexFormLogin").on("submit", function (e) {
    e.preventDefault();
    var form = $("#codexFormLogin")[0];
    var formData = new FormData(form);
    formData.append("method", "login");
    $.ajax({
      type: "POST",
      enctype: "multipart/form-data",
      url: SITE_URL + "/data",
      data: formData,
      dataType: "json",
      cache: false,
      processData: false,
      contentType: false,
      success: function (data) {
        // --- START: MODIFIED LOGIC ---
        if (data.state === "redirect") {
          // New case: Handle redirection for forced password change
          window.location.href = data.url;
        } else if (data.state === "true") {
          // Original success case
          let timerInterval;
          Swal.fire({
            title: data.message,
            icon: "success",
            showConfirmButton: false,
            timer: 900,
            timerProgressBar: true,
          }).then((result) => {
            location.reload();
          });
        } else {
          // Failure case
          Swal.fire({
            title: data.message,
            icon: "error",
            confirmButtonText: "back",
            customClass: {
              confirmButton: "btn btn-primary",
            },
            buttonsStyling: false,
          });
        }
        // --- END: MODIFIED LOGIC ---
      },
    });
  });

  $(".change-pass").on("submit", function (e) {
    e.preventDefault();
    var form = $(this)[0];
    var formData = new FormData(form);
    formData.append("method", "changePassword");
    $.ajax({
      type: "POST",
      enctype: "multipart/form-data",
      url: SITE_URL + "/data",
      data: formData,
      dataType: "json",
      cache: false,
      processData: false,
      contentType: false,
      success: function (data) {
        if (data.state != "false") {
          let timerInterval;
          Swal.fire({
            title: data.message,
            icon: "success",
            showConfirmButton: false,
            timer: 900,
            timerProgressBar: true,
          }).then((result) => {
            history.back(-1);
          });
        } else {
          Swal.fire({
            title: data.message,
            icon: "error",
            confirmButtonText: "back",
            customClass: {
              confirmButton: "btn btn-primary",
            },
            buttonsStyling: false,
          });
        }
      },
    });
  });

  $("#logout_").on("click", function (event) {
    event.preventDefault();

    $.ajax({
      type: "POST",
      url: SITE_URL + "/data",
      data: { method: "logout", logout: 1 },
      dataType: "json",
      success: function (data) {
        if (data.state != "false") {
          Swal.fire({
            title: data.message,
            icon: "success",
            confirmButtonText: "Exit",
            customClass: {
              confirmButton: "btn btn-primary",
            },
            buttonsStyling: false,
          }).then((result) => {
            window.location.reload();
          });
        } else {
          Swal.fire({
            title: "something is wrong!",
            icon: "error",
            confirmButtonText: "back",
            customClass: {
              confirmButton: "btn btn-primary",
            },
            buttonsStyling: false,
          });
          self.parents(".table.dataTable").DataTable().ajax.reload();
        }
      },
    });
  });
});

/********************[End]: Authentication ********************/

/********************[Begin]: Update Form ********************/

function getSelectedSelect2(dataExpress, parent) {
  if (parent == "") {
    return $.ajax({
      type: "POST",
      url: SITE_URL + "/data",
      data: { method: "select2Data", token: dataExpress },
      dataType: "json",
    });
  } else {
    return $.ajax({
      type: "POST",
      url: SITE_URL + "/data",
      data: {
        method: "select2Data",
        token: dataExpress,
        parent: $(parent).val(),
      },
      dataType: "json",
    });
  }
}

$(document).on("click", ".codexFire_modal", function (e) {
  e.preventDefault();

  var self = $(this),
    targetModal = self.attr("data-bs-target"),
    selfId = self.attr("data-id"),
    selfUpdate = self.attr("data-update"),
    selfClass = $(targetModal).find("#codexForm").attr("data-express");

  clearForm($(targetModal).find("#codexForm"));
  $(targetModal).find("#codexForm button[type=submit]").text("تعديل");
  $(targetModal).find("#codexForm").attr("data-codex-id", selfId);
  $(targetModal).find("#codexForm").attr("data-update", selfUpdate);

  $.ajax({
    type: "POST",
    url: SITE_URL + "/data",
    data: {
      method: "dataById",
      class: selfClass,
      express: selfUpdate,
      id: selfId,
    },
    dataType: "json",
    success: function (res) {
      //  console.log(res);
      if (res.state != "false") {
        var $inputs = $(targetModal)
            .find("#codexForm")
            .find("input ,select ,textarea"),
          col = "";

        $inputs.each(function () {
          var $this = $(this);
          col = $this.attr("name");
          if ($this.parents(".form-switch").length) {
            if (res[col] == 1) {
              $this.attr("checked", "checked");
            } else {
              $this.removeAttr("checked");
            }
          } else if ($this.parents(".codexSelect2").length) {
            if ($this.find("option[value='" + res[col] + "']").length) {
              $this.val(res[col]).trigger("change");
            } else {
              var colItem = col,
                parentSelect = "";
              //if(typeof $this.attr("his_parent") !== 'undefined' && $this.attr("his_parent") !== false){parentSelect = $this.attr("his_parent");}
              getSelectedSelect2($this.attr("data-express"), parentSelect).done(
                function (data) {
                  var filtered = data.filter(function (el) {
                    return el.id == res[colItem];
                  });
                  if (filtered.length != 0) {
                    var newOption = new Option(
                      filtered[0].text,
                      filtered[0].id,
                      true,
                      true
                    );
                    $this.append(newOption).trigger("change");
                  }
                }
              );
            }
          } else if ($this.parents(".form-check").length) {
            if ($this.val() == res[col]) {
              $this.attr("checked", "checked");
            }
          } else if ($this.parents(".codexFileUp").length) {
            if ($this.parents(".avatar-upload").length) {
              if (res[col] != null) {
                $this
                  .parents(".avatar-upload")
                  .find("#codexPreviewImage")
                  .attr("src", res[col]);
                $this
                  .parents(".avatar-upload")
                  .find("#codexPreviewImage")
                  .addClass("d-block");
              }
            } else if ($this.parents(".avatar-upload").length) {
              if (res[col] != null) {
                $this
                  .parents(".avatar-upload")
                  .find("#codexPreviewImage")
                  .attr("src", res[col]);
                $this
                  .parents(".avatar-upload")
                  .find("#codexPreviewImage")
                  .addClass("d-block");
              }
            }
          } else if ($this.find(".textArea").length) {
            $this.text(res[col]);
          } else {
            $this.val(res[col]);
          }
        });
      } else {
        Swal.fire({
          title: res.message,
          icon: "error",
          confirmButtonText: "back",
          customClass: {
            confirmButton: "btn btn-primary",
          },
          buttonsStyling: false,
        });
      }
    },
  });
});

/********************[End]: Update Form ********************/

$(window).on("load", function () {
  if (feather) {
    feather.replace({
      width: 14,
      height: 14,
    });
  }
});

function clearForm($form) {
  $form
    .find(":input")
    .not(":button, :submit, :reset, :checkbox, :radio")
    .val("");
  $form.find(":checkbox, :radio").removeAttr("checked");
}

$(document).on("change", ".switch-table-record", function (e) {
  e.preventDefault();
  var self = $(this);

  var data = {
    method: "changeState",
    table: self.parents(".table.dataTable").data("express"),
    id: self.data("id"),
  };

  if (self.is(":checked")) data.state = 1;
  else data.state = 0;

  if (
    typeof self.attr("data-express") !== "undefined" &&
    self.attr("data-express") !== false
  )
    data.col = self.attr("data-express");

  $.ajax({
    type: "POST",
    url: SITE_URL + "/data",
    data: data,
    dataType: "json",
    success: function (data) {
      if (data["state"]) {
        Swal.fire({
          title: "Edited successfully",
          icon: "success",
          confirmButtonText: "back",
          customClass: {
            confirmButton: "btn btn-primary",
          },
          buttonsStyling: false,
        }).then((result) => {
          if (result.isConfirmed) {
            self.parents(".table.dataTable").DataTable().ajax.reload();
          }
        });
      } else {
        Swal.fire({
          title: "something is wrong!",
          icon: "error",
          confirmButtonText: "back",
          customClass: {
            confirmButton: "btn btn-primary",
          },
          buttonsStyling: false,
        });
        self.parents(".table.dataTable").DataTable().ajax.reload();
      }
    },
  });
});

function strpos(haystack, needle, offset) {
  var i = (haystack + "").indexOf(needle, offset || 0);
  return i === -1 ? false : i;
}

function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(";");
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == " ") {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

$(document).on("input", "input[type=number]", function (e) {
  e.preventDefault();
  e.stopPropagation();

  $(this)
    .val()
    .replace(/[^0-9.]/g, "")
    .replace(/(\..*)\./g, "$0");
  if ($(this).val().trim().length === 0) $(this).val(0);
});
