let rootPage = null;
export {rootPage};

export function mountRoot(pageEl) {
  const name = pageEl.dataset.page;
  const PageClass = PageRegistry[name];
  if (!PageClass) return;

  rootPage = new PageClass(name);
  rootPage.mount(pageEl);
  pageEl._customPageLogic = rootPage;
}

export function unmountRoot() {
  rootPage?.unmount?.();
  rootPage = null;
}
