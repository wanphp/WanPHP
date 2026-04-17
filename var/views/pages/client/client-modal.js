import {registerModal} from "@core/Registry";
import Page from '@core/Page';

let modalName = 'app.client.modal';

class ClientModal extends Page {
  constructor() {
    super(modalName);
  }

  init() {
    this.bindEvents();
    this.initSelect();
  }

  destroy() {
    if (this.$("#scopes").hasClass('select2-hidden-accessible')) {
      this.$("#scopes").select2('destroy');
    }
  }

  then(data){
    console.log(data);
  }


  initSelect() {
    this.$("#scopes").select2({
      theme: "bootstrap-5",
      dropdownParent: this.root,
      language: "zh-CN",
      placeholder: '选择授权范围'
    });
  }

  bindEvents() {
  }

}

registerModal(modalName, ClientModal);
