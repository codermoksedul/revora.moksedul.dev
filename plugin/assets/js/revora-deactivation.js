jQuery(document).ready(function($) {
    var $modal = $('#revora-deactivation-modal');
    if (!$modal.length) return;

    var deactivateUrl = '';

    // Intercept deactivation link
    $(document).on('click', '#the-list [data-slug="revora"] a.edit[aria-label*="Deactivate"], #the-list [data-slug="revora"] span.deactivate a', function(e) {
        e.preventDefault();
        deactivateUrl = $(this).attr('href');
        $modal.show();
    });

    // Handle "Other" reason toggle
    $('input[name="reason"]').on('change', function() {
        if ($(this).val() === 'other') {
            $('.revora-other-reason').slideDown();
        } else {
            $('.revora-other-reason').slideUp();
        }
    });

    // Skip deactivation survey
    $('#revora-deactivate-skip').on('click', function() {
        window.location.href = deactivateUrl;
    });

    // Close modal if clicking outside container
    $modal.on('click', function(e) {
        if ($(e.target).is($modal)) {
            $modal.hide();
        }
    });

    // Handle form submission
    $('#revora-deactivation-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $('#revora-deactivate-submit');
        var $spinner = $btn.find('.revora-spinner');
        var $text = $btn.find('.revora-btn-text');

        $btn.prop('disabled', true);
        $spinner.show();
        $text.css('opacity', '0.5');

        var data = {
            action: 'revora_submit_deactivation_feedback',
            nonce: revoraDeactivation.nonce,
            reason: $('input[name="reason"]:checked').val(),
            details: $('textarea[name="details"]').val()
        };

        $.post(revoraDeactivation.ajax_url, data, function() {
            window.location.href = deactivateUrl;
        }).fail(function() {
            // Even if it fails, we should deactivate to not block the user
            window.location.href = deactivateUrl;
        });
    });
});
