export function userSelect2(selector, root, action, onConfirm) {
  return selector.select2({
    theme: "bootstrap-5",
    dropdownParent: root,
    ajax: {
      url: action,
      dataType: 'json',
      delay: 500,
      data: function (params) {
        return {
          q: params.term,
          page: params.page || 1
        };
      },
      processResults: function (data, params) {
        params.page = params.page || 1;
        return {
          results: data.users,
          pagination: {
            more: (params.page * 10) < data.total
          }
        };
      },
      cache: true
    },
    width: '100%',
    allowClear: true,
    language: "zh-CN",
    placeholder: '搜索选择绑定用户',
    minimumInputLength: 1,
    templateResult: function (user) {
      if (user.loading) return user.text;
      return $(
        '<div class="info-box">\n' +
        '  <span class="info-box-icon bg-info"><img src="' + user.avatar + '" alt=""></span>' +
        '  <div class="info-box-content">' +
        '    <span class="info-box-text">' + user.nickname + '</span>' +
        '    <span class="info-box-number">' + user.name + '(' + user.tel + ')</span>' +
        '  </div>' +
        '</div>'
      );
    },
    templateSelection: (user) => {
      onConfirm(user)
      if (user.nickname) return user.nickname + '(' + user.name + ' ' + user.tel + ')';
      return user.text;
    }
  });
}