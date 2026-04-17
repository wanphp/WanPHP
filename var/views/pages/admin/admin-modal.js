import {registerModal} from "@core/Registry";
import Page from '@core/Page';

let modalName = 'app.admin.modal';

class AdminModal extends Page {
  constructor() {
    super(modalName);
  }

  init() {
    this.bindEvents();
    this.initSelect();
  }

  destroy() {
    if (this.$("select[name='openid']").hasClass('select2-hidden-accessible')) {
      this.$("select[name='openid']").select2('destroy');
    }
    this.$("select[name='openid']").off('.page');
  }

  initSelect() {
    window.userSelect2(this.$("select#uid"), this.root, this.$("select#uid").attr('data-action'), (user) => {
      if (user.name) {
        this.$("form input[name='name']").val(user.name);
        this.$("form input[name='tel']").val(user.tel);
        this.$("form input[name='openid']").val(user.openid);
      }
    });
  }

  bindEvents() {
    const _this = this;
    this.on('click', '#randomPwd', function () {
      const regex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,16}$/;
      let password = "";
      do {
        password = _this.generateRandomString(Math.floor(Math.random() * 9) + 8);
      } while (!regex.test(password)); // 检查密码是否满足要求
      // 返回生成的密码
      _this.$("#pwd").attr('type', 'text').val(password);
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

registerModal(modalName, AdminModal);
