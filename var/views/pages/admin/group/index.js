import {registerPage, registerDataTable, DataTableFactory} from "@core/Registry";
import Page from '@core/Page';

let pageName = 'app.admin.group';

class AdminGroupPage extends Page {
  constructor() {
    super(pageName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {

  }

  initDataTable() {
    const action = this.$('.table').attr('data-action');
    if (!action) return;
    this.table = DataTableFactory.create(this.$('.table'), {
      serverSide: false,
      search: {return: false},
      ajax: action,
      rowId: 'id',
      columns: [
        {title: '分组', data: "name"},
        {title: '描述', data: "description"},
        {
          title: '操作',
          data: 'actions',
          render: (data) => this.getTableButtons(data)
        }
      ]
    });
    this.use(registerDataTable(this.$('.table[id]').attr('id'), this.table));
  }

  bindEvents() {
  }

}

registerPage(pageName, AdminGroupPage);
