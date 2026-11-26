// Copies browser dependencies from node_modules into the theme and the core
// plugin, where they're committed and shipped as is (the Docker image's Node
// stage only builds blocks, and skips this). Runs as pnpm's postinstall; CI
// fails if the committed copies don't match package.json.
import { copyFileSync, mkdirSync } from "node:fs";

const theme = "twilight-of-steel/assets";
const plugin = "plugins/twilight-of-steel-core/assets";

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

// comical-js, for drawing speech bubbles in the editor. Loaded as its own
// script, not bundled into the Panel block: see inc/comic-blocks.php.
mkdirSync(`${plugin}/js/vendor`, { recursive: true });
copyFileSync(
  "node_modules/comicaljs/dist/index.js",
  `${plugin}/js/vendor/comical.js`,
);
copyFileSync(
  "node_modules/comicaljs/LICENSE.md",
  `${plugin}/js/vendor/comical-LICENSE.md`,
);
