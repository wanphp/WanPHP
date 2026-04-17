import {registerPage} from "@core/Registry";
import Page from '@core/Page';
import htmx from "htmx.org";

let modalName = 'app.login';

class LoginPage extends Page {
  constructor() {
    super(modalName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }


  bindEvents() {
    let timer;
    let checkNum = 0;
    this.on('shown.bs.modal', '.modal',  ()=> {
      timer = setInterval(()=> {
        checkNum++;
        htmx.ajax('post', this.$('.modal').attr('data-action'), {
          swap: 'none'
        }).catch(err => console.log(err));
        if (checkNum > 100) {
          clearInterval(timer);
          $('.modal-body').html('二维码已过期！');
        }
      }, 2000);
    });
    this.on('hidden.bs.modal', function () {
      checkNum = 0;
      console.log(timer);
      clearInterval(timer);
    });
  }

}

registerPage(modalName, LoginPage);
