jQuery(document).ready(function ($) {
    var file_frame;

    $('#lm-add-images').on('click', function (e) {
        e.preventDefault();

        // If the media frame already exists, reopen it.
        if (file_frame) {
            file_frame.open();
            return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select Images',
            button: {
                text: 'Add to Gallery'
            },
            multiple: true // Allow multiple images to be selected
        });

        // When images are selected, store them in the meta field.
        file_frame.on('select', function () {
            var selection = file_frame.state().get('selection');
            selection.map(function (attachment) {
                attachment = attachment.toJSON();
                var imageUrl = attachment.url;

                // Append the selected image to the gallery
                var imageHTML = '<div class="lm-image-item">';
                imageHTML += '<img src="' + imageUrl + '" width="100" height="100">';
                imageHTML += '<input type="hidden" name="lm_images[]" value="' + imageUrl + '">';
                imageHTML += '<button type="button" class="lm-remove-image">Remove</button>';
                imageHTML += '</div>';
                $('#lm-image-gallery').append(imageHTML);
            });
        });

        // Open the media frame.
        file_frame.open();
    });

    // Remove an image from the gallery
    $(document).on('click', '.lm-remove-image', function (e) {
        e.preventDefault();
        $(this).closest('.lm-image-item').remove();
    });
});
