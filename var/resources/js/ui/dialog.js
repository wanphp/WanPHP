export function confirmDialog(title, onConfirm) {
  Swal.fire({
    title,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: '确定',
    cancelButtonText: '取消'
  }).then(r => r.isConfirmed && onConfirm());
}

export function promptConfirmDialog(data, onConfirm) {
  Swal.fire({
    input: data.input,
    title: data.title,
    inputValue: data.value,
    inputOptions: data.inputOptions,
    inputPlaceholder: data.placeholder,
    showCancelButton: true,
    confirmButtonText: '提交',
    cancelButtonText: '取消'
  }).then(r => r.isConfirmed && r.value && onConfirm(r.value));
}