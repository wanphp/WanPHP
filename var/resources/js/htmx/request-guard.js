import {rootPage} from "../page/root";

export function registerRequestGuard() {
  document.addEventListener('htmx:beforeRequest', (e) => {
    const source = e.detail.elt;
    const requestConfig = e.detail.requestConfig;
    // console.log('beforeRequest:' + requestConfig?.triggeringEvent?.type, source.dataset, requestConfig?.elt, requestConfig?.verb, requestConfig?.path);
    if (requestConfig?.triggeringEvent?.type !== 'refresh-page') {
      const pageName = source?.dataset?.pageName;
      if (pageName && rootPage && pageName === rootPage.name) {
        e.preventDefault();
        return;
      }
      // 1. 防止重复打开相同的 Modal
      const modalName = source?.dataset?.modalName;
      if (modalName && document.querySelector(`[data-modal-instance="${modalName}"]`)) {
        e.preventDefault();
        return;
      }

      // 2. 获取表单和提交按钮
      if (source instanceof HTMLFormElement) {
        const form = source;
        const submitBtn = form.querySelector('[type="submit"]');

        // 3. 校验逻辑
        if (form && form.classList.contains('needs-validation')) {
          if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
          }
        }

        // 4. 防重点击 (Loading 状态)
        if (submitBtn) {
          if (submitBtn.disabled) {
            e.preventDefault();
            return;
          }
          submitBtn.disabled = true;
          submitBtn.dataset.originalHtml = submitBtn.innerHTML;
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';
        }
      }
    }
  });

}
