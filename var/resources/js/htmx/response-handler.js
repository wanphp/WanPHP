import {rootPage} from "../page/root";

export function registerResponseHandler() {
  document.addEventListener('htmx:afterRequest', function (evt) {
    const source = evt.detail.elt;
    const xhr = evt.detail.xhr;
    let data = {};
    try {
      data = JSON.parse(xhr.responseText);
    } catch (e) {
    }
    // const requestConfig = evt.detail.requestConfig;
    // console.log('afterRequest:' + requestConfig?.triggeringEvent?.type, requestConfig?.verb, requestConfig?.path, source);
    if (source instanceof HTMLFormElement) {
      const form = source;
      const submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.originalHtml;
      }

      // 请求成功 (2xx)
      if (evt.detail.successful) {
        Toast.fire({
          icon: 'success',
          title: data.message || '操作成功'
        });

        // 重置表单
        if (form.hasAttribute('data-reset')) {
          form.reset();
          $(form).find('.select2-hidden-accessible').val(null).trigger('change');
        }

        // Modal 处理
        if (form.hasAttribute('data-modal-form')) {
          const modalElement = form.closest('.modal');
          if (modalElement) {
            // 刷新 DataTable
            const tableKey = form.dataset.reloadDatatable;
            if (tableKey && window.DataTableRegistry?.[tableKey]) {
              window.DataTableRegistry[tableKey].reload();
            }
            // 处理结果
            const mInstance = modalElement._customModalLogic;
            if (mInstance?.then) mInstance.then(data);
            // 关闭 Modal
            const instance = window.bootstrap.Modal.getInstance(modalElement);
            instance?.hide();
          }
        }
        // 手动确认对话框
        if (data?.dialog && rootPage?.dialog) rootPage.dialog(data.dialog);

        if (data.redirect) window.location.href = data.redirect;
      } else {
        // 422 表彰验证错误
        if (xhr.status === 422) {
          const errors = data.errors || {};
          // 1. 清空旧错误 (保持原有逻辑)
          form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
          form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

          Object.entries(errors).forEach(([name, messages]) => {
            const input = form.querySelector(`[name="${name}"], [name="${name}[]"]`);
            if (!input) return;

            input.classList.add('is-invalid');

            // 2. 适配 Select2 的错误显示
            let targetElement = input;
            if (input.classList.contains('select2-hidden-accessible')) {
              // 找到 select2 的渲染容器并加上红色边框样式
              const select2Container = $(input).next('.select2-container');
              select2Container.find('.select2-selection').addClass('is-invalid-select2');
              targetElement = select2Container[0];
            }

            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block'; // 强制显示
            feedback.textContent = messages[0];

            targetElement.insertAdjacentElement('afterend', feedback);
          });
          Toast.fire({icon: 'error', title: data.message || '字段验证失败'}).then();
          return;
        }
        // 其他错误 (400，500 等)
        Swal.fire({
          icon: 'error',
          title: '提交失败',
          text: data.message || '服务器内部错误'
        }).then();
      }
    } else {
      if (evt.detail.successful) {
        // 删除成功
        if (xhr.status === 204) {
          const id = evt.detail.target.closest('tr')?.id;
          const tableKey = evt.detail.target.closest('table')?.id;
          if (id && tableKey && window.DataTableRegistry?.[tableKey]) {
            window.DataTableRegistry[tableKey].removeRow(id);
          }
        }
        if (data?.dialog && rootPage?.dialog) rootPage.dialog(data.dialog);
        if (data?.message) Toast.fire({icon: 'success', title: data.message}).then();
        if (data?.redirect) window.location.href = data.redirect;
      } else {
        Swal.fire({icon: 'error', title: '请求失败', text: data.message || '服务器内部错误'}).then();
      }
      if (data?.reload) {
        // 延迟 2 秒刷新，让用户看清 Toast 提示
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      }
    }
  });

  document.addEventListener('htmx:responseError', (e) => {
    if (e.detail.xhr.status === 403) {
      Toast.fire({
        icon: 'error',
        title: data.message || '没有权限'
      }).then();
    }
    if (e.detail.xhr.status === 401) {
      location.href = '/login';
    }
  });
}