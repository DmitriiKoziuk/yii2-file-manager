$(document).ready(function () {
  let saveLocationId  = '#save-location-alias';
  let form = $('.upload-file-form');
  let configId = $(form).data('krajee-fileinput');
  let name = $(form).data('name') + '[locationAlias]';
  let fileInputConfig = window[configId];
  fileInputConfig.uploadExtraData[name] = $(saveLocationId).val();

  $(saveLocationId).on('change', function () {
    fileInputConfig.uploadExtraData[name] = $(saveLocationId).val();
  });
});
