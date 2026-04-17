export function registerConfirm() {
  document.body.addEventListener('htmx:confirm', evt => {
    // console.log('confirm', evt.detail.elt.dataset);
    const data = evt.detail.elt.dataset;
    if (evt.detail.elt.hasAttribute('confirm-with-sweet-prompt')) {
      evt.preventDefault();
      window.promptConfirmDialog(data, value => {
        if (value) {
          evt.detail.elt.dataset.inputValue = value;
          evt.detail.issueRequest(true);
        }
      });
      return;
    }

    if (!evt.detail.question) return;
    evt.preventDefault();
    window.confirmDialog(evt.detail.question, () => evt.detail.issueRequest(true));
  });
}
