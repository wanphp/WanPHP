import {registerModal} from "@core/Registry";
import Page from '@core/Page';
import htmx from "htmx.org";

let modalName = 'app.admin.edit.password';

class AdminEditPasswordModal extends Page {

  constructor() {
    super(modalName);
    this.timer = null;
    this.checkNum = 0;
  }

  init() {
    this.bindEvents();
  }

  destroy() {
    this.checkNum = 0;
    if (this.timer) clearInterval(this.timer);
  }

  bindEvents() {
    this.on('click', '#resetPasswd', () => {
      const regex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,16}$/;
      let password = "";
      do {
        password = this.generateRandomString(Math.floor(Math.random() * 9) + 8);
      } while (!regex.test(password)); // 检查密码是否满足要求
      // 返回生成的密码
      this.$("input[name='password']").attr('type', 'text').val(password);
    });
    this.on('click', '.modal-body [data-type]', (e) => {
      $(e.currentTarget).hide();
      this.$('.info-box').remove();
      if (e.currentTarget.dataset.type === 'bind') {
        this.$('#bindQr').before('<div class="alert alert-info mb-0">使用新的微信扫码</div>');
        this.$('#bindQr').show();
      } else this.$('#unBindQr').show();

      this.timer = setInterval(() => {
        this.checkNum++;
        htmx.ajax('post', this.$('[data-bind-action]').attr('data-bind-action'), {
          swap: 'none',
          target: e.currentTarget
        });
        if (this.checkNum > 100) {
          clearInterval(this.timer);
          this.$('.alert').html('二维码已过期！');
          this.$('#bindQr,#bindQr').html('');
        }
      }, 2000);
    });
  }

  // 随机密码
  generateRandomString(length) {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*";
    let result = "";
    for (let i = 0; i < length; i++) {
      result += chars[Math.floor(Math.random() * chars.length)];
    }
    return result;
  }

}

registerModal(modalName, AdminEditPasswordModal);
