jQuery(document).ready(function($) {
    // Function to render stars based on numeric rating (supports halves)
    function renderSelectorStars($selector, rating) {
        $selector.find('.revora-star-icon, .material-symbols-outlined, .dashicons').each(function() {
            var starIndex = parseFloat($(this).data('rating'));
            $(this).removeClass('active fill-1');
            if (rating >= starIndex) {
                $(this).text('star').addClass('active fill-1');
            } else if (rating >= (starIndex - 0.5)) {
                $(this).text('star_half').addClass('active fill-1');
            } else {
                $(this).text('star').removeClass('active fill-1');
            }
        });
        $selector.find('.revora-rating-value-display').text(rating.toFixed(1));
    }

    // Hover effect for half and full stars
    $(document).on('mousemove', '.revora-rating-selector .revora-star-icon, .revora-rating-selector .material-symbols-outlined, .revora-rating-selector .dashicons', function(e) {
        var starIndex = parseFloat($(this).data('rating'));
        var offset = $(this).offset();
        var width = $(this).outerWidth();
        var x = e.pageX - offset.left;
        var hoverRating = (x < width / 2) ? (starIndex - 0.5) : starIndex;
        var $selector = $(this).closest('.revora-rating-selector');
        renderSelectorStars($selector, hoverRating);
    });

    $(document).on('mouseleave', '.revora-rating-selector', function() {
        var $selector = $(this);
        var currentRating = parseFloat($selector.attr('data-rating') || $selector.find('input[name="rating"]').val() || 5);
        renderSelectorStars($selector, currentRating);
    });

    // Click handler for half and full stars
    $(document).on('click', '.revora-rating-selector .revora-star-icon, .revora-rating-selector .material-symbols-outlined, .revora-rating-selector .dashicons', function(e) {
        var starIndex = parseFloat($(this).data('rating'));
        var offset = $(this).offset();
        var width = $(this).outerWidth();
        var x = e.pageX - offset.left;
        var selectedRating = (x < width / 2) ? (starIndex - 0.5) : starIndex;
        var $selector = $(this).closest('.revora-rating-selector');
        $selector.attr('data-rating', selectedRating);
        renderSelectorStars($selector, selectedRating);
        $selector.find('input[name="rating"], .revora-rating-input').val(selectedRating);
        $selector.closest('form').find('input[name="rating"]').val(selectedRating);
    });

    // Quick Edit Logic
    $(document).on('click', '.revora-quick-edit-trigger', function(e) {
        e.preventDefault();
        var $row = $(this).closest('tr');
        var id = $(this).data('id');
        
        if ($('#revora-quick-edit-' + id).length) return;

        var status = $row.find('.revora-inline-status').val();
        var rating = $row.find('.revora-admin-stars .star-filled').length;
        
        var template = $('#revora-quick-edit-template').html();
        template = template.replace(/{{id}}/g, id)
                          .replace(/{{rating}}/g, rating)
                          .replace('{{status_' + status + '}}', 'selected');
        
        // Clean remaining placeholders
        template = template.replace(/{{status_\w+}}/g, '');

        var $quickRow = $(template);
        $row.after($quickRow);

        // Initialize stars in quick edit
        var $stars = $quickRow.find('.revora-rating-selector .dashicons');
        $quickRow.find('.revora-rating-selector .dashicons').each(function() {
            if ($(this).data('rating') <= rating) $(this).addClass('active');
        });

        $stars.on('click', function() {
            var r = $(this).data('rating');
            $(this).parent().next('input').val(r);
            $stars.removeClass('active');
            $(this).addClass('active').prevAll().addClass('active');
        });
    });

    $(document).on('click', '.revora-quick-cancel', function() {
        $(this).closest('.revora-quick-row').remove();
    });

    $(document).on('click', '.revora-quick-save', function() {
        var $row = $(this).closest('.revora-quick-row');
        var $originalRow = $row.prev();
        var data = $row.find('form').serialize();
        
        $row.addClass('loading');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data + '&action=revora_quick_edit&nonce=' + revora_admin.nonce,
            success: function(response) {
                if (response.success) {
                    location.reload(); // Simplest way to update the table correctly with all relationships
                } else {
                    alert(response.data || 'Update failed');
                }
            },
            error: function() {
                alert('Connection error');
            },
            complete: function() {
                $row.removeClass('loading');
            }
        });
    });

    // Inline Status Dropdown Logic
    $(document).on('change', '.revora-inline-status', function() {
        var $select = $(this);
        var $col = $select.closest('.revora-status-col');
        var id = $select.data('id');
        var status = $select.val();

        $col.addClass('loading');

        // Update class immediately for visual feedback
        $select.removeClass('status-pending status-approved status-rejected')
               .addClass('status-' + status);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'revora_quick_edit',
                review_id: id,
                status: status,
                nonce: revora_admin.nonce
            },
            success: function(response) {
                if (!response.success) {
                    alert(response.data || 'Status update failed');
                }
            },
            error: function() {
                alert('Connection error');
            },
            complete: function() {
                $col.removeClass('loading');
            }
        });
    });

    // WP Media Uploader for Brand Logo
    $(document).on('click', '#revora-upload-logo-btn', function(e) {
        e.preventDefault();
        var customUploader = wp.media({
            title: 'Select Brand Logo',
            button: { text: 'Use this logo' },
            multiple: false
        }).on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            $('#revora_website_logo').val(attachment.url);
            $('#revora-logo-preview').html('<img src="' + attachment.url + '" style="max-width:100%; max-height:100%; object-fit:contain;" />');
            $('#revora-remove-logo-btn').show();
        }).open();
    });

    $(document).on('click', '#revora-remove-logo-btn', function(e) {
        e.preventDefault();
        $('#revora_website_logo').val('');
        $('#revora-logo-preview').html('<span class="material-symbols-outlined" style="color:#94a3b8; font-size:24px;">image</span>');
        $(this).hide();
    });
});
