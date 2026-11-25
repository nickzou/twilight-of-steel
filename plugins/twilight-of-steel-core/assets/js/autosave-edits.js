// Keeps edits made after an autosave from going unnoticed.
//
// The block editor merges back-to-back changes to the same block attribute
// into one undo step, and only the first change of a run marks the post as
// edited. A manual save ends the run; an autosave clears the edited flag but
// doesn't, so the next edit continues the old run and the post looks saved.
// Core blocks rarely hit this because moving the caret ends the run, but
// Carbon Fields blocks (their inputs only ever set the block's data
// attribute) always do. End the run when an autosave finishes, as a manual
// save does.
(() => {
  const { select, subscribe, dispatch } = wp.data;
  let autosaving = false;

  subscribe(() => {
    const wasAutosaving = autosaving;
    autosaving = select("core/editor").isAutosavingPost();
    if (wasAutosaving && !autosaving) {
      dispatch("core/block-editor").__unstableMarkLastChangeAsPersistent();
    }
  }, "core/editor");
})();
