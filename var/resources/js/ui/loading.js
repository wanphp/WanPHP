import Swal from 'sweetalert2';

export function showLoading(title) {
  Swal.fire({
    title,
    allowOutsideClick: false, // 禁止点击外部关闭
    allowEscapeKey: false,    // 禁止 Esc 键关闭
    showConfirmButton: false, // 隐藏确认按钮
    didOpen: () => {
      Swal.showLoading();// 显示加载动画
    }
  });
}
