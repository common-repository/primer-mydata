(function ($) {
    $(document).ready(function () {
        "use strict";

        if (typeof ajax_object !== 'undefined') {
            var ajaxURL = ajax_object.url;
            var upload_nonce = ajax_object.upload_nonce;
            var verify_file_type = ajax_object.verify_file_type;

            plupload.addFileFilter('min_width', function(maxwidth, file, cb) {
                var self = this, img = new o.Image();

                function finalize(result) {
                    // cleanup
                    img.destroy();
                    img = null;

                    // if rule has been violated in one way or another, trigger an error
                    if (!result) {
                        self.trigger('Error', {
                            code : plupload.IMAGE_DIMENSIONS_ERROR,
                            message : "Image width should be equals " + maxwidth  + " pixels.",
                            file : file
                        });
                    }
                    cb(result);
                }
                img.onload = function() {
                    // check if resolution cap is not exceeded
                    finalize(img.width == maxwidth);
                };
                img.onerror = function() {
                    finalize(false);
                };
                img.load(file.getSource());
            });
            plupload.addFileFilter('height', function(maxheight, file, cb) {
                var self = this, img = new o.Image();

                function finalize(result) {
                    // cleanup
                    img.destroy();
                    img = null;

                    // if rule has been violated in one way or another, trigger an error
                    if (!result) {
                        self.trigger('Error', {
                            code : plupload.IMAGE_DIMENSIONS_ERROR,
                            message : "Image height should be equals " + maxheight  + " pixels.",
                            file : file
                        });
                    }
                    cb(result);
                }
                img.onload = function() {
                    // check if resolution cap is not exceeded
                    finalize(img.height == maxheight);
                };
                img.onerror = function() {
                    finalize(false);
                };
                img.load(file.getSource());
            });

            var v_plupload = new plupload.Uploader({

                browse_button: 'select_user_profile_photo',
                file_data_name: 'file_data_name',
                container: 'profile_upload_container',
                multi_selection: false,
                url: ajaxURL + "?action=primer_user_picture_upload&verify_nonce=" + upload_nonce,
                filters: {
                    mime_types: [
                        {title: verify_file_type, extensions: "jpg,jpeg,png"}
                    ],
                    max_file_size: '75kb',
                    prevent_duplicates: true,
                    min_width: 350,
                    height: 100,
                }
            });

            v_plupload.init();

            v_plupload.bind('FilesAdded', function (up, files) {
                var thumbnail = "";
                plupload.each(files, function (file) {
                    thumbnail += '<div id="imageholder-' + file.id + '" class="thumb"><div class="avatar-wrapper">' + '' + '</div></div>';
                });

                document.getElementById('profile_photo').innerHTML = thumbnail;
                up.refresh();
                v_plupload.start();
            });

            v_plupload.bind('UploadProgress', function (up, file) {
                document.getElementById("imageholder-" + file.id).innerHTML = '<span>' + file.percent + "%</span>";
            });

            v_plupload.bind('Error', function (up, err) {
                document.getElementById('upload_errors').innerHTML += "<br/>" + "Error #" + err.code + ": " + err.message;
            });

            v_plupload.bind('FileUploaded', function (up, file, ajax_res) {
                var response = $.parseJSON(ajax_res.response);
                console.log(response)
                if (response.success) {
                    var profile_thumb = '<img src="' + response.url + '" alt="" />' +
                        '<input type="hidden" class="profile-pic-id" id="profile-pic-id" name="profile-pic-id" value="' + response.attachment_id + '"/>';
                    document.getElementById("imageholder-" + file.id).innerHTML = profile_thumb;
                } else {
                    alert(response.reason)
                    window.location = window.location.href
                }
            });
        }
    });
})(jQuery)
