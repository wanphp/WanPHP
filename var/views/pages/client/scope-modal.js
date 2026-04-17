import {registerModal} from "@core/Registry";
import Page from '@core/Page';

let modalName = 'app.client.scope.modal';

class ClientModal extends Page {
  constructor() {
    super(modalName);
  }

  init() {
    this.initSelect();
  }

  destroy() {
    if (this.$("#scopes").hasClass('select2-hidden-accessible')) {
      this.$("#scopes").select2('destroy');
    }
  }


  initSelect() {
    this.$("#scopes").select2({
      theme: "bootstrap-5",
      dropdownParent: this.root,
      language: "zh-CN",
      placeholder: '选择授权范围'
    });
  }

}

registerModal(modalName, ClientModal);
