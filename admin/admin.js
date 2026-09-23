(function () {
  var side = document.querySelector("[data-cms-side]");
  var menu = document.querySelector("[data-cms-menu]");
  if (side && menu) {
    menu.addEventListener("click", function () {
      var open = side.classList.toggle("is-open");
      menu.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    });
    document.addEventListener("click", function (e) {
      if (!side.classList.contains("is-open")) return;
      if (side.contains(e.target) || menu.contains(e.target)) return;
      side.classList.remove("is-open");
    });
  }

  try {
    var raw = localStorage.getItem("cfc-cms-nav") || "{}";
    var saved = JSON.parse(raw);
    document.querySelectorAll("[data-nav-group]").forEach(function (el) {
      var id = el.getAttribute("data-nav-group");
      if (el.querySelector(".is-on")) {
        el.open = true;
      } else if (Object.prototype.hasOwnProperty.call(saved, id)) {
        el.open = !!saved[id];
      }
      el.addEventListener("toggle", function () {
        saved[id] = el.open;
        localStorage.setItem("cfc-cms-nav", JSON.stringify(saved));
      });
    });
  } catch (e) {}

  document.querySelectorAll("[data-repeater]").forEach(function (wrap) {
    var list = wrap.querySelector("[data-repeater-list]");
    var tpl = wrap.querySelector("[data-repeater-template]");
    var add = wrap.querySelector("[data-repeater-add]");
    if (!list || !tpl || !add) return;
    add.addEventListener("click", function () {
      var html = tpl.innerHTML.replace(/__i__/g, String(Date.now()));
      var hold = document.createElement("div");
      hold.innerHTML = html.trim();
      var node = hold.firstElementChild;
      if (node) list.appendChild(node);
    });
    wrap.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-repeater-remove]");
      if (!btn) return;
      var item = btn.closest("[data-repeater-item]");
      if (item) item.remove();
    });
  });

  var wys = document.querySelectorAll("textarea[data-wysiwyg]");
  if (wys.length) {
    var boot = function () {
      if (!window.tinymce) return;
      window.tinymce.init({
        selector: "textarea[data-wysiwyg]",
        license_key: "gpl",
        height: 480,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: "lists link image table autoresize code",
        toolbar: "undo redo | blocks | bold italic underline | alignleft aligncenter | bullist numlist | link image | removeformat | code",
        block_formats: "Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4",
        content_style: "body{font-family:Poppins,sans-serif;font-size:16px;line-height:1.65;color:#2a1a12}a{color:#633018}h2,h3{margin:1em 0 .4em}",
        convert_urls: false,
        relative_urls: false
      });
    };
    if (window.tinymce) {
      boot();
    } else {
      var s = document.createElement("script");
      s.src = "https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js";
      s.referrerPolicy = "origin";
      s.onload = boot;
      document.head.appendChild(s);
    }
  }

  document.querySelectorAll("input[type=file][data-preview]").forEach(function (input) {
    input.addEventListener("change", function () {
      var img = document.querySelector(input.getAttribute("data-preview"));
      if (!img || !input.files || !input.files[0] || !input.files[0].type.startsWith("image/")) return;
      var url = URL.createObjectURL(input.files[0]);
      img.src = url;
      img.hidden = false;
    });
  });
})();

/* Gallery photos: bulk upload, drag to reorder, move between categories. */
(function () {
  var form = document.querySelector("[data-gallery]");
  if (!form) return;

  var catList = form.querySelector("[data-cat-list]");
  var tpl = document.querySelector("[data-cat-template]");
  var bulk = document.querySelector("[data-bulk]");
  var bulkN = bulk && bulk.querySelector("[data-bulk-n]");
  var bulkCat = bulk && bulk.querySelector("[data-bulk-cat]");
  var dirtyTag = form.querySelector("[data-dirty]");
  var totalEl = form.querySelector("[data-total]");
  var submitBtn = form.querySelector('button[type="submit"]');
  var dirty = false;
  var leaving = false;
  var newSeq = 0;

  function list(root, sel) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }
  function cats() {
    return list(catList, "[data-cat]");
  }
  function tilesIn(cat) {
    var grid = cat.querySelector("[data-cat-grid]");
    return grid ? list(grid, "[data-img]") : [];
  }
  function picked() {
    return list(catList, "[data-img-pick]:checked").map(function (c) {
      return c.closest("[data-img]");
    });
  }
  function catLabel(cat, i) {
    var input = cat.querySelector("[data-cat-title]");
    var v = input ? input.value.trim() : "";
    return v || "Category " + (i + 1);
  }
  function markDirty() {
    dirty = true;
    if (dirtyTag) dirtyTag.hidden = false;
  }
  function plural(n, word) {
    return n + " " + word + (n === 1 ? "" : "s");
  }

  /* Write the DOM order back into one hidden field per category. Nothing else
     carries the order, so this must run after every move, add or remove. */
  function sync() {
    var all = cats();
    var total = 0;
    all.forEach(function (cat) {
      var tiles = tilesIn(cat);
      var refs = [];
      tiles.forEach(function (tile, i) {
        var ref = tile.getAttribute("data-ref") || "";
        if (ref) refs.push(ref);
        var n = tile.querySelector("[data-img-n]");
        if (n) n.textContent = String(i + 1);
      });
      var order = cat.querySelector("[data-cat-order]");
      if (order) order.value = refs.join(",");
      var count = cat.querySelector("[data-cat-count]");
      if (count) count.textContent = plural(tiles.length, "photo");
      var empty = cat.querySelector("[data-cat-empty]");
      if (empty) empty.hidden = tiles.length > 0;
      total += tiles.length;
    });
    if (totalEl) {
      totalEl.textContent = plural(total, "photo") + " in " + all.length +
        (all.length === 1 ? " category." : " categories.");
    }
    if (bulkCat) {
      var keep = bulkCat.value;
      bulkCat.innerHTML = "";
      all.forEach(function (cat, i) {
        var o = document.createElement("option");
        o.value = cat.getAttribute("data-cat-key") || "";
        o.textContent = catLabel(cat, i);
        bulkCat.appendChild(o);
      });
      if (keep) bulkCat.value = keep;
    }
    syncBulk();
  }

  function syncBulk() {
    if (!bulk) return;
    var n = picked().length;
    bulk.hidden = n === 0;
    if (bulkN) bulkN.textContent = n + " selected";
  }

  function shift(node, dir) {
    var sib = dir < 0 ? node.previousElementSibling : node.nextElementSibling;
    if (!sib) return false;
    if (dir < 0) sib.before(node);
    else sib.after(node);
    return true;
  }

  catList.addEventListener("click", function (e) {
    var step = e.target.closest("[data-img-step]");
    if (step) {
      if (shift(step.closest("[data-img]"), Number(step.getAttribute("data-img-step")))) {
        markDirty();
        sync();
      }
      return;
    }
    if (e.target.closest("[data-img-del]")) {
      e.target.closest("[data-img]").remove();
      markDirty();
      sync();
      return;
    }
    var cstep = e.target.closest("[data-cat-step]");
    if (cstep) {
      if (shift(cstep.closest("[data-cat]"), Number(cstep.getAttribute("data-cat-step")))) {
        markDirty();
        sync();
      }
      return;
    }
    if (e.target.closest("[data-cat-del]")) {
      var cat = e.target.closest("[data-cat]");
      var n = tilesIn(cat).length;
      var name = catLabel(cat, cats().indexOf(cat));
      var msg = n
        ? 'Delete "' + name + '" and take its ' + plural(n, "photo") + " off the gallery page?"
        : 'Delete "' + name + '"?';
      if (!window.confirm(msg)) return;
      cat.remove();
      markDirty();
      sync();
    }
  });

  catList.addEventListener("change", function (e) {
    if (e.target.matches("[data-img-pick]")) {
      syncBulk();
      return;
    }
    markDirty();
  });

  catList.addEventListener("input", function (e) {
    if (e.target.matches("[data-cat-title]")) sync();
    markDirty();
  });

  var addBtn = form.querySelector("[data-cat-add]");
  if (addBtn && tpl) {
    addBtn.addEventListener("click", function () {
      newSeq += 1;
      var hold = document.createElement("div");
      hold.innerHTML = tpl.innerHTML.replace(/__k__/g, "new-" + Date.now() + "-" + newSeq).trim();
      var node = hold.firstElementChild;
      if (!node) return;
      catList.appendChild(node);
      markDirty();
      sync();
      var title = node.querySelector("[data-cat-title]");
      if (title) title.focus();
      node.scrollIntoView({ block: "nearest" });
    });
  }

  /* Drag a photo anywhere inside another category card to move it there. */
  var dragged = null;

  function gridFrom(target) {
    var grid = target.closest("[data-cat-grid]");
    if (grid) return grid;
    var cat = target.closest("[data-cat]");
    return cat ? cat.querySelector("[data-cat-grid]") : null;
  }
  function clearDrop() {
    list(catList, ".is-drop").forEach(function (el) {
      el.classList.remove("is-drop");
    });
  }

  catList.addEventListener("dragstart", function (e) {
    var tile = e.target.closest("[data-img]");
    if (!tile) return;
    dragged = tile;
    tile.classList.add("is-dragging");
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = "move";
      try {
        e.dataTransfer.setData("text/plain", tile.getAttribute("data-ref") || "photo");
      } catch (err) {}
    }
  });

  /* dragover does the reordering, so the DOM can already have changed even when
     the drag ends outside a drop zone or is cancelled. dragend always fires, so
     commit from here rather than from drop alone. */
  catList.addEventListener("dragend", function () {
    if (dragged) dragged.classList.remove("is-dragging");
    dragged = null;
    clearDrop();
    markDirty();
    sync();
  });

  catList.addEventListener("dragover", function (e) {
    if (!dragged) return;
    var grid = gridFrom(e.target);
    if (!grid) return;
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = "move";
    if (!grid.classList.contains("is-drop")) {
      clearDrop();
      grid.classList.add("is-drop");
    }
    var over = e.target.closest("[data-img]");
    if (!over || over === dragged) {
      if (!over && dragged.parentNode !== grid) grid.appendChild(dragged);
      return;
    }
    var box = over.getBoundingClientRect();
    if (e.clientX - box.left > box.width / 2) over.after(dragged);
    else over.before(dragged);
  });

  catList.addEventListener("drop", function (e) {
    if (!dragged) return;
    var grid = gridFrom(e.target);
    if (!grid) return;
    e.preventDefault();
    if (dragged.parentNode !== grid) grid.appendChild(dragged);
    clearDrop();
    markDirty();
    sync();
  });

  if (bulk) {
    bulk.addEventListener("click", function (e) {
      var sel = picked();
      if (e.target.closest("[data-bulk-clear]")) {
        sel.forEach(function (t) {
          var c = t.querySelector("[data-img-pick]");
          if (c) c.checked = false;
        });
        syncBulk();
        return;
      }
      if (!sel.length) return;
      if (e.target.closest("[data-bulk-del]")) {
        if (!window.confirm("Take " + plural(sel.length, "photo") + " off the gallery page?")) return;
        sel.forEach(function (t) {
          t.remove();
        });
        markDirty();
        sync();
        return;
      }
      if (e.target.closest("[data-bulk-move]") && bulkCat) {
        var target = null;
        cats().forEach(function (c) {
          if (c.getAttribute("data-cat-key") === bulkCat.value) target = c;
        });
        var grid = target && target.querySelector("[data-cat-grid]");
        if (!grid) return;
        sel.forEach(function (t) {
          var c = t.querySelector("[data-img-pick]");
          if (c) c.checked = false;
          grid.appendChild(t);
        });
        markDirty();
        sync();
        target.scrollIntoView({ block: "nearest", behavior: "smooth" });
      }
    });
  }

  window.addEventListener("beforeunload", function (e) {
    if (!dirty || leaving) return;
    e.preventDefault();
    e.returnValue = "";
  });
  form.addEventListener("submit", function () {
    leaving = true;
    sync(); // what gets posted always matches what is on screen
  });

  /* Bulk upload: one file per request, so max_file_uploads and post_max_size
     never come into it. Each file is saved server-side as it arrives. */
  var upInput = form.querySelector("[data-up-input]");
  var upStart = form.querySelector("[data-up-start]");
  var upStop = form.querySelector("[data-up-stop]");
  var upTarget = form.querySelector("[data-up-target]");
  var upPanel = form.querySelector("[data-up-panel]");
  var upFill = form.querySelector("[data-up-fill]");
  var upStat = form.querySelector("[data-up-stat]");
  var upLog = form.querySelector("[data-up-log]");
  var upReload = form.querySelector("[data-up-reload]");
  var maxBytes = Number(form.getAttribute("data-max-bytes")) || 16777216;
  var maxMb = Math.floor(maxBytes / 1048576);
  var stopped = false;
  var busy = false;

  function logLine(name, text, kind) {
    if (!upLog) return;
    var li = document.createElement("li");
    li.className = "is-" + kind;
    li.textContent = name + " — " + text;
    upLog.appendChild(li);
    upLog.scrollTop = upLog.scrollHeight;
  }

  function sendOne(file, section, title, token, action) {
    var fd = new FormData();
    fd.append("cfc_csrf", token);
    fd.append("cms_action", "upload_gallery_image");
    fd.append("section", section);
    fd.append("section_title", title);
    fd.append("file", file, file.name);
    return fetch(action, {
      method: "POST",
      body: fd,
      credentials: "same-origin",
      headers: { Accept: "application/json" }
    }).then(function (res) {
      return res.json().catch(function () {
        return null;
      }).then(function (data) {
        if (res.ok && data && data.ok) {
          return { error: "", warning: (data && data.warning) || "" };
        }
        return {
          error: data && data.error ? String(data.error) : "the server answered " + res.status,
          // A rejected session, or a gallery that changed underneath us, will
          // reject every remaining file too. Stop instead of replaying the same
          // error 200 times. The server flags these; status covers auth.
          fatal: !!(data && data.fatal) || res.status === 401 || res.status === 403 || res.status === 419
        };
      });
    }, function () {
      return { error: "the connection dropped", fatal: false };
    });
  }

  function runUpload() {
    if (busy) return;
    var files = upInput && upInput.files ? Array.prototype.slice.call(upInput.files) : [];
    if (!files.length) return;
    if (dirty && !window.confirm(
      "Uploading reloads this page, so your unsaved order and category changes would be lost. Upload anyway?"
    )) return;

    busy = true;
    stopped = false;
    upStart.disabled = true;
    if (submitBtn) submitBtn.disabled = true;
    if (upStop) {
      upStop.hidden = false;
      upStop.disabled = false;
    }
    if (upPanel) upPanel.hidden = false;
    if (upReload) upReload.hidden = true;
    if (upLog) upLog.innerHTML = "";

    var tokenEl = form.querySelector('input[name="cfc_csrf"]');
    var token = tokenEl ? tokenEl.value : "";
    var action = form.getAttribute("action") || window.location.href;
    var section = upTarget ? upTarget.value : "0";
    var sectionTitle = "";
    if (upTarget && upTarget.selectedOptions && upTarget.selectedOptions[0]) {
      sectionTitle = upTarget.selectedOptions[0].getAttribute("data-title") || "";
    }
    var added = 0;
    var skipped = 0;
    var i = 0;
    var streak = 0;
    var halted = "";

    function bar() {
      if (upFill) upFill.style.width = Math.round((i / files.length) * 100) + "%";
    }

    function next() {
      if (stopped || i >= files.length) return finish();
      var file = files[i];
      if (upStat) {
        upStat.textContent = "Uploading " + (i + 1) + " of " + files.length + " — " + file.name;
      }
      if (file.size > maxBytes) {
        skipped += 1;
        logLine(file.name, "skipped, bigger than " + maxMb + " MB", "err");
        i += 1;
        bar();
        return next();
      }
      return sendOne(file, section, sectionTitle, token, action).then(function (res) {
        if (res.error) {
          skipped += 1;
          streak += 1;
          logLine(file.name, res.error, "err");
          if (res.fatal) halted = res.error;
          else if (streak >= 3) halted = "three uploads in a row failed";
        } else {
          added += 1;
          streak = 0;
          logLine(file.name, res.warning || "added", res.warning ? "warn" : "ok");
        }
        i += 1;
        bar();
        if (halted) {
          stopped = true;
          return finish();
        }
        return next();
      });
    }

    function finish() {
      busy = false;
      if (upStop) upStop.hidden = true;
      if (submitBtn) submitBtn.disabled = false;
      if (upFill) upFill.style.width = "100%";
      var summary = plural(added, "photo") + " added";
      if (skipped) summary += ", " + skipped + " skipped";
      if (halted) summary += " — stopped: " + halted.replace(/\.\s*$/, "");
      else if (stopped) summary += " (stopped early)";
      if (upInput) upInput.value = "";
      upStart.textContent = "Upload";
      upStart.disabled = true;
      if (added && !skipped && !stopped && !halted) {
        if (upStat) upStat.textContent = summary + ". Reloading…";
        leaving = true;
        window.setTimeout(function () {
          window.location.reload();
        }, 900);
        return;
      }
      if (upStat) upStat.textContent = summary + ".";
      if (added && upReload) upReload.hidden = false;
    }

    bar();
    next();
  }

  if (upInput && upStart) {
    upInput.addEventListener("change", function () {
      var n = upInput.files ? upInput.files.length : 0;
      upStart.disabled = n === 0 || busy;
      upStart.textContent = n ? "Upload " + plural(n, "image") : "Upload";
    });
    upStart.addEventListener("click", runUpload);
    if (upStop) {
      upStop.addEventListener("click", function () {
        stopped = true;
        upStop.disabled = true;
        if (upStat) upStat.textContent = "Stopping after the current image…";
      });
    }
    if (upReload) {
      upReload.addEventListener("click", function () {
        leaving = true;
        window.location.reload();
      });
    }
  }

  sync();
  dirty = false;
  if (dirtyTag) dirtyTag.hidden = true;
})();
