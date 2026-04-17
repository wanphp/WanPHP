import {DataTableFactory, registerDataTable, registerPage} from "@core/Registry";
import Page from '@core/Page';
import {Mandarin} from "flatpickr/dist/l10n/zh";
import htmx from "htmx.org";

let pageName = 'app.audit.logs';

class LogsPage extends Page {
  constructor() {
    super(pageName);
    this.date = '';
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
      responsive: false,
      autoWidth: false,
      scrollX: true,
      columnDefs: [
        {orderable: false, targets: [0, 1]}
      ],
      ordering: true,//使用排序
      order: [[4, "desc"]],
      ajax: {
        "url": action,
        "data": (d) => {
          if (this.date) d.date = this.date;
        }
      },
      rowId: 'id',
      columns: [
        {
          title: '用户类型', data: "actor_type"
        },
        {
          title: '用户', data: "user", render: (data) => {
            if (data?.avatar) {
              return (data.avatar ? '<img src="' + data.avatar + '" class="img-thumbnail" alt="" style="padding:0;" width="30">' : '') + data.nickname;
            } else {
              return data;
            }
          }
        },
        {title: '行为', data: "action"},
        {
          title: '行为描述',
          className: "text-center position-relative pe-4",
          data: "action_desc",
          render: (data) => {
            return data + `<button class="btn btn-tool hover-btn position-absolute top-0 end-0" data-modal-name="app.audit.logs.detail" data-bs-toggle="tooltip" data-bs-title="查看详情">
          <i class="fas fa-file-text"></i>
        </button>`;
          }
        },
        {
          title: '事件时间', data: "event_time", render: (data) => {
            return this.formatDateTime(data);
          }
        }
      ]
    });
    this.use(registerDataTable(this.$('.table[id]').attr('id'), this.table));
  }

  bindEvents() {
    this.on('click', 'table .hover-btn', (e) => {
      const data = this.getTableRowData(e.currentTarget.closest('tr').id);
      e.currentTarget.dataset.modalSize = 'lg';
      htmx.ajax('get', data.detail, {source: e.currentTarget, target: 'body', swap: 'beforeend'}).then();
    });
    const _this = this;
    flatpickr('#selectDate', {
      dateFormat: 'Y-m-d',
      maxDate: 'today',
      locale: Mandarin,
      onChange(selectedDates, dateStr) {
        _this.date = dateStr;
        _this.table.reload();
      }
    })
  }

}

registerPage(pageName, LogsPage);
