import {mountRoot, rootPage, unmountRoot} from "../page/root";
import {childPages} from "../page/child";

export function registerLifecycle() {
  // 首次加载
  document.addEventListener('DOMContentLoaded', () => {
    const pageEl = document.querySelector('[data-page]');
    if (pageEl) mountRoot(pageEl);
  });

  document.addEventListener('htmx:beforeSwap', (evt) => {
    // 更新目标
    const target = evt.detail.target;
    //console.log('beforeSwap', target.tagName, target.id);
    if (target.id === 'app-content') {
      unmountRoot();
      // unmount 子 Page
      [...childPages.entries()].forEach(([el, instance]) => {
        instance.unmount();
        childPages.delete(el);
      });
    } else {
      const childPageEl = target.hasAttribute('data-page') ? target : target.querySelector('[data-page]');
      if (childPages.has(childPageEl)) {
        childPages.get(childPageEl).unmount();
        childPages.delete(childPageEl);
      }
    }
  });

  document.addEventListener('htmx:afterSwap', (evt) => {
    const target = evt.target instanceof HTMLElement ? evt.target : null;
    const requestConfig = evt.detail.requestConfig;
    const triggerEl = requestConfig.elt;
    //console.log('afterSwap:' + requestConfig?.triggeringEvent?.type, requestConfig?.verb, requestConfig?.path);
    if (!target) return;
    if (target.tagName !== 'BODY') {
      const pageEl = target.hasAttribute('data-page') ? target : target.querySelector('[data-page]');
      if (pageEl) {
        let targetId = target.id;
        if (target.hasAttribute('data-page')) targetId = target.parentElement.id;
        /* ========= 根 Page ========= */
        if (targetId === 'app-content') {
          if (!rootPage) {
            mountRoot(pageEl);
            console.log(rootPage);
          } else {
            const trigger = evt.detail.requestConfig?.triggeringEvent;
            if (trigger && trigger.type === 'refresh-page' && rootPage.name === pageEl.dataset.page) {
              console.log('根 Pagerefresh-page 触发的请求');
              rootPage.unmount();
              rootPage.mount(pageEl);
            }
          }
        } else {
          /* ========= 子 Page ========= */
          if (!childPages.has(pageEl)) {
            const name = pageEl.dataset.page;
            const PageClass = window.PageRegistry[name];
            if (!PageClass) return;

            const instance = new PageClass(name);
            instance.mount(pageEl);
            childPages.set(pageEl, instance);
            pageEl._customPageLogic = instance;
            console.log('childPages', childPages);
          } else {
            const trigger = evt.detail.requestConfig?.triggeringEvent;
            const childPage = childPages.get(pageEl);
            if (trigger && trigger.type === 'refresh-page' && childPage.name === pageEl.dataset.page) {
              console.log('子 Page refresh-page 触发的请求');
              childPage.unmount();
              childPage.mount(pageEl);
            }
          }
        }
      }
    } else {
      /* ========= 动态 Modal ========= */
      const modalName = triggerEl?.dataset?.modalName;
      const modalSize = triggerEl?.dataset?.modalSize;
      const modalBackdrop = triggerEl?.dataset?.Backdrop;
      // 没有指定modalName 跳过
      if (!modalName) return;

      console.log('beforeSwapModal', triggerEl.dataset);

      const modalEl = target.querySelector('[data-modal-instance="' + modalName + '"]');
      if (modalEl) {
        if (window.bootstrap.Modal.getInstance(modalEl)) return;

        const modalClass = window.ModalRegistry[modalName];
        if (modalClass) {
          const mInstance = new modalClass();
          mInstance.mount(modalEl);
          modalEl._customModalLogic = mInstance;
        }

        if (modalSize) {
          modalEl.querySelector('.modal-dialog').classList.add('modal-' + modalSize);
        }

        const options = {
          backdrop: modalBackdrop === 'static' ? 'static' : (modalBackdrop !== 'false'),
          keyboard: modalBackdrop !== 'static'
        };

        const instance = new window.bootstrap.Modal(modalEl, options);
        instance.show();

        modalEl.addEventListener('shown.bs.modal', () => {
          const zIndex = 1050 + (10 * document.querySelectorAll('.modal.show').length);
          modalEl.style.zIndex = zIndex;
          setTimeout(() => {
            const backdrop = document.querySelector('.modal-backdrop:last-child');
            if (backdrop) backdrop.style.zIndex = zIndex - 1;
          }, 0);
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            const mInstance = modalEl?._customModalLogic;
            console.log(mInstance);
            if (mInstance?.unmount) mInstance.unmount();
            modalEl.remove();

            if (document.querySelectorAll('.modal.show').length > 0) {
              document.body.classList.add('modal-open');
            }
          },
          {once: true}
        );
      }
    }
  });

}