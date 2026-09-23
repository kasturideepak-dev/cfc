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
