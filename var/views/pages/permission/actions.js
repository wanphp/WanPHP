import {registerPage} from "@core/Registry";
import Page from '@core/Page';
import Sortable from 'sortablejs';
import htmx from "htmx.org";

let pageName = 'app.permission';

class ActionsPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.initSortable();
    this.bindEvents();
  }

  destroy() {
    this.$('.form-select').each(function () {
      if ($(this).hasClass('select2-hidden-accessible')) {
        $(this).select2('destroy');
      }
    });

    this.$('.form-select').off('.page');
  }

  initSortable() {
    const _this = this; // 保存 Page 实例引用

    this.$('.sublist').each(function () {
      const container = this;
      const sortUrl = this.dataset.action;
      if (!sortUrl) return;

      const instance = Sortable.create(container, {
        animation: 150,
        handle: '.nav-item',
        ghostClass: 'bg-secondary',
        onEnd: (evt) => {
          if (evt.oldIndex === evt.newIndex) return;
          // 调用 htmx 发送请求
          htmx.ajax('post', sortUrl, {
            values: {id: evt.item.dataset.id, newIndex: evt.newIndex, oldIndex: evt.oldIndex},
            swap: 'none',
            target: container
          }).then();
        }
      });

      _this.use(instance);
    });
  }

  bindEvents() {
  }

}

registerPage(pageName, ActionsPage);
