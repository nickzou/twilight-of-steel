// Copies front-end dependencies from node_modules into the theme, which is
// committed and shipped as is: the Docker image has no Node. Runs as pnpm's
// postinstall; CI fails if the committed copies don't match package.json.
import { copyFileSync, mkdirSync } from "node:fs";

const theme = "twilight-of-steel/assets";

// Lucide icons the theme uses, by name (https://lucide.dev/icons).
const icons = ["chevron-down"];

mkdirSync(`${theme}/js/vendor`, { recursive: true });
copyFileSync(
  "node_modules/alpinejs/dist/cdn.min.js",
  `${theme}/js/vendor/alpine.min.js`,
);

mkdirSync(`${theme}/icons`, { recursive: true });
copyFileSync("node_modules/lucide-static/LICENSE", `${theme}/icons/LICENSE`);
for (const icon of icons) {
  copyFileSync(
    `node_modules/lucide-static/icons/${icon}.svg`,
    `${theme}/icons/${icon}.svg`,
  );
}
