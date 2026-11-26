// Panel: one panel of a comic page. Its artwork is cropped to the panel's
// shape (aspect ratio) around a focal point. The page grid decides where the
// panel goes and how big it is; the panel decides its shape, which stays the
// same at every screen size. Rendered in PHP (render.php) for srcset.
// Speech bubbles sit on top of the artwork (bubbles.tsx).

import {
  BlockControls,
  store as blockEditorStore,
  InspectorControls,
  MediaPlaceholder,
  MediaReplaceFlow,
  useBlockProps,
} from "@wordpress/block-editor";
import { type BlockConfiguration, registerBlockType } from "@wordpress/blocks";
import {
  Button,
  FocalPointPicker,
  PanelBody,
  RangeControl,
  SelectControl,
  TextareaControl,
  ToolbarButton,
} from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { useRef, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import metadata from "./block.json";
import {
  BubbleStage,
  newBubble,
  type PanelBubble,
  restyle,
  stageHeight,
} from "./bubbles";
import "./style.css";

type FocalPoint = { x: number; y: number };

type Attributes = {
  id?: number;
  url?: string;
  alt: string;
  aspectRatio: string;
  focalPoint: FocalPoint;
  bubbles: PanelBubble[];
  outlines: string;
};

type Media = { id: number; url: string; alt?: string };

type Attachment = { media_details?: { width?: number; height?: number } };

const SHAPES = [
  { label: __("Square (1:1)", "twilight-of-steel-core"), value: "1/1" },
  { label: __("Landscape (4:3)", "twilight-of-steel-core"), value: "4/3" },
  { label: __("Landscape (3:2)", "twilight-of-steel-core"), value: "3/2" },
  { label: __("Wide (16:9)", "twilight-of-steel-core"), value: "16/9" },
  { label: __("Panoramic (21:9)", "twilight-of-steel-core"), value: "21/9" },
  { label: __("Strip (3:1)", "twilight-of-steel-core"), value: "3/1" },
  { label: __("Portrait (3:4)", "twilight-of-steel-core"), value: "3/4" },
  { label: __("Portrait (2:3)", "twilight-of-steel-core"), value: "2/3" },
  { label: __("Tall (9:16)", "twilight-of-steel-core"), value: "9/16" },
];

const BUBBLE_STYLES = [
  { label: __("Speech", "twilight-of-steel-core"), value: "speech" },
  { label: __("Shout", "twilight-of-steel-core"), value: "shout" },
  { label: __("Thought", "twilight-of-steel-core"), value: "thought" },
  { label: __("Caption", "twilight-of-steel-core"), value: "caption" },
  { label: __("Ellipse", "twilight-of-steel-core"), value: "ellipse" },
  { label: __("Pointed arcs", "twilight-of-steel-core"), value: "pointedArcs" },
  { label: __("Circle", "twilight-of-steel-core"), value: "circle" },
  { label: __("Rectangle", "twilight-of-steel-core"), value: "rectangle" },
  { label: __("Text only", "twilight-of-steel-core"), value: "none" },
];

const TEXT_SIZES = [
  { label: __("Small", "twilight-of-steel-core"), value: "22" },
  { label: __("Medium", "twilight-of-steel-core"), value: "28" },
  { label: __("Large", "twilight-of-steel-core"), value: "36" },
  { label: __("Huge", "twilight-of-steel-core"), value: "48" },
];

const gcd = (a: number, b: number): number => (b ? gcd(b, a % b) : a);

// block.json is read as plain JSON, so literal fields (category and so on)
// are widened to string; the cast restores the block.json shape.
registerBlockType<Attributes>(metadata as BlockConfiguration<Attributes>, {
  edit: ({ attributes, setAttributes, isSelected }) => {
    const { id, url, alt, aspectRatio, focalPoint, bubbles, outlines } =
      attributes;
    const blockProps = useBlockProps({ style: { aspectRatio } });
    const stageRef = useRef<HTMLDivElement>(null);
    // The outlines are re-exported after each bubble change; fold that into
    // the change's undo step, so one undo reverts both.
    const { __unstableMarkNextChangeAsNotPersistent: markNotPersistent } =
      useDispatch(blockEditorStore);
    const [activeId, setActiveId] = useState<string | null>(null);
    const active = bubbles.find((b) => b.id === activeId);

    const setBubbles = (next: PanelBubble[]) =>
      setAttributes({ bubbles: next });
    const updateActive = (changes: Partial<PanelBubble>) =>
      active &&
      setBubbles(
        bubbles.map((b) => (b.id === active.id ? { ...b, ...changes } : b)),
      );

    // The artwork's own shape, offered as a panel shape, in lowest terms so
    // a 900×900 image matches the 1/1 preset.
    const imageShape = useSelect(
      (select) => {
        if (!id) return undefined;
        const media = (
          select("core") as {
            getMedia: (id: number) => Attachment | undefined;
          }
        ).getMedia(id);
        const { width, height } = media?.media_details ?? {};
        if (!width || !height) return undefined;
        const divisor = gcd(width, height);
        return `${width / divisor}/${height / divisor}`;
      },
      [id],
    );

    const onSelect = (media: Media) =>
      setAttributes({ id: media.id, url: media.url, alt: media.alt ?? "" });

    if (!url) {
      return (
        <figure {...blockProps}>
          <MediaPlaceholder
            icon="format-image"
            labels={{
              title: __("Panel", "twilight-of-steel-core"),
              instructions: __(
                "Upload or choose the panel's artwork.",
                "twilight-of-steel-core",
              ),
            }}
            accept="image/*"
            allowedTypes={["image"]}
            onSelect={onSelect}
          />
        </figure>
      );
    }

    const shapes = SHAPES.some((shape) => shape.value === aspectRatio)
      ? SHAPES
      : [...SHAPES, { label: aspectRatio, value: aspectRatio }];

    return (
      <>
        <BlockControls group="other">
          <MediaReplaceFlow
            mediaId={id}
            mediaURL={url}
            accept="image/*"
            allowedTypes={["image"]}
            onSelect={onSelect}
            name={__("Replace artwork", "twilight-of-steel-core")}
          />
          <ToolbarButton
            icon="format-chat"
            label={__("Add bubble", "twilight-of-steel-core")}
            onClick={() => {
              const bubble = newBubble(bubbles, stageHeight(aspectRatio));
              setBubbles([...bubbles, bubble]);
              setActiveId(bubble.id);
            }}
          />
        </BlockControls>
        <InspectorControls>
          <PanelBody title={__("Panel", "twilight-of-steel-core")}>
            <SelectControl
              __next40pxDefaultSize
              label={__("Shape", "twilight-of-steel-core")}
              help={__(
                "Stays the same on every screen size.",
                "twilight-of-steel-core",
              )}
              value={aspectRatio}
              options={shapes}
              onChange={(value: string) =>
                setAttributes({ aspectRatio: value })
              }
            />
            {imageShape && imageShape !== aspectRatio && (
              <div style={{ marginBlockEnd: 16 }}>
                <Button
                  variant="secondary"
                  size="compact"
                  onClick={() => setAttributes({ aspectRatio: imageShape })}
                >
                  {__("Use the artwork's shape", "twilight-of-steel-core")}
                </Button>
              </div>
            )}
            <FocalPointPicker
              __nextHasNoMarginBottom
              label={__("Focal point", "twilight-of-steel-core")}
              help={__(
                "Kept in view when the artwork is cropped.",
                "twilight-of-steel-core",
              )}
              url={url}
              value={focalPoint}
              onChange={(value: FocalPoint) =>
                setAttributes({ focalPoint: value })
              }
            />
            <TextareaControl
              __nextHasNoMarginBottom
              label={__("Description", "twilight-of-steel-core")}
              help={__(
                "What happens in this panel, for readers using a screen reader.",
                "twilight-of-steel-core",
              )}
              value={alt}
              onChange={(value: string) => setAttributes({ alt: value })}
            />
          </PanelBody>
          {active && (
            <PanelBody title={__("Bubble", "twilight-of-steel-core")}>
              <SelectControl
                __next40pxDefaultSize
                label={__("Style", "twilight-of-steel-core")}
                value={active.spec.style}
                options={BUBBLE_STYLES}
                onChange={(style: string) =>
                  updateActive({
                    spec: restyle(stageRef.current, active, style),
                  })
                }
              />
              <SelectControl
                __next40pxDefaultSize
                label={__("Text size", "twilight-of-steel-core")}
                value={String(active.size)}
                options={TEXT_SIZES}
                onChange={(size: string) =>
                  updateActive({ size: Number(size) })
                }
              />
              <RangeControl
                __next40pxDefaultSize
                label={__("Width", "twilight-of-steel-core")}
                value={active.width}
                min={80}
                max={900}
                step={10}
                onChange={(width?: number) => width && updateActive({ width })}
              />
              <Button
                variant="secondary"
                isDestructive
                onClick={() => {
                  setBubbles(bubbles.filter((b) => b.id !== active.id));
                  setActiveId(null);
                }}
              >
                {__("Delete bubble", "twilight-of-steel-core")}
              </Button>
            </PanelBody>
          )}
        </InspectorControls>
        <figure {...blockProps}>
          <img
            src={url}
            alt={alt}
            style={{
              objectPosition: `${focalPoint.x * 100}% ${focalPoint.y * 100}%`,
            }}
          />
          <BubbleStage
            stageRef={stageRef}
            bubbles={bubbles}
            aspectRatio={aspectRatio}
            outlines={outlines}
            isEditing={isSelected}
            activeId={activeId}
            onChangeBubbles={setBubbles}
            onChangeOutlines={(svg) => {
              if (svg === outlines) return;
              markNotPersistent();
              setAttributes({ outlines: svg });
            }}
            onActivate={setActiveId}
          />
        </figure>
      </>
    );
  },
  // Rendered in PHP (render.php).
  save: () => null,
});
