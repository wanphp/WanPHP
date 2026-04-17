import {registerModal} from "@core/Registry";
import Page from '@core/Page';

let modalName = 'app.admin.role.modal';

class RoleModal extends Page {
  constructor() {
    super(modalName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }


  bindEvents() {
    const _this = this;
    this.on('dblclick', '#route option', function () {
      $(this).prop('selected', true);
      _this.$('#scopes').append(this);
    });

    this.on('dblclick', '#scopes option', function () {
      $(this).prop('selected', false);
      _this.$('#route').append(this);
    });

    this.on('mousedown', '#route option', function (e) {
      e.preventDefault();
      $(this).prop('selected', false);
    });
    this.on('mousedown', '#scopes option', function (e) {
      e.preventDefault();
      $(this).prop('selected', true);
    });
  }

}

registerModal(modalName, RoleModal);
