jQuery(document).ready(function($) {
    'use strict';

    /**
     * AJAX Review Submission
     */
    $('#revora-submission-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('.revora-submit-btn');
        const $spinner = $form.find('.revora-spinner');
        const $message = $form.find('.revora-form-message');
        const formData = new FormData(this);

        // Security check
        if (!revora_vars.nonce) {
            console.error('Revora: Nonce missing.');
            return;
        }

        // Add action and nonce to formData if not already there
        // (They should be in hidden fields, but let's be safe)
        formData.append('action', 'revora_submit');
        formData.append('nonce', revora_vars.nonce);

        // UI Feedback: Loading state
        $submitBtn.prop('disabled', true);
        $spinner.show();
        $message.hide().removeClass('success error');

        $.ajax({
            url: revora_vars.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $message.addClass('success').text(response.data.message).fadeIn();
                    $form[0].reset();
                    // Reset star selection
                    $form.find('input[name="rating"]').prop('checked', false);
                } else {
                    $message.addClass('error').text(response.data.message || 'Error occurred.').fadeIn();
                }
            },
            error: function(xhr, status, error) {
                $message.addClass('error').text('Server error. Please try again later.').fadeIn();
                console.error('Revora AJAX Error:', error);
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $spinner.hide();
            }
        });
    });

    /**
     * Simple star rating interaction enhancement (optional)
     * The CSS handles the hover/check, but we could add JS if needed.
     */
});
