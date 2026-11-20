// Codex contents menu: tracks the section being read, so its link can be
// marked current. A section counts as being read once its heading passes 30%
// of the way down the screen; at the bottom of the page, the last one does.
document.addEventListener("alpine:init", () => {
  Alpine.data("codexContents", () => ({
    active: "",

    init() {
      const headings = [
        ...document.querySelectorAll(".wp-block-post-content h2[id]"),
      ];
      const update = () => {
        const line = window.innerHeight * 0.3;
        const atBottom =
          window.innerHeight + window.scrollY >=
          document.documentElement.scrollHeight - 2;
        let current = "";
        for (const heading of headings) {
          if (atBottom || heading.getBoundingClientRect().top <= line) {
            current = heading.id;
          }
        }
        this.active = current;
      };
      update();
      window.addEventListener("scroll", update, { passive: true });
      window.addEventListener("resize", update, { passive: true });
    },
  }));
});
