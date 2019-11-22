$(document).ready(function () {
  $('.upload-file-form').on('filesorted', function(event, params) {
    console.log('File sorted ', params.previewId, params.oldIndex, params.newIndex, params.stack);
    let url = params.stack[ params.newIndex ].url;
    $.ajax({
      url: '/dk-file-manager/file/sort',
      type: 'POST',
      data: JSON.stringify({
        id: url.substring(url.lastIndexOf('=') + 1),
        name: params.stack[ params.newIndex ].caption,
        sort: params.newIndex,
      }),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
    }).done(function () {
      console.log('Done.')
    });
  });
});
