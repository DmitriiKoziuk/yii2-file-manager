$(document).ready(function () {
  var saveLocationId  = '#save-location-alias';
  var configId        = $('#upload-file-form').data('krajee-fileinput');
  var fileInputConfig = window[configId];
  var name            = 'UploadFileData[saveLocationAlias]';
  fileInputConfig.uploadExtraData[name] = $(saveLocationId).val();

  $(saveLocationId).on('change', function () {
    fileInputConfig.uploadExtraData[name] = $(saveLocationId).val();
  });
});