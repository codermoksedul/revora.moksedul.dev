jQuery(document).ready(function($) {
    'use strict';

    // Star Rating Interaction
    $('.revora-star-rating .dashicons').on('mouseover', function() {
        var rating = $(this).data('rating');
        $(this).parent().find('.dashicons').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }).on('mouseout', function() {
        var currentRating = $('#revora_rating_val').val();
        $(this).parent().find('.dashicons').each(function() {
            if ($(this).data('rating') <= currentRating) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }).on('click', function() {
        var rating = $(this).data('rating');
        $('#revora_rating_val').val(rating);
    });

    // Initialize stars
    $('.revora-star-rating').each(function() {
        var initial = $(this).next('input').val() || 5;
        $(this).find('.dashicons').each(function() {
            if ($(this).data('rating') <= initial) {
                $(this).addClass('active');
            }
        });
    });

    /**
     * AJAX Review Submission
     */
    $('#revora-review-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('.revora-submit-btn');
        const $message = $('#revora-form-message');
        const formData = new FormData(this);

        formData.append('action', 'revora_submit');

        $submitBtn.prop('disabled', true).text('Submitting...');
        $message.hide().removeClass('success error');

        $.ajax({
            url: revora_vars.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $message.addClass('success').html(response.data.message).fadeIn();
                    $form[0].reset();
                    // Reset stars to 5
                    $('#revora_rating_val').val(5);
                    $('.revora-star-rating .dashicons').addClass('active');
                } else {
                    $message.addClass('error').html(response.data.message || 'Error occurred.').fadeIn();
                }
            },
            error: function() {
                $message.addClass('error').text('Server error. Please try again later.').fadeIn();
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Submit Review');
            }
        });
    });

    /**
     * Load More Reviews
     */
    $('.revora-load-more-btn').on('click', function() {
        const $btn = $(this);
        const $container = $btn.closest('.revora-reviews-container');
        const $grid = $container.find('.revora-reviews-grid');
        const category = $container.data('category') || '';
        const limit = parseInt($container.data('limit')) || 6;
        const card_style = $container.data('card-style') || 'classic';
        let page = parseInt($btn.data('page')) || 1;

        $btn.prop('disabled', true);
        $btn.find('.btn-text').text('Loading...');

        $.ajax({
            url: revora_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'revora_load_more',
                category: category,
                page: page,
                limit: limit,
                card_style: card_style
            },
            success: function(response) {
                if (response.success) {
                    $grid.append(response.data.html);
                    page++;
                    $btn.data('page', page);

                    if (!response.data.has_more) {
                        $btn.parent().fadeOut();
                    }
                } else {
                    $btn.parent().fadeOut();
                }
            },
            error: function() {
                alert('Error loading more reviews. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.btn-text').text('Load More Reviews');
            }
        });
    });
});
