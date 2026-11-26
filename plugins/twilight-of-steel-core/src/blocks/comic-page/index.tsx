// Comic page: a grid of panels. The grid is WordPress's own grid layout, so
// panels get span controls and resize handles, and Responsive editing (in the
// editor's preview menu) stores a separate layout per screen size. By default
// phones get one column, so the panels stack in reading order.
//
// The layout attribute repeats supports.layout.default on purpose: the
// editor's grid tools (span controls, resize handles) read the attribute,
// and do nothing for a grid that only comes from the support's default.

import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import { type BlockConfiguration, registerBlockType } from "@wordpress/blocks";
import metadata from "./block.json";
import "./style.css";

// [block name, attributes], as useInnerBlocksProps takes a template.
type TemplateBlock = [string, Record<string, unknown>];

// A phone stacks panels in one column, so a panel must span one column there,
// whatever it spans on wider screens. Every panel carries that override.
const panel = (columnSpan: number): TemplateBlock => [
  "twilight/panel",
  {
    style: {
      layout: { columnSpan, rowSpan: 1 },
      "@mobile": { layout: { columnSpan: 1, rowSpan: 1 } },
    },
  },
];

// A starting page: one wide panel over two halves.
const TEMPLATE: TemplateBlock[] = [panel(6), panel(3), panel(3)];

// block.json is read as plain JSON, so literal fields (category and so on)
// are widened to string; the cast restores the block.json shape.
registerBlockType(metadata as BlockConfiguration, {
  edit: () => {
    const innerBlocksProps = useInnerBlocksProps(useBlockProps(), {
      template: TEMPLATE,
      orientation: "horizontal",
    });
    return <div {...innerBlocksProps} />;
  },
  save: () => {
    const innerBlocksProps = useInnerBlocksProps.save(useBlockProps.save());
    return <div {...innerBlocksProps} />;
  },
});
