// Speech bubbles over a panel's artwork, edited with comical-js.
//
// Bubbles live on a stage STAGE_WIDTH units wide (and as tall as the panel's
// shape makes it), scaled to fit the panel, so their positions and tails
// mean the same thing at any size. comical-js draws the outlines and tails
// around each bubble's text while the panel is selected. The outlines are
// exported as a static SVG as they change, which is what the site shows:
// readers never load comical-js. render.php lays the text out with the same
// proportions, in percentages and container units.
//
// comical-js isn't bundled: it's loaded into the editor's canvas iframe
// (inc/comic-blocks.php), and used from the panel's own window.

import { RichText } from "@wordpress/block-editor";
import {
  useEffect,
  useLayoutEffect,
  useRef,
  useState,
} from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import type { BubbleSpec } from "comicaljs";
import type { PointerEvent as ReactPointerEvent, RefObject } from "react";

// Width of the stage in units. render.php relies on the same number.
export const STAGE_WIDTH = 1000;

export type PanelBubble = {
  id: string;
  // Rich text (bold, italic).
  text: string;
  // Top-left corner and width, in stage units.
  x: number;
  y: number;
  width: number;
  // Font size, in stage units.
  size: number;
  // comical-js's description of the outline and tails. Tail points are in
  // stage units too.
  spec: BubbleSpec;
};

export const stageHeight = (aspectRatio: string): number => {
  const [w, h] = aspectRatio.split("/").map(Number);
  return w > 0 && h > 0 ? (STAGE_WIDTH * h) / w : STAGE_WIDTH;
};

type ComicalJS = typeof import("comicaljs");

// comical-js as loaded in the window an element belongs to.
const comical = (element: Element): ComicalJS => {
  const view = element.ownerDocument.defaultView as
    | (Window & { ComicalJS?: ComicalJS })
    | null;
  if (!view?.ComicalJS) {
    throw new Error("comical-js isn't loaded in the editor canvas.");
  }
  return view.ComicalJS;
};

// comical-js keeps the spec in a data-bubble attribute, as JSON with
// backticks for quotes.
const specAttribute = (spec: BubbleSpec): string =>
  JSON.stringify(spec).replace(/"/g, "`");

const bubbleElement = (
  stage: HTMLElement | null,
  id: string,
): HTMLElement | null =>
  stage?.querySelector<HTMLElement>(`[data-bubble-id="${id}"]`) ?? null;

// The outlines as a standalone SVG for the site, scaled to the panel by CSS.
// Without the comical-generated class, so comical-js never mistakes a saved
// drawing for one of its own.
function exportOutlines(stage: HTMLElement): string {
  const copy = stage.cloneNode(true) as HTMLElement;
  comical(stage).Comical.exportSvgToCopyOfParent(stage, copy);
  const svg = copy.querySelector("svg.comical-generated");
  if (!svg) return "";
  svg.removeAttribute("class");
  svg.removeAttribute("width");
  svg.removeAttribute("height");
  // comical-js sizes its canvas, and so the viewBox, to make up for the
  // stage's scale; the drawing itself is in stage units.
  svg.setAttribute("viewBox", `0 0 ${stage.offsetWidth} ${stage.offsetHeight}`);
  svg.setAttribute("preserveAspectRatio", "none");
  return svg.outerHTML;
}

export function newBubble(bubbles: PanelBubble[], height: number): PanelBubble {
  const width = 280;
  const x = (STAGE_WIDTH - width) / 2;
  const y = Math.round(height * 0.15);
  return {
    id: Math.random().toString(36).slice(2, 10),
    text: "",
    x,
    y,
    width,
    size: 28,
    spec: {
      version: "1.0",
      style: "speech",
      // Bubbles sharing a level are one family whose outlines merge, so each
      // gets its own.
      level: Math.max(0, ...bubbles.map((b) => b.spec.level ?? 0)) + 1,
      tails: [
        {
          tipX: x + width / 2 - 60,
          tipY: Math.min(height - 20, y + 260),
          midpointX: x + width / 2 - 20,
          midpointY: Math.min(height - 60, y + 180),
          autoCurve: true,
        },
      ],
    },
  };
}

// Switch a bubble to another comical-js style, keeping its tail and level.
// Works on the live element, so only while editing.
export function restyle(
  stage: HTMLElement | null,
  bubble: PanelBubble,
  style: string,
): BubbleSpec {
  const element = bubbleElement(stage, bubble.id);
  if (!element) return { ...bubble.spec, style };
  const { Bubble } = comical(element);
  new Bubble(element).mergeWithNewBubbleProps({ style });
  return Bubble.getBubbleSpec(element);
}

type StageProps = {
  stageRef: RefObject<HTMLDivElement | null>;
  bubbles: PanelBubble[];
  aspectRatio: string;
  outlines: string;
  isEditing: boolean;
  activeId: string | null;
  onChangeBubbles: (bubbles: PanelBubble[]) => void;
  onChangeOutlines: (svg: string) => void;
  onActivate: (id: string | null) => void;
};

export function BubbleStage({
  stageRef,
  bubbles,
  aspectRatio,
  outlines,
  isEditing,
  activeId,
  onChangeBubbles,
  onChangeOutlines,
  onActivate,
}: StageProps) {
  const [scale, setScale] = useState(1);
  const outlinesRef = useRef<HTMLDivElement>(null);
  const height = stageHeight(aspectRatio);

  // Effects that comical-js triggers need the current values, not the ones
  // from the render that set them up.
  const latest = useRef({
    bubbles,
    onChangeBubbles,
    onChangeOutlines,
    onActivate,
  });
  latest.current = { bubbles, onChangeBubbles, onChangeOutlines, onActivate };

  // Scale the stage to the panel's width.
  useLayoutEffect(() => {
    const panel = stageRef.current?.parentElement;
    if (!panel) return;
    const observer = new ResizeObserver(() =>
      setScale(panel.clientWidth / STAGE_WIDTH),
    );
    observer.observe(panel);
    return () => observer.disconnect();
  }, [stageRef]);

  // The saved outlines, shown while comical-js isn't drawing them live.
  useEffect(() => {
    if (outlinesRef.current)
      outlinesRef.current.innerHTML = isEditing ? "" : outlines;
  }, [outlines, isEditing]);

  // comical-js runs while the panel is selected.
  useEffect(() => {
    const stage = stageRef.current;
    if (!isEditing || !stage) return;

    const { Comical, Bubble } = comical(stage);
    Comical.startEditing([stage]);
    Comical.setActiveBubbleListener((element) =>
      latest.current.onActivate(element?.dataset.bubbleId ?? null),
    );

    // Dragging a tail rewrites the bubble's data-bubble attribute; copy the
    // new spec into the block's attributes.
    const observer = new MutationObserver(() => {
      const { bubbles: current, onChangeBubbles: change } = latest.current;
      let changed = false;
      const next = current.map((bubble) => {
        const element = bubbleElement(stage, bubble.id);
        if (!element) return bubble;
        const spec = Bubble.getBubbleSpec(element);
        if (JSON.stringify(spec) === JSON.stringify(bubble.spec)) return bubble;
        changed = true;
        return { ...bubble, spec };
      });
      if (changed) change(next);
    });
    observer.observe(stage, {
      subtree: true,
      attributes: true,
      attributeFilter: ["data-bubble"],
    });

    return () => {
      observer.disconnect();
      latest.current.onChangeOutlines(exportOutlines(stage));
      Comical.setActiveBubbleListener(undefined);
      Comical.stopEditing();
      // stopEditing leaves its own SVG in the stage; the saved copy is shown instead.
      for (const svg of stage.querySelectorAll("svg.comical-generated"))
        svg.remove();
      latest.current.onActivate(null);
    };
  }, [isEditing, stageRef]);

  // Redraw when bubbles are added, removed or restyled. (Moves and text
  // changes comical-js notices by itself.)
  const shape = bubbles
    .map((b) => `${b.id}:${b.spec.style}:${b.spec.level}`)
    .join();
  useEffect(() => {
    const stage = stageRef.current;
    if (!isEditing || !stage) return;
    const { Comical } = comical(stage);
    if (Comical.activeContainers.has(stage)) Comical.update(stage);
  }, [shape, isEditing, stageRef]);

  // Keep the saved outlines in step while editing, so saving with the panel
  // still selected stores the latest drawing.
  useEffect(() => {
    if (!isEditing) return;
    const timer = setTimeout(() => {
      const stage = stageRef.current;
      if (stage && comical(stage).Comical.activeContainers.has(stage)) {
        latest.current.onChangeOutlines(exportOutlines(stage));
      }
    }, 400);
    return () => clearTimeout(timer);
  }, [bubbles, isEditing, stageRef]);

  const update = (id: string, changes: Partial<PanelBubble>) =>
    onChangeBubbles(
      bubbles.map((b) => (b.id === id ? { ...b, ...changes } : b)),
    );

  // Moving a bubble: comical-js draws tails but leaves placement to us.
  const startMove = (event: ReactPointerEvent, bubble: PanelBubble) => {
    event.preventDefault();
    event.stopPropagation();
    const handle = event.currentTarget as HTMLElement;
    handle.setPointerCapture(event.pointerId);
    const start = {
      x: event.clientX,
      y: event.clientY,
      bubbleX: bubble.x,
      bubbleY: bubble.y,
    };
    const element = bubbleElement(stageRef.current, bubble.id);
    const move = (e: PointerEvent) => {
      const x = start.bubbleX + (e.clientX - start.x) / scale;
      const y = start.bubbleY + (e.clientY - start.y) / scale;
      latest.current.onChangeBubbles(
        latest.current.bubbles.map((b) =>
          b.id === bubble.id
            ? {
                ...b,
                x: Math.round(Math.max(0, Math.min(STAGE_WIDTH - b.width, x))),
                y: Math.round(
                  Math.max(
                    0,
                    Math.min(height - (element?.offsetHeight ?? 0), y),
                  ),
                ),
              }
            : b,
        ),
      );
    };
    const end = () => {
      handle.removeEventListener("pointermove", move);
      handle.removeEventListener("pointerup", end);
      handle.removeEventListener("pointercancel", end);
    };
    handle.addEventListener("pointermove", move);
    handle.addEventListener("pointerup", end);
    handle.addEventListener("pointercancel", end);
  };

  return (
    <>
      <div className="twilight-bubbles__outlines" ref={outlinesRef} />
      <div
        ref={stageRef}
        className={`twilight-bubbles__stage${isEditing ? " is-editing" : ""}`}
        style={{ width: STAGE_WIDTH, height, transform: `scale(${scale})` }}
      >
        {bubbles.map((bubble) => (
          <div
            key={bubble.id}
            className={`twilight-bubble is-${bubble.spec.style}${bubble.id === activeId ? " is-active" : ""}`}
            data-bubble={specAttribute(bubble.spec)}
            data-bubble-id={bubble.id}
            style={{
              left: bubble.x,
              top: bubble.y,
              width: bubble.width,
              fontSize: bubble.size,
            }}
            onPointerDown={(event) => {
              if (!isEditing) return;
              comical(event.currentTarget).Comical.activateElement(
                event.currentTarget,
              );
              onActivate(bubble.id);
            }}
          >
            {isEditing && bubble.id === activeId && (
              <button
                type="button"
                className="twilight-bubble__move"
                aria-label={__("Move bubble", "twilight-of-steel-core")}
                onPointerDown={(event) => startMove(event, bubble)}
              >
                ✥
              </button>
            )}
            <RichText
              tagName="div"
              className="twilight-bubble__text"
              identifier={`bubble-${bubble.id}`}
              value={bubble.text}
              allowedFormats={["core/bold", "core/italic"]}
              placeholder={__("Speech…", "twilight-of-steel-core")}
              onChange={(text: string) => update(bubble.id, { text })}
            />
          </div>
        ))}
      </div>
    </>
  );
}
