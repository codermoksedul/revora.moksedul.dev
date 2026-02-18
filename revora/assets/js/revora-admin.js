jQuery(document).ready(function($) {
    function updateStars(rating) {
        $('.revora-rating-selector .dashicons').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }

    // Star Rating Click Handler
    $('.revora-rating-selector .dashicons').on('click', function() {
        var rating = $(this).data('rating');
        $('#rating_input').val(rating);
        updateStars(rating);
    });

    // Initialize stars on page load
    var initialRating = $('#rating_input').val();
    if (initialRating) {
        updateStars(parseInt(initialRating));
    }

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
});
