(function ($) {
  function frame(library, onSelect) {
    var picker = wp.media({
      title: "Select file",
      button: { text: "Use this file" },
      multiple: false,
      library: library ? { type: library } : undefined,
    });
    picker.on("select", function () {
      var file = picker.state().get("selection").first().toJSON();
      onSelect(file);
    });
    picker.open();
  }

  $(document).on("click", ".mom-pick-image", function (event) {
    event.preventDefault();
    var target = $(this).data("target");
    frame("image", function (file) {
      $("#" + target).val(file.id);
      var preview = $(".mom-logo-preview");
      if (preview.length && file.sizes && file.sizes.thumbnail) {
        preview.html('<img src="' + file.sizes.thumbnail.url + '" alt="" style="max-height:72px">');
      }
    });
  });

  $(document).on("click", ".mom-clear-image", function (event) {
    event.preventDefault();
    $("#" + $(this).data("target")).val("0");
    $(".mom-logo-preview").empty();
  });

  $(document).on("click", ".mom-pick-file", function (event) {
    event.preventDefault();
    var target = $(this).data("target");
    var label = $(this).siblings(".mom-file-name");
    frame("", function (file) {
      $("#" + target).val(file.id);
      label.text(file.filename || file.title || "");
    });
  });

  $(document).on("click", "[data-add-row]", function (event) {
    event.preventDefault();
    var box = $(this).closest("[data-repeater]");
    var row = box.find("[data-repeater-row]").last();
    if (!row.length) return;
    var clone = row.clone();
    clone.find("input, textarea").val("");
    clone.find("select").prop("selectedIndex", 0);
    var next = box.find("[data-repeater-row]").length;
    var direct = row.children("p").find("input, textarea, select").first().attr("name") || "";
    var targetDepth = 0;
    var seen = 0;
    direct.replace(/\[(\d+)\]/g, function () {
      targetDepth = seen;
      seen += 1;
      return "";
    });
    clone.find("[name]").each(function () {
      var depth = 0;
      var updated = $(this).attr("name").replace(/\[(\d+)\]/g, function (match) {
        var out = depth === targetDepth ? "[" + next + "]" : match;
        depth += 1;
        return out;
      });
      $(this).attr("name", updated);
    });
    row.after(clone);
  });

  $(document).on("click", "[data-remove-row]", function (event) {
    event.preventDefault();
    var box = $(this).closest("[data-repeater]");
    if (box.find("[data-repeater-row]").length < 2) {
      $(this).closest("[data-repeater-row]").find("input, textarea").val("");
      return;
    }
    $(this).closest("[data-repeater-row]").remove();
  });

  function photoSrc(file) {
    if (file.sizes && file.sizes.thumbnail && file.sizes.thumbnail.url) {
      return file.sizes.thumbnail.url;
    }
    return file.url || "";
  }

  function renumberPhotos(list) {
    list.find(".mom-photo-row").each(function (index) {
      $(this).find("[name]").each(function () {
        var name = $(this).attr("name");
        if (name) {
          $(this).attr("name", name.replace(/mom_photos\[\d+\]/, "mom_photos[" + index + "]"));
        }
      });
    });
  }

  $(document).on("click", ".mom-photo-pick", function (event) {
    event.preventDefault();
    var row = $(this).closest(".mom-photo-row");
    frame("image", function (file) {
      row.find(".mom-photo-id").val(file.id);
      var src = photoSrc(file);
      row.find(".mom-photo-preview").html(src ? '<img src="' + src.replace(/"/g, "&quot;") + '" alt="">' : "");
    });
  });

  $(document).on("click", ".mom-add-photo", function (event) {
    event.preventDefault();
    var list = $(this).closest(".mom-album-photos").find(".mom-photo-list");
    var row = list.find(".mom-photo-row").last();
    if (!row.length) return;
    var clone = row.clone();
    clone.find("input[type=text], input[type=hidden]").val("");
    clone.find("input[type=checkbox]").prop("checked", false);
    clone.find(".mom-photo-preview").empty();
    list.append(clone);
    renumberPhotos(list);
  });

  $(document).on("click", ".mom-remove-photo", function (event) {
    event.preventDefault();
    var list = $(this).closest(".mom-photo-list");
    if (list.find(".mom-photo-row").length < 2) {
      var row = $(this).closest(".mom-photo-row");
      row.find("input[type=text], input[type=hidden]").val("");
      row.find("input[type=checkbox]").prop("checked", false);
      row.find(".mom-photo-preview").empty();
      return;
    }
    $(this).closest(".mom-photo-row").remove();
    renumberPhotos(list);
  });

  $(function () {
    var list = $(".mom-photo-list");
    if (!list.length || !$.fn.sortable) return;
    list.sortable({
      handle: ".mom-photo-handle",
      items: ".mom-photo-row",
      placeholder: "mom-photo-placeholder",
      update: function () {
        renumberPhotos(list);
      },
    });
    $("#post").on("submit", function () {
      renumberPhotos(list);
    });
  });
})(jQuery);
