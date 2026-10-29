(function ($) {
  function frame(options, onSelect) {
    var picker = wp.media(options);
    picker.on("select", function () {
      var selection = picker.state().get("selection");
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

  $(document).on("click", ".mom-editor-tools button", function () {
    var area = $(this).closest(".mom-editor").find("textarea").get(0);
    if (!area) return;
    var token = $(this).data("wrap");
    var start = area.selectionStart;
    var end = area.selectionEnd;
    var selected = area.value.slice(start, end) || "text";
    var insert = token === "link" ? '<a href="">' + selected + "</a>" : "<" + token + ">" + selected + "</" + token + ">";
    area.setRangeText(insert, start, end, "end");
    area.focus();
  });

  function applyPick(button, file) {
    var target = $("#" + button.data("target"));
    var preview = button.closest(".mom-field, .mom-card, .mom-side-card").find(".mom-pick-preview").first();
    target.val(file.id);
    button.siblings(".mom-file-name").text(file.filename || file.title || "Selected");
    if (preview.length && file.url) {
      preview.html('<img src="' + file.url + '" alt="">');
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

  function openCrop(file, aspect, done) {
    if (!file || !file.url || typeof $.fn.imgAreaSelect !== "function") {
      done(file);
      return;
    }
    $(".mom-crop-modal").remove();
    var modal = $(
      '<div class="mom-crop-modal" role="dialog" aria-label="Crop image">' +
        '<div class="mom-crop-panel"><p class="mom-crop-title">Crop image</p>' +
        '<div class="mom-crop-stage"><img alt=""></div>' +
        '<p class="mom-crop-error" hidden></p>' +
        '<div class="mom-crop-actions">' +
        '<button type="button" class="mom-btn" data-crop-skip>Use full image</button>' +
        '<button type="button" class="mom-btn mom-btn-primary" data-crop-apply>Crop and use</button>' +
        "</div></div></div>"
    );
    var img = modal.find("img");
    var ias = null;
    $("body").append(modal);
    img.on("load", function () {
      var ratio = aspectValue(aspect);
      var options = {
        handles: true,
        persistent: true,
        parent: modal.find(".mom-crop-stage"),
        instance: true,
      };
      if (ratio) options.aspectRatio = ratio;
      ias = img.imgAreaSelect(options);
      var w = img.width();
      var h = img.height();
      var x2 = w;
      var y2 = h;
      if (ratio) {
        var parts = ratio.split(":");
        var aw = parseFloat(parts[0]);
        var ah = parseFloat(parts[1]);
        if (w / h > aw / ah) {
          x2 = Math.round(h * (aw / ah));
        } else {
          y2 = Math.round(w * (ah / aw));
        }
      }
      ias.setSelection(0, 0, x2, y2);
      ias.setOptions({ show: true });
      ias.update();
    });
    img.attr("src", file.url);
    function closeCrop() {
      if (ias) ias.remove();
      modal.remove();
    }
    modal.on("click", "[data-crop-skip]", function () {
      closeCrop();
      done(file);
    });
    modal.on("click", "[data-crop-apply]", function () {
      if (!ias || !window.ministryDesk) {
        closeCrop();
        done(file);
        return;
      }
      var sel = ias.getSelection();
      if (!sel || sel.width < 8 || sel.height < 8) {
        modal.find(".mom-crop-error").text("Choose an area to crop.").prop("hidden", false);
        return;
      }
      var scaleX = (file.width || img[0].naturalWidth) / img.width();
      var scaleY = (file.height || img[0].naturalHeight) / img.height();
      $.post(window.ministryDesk.ajax, {
        action: "ministry_desk_crop",
        nonce: window.ministryDesk.cropNonce,
        id: file.id,
        x: Math.round(sel.x1 * scaleX),
        y: Math.round(sel.y1 * scaleY),
        w: Math.round(sel.width * scaleX),
        h: Math.round(sel.height * scaleY),
      })
        .done(function (res) {
          if (!res || !res.success || !res.data) {
            modal.find(".mom-crop-error").text("Crop failed.").prop("hidden", false);
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
        .fail(function () {
          modal.find(".mom-crop-error").text("Crop failed.").prop("hidden", false);
        });
    });
  }

  $(document).on("click", ".mom-pick", function (event) {
    event.preventDefault();
    var button = $(this);
    var isImage = button.data("library") === "image";
    frame(
      {
        title: "Select file",
        button: { text: isImage ? "Crop this image" : "Use this file" },
        multiple: false,
        library: button.data("library") ? { type: button.data("library") } : undefined,
      },
      function (selection) {
        var file = selection.first().toJSON();
        if (isImage) {
          openCrop(file, button.data("aspect"), function (cropped) {
            applyPick(button, cropped);
          });
          return;
        }
        applyPick(button, file);
      }
    );
  });

  $(document).on("click", ".mom-add-photos", function (event) {
    event.preventDefault();
    var list = $(".mom-photo-grid");
    frame(
      {
        title: "Add photos",
        button: { text: "Add to album" },
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
          list.append(card);
        });
      }
    );
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
    list.find("[data-id]").each(function () {
      ids.push($(this).data("id"));
    });
    list.closest("form").find("[name='" + field + "']").val(ids.join(","));
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
})(jQuery);
