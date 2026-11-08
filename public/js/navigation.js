document.addEventListener("DOMContentLoaded", () => {
  const hamburger = document.getElementById("hamburger");
  const navLinks = document.getElementById("navLinks");
  const navbar = document.getElementById("navbar");

  // ── Active nav link tracking & Scroll Spy ─────────────────────────────
  const allNavLinks = navLinks?.querySelectorAll("a") ?? [];

  function setActiveLink(el) {
    allNavLinks.forEach((l) => l.classList.remove("active-nav"));
    if (el) el.classList.add("active-nav");
  }

  // Set active on click
  allNavLinks.forEach((link) => {
    link.addEventListener("click", () => setActiveLink(link));
  });

  // Set active based on current page URL on load
  allNavLinks.forEach((link) => {
    const href = link.getAttribute("href");
    if (
      href &&
      !href.startsWith("#") &&
      window.location.pathname.endsWith(href.split("/").pop())
    ) {
      setActiveLink(link);
    }
  });

  // Scroll spy (updates active link while scrolling through Home, Services, About, Location, Contact Us)
  if (window.location.pathname.endsWith("index.php") || window.location.pathname.endsWith("/sdn/") || window.location.pathname === "/") {
    const trackedIds = ["hero-new", "services-new", "about-new", "location-new", "footer-new"];
    const trackedElements = trackedIds
      .map((id) => document.getElementById(id))
      .filter(Boolean);

    function updateActiveNavOnScroll() {
      if (window.scrollY < 100) {
        const homeLink = Array.from(allNavLinks).find(
          (link) => (link.getAttribute("href") || "").includes("index.php")
        );
        if (homeLink) setActiveLink(homeLink);
        return;
      }

      const triggerY = (navbar?.offsetHeight ?? 76) + window.innerHeight * 0.28;
      let activeId = "hero-new";

      for (const el of trackedElements) {
        const rect = el.getBoundingClientRect();
        if (rect.top <= triggerY) {
          activeId = el.id;
        }
      }

      // If scrolled near the very bottom of the page, highlight Contact Us (#footer-new)
      if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 40) {
        activeId = "footer-new";
      }

      if (activeId === "hero-new") {
        const homeLink = Array.from(allNavLinks).find(
          (link) => (link.getAttribute("href") || "").includes("index.php")
        );
        if (homeLink) setActiveLink(homeLink);
      } else {
        const correspondingLink = Array.from(allNavLinks).find(
          (link) => link.getAttribute("href") === `#${activeId}`
        );
        if (correspondingLink) setActiveLink(correspondingLink);
      }
    }

    window.addEventListener("scroll", updateActiveNavOnScroll, { passive: true });
    updateActiveNavOnScroll();
  }

  // ── Mobile menu toggle ────────────────────────────────────────────────
  if (hamburger && navLinks) {
    hamburger.addEventListener("click", () => {
      const isOpen = navLinks.classList.toggle("active");
      hamburger.classList.toggle("open", isOpen);
      hamburger.setAttribute("aria-expanded", String(isOpen));
    });
    allNavLinks.forEach((link) => {
      link.addEventListener("click", () => {
        navLinks.classList.remove("active");
        hamburger.classList.remove("open");
        hamburger.setAttribute("aria-expanded", "false");
      });
    });
  }

  // ── Navbar scroll shadow ──────────────────────────────────────────────
  if (navbar) {
    const onScroll = () =>
      navbar.classList.toggle("scrolled", window.scrollY > 8);
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  // ── Smooth scroll for anchor links ────────────────────────────────────
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const targetId = this.getAttribute("href").substring(1);
      if (!targetId) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: "smooth" });
        return;
      }
      const target = document.getElementById(targetId);
      if (target) {
        e.preventDefault();
        const offset = (navbar?.offsetHeight ?? 64) + 8;
        window.scrollTo({
          top: target.getBoundingClientRect().top + window.scrollY - offset,
          behavior: "smooth",
        });
      }
    });
  });

  // ── Logout: call PHP API then redirect ────────────────────────────────
  document.querySelectorAll(".btn-logout").forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();
      btn.disabled = true;
      btn.style.opacity = "0.5";
      try {
        const response = await fetch(`${API_BASE}/auth.php?action=logout`, {
          method: "GET",
          credentials: "same-origin",
        });
        // Wait a moment for server to fully destroy session
        await new Promise((resolve) => setTimeout(resolve, 200));
      } catch (err) {
        console.error("Logout error:", err);
      } finally {
        // Clear any remaining session data from client
        window.location.href = "/sdn/index.php";
      }
    });
  });

  // ── Schedules page: clock + stat cards ───────────────────────────────
  if (document.getElementById("clockTime")) {
    updateClock();
    setInterval(updateClock, 1000);
    setUpdatedTime();
    calcScheduleStats();
  }
});

// ── Schedule tab switching (called inline from HTML) ──────────────────────
window.openScheduleTab = function (evt, departmentName) {
  document
    .querySelectorAll(".tab-content")
    .forEach((el) => (el.style.display = "none"));
  document
    .querySelectorAll(".tab-btn")
    .forEach((btn) => btn.classList.remove("active"));
  const panel = document.getElementById(departmentName);
  if (panel) panel.style.display = "block";
  if (evt?.currentTarget) evt.currentTarget.classList.add("active");
};
