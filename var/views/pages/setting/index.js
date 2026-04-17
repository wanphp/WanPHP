import {registerPage} from "@core/Registry";
import Page from '@core/Page';

let pageName = 'app.setting';

class SettingPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }

  bindEvents() {
    this.on('click', '.table img', e => {
      Swal.fire({
        imageUrl: e.target.src,
        imageAlt: e.target.alt,
        customClass: {
          image: 'm-0 rounded shadow-lg'
        },
        width: 'auto',
        padding: '0',
        showConfirmButton: false
      }).then();
    });
  }
}

registerPage(pageName, SettingPage);
