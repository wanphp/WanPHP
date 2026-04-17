import {childPages} from "../page/child";

export function openPageInModal(triggerEl, url, page, options = {}) {
  if (!url || !page) return;

  // 防止重复
  if (document.querySelector(`[data-modal-page="${page}"]`)) {
    return;
  }

  const {
    title = '',
    size = '',        // sm | lg | xl | fullscreen
    backdrop = true
  } = options;

  /** @type {HTMLDivElement & {_triggerEl?: HTMLElement}} */
  const modal = document.createElement('div');
  modal.className = 'modal fade';
  modal.tabIndex = -1;
  modal.dataset.modalPage = page;
  modal._triggerEl = triggerEl;

  modal.innerHTML = `
    <div class="modal-dialog modal-${size}">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">${title}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body"
             hx-get="${url}"
             hx-trigger="load"
             hx-target="this"
             hx-swap="innerHTML">
          <div class="text-center py-5 text-muted">加载中…</div>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  // 关键：让 htmx 识别新 DOM
  htmx.process(modal);

  // 使用 Bootstrap Modal API
  const bsModal = new window.bootstrap.Modal(modal, {
    backdrop: backdrop,
    keyboard: backdrop !== 'static'
  });

  bsModal.show();

  modal.addEventListener('shown.bs.modal', () => {
    const zIndex = 1050 + (10 * document.querySelectorAll('.modal.show').length);
    modal.style.zIndex = zIndex;
    setTimeout(() => {
      const backdrop = document.querySelector('.modal-backdrop:last-child');
      if (backdrop) backdrop.style.zIndex = zIndex - 1;
    }, 0);
  });

  modal.addEventListener('hidden.bs.modal', () => {
      const childPageEl = modal.querySelector('[data-page]');
      if (childPages.has(childPageEl)) {
        childPages.get(childPageEl).unmount();
        childPages.delete(childPageEl);
      }
      console.log(childPageEl);
      modal.remove();

      if (document.querySelectorAll('.modal.show').length > 0) {
        document.body.classList.add('modal-open');
      }
    },
    {once: true}
  );
}
