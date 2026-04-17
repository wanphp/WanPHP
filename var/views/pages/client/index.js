import {registerPage} from "@core/Registry";
import Page from '@core/Page';

let pageName = 'app.client';

class ClientPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }

  bindEvents() {
  }

  dialog(data) {
    window.Swal.fire({
      title: "重置成功,密钥只显示一次！！",
      text: data.client_secret,
      icon: "success",
      allowOutsideClick: false, // 禁止点击外部遮罩层关闭
      allowEscapeKey: false,    // 禁止按下 Esc 键关闭
      allowEnterKey: false,     // 可选：禁止按下回车键关闭（防止误触）
      showConfirmButton: true,  // 确保显示确定按钮
      confirmButtonColor: '#d33',
      confirmButtonText: '确认已保存'
    }).then();
  }
}

registerPage(pageName, ClientPage);
