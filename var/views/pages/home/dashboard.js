import {registerPage} from "@core/Registry";
import Page from '@core/Page';

let pageName = 'app.dashboard';

class DashboardPage extends Page {
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
}

registerPage(pageName, DashboardPage);
