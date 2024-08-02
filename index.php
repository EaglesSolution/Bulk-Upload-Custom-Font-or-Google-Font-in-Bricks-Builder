function enqueue_media_uploader() {
    global $post_type;

    if ('bricks_fonts' === $post_type) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'enqueue_media_uploader');
function add_inline_media_uploader_script() {
    global $post_type;

    if ('bricks_fonts' === $post_type) {
        ?>
       <script type="text/javascript">
        jQuery(document).ready(function($) {
            var file_frame;

            $('#upload_fonts_button').on('click', function(e) {
                e.preventDefault();

                if (file_frame) {
                    file_frame.open();
                    return;
                }

                file_frame = wp.media.frames.file_frame = wp.media({
                    title: 'Select Fonts',
                    button: {
                        text: 'Add Fonts'
                    },
                    multiple: true
                });

                file_frame.on('select', function() {
                    var attachments = file_frame.state().get('selection').map(function(attachment) {
                        return attachment.toJSON();
                    });

                    attachments.forEach(function(attachment) {
                        var extension = attachment.filename.split('.').pop().toLowerCase();
                        var fontUrl = attachment.url;
                        var filename = attachment.filename.split('.')[0];

                        // Use regex to extract weight and style
                        var weightStyleMatch = filename.match(/(\d{3}|thin|extralight|light|regular|medium|semibold|bold|extrabold|black)(italic)?/i);
                        var fontWeightStr = weightStyleMatch ? weightStyleMatch[1] : '400';
                        var isItalic = weightStyleMatch && weightStyleMatch[2];

                        var numericWeight = 400;

                        // Determine numeric weight based on string or numeric representation
                        switch (fontWeightStr.toLowerCase()) {
                            case 'thin':
                            case '100': numericWeight = 100; break;
                            case 'extralight':
                            case '200': numericWeight = 200; break;
                            case 'light':
                            case '300': numericWeight = 300; break;
                            case 'regular':
                            case '400': numericWeight = 400; break;
                            case 'medium':
                            case '500': numericWeight = 500; break;
                            case 'semibold':
                            case '600': numericWeight = 600; break;
                            case 'bold':
                            case '700': numericWeight = 700; break;
                            case 'extrabold':
                            case '800': numericWeight = 800; break;
                            case 'black':
                            case '900': numericWeight = 900; break;
                        }

                        // Determine font style based on the presence of "italic"
                        var fontStyle = isItalic ? 'italic' : '';

                        // Check if a variant with the same weight and style already exists
                        var existingVariant = Array.from(document.querySelectorAll(".bricks-font-variant")).find(function(variant) {
                            return variant.querySelector("[name=font_weight]").value == numericWeight && variant.querySelector("[name=font_style]").value == fontStyle;
                        });

                        var variant;
                        if (existingVariant) {
                            variant = existingVariant;
                        } else {
                            // Clone the last font variant template if no existing variant found
                            var lastVariant = document.querySelector(".bricks-font-variant:last-of-type");
                            variant = lastVariant.cloneNode(true);

                            variant.querySelector("[name=font_weight]").value = numericWeight;

                            // Set the value and selected attribute for font style
                            var fontStyleSelect = variant.querySelector("[name=font_style]");
                            fontStyleSelect.value = fontStyle;
                            var options = fontStyleSelect.options;
                            for (var i = 0; i < options.length; i++) {
                                if (options[i].value === fontStyle || (fontStyle === '' && options[i].value === 'normal')) {
                                    options[i].selected = true;
                                    break;
                                }
                            }

                            variant.querySelectorAll("input").forEach(function(input) {
                                input.value = "";
                            });

                            variant.querySelectorAll(".initialized").forEach(function(button) {
                                button.classList.remove("initialized");
                                if (button.classList.contains("upload")) {
                                    button.classList.remove("hide");
                                    button.id = Math.random().toString(36).replace(/[^a-z]+/g, "").substr(0, 12);
                                } else if (button.classList.contains("remove")) {
                                    button.classList.add("hide");
                                }
                            });

                            variant.querySelector(".pangram").removeAttribute("style");

                            lastVariant.after(variant);
                        }

                        // Update specific input fields based on file extension
                        variant.querySelectorAll('.font-face').forEach(function(fontFace) {
                            var placeholderText = fontFace.querySelector('input[name="font_url"]').getAttribute('placeholder');

                            if ((extension === 'ttf' && placeholderText.includes('TrueType Font')) ||
                                (extension === 'woff' && placeholderText.includes('Web Open Font Format')) ||
                                (extension === 'woff2' && placeholderText.includes('Web Open Font Format 2.0'))) {
                                fontFace.querySelector('input[name="font_url"]').value = fontUrl;
                                fontFace.querySelector('input[name="font_id"]').value = attachment.id;
                            }
                        });
                    });

                    bricksCustomFontsUpload();
                    bricksCustomFontsToggleEdit();
                    bricksCustomFontsDeleteVariant();
                });

                file_frame.open();
            });
        });
        </script>

        <?php
    }
}
add_action('admin_footer', 'add_inline_media_uploader_script', 999999999);
function allow_custom_font_mime_types($mime_types) {
    $mime_types['woff2'] = 'application/font-woff2';
    $mime_types['woff'] = 'application/font-woff';
    $mime_types['ttf'] = 'application/x-font-ttf';
    return $mime_types;
}
add_filter('upload_mimes', 'allow_custom_font_mime_types');
function add_bricks_fonts_metabox() {
    add_meta_box(
        'bricks_fonts_metabox',
        'Upload Multiple Fonts',
        'bricks_fonts_metabox_html',
        'bricks_fonts',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_bricks_fonts_metabox');
function bricks_fonts_metabox_html($post) {
    wp_nonce_field('save_bricks_fonts', 'bricks_fonts_nonce');
    $uploaded_fonts = get_post_meta($post->ID, '_bricks_fonts_uploaded_fonts', true);
    ?>
    <p>
        <button id="upload_fonts_button" class="button">Upload Fonts</button>
    </p>
    <p>
        <input type="hidden" id="uploaded_fonts" name="uploaded_fonts" value="<?php echo esc_attr($uploaded_fonts); ?>">
    </p>
    <?php
}
