/* App shell behaviour: sidebar (desktop collapse / mobile drawer), colour theme, small form helpers. */
(function () {
  "use strict";

  var MINI_KEY = "sms.sidebarMini";
  var THEME_KEY = "sms.theme";
  var desktop = window.matchMedia("(min-width: 992px)");

  function store(key, value) {
    try {
      if (value === undefined) return window.localStorage.getItem(key);
      window.localStorage.setItem(key, value);
    } catch (e) { return null; }
  }

  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    var body = document.body;
    var root = document.documentElement;

    /* ---- Sidebar ---- */
    var toggles = document.querySelectorAll("[data-sidebar-toggle]");
    var closers = document.querySelectorAll("[data-sidebar-close]");

    function syncExpanded() {
      var open = desktop.matches ? !body.classList.contains("sidebar-mini") : body.classList.contains("sidebar-open");
      toggles.forEach(function (t) { t.setAttribute("aria-expanded", String(open)); });
    }

    function toggleSidebar() {
      if (desktop.matches) {
        body.classList.toggle("sidebar-mini");
        store(MINI_KEY, String(body.classList.contains("sidebar-mini")));
      } else {
        body.classList.toggle("sidebar-open");
      }
      syncExpanded();
    }

    function closeDrawer() {
      body.classList.remove("sidebar-open");
      syncExpanded();
    }

    if (desktop.matches && store(MINI_KEY) === "true") body.classList.add("sidebar-mini");

    toggles.forEach(function (t) { t.addEventListener("click", toggleSidebar); });
    closers.forEach(function (c) { c.addEventListener("click", closeDrawer); });

    document.querySelectorAll(".sidebar-nav a[href]:not([data-bs-toggle])").forEach(function (link) {
      link.addEventListener("click", function () { if (!desktop.matches) closeDrawer(); });
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && body.classList.contains("sidebar-open")) closeDrawer();
    });

    // In mini mode, clicking a group icon expands the sidebar so the sub-menu is usable.
    document.querySelectorAll(".sidebar-nav [data-bs-toggle='collapse']").forEach(function (link) {
      link.addEventListener("click", function () {
        if (desktop.matches && body.classList.contains("sidebar-mini")) {
          body.classList.remove("sidebar-mini");
          store(MINI_KEY, "false");
          syncExpanded();
        }
      });
    });

    var onChange = function () {
      if (desktop.matches) {
        body.classList.remove("sidebar-open");
        body.classList.toggle("sidebar-mini", store(MINI_KEY) === "true");
      } else {
        body.classList.remove("sidebar-mini");
      }
      syncExpanded();
    };
    if (desktop.addEventListener) desktop.addEventListener("change", onChange);
    else if (desktop.addListener) desktop.addListener(onChange);
    syncExpanded();

    /* ---- Theme ---- */
    function setTheme(theme) {
      root.setAttribute("data-theme", theme);
      root.setAttribute("data-bs-theme", theme);
      store(THEME_KEY, theme);
      document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
        var next = theme === "dark" ? "light" : "dark";
        btn.setAttribute("aria-label", "Switch to " + next + " mode");
        btn.setAttribute("title", "Switch to " + next + " mode");
        var icon = btn.querySelector("i");
        if (icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
      });
    }
    setTheme(root.getAttribute("data-theme") === "dark" ? "dark" : "light");
    document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        setTheme(root.getAttribute("data-theme") === "dark" ? "light" : "dark");
      });
    });

    /* ---- Password visibility ---- */
    document.querySelectorAll("[data-password-toggle]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var input = document.getElementById(btn.getAttribute("data-password-toggle"));
        if (!input) return;
        var show = input.type === "password";
        input.type = show ? "text" : "password";
        btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
        var icon = btn.querySelector("i");
        if (icon) icon.className = show ? "bi bi-eye-slash" : "bi bi-eye";
      });
    });

    /* ---- Bootstrap client-side validation (forms that opt in) ---- */
    document.querySelectorAll("form.needs-validation").forEach(function (form) {
      form.addEventListener("submit", function (e) {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add("was-validated");
      });
    });

    /* ---- Live table filter: <input data-table-search="tableId"> ---- */
    document.querySelectorAll("[data-table-search]").forEach(function (input) {
      var table = document.getElementById(input.getAttribute("data-table-search"));
      if (!table) return;
      input.addEventListener("input", function () {
        var q = input.value.trim().toLowerCase();
        table.querySelectorAll("tbody tr").forEach(function (row) {
          row.hidden = q !== "" && row.textContent.toLowerCase().indexOf(q) === -1;
        });
      });
    });
  });
})();
