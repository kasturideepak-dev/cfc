(function () {
  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    var nav = document.querySelector("[data-nav]");
    var toggle = document.querySelector("[data-nav-toggle]");
    var closeBtn = document.querySelector("[data-nav-close]");
    var backdrop = document.querySelector("[data-nav-backdrop]");

    function openNav() {
      if (!nav) return;
      nav.classList.add("is-open");
      if (toggle) toggle.setAttribute("aria-expanded", "true");
      if (backdrop) backdrop.hidden = false;
      document.body.style.overflow = "hidden";
    }
    function closeNav() {
      if (!nav) return;
      nav.classList.remove("is-open");
      if (toggle) toggle.setAttribute("aria-expanded", "false");
      if (backdrop) backdrop.hidden = true;
      document.body.style.overflow = "";
    }
    if (toggle) toggle.addEventListener("click", openNav);
    if (closeBtn) closeBtn.addEventListener("click", closeNav);
    if (backdrop) backdrop.addEventListener("click", closeNav);

    var motionOk = !window.matchMedia || !window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var anims = document.querySelectorAll("[data-cfc-anim]");
    if (anims.length) {
      if (!motionOk || !("IntersectionObserver" in window)) {
        anims.forEach(function (el) { el.classList.add("is-in"); });
      } else {
        var io = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (!entry.isIntersecting) return;
              entry.target.classList.add("is-in");
              io.unobserve(entry.target);
            });
          },
          { threshold: 0.18, rootMargin: "0px 0px -40px 0px" }
        );
        anims.forEach(function (el) { io.observe(el); });
      }
    }

    document.querySelectorAll("[data-timeline]").forEach(function (wrap) {
      var scroller = wrap.querySelector("[data-timeline-scroller]");
      var prev = wrap.querySelector("[data-timeline-prev]");
      var next = wrap.querySelector("[data-timeline-next]");
      var fill = wrap.querySelector("[data-timeline-fill]");
      if (!scroller) return;
      function step() {
        var card = scroller.querySelector(".about-milestone");
        return card ? card.getBoundingClientRect().width + 20 : scroller.clientWidth * 0.8;
      }
      function updateFill() {
        if (!fill) return;
        var max = scroller.scrollWidth - scroller.clientWidth;
        var p = max <= 0 ? 0.36 : 0.36 + (scroller.scrollLeft / max) * 0.36;
        fill.style.width = Math.round(p * 100) + "%";
      }
      if (prev) prev.addEventListener("click", function () {
        scroller.scrollBy({ left: -step(), behavior: "smooth" });
      });
      if (next) next.addEventListener("click", function () {
        scroller.scrollBy({ left: step(), behavior: "smooth" });
      });
      scroller.addEventListener("scroll", updateFill, { passive: true });
      updateFill();
    });

    (function () {
      var nodes = document.querySelectorAll("img[data-cfc-lazy-img]");
      if (!nodes.length) return;
      function load(img) {
        var src = img.getAttribute("data-src");
        if (!src) return;
        img.src = src;
        img.removeAttribute("data-src");
        img.removeAttribute("data-cfc-lazy-img");
        // Gallery tiles that letterbox fill the gap with the same photo,
        // blurred. Setting it here rather than in the markup means the
        // backdrop costs no extra request and does not pull the image in
        // before the tile is anywhere near the viewport.
        var tile = img.closest ? img.closest(".gallery-item--fill") : null;
        if (tile && !tile.style.getPropertyValue("--bg")) {
          tile.style.setProperty("--bg", 'url("' + src + '")');
        }
      }
      if (!("IntersectionObserver" in window)) {
        nodes.forEach(load);
        return;
      }
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            load(entry.target);
            io.unobserve(entry.target);
          });
        },
        { rootMargin: "480px 0px", threshold: 0.01 }
      );
      nodes.forEach(function (img) { io.observe(img); });
    })();

    document.querySelectorAll("video[autoplay]").forEach(function (v) {
      v.muted = true;
      v.playsInline = true;
      var p = v.play();
      if (p && p.catch) p.catch(function () {});
    });

    (function () {
      var lazy = document.querySelectorAll("video[data-cfc-lazy-video]");
      if (!lazy.length) return;
      function activate(v) {
        if (v.dataset.src) {
          v.src = v.dataset.src;
          delete v.dataset.src;
          v.load();
        }
        v.muted = true;
        v.playsInline = true;
        var p = v.play();
        if (p && p.catch) p.catch(function () {});
      }
      if (!("IntersectionObserver" in window)) {
        lazy.forEach(activate);
        return;
      }
      var io = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            var v = entry.target;
            if (entry.isIntersecting) activate(v);
            else if (v.pause) v.pause();
          });
        },
        { rootMargin: "240px 0px", threshold: 0.01 }
      );
      lazy.forEach(function (v) { io.observe(v); });
    })();

    document.querySelectorAll("[data-cfc-yt]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = btn.getAttribute("data-cfc-yt");
        if (!id || !/^[A-Za-z0-9_-]{6,}$/.test(id)) return;
        var iframe = document.createElement("iframe");
        iframe.src = "https://www.youtube.com/embed/" + id + "?autoplay=1&rel=0&modestbranding=1";
        iframe.title = btn.getAttribute("aria-label") || "Video";
        iframe.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
        iframe.allowFullscreen = true;
        iframe.setAttribute("allowfullscreen", "");
        btn.replaceWith(iframe);
      });
    });

    var top = document.querySelector("[data-scroll-top]");
    if (top) {
      window.addEventListener("scroll", function () {
        top.classList.toggle("is-on", window.scrollY > 240);
      });
      top.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }

    (function () {
      var lb = document.querySelector("[data-gallery-lb]");
      if (!lb) return;
      var img = lb.querySelector("[data-gallery-img]");
      var items = [];
      var idx = 0;

      function show(i) {
        if (!items.length) return;
        idx = (i + items.length) % items.length;
        var href = items[idx].getAttribute("href");
        if (img && href) {
          img.src = href;
          var thumb = items[idx].querySelector("img");
          img.alt = thumb ? thumb.alt : "";
        }
        lb.hidden = false;
        document.body.style.overflow = "hidden";
      }
      function hide() {
        lb.hidden = true;
        document.body.style.overflow = "";
      }

      document.querySelectorAll("[data-gallery]").forEach(function (grid) {
        var group = Array.prototype.slice.call(grid.querySelectorAll("[data-gallery-item]"));
        group.forEach(function (a, i) {
          a.addEventListener("click", function (e) {
            e.preventDefault();
            items = group;
            show(i);
          });
        });
      });

      var closeBtn = lb.querySelector("[data-gallery-close]");
      var prevBtn = lb.querySelector("[data-gallery-prev]");
      var nextBtn = lb.querySelector("[data-gallery-next]");
      if (closeBtn) closeBtn.addEventListener("click", hide);
      if (prevBtn) prevBtn.addEventListener("click", function () { show(idx - 1); });
      if (nextBtn) nextBtn.addEventListener("click", function () { show(idx + 1); });
      lb.addEventListener("click", function (e) {
        if (e.target === lb) hide();
      });
      document.addEventListener("keydown", function (e) {
        if (lb.hidden) return;
        if (e.key === "Escape") hide();
        if (e.key === "ArrowLeft") show(idx - 1);
        if (e.key === "ArrowRight") show(idx + 1);
      });
    })();

    document.querySelectorAll("[data-blog-list]").forEach(function (root) {
      var cards = Array.prototype.slice.call(root.querySelectorAll("[data-blog-card]"));
      var more = root.querySelector("[data-blog-more]");
      var end = root.querySelector("[data-blog-end]");
      var per = parseInt(root.getAttribute("data-blog-per") || "9", 10) || 9;
      var shown = parseInt(root.getAttribute("data-blog-initial") || String(per), 10) || per;
      var filterBtns = root.querySelectorAll("[data-blog-filter]");
      var onBtn = root.querySelector("[data-blog-filter].is-on");
      var filter = onBtn ? (onBtn.getAttribute("data-blog-filter") || "") : "";

      function matching() {
        return cards.filter(function (card) {
          return !filter || card.getAttribute("data-cat") === filter;
        });
      }

      function hydrate(card) {
        if (!card || card.hidden) return;
        card.querySelectorAll("img[data-cfc-lazy-img]").forEach(function (img) {
          var src = img.getAttribute("data-src");
          if (!src) return;
          img.src = src;
          img.removeAttribute("data-src");
          img.removeAttribute("data-cfc-lazy-img");
        });
      }

      function paint() {
        var match = matching();
        cards.forEach(function (card) {
          card.hidden = true;
        });
        match.forEach(function (card, i) {
          card.hidden = i >= shown;
          if (i < shown) hydrate(card);
        });
        var leftover = match.length > shown;
        if (more) more.hidden = !leftover;
        if (more && more.parentElement && more.parentElement.classList.contains("blog-more")) {
          more.parentElement.hidden = !leftover;
        }
        if (end) end.hidden = leftover || match.length === 0;
      }

      filterBtns.forEach(function (btn) {
        btn.addEventListener("click", function (e) {
          if (btn.getAttribute("href") && btn.getAttribute("data-blog-filter") === null) return;
          e.preventDefault();
          filter = btn.getAttribute("data-blog-filter") || "";
          shown = per;
          filterBtns.forEach(function (b) {
            b.classList.toggle("is-on", b === btn);
          });
          paint();
        });
      });

      if (more) {
        more.addEventListener("click", function (e) {
          e.preventDefault();
          shown += per;
          paint();
        });
      }

      paint();
    });

    document.querySelectorAll("[data-quotes]").forEach(function (root) {
      var track = root.querySelector("[data-quotes-track]");
      var dotsWrap = root.querySelector("[data-quotes-dots]");
      if (!track) return;
      var slides = Array.prototype.slice.call(track.children);
      var n = slides.length;
      if (!n) return;

      var clones = Math.min(2, n);
      for (var c = 0; c < clones; c++) {
        var clone = slides[c].cloneNode(true);
        clone.setAttribute("aria-hidden", "true");
        track.appendChild(clone);
      }

      var index = 0;
      var animMs = 800;

      function perView() {
        return window.matchMedia("(max-width: 767px)").matches ? 1 : 2;
      }

      function stepWidth() {
        var first = track.children[0];
        if (!first) return 0;
        var styles = window.getComputedStyle(track);
        var gap = parseFloat(styles.columnGap || styles.gap || "0") || 0;
        return first.getBoundingClientRect().width + gap;
      }

      function paint(i, animate) {
        index = i;
        track.style.transition = animate === false ? "none" : "transform 0.8s ease";
        track.style.transform = "translate3d(" + (-index * stepWidth()) + "px,0,0)";
        if (dotsWrap) {
          var dots = dotsWrap.querySelectorAll("button");
          var logical = ((index % n) + n) % n;
          dots.forEach(function (btn, di) {
            var on = di === logical;
            btn.classList.toggle("is-on", on);
            if (on) btn.setAttribute("aria-current", "true");
            else btn.removeAttribute("aria-current");
          });
        }
      }

      function go(i) {
        if (i < 0) {
          paint(n - 1, true);
          return;
        }
        if (i === n) {
          paint(n, true);
          window.setTimeout(function () { paint(0, false); }, animMs);
          return;
        }
        paint(((i % n) + n) % n, true);
      }

      if (dotsWrap) {
        dotsWrap.innerHTML = "";
        slides.forEach(function (_, i) {
          var btn = document.createElement("button");
          btn.type = "button";
          btn.setAttribute("aria-label", "Show testimonial " + (i + 1));
          btn.addEventListener("click", function () { go(i); });
          dotsWrap.appendChild(btn);
        });
      }

      var startX = 0;
      var tracking = false;
      track.addEventListener("touchstart", function (e) {
        if (!e.touches || !e.touches.length) return;
        tracking = true;
        startX = e.touches[0].clientX;
      }, { passive: true });
      track.addEventListener("touchend", function (e) {
        if (!tracking || !e.changedTouches || !e.changedTouches.length) return;
        tracking = false;
        var dx = e.changedTouches[0].clientX - startX;
        if (dx > 40) go(index - 1);
        else if (dx < -40) go(index + 1);
      });

      window.addEventListener("resize", function () { paint(index % n, false); });
      paint(0, false);
    });

    document.querySelectorAll("form[data-cfc-form]").forEach(function (form) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        if (form.dataset.cfcBusy === "1") return;
        var missing = false;
        form.querySelectorAll("[required]").forEach(function (el) {
          if (!String(el.value || "").trim()) {
            el.setAttribute("aria-invalid", "true");
            missing = true;
          } else el.setAttribute("aria-invalid", "false");
        });
        var email = form.querySelector('input[type="email"]');
        if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
          email.setAttribute("aria-invalid", "true");
          missing = true;
        }
        if (missing) return;
        var captcha = form.querySelector(".cf-turnstile");
        var token = form.querySelector("[name='cf-turnstile-response']");
        if (captcha && (!token || !String(token.value || "").trim())) {
          alert(window.turnstile ? "Please complete the captcha." : "Captcha is still loading. Please wait a moment.");
          return;
        }
        var buttons = form.querySelectorAll("[type=submit], button:not([type])");
        form.dataset.cfcBusy = "1";
        buttons.forEach(function (b) { b.disabled = true; });
        function unlock() {
          form.dataset.cfcBusy = "0";
          buttons.forEach(function (b) { b.disabled = false; });
          if (window.turnstile && captcha) {
            try { window.turnstile.reset(captcha); } catch (err) {}
          }
        }
        function safeThankYou(url) {
          if (typeof url === "string" && url.charAt(0) === "/" && url.indexOf("//") !== 0 && url.indexOf("\\") === -1) {
            return url;
          }
          return "thank-you/";
        }
        fetch(form.action, {
          method: "POST",
          body: new FormData(form),
          headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        })
          .then(function (r) { return r.text(); })
          .then(function (text) {
            var json = null;
            try { json = JSON.parse(text); } catch (err) {}
            if (json && json.success) {
              window.location.href = safeThankYou(json.redirectUrl);
              return;
            }
            unlock();
            alert((json && json.message) || "Please check the form and try again.");
          })
          .catch(function () {
            unlock();
            alert("Network error. Please try again.");
          });
      });
    });
  });
})();

/* Media Hub "Load More". Every tile is already in the page, so this reveals
   them in batches rather than fetching anything. The button starts hidden and
   is only shown once there is something to reveal: with JavaScript off the
   grid simply shows everything, which is a better outcome than a button that
   silently sends people to Instagram. */
(function () {
  var grid = document.querySelector("[data-media-grid]");
  var more = document.querySelector("[data-media-more]");
  if (!grid || !more) return;

  var step = parseInt(grid.getAttribute("data-media-step"), 10);
  if (!step || step < 1) step = 12;

  var items = Array.prototype.slice.call(grid.children);
  if (items.length <= step) return;

  var shown = step;
  function apply() {
    items.forEach(function (el, i) {
      el.hidden = i >= shown;
    });
    var left = items.length - shown;
    more.hidden = left <= 0;
    more.setAttribute("aria-label", left > 0 ? "Show " + Math.min(step, left) + " more posts" : "");
  }

  more.addEventListener("click", function () {
    var first = items[shown];
    shown += step;
    apply();
    if (first) {
      first.setAttribute("tabindex", "-1");
      first.focus({ preventScroll: true });
    }
  });

  apply();
})();
