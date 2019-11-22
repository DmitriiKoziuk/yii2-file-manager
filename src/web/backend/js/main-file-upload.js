$(document).ready(function () {
  let saveLocationId  = '#save-location-alias';
  let configId        = $('.upload-file-form').data('krajee-fileinput');
  let fileInputConfig = window[configId];
  let name            = 'UploadFileData[saveLocationAlias]';
  fileInputConfig.uploadExtraData[name] = $(saveLocationId).val();

  $(saveLocationId).on('change', function () {
    fileInputConfig.uploadExtraData[name] = $(saveLocationId).val();
  });
});
