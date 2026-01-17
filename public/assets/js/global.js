// Initialize AOS
AOS.init({
  once: true,
  duration: 800,
  offset: 100,
});

// Dark Mode Toggle
const toggleBtn = document.getElementById("themeToggle");
const html = document.documentElement;

// Set RGB values for CSS variables
function updateCSSVariables() {
  const theme = html.getAttribute("data-theme");
  if (theme === "dark") {
    document.documentElement.style.setProperty("--bg-rgb", "15, 23, 42");
  } else {
    document.documentElement.style.setProperty("--bg-rgb", "249, 250, 251");
  }
  document.documentElement.style.setProperty("--primary-rgb", "16, 185, 129");
}

toggleBtn.addEventListener("click", () => {
  const theme = html.getAttribute("data-theme") === "dark" ? "light" : "dark";
  html.setAttribute("data-theme", theme);
  localStorage.setItem("theme", theme);
  toggleBtn.innerHTML =
    theme === "dark"
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';
  updateCSSVariables();
});

// Load saved theme
const savedTheme = localStorage.getItem("theme");
if (savedTheme) {
  html.setAttribute("data-theme", savedTheme);
  toggleBtn.innerHTML =
    savedTheme === "dark"
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';
}

// Initial CSS variable setup
updateCSSVariables();

// Navbar scroll effect
const nav = document.getElementById("mainNav");

window.addEventListener("scroll", () => {
  if (window.scrollY > 50) {
    nav.classList.add("scrolled");
  } else {
    nav.classList.remove("scrolled");
  }
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const targetId = this.getAttribute("href");
    if (targetId === "#") return;

    const targetElement = document.querySelector(targetId);
    if (targetElement) {
      window.scrollTo({
        top: targetElement.offsetTop - 80,
        behavior: "smooth",
      });

      // Update URL without page reload
      history.pushState(null, null, targetId);
    }
  });
});

// Update version badge text dynamically (example)
// In practice, you might load this from an API or config file
function updateVersionInfo() {
  const version = "3.0";
  const releaseDate = "October 2023";

  // Update all version badges
  document.querySelectorAll(".version-badge").forEach((badge) => {
    const icon =
      badge.querySelector("i")?.outerHTML || '<i class="fas fa-tag me-2"></i>';
    badge.innerHTML = `${icon}Version ${version}`;
  });

  // Update release date in hero
  const dateElement = document.querySelector(".hero p .fa-clock").parentElement;
  if (dateElement) {
    dateElement.innerHTML = `<i class="fas fa-clock me-1"></i> Latest release: ${releaseDate}`;
  }
}

// Call on load
updateVersionInfo();
