import {registerPage, registerDataTable, DataTableFactory} from "@core/Registry";
import Page from '@core/Page';

let pageName = 'app.admin';

class AdminPage extends Page {
  constructor() {
    super(pageName);
    this.currentGroup = 0;
    this.currentRole = 0;
  }

  init() {
    this.bindEvents();
  }

  destroy() {

  }

  initDataTable() {
    const _this = this;
    const action = this.$('.table').attr('data-action');
    if (!action) return;
    this.table = DataTableFactory.create(this.$('.table'), {
      responsive: false,
      autoWidth: false,
      scrollX: true,
      ajax: {
        "url": action,
        "data": function (d) {
          d.role_id = _this.currentRole;
          d.groupId = _this.currentGroup;
        }
      },
      rowId: 'id',
      columns: [
        {title: '登录帐号', data: "account"},
        {
          title: '绑定微信',
          data: "user", render: function (data) {
            if (data) {
              return (data.avatar ? '<img src="' + data.avatar + '" class="img-thumbnail" alt="" style="padding:0;" width="30">' : '') + data.nickname;
            } else {
              return '未绑定';
            }
          }
        },
        {title: '联系人', data: "name", defaultContent: ''},
        {title: '手机号', data: "tel", defaultContent: ''},
        {title: '分组', data: "group", defaultContent: ''},
        {title: '最后登录时间', data: "lastLoginTime"},
        {title: '最后登录IP', data: "lastLoginIp", defaultContent: ''},
        {
          title: '状态',
          data: "status", render: function (data) {
            if (data) {
              return '<span class="text-success">启用</span>';
            } else {
              return '<span class="text-danger">禁用</span>';
            }
          }
        },
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
    const _this = this;
    this.on('click', '.card-header .dropdown-item', function (e) {
      e.preventDefault(); // 阻止 <a> 标签跳转

      // 按钮显示的文字
      $(this).closest('.dropdown').find('button').text($(this).text());
      if (e.target.dataset.type === 'role') _this.currentRole = e.target.dataset.id;
      else _this.currentGroup = e.target.dataset.id;

      $(e.currentTarget).parents('ul.dropdown-menu').find('a.active').removeClass('active');
      $(e.currentTarget).addClass('active');

      _this.table.reload();
    })
  }
}

registerPage(pageName, AdminPage);
