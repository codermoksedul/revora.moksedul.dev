jQuery(document).ready(function($) {
    'use strict';

    // Frontend Half & Full Star Interactive Picker
    function updateFrontendStars($picker, rating) {
        $picker.find('.revora-star-btn').each(function() {
            var starIndex = parseFloat($(this).data('rating'));
            $(this).removeClass('fill-1 active');
            if (rating >= starIndex) {
                $(this).text('star').addClass('fill-1 active');
            } else if (rating >= (starIndex - 0.5)) {
                $(this).text('star_half').addClass('fill-1 active');
            } else {
                $(this).text('star').removeClass('fill-1 active');
            }
        });
        $picker.find('.revora-rating-score-badge').text(rating.toFixed(1) + ' / 5.0');
    }

    $(document).on('mousemove', '.revora-frontend-rating-picker .revora-star-btn', function(e) {
        var starIndex = parseFloat($(this).data('rating'));
        var offset = $(this).offset();
        var width = $(this).outerWidth();
        var x = e.pageX - offset.left;
        var hoverRating = (x < width / 2) ? (starIndex - 0.5) : starIndex;
        var $picker = $(this).closest('.revora-frontend-rating-picker');
        updateFrontendStars($picker, hoverRating);
    });

    $(document).on('mouseleave', '.revora-frontend-rating-picker', function() {
        var $picker = $(this);
        var curRating = parseFloat($picker.attr('data-rating') || $picker.find('.revora-frontend-rating-val').val() || 5.0);
        updateFrontendStars($picker, curRating);
    });

    $(document).on('click', '.revora-frontend-rating-picker .revora-star-btn', function(e) {
        var starIndex = parseFloat($(this).data('rating'));
        var offset = $(this).offset();
        var width = $(this).outerWidth();
        var x = e.pageX - offset.left;
        var selRating = (x < width / 2) ? (starIndex - 0.5) : starIndex;
        var $picker = $(this).closest('.revora-frontend-rating-picker');
        $picker.attr('data-rating', selRating);
        updateFrontendStars($picker, selRating);
        $picker.find('.revora-frontend-rating-val').val(selRating.toFixed(1));
    });

    // Revora Success Popup Modal Handler
    function showSuccessModal(message) {
        var $modal = $('#revora-success-modal-overlay');
        if ( ! $modal.length ) {
            var modalHtml = `
                <div id="revora-success-modal-overlay" class="revora-modal-overlay">
                    <div class="revora-success-modal">
                        <div class="revora-modal-icon-wrap">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                        <h3 class="revora-modal-title">Thank You!</h3>
                        <p class="revora-modal-desc" id="revora-modal-desc-text"></p>
                        <button type="button" class="revora-modal-close-btn" id="revora-modal-done-btn">Got It</button>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
            $modal = $('#revora-success-modal-overlay');
            
            $modal.on('click', function(e) {
                if ( $(e.target).is('#revora-success-modal-overlay') || $(e.target).is('#revora-modal-done-btn') ) {
                    $modal.removeClass('active');
                }
            });
            $(document).on('keydown', function(e) {
                if ( e.key === 'Escape' && $modal.hasClass('active') ) {
                    $modal.removeClass('active');
                }
            });
        }
        
        $modal.find('#revora-modal-desc-text').html(message || 'Your review has been submitted and is awaiting moderation.');
        setTimeout(function() {
            $modal.addClass('active');
        }, 50);
    }

    /**
     * AJAX Review Submission
     */
    $(document).on('submit', '#revora-review-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('.revora-submit-btn');
        const originalBtnText = $submitBtn.find('.btn-text').text();
        const $message = $form.find('.revora-form-message');
        const formData = new FormData(this);

        formData.append('action', 'revora_submit');

        $submitBtn.prop('disabled', true);
        $submitBtn.find('.btn-text').text('Submitting...');
        $submitBtn.find('.revora-spinner').show();
        $message.hide().removeClass('success error');

        $.ajax({
            url: revora_vars.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $message.hide();
                    $form[0].reset();
                    // Reset star rating picker
                    var $picker = $form.find('.revora-frontend-rating-picker');
                    $picker.attr('data-rating', '5.0');
                    $picker.find('.revora-frontend-rating-val').val('5.0');
                    updateFrontendStars($picker, 5.0);
                    
                    // Show Modern Success Popup
                    showSuccessModal(response.data.message);
                } else {
                    $message.addClass('error').html(response.data.message || 'Error occurred.').fadeIn();
                }
            },
            error: function() {
                $message.addClass('error').text('Server error. Please try again later.').fadeIn();
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $submitBtn.find('.btn-text').text(originalBtnText);
                $submitBtn.find('.revora-spinner').hide();
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
        const form_id = parseInt($container.data('form-id')) || 0;
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
                nonce: revora_vars.nonce,
                form_id: form_id,
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
    /**
     * Initialize Swiper Sliders
     */
    function initRevoraSliders() {
        // Standard Reviews Slider
        $('.revora-slider-widget-container').each(function() {
            const $container = $(this);
            const $slider = $container.find('.revora-reviews-slider');
            const settings = $container.data('slider-settings');

            if (!$slider.length || !settings) return;
            if ($slider.hasClass('swiper-initialized')) return;

            const swiperOptions = {
                slidesPerView: settings.slidesPerView || 1,
                slidesPerGroup: settings.slidesToScroll || 1,
                spaceBetween: settings.spaceBetween || 24,
                loop: settings.loop,
                speed: settings.speed || 500,
                effect: settings.effect || 'slide',
                autoplay: settings.autoplay ? {
                    delay: settings.autoplaySpeed || 3000,
                    disableOnInteraction: false,
                    pauseOnHover: settings.pauseOnHover
                } : false,
                navigation: settings.showArrows ? {
                    nextEl: $container.find('.swiper-button-next')[0],
                    prevEl: $container.find('.swiper-button-prev')[0],
                } : false,
                pagination: settings.showPagination ? {
                    el: $container.find('.swiper-pagination')[0],
                    type: settings.paginationType || 'bullets',
                    clickable: true
                } : false,
                breakpoints: {
                    // Mobile
                    320: {
                        slidesPerView: settings.slidesToShowMobile || 1,
                        spaceBetween: settings.spaceBetweenMobile || 16
                    },
                    // Tablet
                    768: {
                        slidesPerView: settings.slidesToShowTablet || 2,
                        spaceBetween: settings.spaceBetweenTablet || 20
                    },
                    // Desktop
                    1024: {
                        slidesPerView: settings.slidesPerView || 3,
                        spaceBetween: settings.spaceBetween || 24
                    }
                }
            };

            new Swiper($slider[0], swiperOptions);
        });

        // Featured Review Slider / Loop
        $('.revora-featured-widget-wrap').each(function() {
            const $container = $(this);
            const $slider = $container.find('.revora-featured-slider');
            const settings = $container.data('slider-settings');

            if (!$slider.length || !settings) return;
            if ($slider.hasClass('swiper-initialized')) return;

            const swiperOptions = {
                slidesPerView: 1,
                spaceBetween: settings.spaceBetween || 20,
                loop: settings.loop || false,
                speed: settings.speed || 600,
                effect: settings.effect || 'fade',
                fadeEffect: {
                    crossFade: true
                },
                autoplay: settings.autoplay ? {
                    delay: settings.autoplaySpeed || 5000,
                    disableOnInteraction: false,
                    pauseOnHover: settings.pauseOnHover
                } : false,
                navigation: settings.showArrows ? {
                    nextEl: $container.find('.swiper-button-next')[0],
                    prevEl: $container.find('.swiper-button-prev')[0],
                } : false,
                pagination: settings.showPagination ? {
                    el: $container.find('.swiper-pagination')[0],
                    clickable: true
                } : false
            };

            new Swiper($slider[0], swiperOptions);
        });
    }

    // Init on load
    initRevoraSliders();

    // Init in Elementor Editor
    $(window).on('elementor/frontend/init', function() {
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/revora_reviews_slider.default', function($scope) {
                initRevoraSliders();
            });
            elementorFrontend.hooks.addAction('frontend/element_ready/revora_featured_review.default', function($scope) {
                initRevoraSliders();
            });
        }
    });

    // Custom File Input Sync
    $(document).on('change', '.revora-file-input', function() {
        var $this = $(this);
        var fileName = $this.val().split('\\').pop();
        var $text = $this.siblings('.revora-file-fake').find('.revora-file-text');
        
        if (fileName) {
            $text.text(fileName);
        } else {
            // Revert to placeholder if no file
            var originalText = $text.data('placeholder');
            if (!originalText) {
                originalText = 'Choose file...';
                $text.data('placeholder', originalText);
            }
            $text.text(originalText);
        }
    });
    
    // Store original placeholder on load
    $('.revora-file-text').each(function() {
        $(this).data('placeholder', $(this).text());
    });
});
