import {registerPage, registerDataTable, DataTableFactory} from "@core/Registry";
import Page from '@core/Page';
import htmx from "htmx.org";
import {compressImageAuto, selectFile, uploadFile} from "@core/Upload";

let pageName = 'app.files';

class FilesPage extends Page {
  constructor() {
    super(pageName);
    this.fileType = '';
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
      responsive: true,
      autoWidth: false,
      scrollX: true,
      ajax: {
        "url": action,
        "data": (d) => d.type = this.fileType
      },
      rowId: 'id',
      columns: [
        {
          title: '文件',
          data: "url",
          className: "text-center position-relative pe-4",
          render: function (data) {
            if (!data) return '<span class="text-muted">-</span>';
            const extension = data.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (imageExtensions.includes(extension)) {
              return `<img src="${data}" alt="" class="object-fit-cover rounded border" style="height: 50px; cursor: zoom-in;">`;
            }

            let icon = 'fa-file';
            let color = 'text-secondary';

            if (extension === 'mp3') {
              icon = 'fa-volume-up';
            } else if (extension === 'mp4') {
              icon = 'fa-video';
              color = 'text-danger';
            } else if (extension === 'pdf') {
              icon = 'fa-file-pdf';
              color = 'text-danger';
            } else if (['doc', 'docx'].includes(extension)) {
              icon = 'fa-file-word';
              color = 'text-primary';
            } else if (['xls', 'xlsx'].includes(extension)) {
              icon = 'fa-file-excel';
              color = 'text-success';
            } else if (['zip', 'rar', '7z'].includes(extension)) {
              icon = 'fa-file-archive';
              color = 'text-warning';
            }

            return `
        <a href="${data}" class="text-decoration-none">
          <i class="fas ${icon} ${color} fa-2x"></i>
          <div class="small text-muted">${extension.toUpperCase()}</div>
        </a><button class="btn btn-tool hover-btn copy-url position-absolute top-0 end-0" data-bs-toggle="tooltip" data-bs-title="复制路径">
          <i class="fas fa-copy"></i>
        </button>`;
          }
        },
        {
          title: '文件名', className: 'align-middle position-relative pe-4', data: "name", render: function (data) {
            return `<div class="text-limit-2" data-bs-toggle="tooltip" data-bs-title="${data}">${data}</div>
        <button class="btn btn-tool hover-btn edit position-absolute top-0 end-0" data-bs-toggle="tooltip" data-bs-title="修改文件名">
          <i class="fas fa-edit"></i></button>
        <button type="button" class="btn btn-tool hover-btn delete position-absolute bottom-0 end-0">
        <i class="fas fa-trash-alt" data-bs-toggle="tooltip" data-bs-title="删除文件"></i></button>`;
          }
        },
        {
          title: '上传时间', data: "uptime", className: "align-middle", render: (data, type, row) => {
            let date = this.formatDateTime(data);
            if (row.user) {
              return (data.avatar ? '<img src="' + data.avatar + '" class="img-thumbnail" alt="" style="padding:0;" width="30">' : '') + data.nickname + '<br>' + date;
            } else {
              return date;
            }
          }
        },
        {
          title: '文件大小', data: "size", className: "align-middle text-nowrap", render: (data) => {
            if (!data) return '0 MB';
            return this.formatFileSize(data);
          }
        }
      ],
      drawCallback: (settings) => {
        this.initTooltips(settings.api.table().container());
      }
    });
    this.use(registerDataTable(this.$('.table[id]').attr('id'), this.table));
  }

  bindEvents() {
    this.on('click', '.table .edit', e => {
      const data = this.getTableRowData(e.target.closest('tr').id);
      Swal.fire({
        input: "textarea",
        inputLabel: "修改文件名",
        inputValue: data.name,
        inputPlaceholder: "输入文件名",
        showCancelButton: true,
        showConfirmButton: true,  // 确保显示确定按钮
        confirmButtonColor: '#d33',
        confirmButtonText: '提交',
        cancelButtonText: '取消'
      }).then(result => {
        if (result.isConfirmed && result.value) {
          const fd = new FormData();
          fd.append('name', result.value);
          htmx.ajax('patch', data.edit, {
            swap: 'none',
            values: fd,
          }).then();
        }
      });
    });
    this.on('click', '.table .delete', e => {
      console.log('delete', e);
      const data = this.getTableRowData(e.target.closest('tr').id);
      confirmDialog('是否确认要删除文件', () => {
        htmx.ajax('delete', data.delete, {
          swap: 'none',
          target: e.target
        }).catch(err => console.log(err));
      });
    });
    this.on('click', '.card-tools button', async (e) => {
      let file = await selectFile({accept: '*/*'});
      if (!file) return;

      // 自动压缩图片
      if (file.type.startsWith('image/')) {
        file = await compressImageAuto(
          file,
          {maxWidth: 1000, maxHeight: 1000, quality: 0.8}
        );
      }
      const result = await uploadFile(file, e.currentTarget.dataset.action);
      if (result.message) {
        Swal.fire({icon: 'error', title: '上传失败', text: result.message}).then();
        return;
      }
      this.table.reload();
    })
    this.on('click', '.table img', e => {
      Swal.fire({
        imageUrl: e.target.src,
        imageAlt: e.target.alt,
        customClass: {
          image: 'm-0 img-fluid rounded shadow-lg'
        },
        width: 'auto',
        imageHeight: '500',
        padding: '0',
        showConfirmButton: false
      }).then();
    });
    this.on('click', '.table .copy-url', e => {
      const data = this.getTableRowData(e.target.closest('tr').id);
      const extension = data.url.split('.').pop().toLowerCase();
      const mediaExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mp3'];
      let path = data.url;
      if (!mediaExt.includes(extension)) {
        path = data.download;
      }
      navigator.clipboard.writeText(path).then(() => {
        Toast.fire({icon: 'success', position: 'top', title: '已复制'}).then();
      }).catch(err => {
        console.error('复制失败:', err);
      });
    });
    this.on('click', '.table a', e => {
      e.preventDefault();
      const url = e.currentTarget.href;
      const extension = url.split('.').pop().toLowerCase();
      const commonConfig = {
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: true,
        width: 'auto'
      };
      if (extension === 'mp3') {
        window.Swal.fire({
          ...commonConfig,
          title: '音频播放',
          html: `
                <div class="py-3">
                    <i class="fas fa-music fa-3x mb-3 text-info"></i>
                    <audio controls autoplay style="width: 100%;">
                        <source src="${url}" type="audio/mpeg">
                        您的浏览器不支持音频播放。
                    </audio>
                    <div class="mt-2 small text-muted">${url.split('/').pop()}</div>
                </div>`
        }).then();
      } else if (extension === 'mp4') {
        window.Swal.fire({
          ...commonConfig,
          title: '视频预览',
          width: 800,
          html: `
                <video controls autoplay style="width: 100%; border-radius: 8px;">
                    <source src="${url}" type="video/mp4">
                    您的浏览器不支持视频播放。
                </video>`
        }).then();
      } else {
        const data = this.getTableRowData(e.target.closest('tr').id);
        window.open(data.download, '_blank');
      }
    });
  }
}

registerPage(pageName, FilesPage);
