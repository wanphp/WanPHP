import {registerModal} from "@core/Registry";
import Page from '@core/Page';
import {compressImageAuto, selectFile, uploadFile} from "@core/Upload";

let modalName = 'app.setting.modal';

class SettingModal extends Page {
  constructor() {
    super(modalName);
  }

  init() {
    this.bindEvents();
  }

  destroy() {
  }

  bindEvents() {
    this.on('click', '#upload_image', async (e) => {
      const file = await selectFile({accept: 'image/*'});
      if (!file) return;

      // 自动压缩图片
      const optimized = await compressImageAuto(
        file,
        {maxWidth: 1000, maxHeight: 1000, quality: 0.8}
      );
      const result = await uploadFile(optimized, e.currentTarget.dataset.action);
      if (result.message) {
        Swal.fire({icon: 'error', title: '上传失败', text: result.message}).then();
        return;
      }
      this.$('#value').val(result.url).hide();
      e.currentTarget.closest('.from-group').querySelector('img')?.remove();
      $(e.currentTarget.closest('.from-group')).append('<img src="' + result.url + '" class="img-fluid rounded border" alt="' + result.name + '">');
    })

  }

}

registerModal(modalName, SettingModal);
