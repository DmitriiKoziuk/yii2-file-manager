$(document).ready(function () {
  $('.upload-file-form').on('filesorted', function(event, params) {
    console.log('File sorted ', params.previewId, params.oldIndex, params.newIndex, params.stack);
    $.ajax({
      url: '/dk-file-manager/file/sort',
      type: 'POST',
      data: JSON.stringify({
        id: params.stack[ params.newIndex ].key,
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
