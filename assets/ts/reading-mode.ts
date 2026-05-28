const READING_MODE_KEY = "reading-mode";
const DEFAULT_READING_MODE = "page";
export function getReadingMode() {
  const stored = localStorage.getItem(READING_MODE_KEY);
  return stored === "inline" ? "inline" : "page";
}
export function setReadingMode(mode) {
  const value = mode === "inline" ? "inline" : "page";
  localStorage.setItem(READING_MODE_KEY, value);
  applyReadingMode(value);
  updateReadingModeControls(value);
}
function applyReadingMode(mode) {
  const inlineOnly = document.querySelectorAll(".reading-mode-inline-only");
  for (const el of inlineOnly) {
    if (mode === "inline") {
      el.classList.remove("hidden");
    } else {
      el.classList.add("hidden");
    }
  }
}
const SETTINGS_OPTION_SELECTED_CLASS = "settings-option-selected";

function updateReadingModeControls(mode) {
  document.querySelectorAll("[data-reading-mode-option]").forEach((el) => {
    const option = el.dataset.readingModeOption;
    const isActive = option === mode;
    el.classList.toggle(SETTINGS_OPTION_SELECTED_CLASS, isActive);
    el.setAttribute("aria-pressed", isActive ? "true" : "false");
  });
}
function handleArticleNavigationClick(event) {
  if (getReadingMode() !== "inline") {
    return;
  }
  const target = event.target;
  const link = target.closest(".article-link, .article-thumb-link");
  if (!link) {
    return;
  }
  const card = link.closest("[data-article-id]");
  if (!card) {
    return;
  }
  const toggle = card.querySelector(".article-read-toggle");
  if (toggle instanceof HTMLButtonElement) {
    event.preventDefault();
    toggle.click();
    return;
  }
}
document.addEventListener("click", handleArticleNavigationClick);
applyReadingMode(getReadingMode());
updateReadingModeControls(getReadingMode());
document.body.addEventListener("htmx:afterSwap", (event) => {
  const target = event.detail.target;
  if (target instanceof HTMLElement && (target.id === "article-feed" || target.closest("#article-feed"))) {
    applyReadingMode(getReadingMode());
  }
});
document.querySelectorAll("[data-reading-mode-option]").forEach((el) => {
  el.addEventListener("click", () => {
    const mode = el.dataset.readingModeOption;
    if (mode === "page" || mode === "inline") {
      setReadingMode(mode);
      document.activeElement?.blur();
    }
  });
});
