(function ($) {
  function frame(options, onSelect) {
    var picker = wp.media(options);
    picker.on("select", function () {
      var selection = picker.state().get("selection");
      picker.close();
      onSelect(selection);
    });
    picker.open();
  }

  $(document).on("click", ".mom-burger", function () {
    document.body.classList.toggle("mom-nav-open");
  });

  $(document).on("input", "[name=banner_text]", function () {
    var text = $.trim($(this).val()) || "Banner text shows here.";
    $("[data-banner-preview] span").first().text(text);
  });

  $(document).on("click", "[data-add-row]", function (event) {
    event.preventDefault();
    var box = $(this).closest("[data-repeater]");
    var row = box.find("[data-row]").last();
    if (!row.length) return;
    var clone = row.clone();
    clone.find("input, textarea").val("");
    clone.find("select").prop("selectedIndex", 0);
    var next = box.find("[data-row]").length;
    clone.find("[name]").each(function () {
      var name = $(this).attr("name");
      $(this).attr("name", name.replace(/\[\d+\]/, "[" + next + "]"));
    });
    row.after(clone);
  });

  $(document).on("click", "[data-remove-row]", function (event) {
    event.preventDefault();
    var box = $(this).closest("[data-repeater]");
    var row = $(this).closest("[data-row]");
    if (box.find("[data-row]").length < 2) {
      row.find("input, textarea").val("");
      return;
    }
    row.remove();
  });

  $(document).on("click", "[data-chip-add]", function (event) {
    event.preventDefault();
    var wrap = $(this).closest("[data-chips]");
    var input = wrap.find("[data-chip-input]");
    var value = $.trim(input.val());
    if (!value) return;
    var name = wrap.data("name");
    var chip = $('<span class="mom-chip"></span>');
    chip.append(document.createTextNode(value + " "));
    chip.append('<input type="hidden" name="' + name + '[]" value="">');
    chip.find("input").val(value);
    chip.append('<button type="button" data-chip-remove aria-label="Remove">&times;</button>');
    wrap.find(".mom-chips").append(chip);
    input.val("");
  });

  $(document).on("click", "[data-chip-remove]", function () {
    $(this).closest(".mom-chip").remove();
  });

  $(document).on("click", ".mom-icons button", function () {
    var box = $(this).closest(".mom-icons");
    box.find("button").removeClass("is-on");
    $(this).addClass("is-on");
    box.find("input").val($(this).data("icon"));
  });

  $(document).on("click", ".mom-steps button", function () {
    var box = $(this).closest(".mom-steps");
    box.find("button").removeClass("is-on");
    $(this).addClass("is-on");
    box.find("input").val($(this).data("value"));
  });

  function syncEditor(box) {
    var surface = box.find(".mom-editor-surface");
    var area = box.find(".mom-editor-html");
    if (!surface.length || !area.length) return;
    area.val(surface.html());
    surface.toggleClass("is-empty", !$.trim(surface.text()));
  }

  function initEditors() {
    document.execCommand("defaultParagraphSeparator", false, "p");
    $(".mom-editor").each(function () {
      syncEditor($(this));
    });
  }

  $(document).on("input blur", ".mom-editor-surface", function () {
    syncEditor($(this).closest(".mom-editor"));
  });

  $(document).on("submit", "form", function () {
    $(this).find(".mom-editor").each(function () {
      syncEditor($(this));
    });
  });

  $(document).on("paste", ".mom-editor-surface", function (event) {
    event.preventDefault();
    var clip = event.originalEvent && event.originalEvent.clipboardData;
    var text = clip ? clip.getData("text/plain") : "";
    document.execCommand("insertText", false, text || "");
    syncEditor($(this).closest(".mom-editor"));
  });

  $(document).on("click", ".mom-editor-tools button", function () {
    var box = $(this).closest(".mom-editor");
    var surface = box.find(".mom-editor-surface").get(0);
    var cmd = $(this).data("cmd");
    if (!surface) return;
    surface.focus();
    document.execCommand("defaultParagraphSeparator", false, "p");
    if (cmd === "link") {
      var href = window.prompt("Link address", "https://");
      if (href) document.execCommand("createLink", false, href);
    } else if (cmd === "h2") {
      document.execCommand("formatBlock", false, "h2");
    } else if (cmd === "ul") {
      document.execCommand("insertUnorderedList", false, null);
    } else {
      document.execCommand(cmd, false, null);
    }
    syncEditor(box);
  });

  initEditors();

  function applyPick(button, file) {
    var target = $("#" + button.data("target"));
    var card = button.closest(".mom-field, .mom-card, .mom-side-card");
    var preview = card.find(".mom-pick-preview").first();
    target.val(file.id);
    button.siblings(".mom-file-name").text(file.filename || file.title || "Selected");
    if (!file.url) return;
    if (preview.length) {
      preview.html('<img src="' + file.url + '" alt="">');
      return;
    }
    var existing = card.find("img").first();
    if (existing.length) {
      existing.attr("src", file.url);
    }
  }

  function aspectValue(raw) {
    if (!raw) return "";
    var parts = String(raw).split(":");
    if (parts.length === 2) {
      var w = parseFloat(parts[0]);
      var h = parseFloat(parts[1]);
      if (w && h) return w + ":" + h;
    }
    return "";
  }

  function cropErrorMessage(res) {
    if (res && res.data && res.data.message) return res.data.message;
    return "Crop failed.";
  }

  function aspectRatio(raw) {
    var value = aspectValue(raw);
    if (!value) return 0;
    var parts = value.split(":");
    var w = parseFloat(parts[0]);
    var h = parseFloat(parts[1]);
    return w && h ? w / h : 0;
  }

  function clampCrop(sel, dw, dh, ratio) {
    sel.w = Math.max(24, sel.w);
    sel.h = Math.max(24, sel.h);
    if (ratio) {
      if (sel.w / sel.h > ratio) sel.h = sel.w / ratio;
      else sel.w = sel.h * ratio;
      if (sel.w > dw) {
        sel.w = dw;
        sel.h = dw / ratio;
      }
      if (sel.h > dh) {
        sel.h = dh;
        sel.w = dh * ratio;
      }
    } else {
      sel.w = Math.min(sel.w, dw);
      sel.h = Math.min(sel.h, dh);
    }
    sel.x = Math.max(0, Math.min(sel.x, dw - sel.w));
    sel.y = Math.max(0, Math.min(sel.y, dh - sel.h));
    return sel;
  }

  function openCrop(file, aspect, done) {
    if (!file || !file.url) {
      done(file);
      return;
    }
    $(".mom-crop-modal").remove();
    var modal = $(
      '<div class="mom-crop-modal" role="dialog" aria-label="Crop image">' +
        '<div class="mom-crop-panel"><p class="mom-crop-title">Crop image</p>' +
        '<div class="mom-crop-stage"><div class="mom-crop-frame"><img alt=""></div></div>' +
        '<p class="mom-crop-error" hidden></p>' +
        '<div class="mom-crop-actions">' +
        '<button type="button" class="mom-btn" data-crop-skip>Cancel</button>' +
        '<button type="button" class="mom-btn mom-btn-primary" data-crop-apply>Crop and use</button>' +
        "</div></div></div>"
    );
    var img = modal.find("img");
    var frame = modal.find(".mom-crop-frame");
    var box = $(
      '<div class="mom-crop-box" hidden>' +
        '<span data-handle="nw"></span><span data-handle="n"></span><span data-handle="ne"></span>' +
        '<span data-handle="w"></span><span data-handle="e"></span>' +
        '<span data-handle="sw"></span><span data-handle="s"></span><span data-handle="se"></span>' +
        "</div>"
    );
    var started = false;
    var applying = false;
    var sel = { x: 0, y: 0, w: 0, h: 0 };
    var ratio = aspectRatio(aspect);
    frame.append(box);
    $("body").append(modal);

    function displaySize() {
      var el = img.get(0);
      return { dw: el.clientWidth, dh: el.clientHeight, nw: el.naturalWidth, nh: el.naturalHeight };
    }

    function renderBox() {
      box.css({
        left: sel.x + "px",
        top: sel.y + "px",
        width: sel.w + "px",
        height: sel.h + "px",
      });
    }

    function startSelect() {
      var size = displaySize();
      if (started || !size.nw || !size.nh || !size.dw || !size.dh) return;
      started = true;
      sel = { x: 0, y: 0, w: size.dw, h: size.dh };
      if (ratio) {
        if (size.dw / size.dh > ratio) {
          sel.w = size.dh * ratio;
          sel.x = (size.dw - sel.w) / 2;
        } else {
          sel.h = size.dw / ratio;
          sel.y = (size.dh - sel.h) / 2;
        }
      }
      clampCrop(sel, size.dw, size.dh, ratio);
      renderBox();
      box.prop("hidden", false);
    }

    img.on("load", startSelect);
    img.attr("src", file.url);
    if (img[0].complete) startSelect();

    box.on("pointerdown", function (event) {
      if (applying) return;
      event.preventDefault();
      var handle = event.target.getAttribute("data-handle") || "";
      var startX = event.clientX;
      var startY = event.clientY;
      var orig = { x: sel.x, y: sel.y, w: sel.w, h: sel.h };
      var pointer = event.originalEvent && event.originalEvent.pointerId;
      if (event.target.setPointerCapture && pointer != null) {
        event.target.setPointerCapture(pointer);
      }

      function onMove(ev) {
        var dx = ev.clientX - startX;
        var dy = ev.clientY - startY;
        var size = displaySize();
        if (!handle) {
          sel.x = orig.x + dx;
          sel.y = orig.y + dy;
        } else {
          var x1 = orig.x;
          var y1 = orig.y;
          var x2 = orig.x + orig.w;
          var y2 = orig.y + orig.h;
          if (handle.indexOf("w") !== -1) x1 = orig.x + dx;
          if (handle.indexOf("e") !== -1) x2 = orig.x + orig.w + dx;
          if (handle.indexOf("n") !== -1) y1 = orig.y + dy;
          if (handle.indexOf("s") !== -1) y2 = orig.y + orig.h + dy;
          var nextW = x2 - x1;
          var nextH = y2 - y1;
          if (ratio && nextW > 0 && nextH > 0) {
            if (Math.abs(dx) * (1 / ratio) > Math.abs(dy)) {
              nextH = nextW / ratio;
              if (handle.indexOf("n") !== -1) y1 = y2 - nextH;
              else y2 = y1 + nextH;
            } else {
              nextW = nextH * ratio;
              if (handle.indexOf("w") !== -1) x1 = x2 - nextW;
              else x2 = x1 + nextW;
            }
          }
          sel.x = x1;
          sel.y = y1;
          sel.w = x2 - x1;
          sel.h = y2 - y1;
        }
        clampCrop(sel, size.dw, size.dh, ratio);
        renderBox();
      }

      function onUp() {
        $(document).off("pointermove", onMove);
        $(document).off("pointerup pointercancel", onUp);
      }

      $(document).on("pointermove", onMove);
      $(document).on("pointerup pointercancel", onUp);
    });

    function closeCrop() {
      modal.remove();
    }
    modal.on("click", "[data-crop-skip]", function () {
      closeCrop();
    });
    modal.on("click", "[data-crop-apply]", function () {
      if (applying) return;
      var size = displaySize();
      if (!started || !size.dw || !size.dh || !window.ministryDesk) {
        closeCrop();
        done(file);
        return;
      }
      var x = Math.round(sel.x * size.nw / size.dw);
      var y = Math.round(sel.y * size.nh / size.dh);
      var w = Math.round(sel.w * size.nw / size.dw);
      var h = Math.round(sel.h * size.nh / size.dh);
      if (w < 8 || h < 8) {
        modal.find(".mom-crop-error").text("Choose an area to crop.").prop("hidden", false);
        return;
      }
      applying = true;
      modal.find("[data-crop-apply]").prop("disabled", true).text("Cropping…");
      $.post(window.ministryDesk.ajax, {
        action: "ministry_desk_crop",
        nonce: window.ministryDesk.cropNonce,
        id: file.id,
        x: x,
        y: y,
        w: w,
        h: h,
      })
        .done(function (res) {
          if (!res || !res.success || !res.data) {
            applying = false;
            modal.find("[data-crop-apply]").prop("disabled", false).text("Crop and use");
            modal.find(".mom-crop-error").text(cropErrorMessage(res)).prop("hidden", false);
            return;
          }
          closeCrop();
          done({
            id: res.data.id,
            url: res.data.url,
            filename: res.data.filename,
            title: res.data.filename,
          });
        })
        .fail(function (xhr) {
          applying = false;
          modal.find("[data-crop-apply]").prop("disabled", false).text("Crop and use");
          var res = xhr && xhr.responseJSON;
          modal.find(".mom-crop-error").text(cropErrorMessage(res)).prop("hidden", false);
        });
    });
  }

  function ensureCropButton(pick, file) {
    if (pick.data("library") !== "image") return;
    var crop = pick.siblings(".mom-crop-open");
    if (!crop.length) {
      crop = $('<button type="button" class="mom-btn mom-btn-small mom-crop-open">Crop</button>');
      pick.after(crop);
    }
    if (file) crop.data("file", file);
  }

  $(document).on("click", ".mom-pick", function (event) {
    event.preventDefault();
    var button = $(this);
    var isImage = button.data("library") === "image";
    frame(
      {
        title: "Select file",
        button: { text: isImage ? "Use this image" : "Use this file" },
        multiple: false,
        library: button.data("library") ? { type: button.data("library") } : undefined,
      },
      function (selection) {
        var file = selection.first().toJSON();
        applyPick(button, file);
        if (isImage) ensureCropButton(button, file);
      }
    );
  });

  $(document).on("click", ".mom-crop-open", function (event) {
    event.preventDefault();
    var crop = $(this);
    var pick = crop.siblings(".mom-pick").first();
    var file = crop.data("file");
    function run(source) {
      if (!source || !source.url) return;
      openCrop(source, pick.data("aspect"), function (cropped) {
        applyPick(pick, cropped);
        crop.data("file", cropped);
      });
    }
    if (file && file.url) {
      run(file);
      return;
    }
    var id = $("#" + pick.data("target")).val();
    if (!id || !window.wp || !wp.media || !wp.media.attachment) return;
    var att = wp.media.attachment(id);
    att.fetch().done(function () {
      run({
        id: att.get("id"),
        url: att.get("url"),
        filename: att.get("filename"),
      });
    });
  });

  $(".mom-pick[data-library=image]").each(function () {
    var pick = $(this);
    var id = $("#" + pick.data("target")).val();
    if (id) ensureCropButton(pick);
  });

  $(document).on("click", ".mom-add-photos", function (event) {
    event.preventDefault();
    var list = $(this).closest("form").find(".mom-photo-grid");
    if (!list.length) list = $(".mom-photo-grid");
    frame(
      {
        title: "Upload photos",
        button: { text: "Upload" },
        multiple: true,
        library: { type: "image" },
      },
      function (selection) {
        selection.each(function (item) {
          var file = item.toJSON();
          var index = list.children().length;
          var card = $($("#mom-photo-template").html());
          card.find("[name]").each(function () {
            $(this).attr("name", $(this).attr("name").replace("__i__", index));
          });
          card.find(".mom-photo-id").val(file.id);
          card.find("img").attr("src", (file.sizes && file.sizes.medium && file.sizes.medium.url) || file.url);
          var holder = card.find(".mom-upload-albums");
          if (holder.length) {
            $(".mom-upload-album:checked").each(function () {
              holder.append(
                $("<input>", {
                  type: "hidden",
                  name: "photos[" + index + "][albums][]",
                  value: $(this).val(),
                })
              );
            });
          }
          list.append(card);
        });
      }
    );
  });
  $(document).on("click", ".mom-faq-card .mom-danger-link", function (event) {
    event.stopPropagation();
  });

  $(document).on("click", ".mom-remove-photo", function () {
    $(this).closest(".mom-photo").remove();
  });

  $(document).on("change", "#mom-missing-only", function () {
    var on = $(this).is(":checked");
    $(".mom-photo").each(function () {
      var missing = !$.trim($(this).find(".mom-alt").val());
      $(this).toggle(!on || missing);
    });
  });

  function writeOrder(list) {
    var ids = [];
    var field = list.attr("data-order-field") || "order";
    list.children("[data-id]").each(function () {
      ids.push($(this).data("id"));
    });
    var form = list.data("order-form") ? $(list.data("order-form")) : list.closest("form");
    form.find("[name='" + field + "']").val(ids.join(","));
  }

  $(".mom-sortable").each(function () {
    var list = $(this);
    if (!$.fn.sortable) return;
    var options = {
      handle: ".grip",
      update: function () {
        writeOrder(list);
        refreshHomePreview();
        list.children().each(function (index) {
          $(this).find("[name]").each(function () {
            var name = $(this).attr("name") || "";
            $(this).attr("name", name.replace(/mom_photos\[\d+\]/, "mom_photos[" + index + "]"));
          });
        });
      },
    };
    if (list.is("tbody")) {
      options.items = "tr";
    }
    if (list.hasClass("mom-faq-list")) {
      options.items = "details";
    }
    list.sortable(options);
  });

  function refreshHomePreview() {
    var preview = $("#mom-home-preview");
    if (!preview.length) return;
    preview.empty();
    var count = 0;
    $(".mom-sortable .mom-person").each(function () {
      if (count >= 4) return;
      var row = $(this);
      var home = row.find("input[name^='home']");
      if (row.attr("data-former") === "1") return;
      if (home.length && !home.is(":checked")) return;
      if (!home.length && row.attr("data-home") === "0") return;
      var cell = $("<div>");
      var src = row.find("img").attr("src");
      if (src) {
        $("<img>", { src: src, alt: "" }).css({
          width: "100%",
          aspectRatio: "4 / 5",
          objectFit: "cover",
          borderRadius: "8px",
        }).appendTo(cell);
      }
      $("<p>").append($("<strong>").text(row.find(".who b").text())).appendTo(cell);
      preview.append(cell);
      count += 1;
    });
  }

  $(document).on("change", ".mom-person input[name^='home']", refreshHomePreview);

  $(document).on("change", "#mom-dept-page", function () {
    if ($("#mom-dept-photo").val()) return;
    var src = $(this).find("option:selected").data("photo");
    var preview = $("#mom-dept-photo").closest(".mom-field").find(".mom-pick-preview");
    preview.html(src ? '<img src="' + src + '" alt="">' : "");
  });

  refreshHomePreview();

  function toggleCustomCaps() {
    var role = $("select[name=role]").val();
    $("[data-custom-caps]").prop("hidden", role !== "mom_custom");
  }
  $(document).on("change", "select[name=role]", toggleCustomCaps);
  toggleCustomCaps();
})(jQuery);
