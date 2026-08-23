jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize International Telephone Input
    var itiInstances = [];
    $('.revora-form-container input[type="tel"], .revora-form-container input[name="phone"], .revora-form-container input[name="number"]').each(function() {
        $(this).attr('type', 'tel'); // Ensure type is tel for better mobile keyboard
        $(this).removeAttr('placeholder'); // Remove default or user-set placeholder to prevent confusing overlap
        
        // Restrict input to numbers only
        $(this).on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        if (typeof window.intlTelInput === 'function') {
            var iti = window.intlTelInput(this, {
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.21/js/utils.js",
                initialCountry: "bd",
                separateDialCode: true,
                autoPlaceholder: "off"
            });
            itiInstances.push({ input: this, iti: iti });
            
            // Format on blur
            $(this).on('blur', function() {
                if (iti.isValidNumber()) {
                    $(this).val(iti.getNumber());
                }
            });
        }
    });

    // Frontend Half & Full Star Interactive Picker
    function updateFrontendStars($picker, rating, isHover = false) {
        $picker.find('.revora-star-btn').each(function() {
            var starIndex = parseFloat($(this).data('rating'));
            $(this).removeClass('fill-1 active hover-active');
            
            if (rating >= starIndex) {
                $(this).text('star').addClass(isHover ? 'hover-active fill-1' : 'active fill-1');
            } else if (rating >= (starIndex - 0.5)) {
                $(this).text('star_half').addClass(isHover ? 'hover-active fill-1' : 'active fill-1');
            } else {
                $(this).text('star').removeClass('fill-1 active hover-active');
            }
        });
        
        if (!isHover) {
            $picker.find('.revora-rating-score-badge').text(rating.toFixed(1) + ' / 5.0');
        }
    }

    $(document).on('mousemove', '.revora-frontend-rating-picker .revora-star-btn', function(e) {
        var starIndex = parseFloat($(this).data('rating'));
        var offset = $(this).offset();
        var width = $(this).outerWidth();
        var x = e.pageX - offset.left;
        var hoverRating = (x < width / 2) ? (starIndex - 0.5) : starIndex;
        var $picker = $(this).closest('.revora-frontend-rating-picker');
        updateFrontendStars($picker, hoverRating, true);
    });

    $(document).on('mouseleave', '.revora-frontend-rating-picker', function() {
        var $picker = $(this);
        var curRating = parseFloat($picker.attr('data-rating') || $picker.find('.revora-frontend-rating-val').val() || 0);
        updateFrontendStars($picker, curRating, false);
    });

    $(document).on('click', '.revora-frontend-rating-picker .revora-star-btn', function(e) {
        var starIndex = parseFloat($(this).data('rating'));
        var offset = $(this).offset();
        var width = $(this).outerWidth();
        var x = e.pageX - offset.left;
        var selRating = (x < width / 2) ? (starIndex - 0.5) : starIndex;
        var $picker = $(this).closest('.revora-frontend-rating-picker');
        $picker.attr('data-rating', selRating);
        updateFrontendStars($picker, selRating, false);
        $picker.find('.revora-frontend-rating-val').val(selRating.toFixed(1));
    });

    // Revora Success Popup Modal Handler
    function showSuccessModal(data, avatarDataUrl) {
        var message = typeof data === 'string' ? data : data.message;
        var hasShareCard = data.enable_share_card === true;
        
        var $modal = $('#revora-success-modal-overlay');
        if ( ! $modal.length ) {
            var modalHtml = `
                <div id="revora-success-modal-overlay" class="revora-modal-overlay">
                    <div class="revora-success-modal" style="position:relative;">
                        <span class="material-symbols-outlined revora-success-close-icon" id="revora-modal-close-icon" style="position:absolute; top:15px; right:15px; cursor:pointer; color:#64748b;">close</span>
                        <div class="revora-modal-icon-wrap">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                        <h3 class="revora-modal-title">Thank You!</h3>
                        <p class="revora-modal-desc" id="revora-modal-desc-text"></p>
                        <div class="revora-modal-actions" style="margin-top:20px;">
                            <button type="button" class="revora-modal-share-btn" id="revora-modal-share-btn" style="display:none; width:100%; background:#f1f5f9; color:#0f172a; font-weight:600; padding:12px; border-radius:8px; border:1px solid #e2e8f0; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;"><span class="material-symbols-outlined" style="font-size:20px;">ios_share</span> Share Card</button>
                        </div>
                    </div>
                </div>
                
                <div id="revora-share-card-modal" class="revora-modal-overlay">
                    <div class="revora-share-card-modal-content">
                        <div id="revora-share-card-canvas" class="revora-share-card-canvas">
                            <div class="revora-share-card-bg">
                                <div class="revora-share-card-glow"></div>
                                <div class="revora-share-card-inner">
                                    <div class="revora-share-card-avatar" id="revora-share-avatar"><img id="revora-share-avatar-fallback" src="" alt="" style="display:none; width:55%; height:55%; object-fit:contain;"></div>
                                    <h3 class="revora-share-card-name" id="revora-share-name"></h3>
                                    <div class="revora-share-card-stars" id="revora-share-stars"></div>
                                    <p class="revora-share-card-footer" id="revora-share-content"></p>
                                    <div class="revora-share-card-brand" id="revora-share-brand">
                                        <span class="material-symbols-outlined" style="font-size:16px; color:#10b981;">verified</span>
                                        <span id="revora-share-brand-name"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="revora-download-card-btn" id="revora-download-card-btn" style="display:flex; align-items:center; justify-content:center; gap:8px;"><span class="material-symbols-outlined" style="font-size:20px;">download</span> Download Card</button>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
            $modal = $('#revora-success-modal-overlay');
            
            $modal.on('click', function(e) {
                if ( $(e.target).is('#revora-success-modal-overlay') || $(e.target).closest('#revora-modal-close-icon').length ) {
                    $modal.removeClass('active');
                }
            });
            $(document).on('keydown', function(e) {
                if ( e.key === 'Escape' && $modal.hasClass('active') ) {
                    $modal.removeClass('active');
                    $('#revora-share-card-modal').removeClass('active');
                }
            });
            
            $('#revora-modal-share-btn').on('click', function() {
                $modal.removeClass('active');
                $('#revora-share-card-modal').addClass('active');
            });
            

            
            $('#revora-share-card-modal').on('click', function(e) {
                if ( $(e.target).is('#revora-share-card-modal') ) {
                    $(this).removeClass('active');
                }
            });
            
            $('#revora-download-card-btn').on('click', function() {
                var $btn = $(this);
                var originalHtml = $btn.html();
                $btn.html('<span class="material-symbols-outlined" style="font-size:20px;">hourglass_empty</span> Generating...').prop('disabled', true);
                
                // Dynamically load html2canvas if not loaded
                if (typeof html2canvas === 'undefined') {
                    $.getScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', function() {
                        generateAndDownloadCard($btn, originalHtml);
                    });
                } else {
                    generateAndDownloadCard($btn, originalHtml);
                }
            });
            
            function generateAndDownloadCard($btn, originalHtml) {
                var canvasNode = document.getElementById('revora-share-card-canvas');
                html2canvas(canvasNode, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: null
                }).then(function(canvas) {
                    var link = document.createElement('a');
                    link.download = 'share-card.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                    $btn.html(originalHtml).prop('disabled', false);
                });
            }
        }
        
        $modal.find('#revora-modal-desc-text').html(message || 'Your review has been submitted and is awaiting moderation.');
        
        if (hasShareCard) {
            $('#revora-modal-share-btn').show();
            $('#revora-share-name').text(data.reviewer_name);
            
            // Dynamic SVG Dot Grid Pattern for html2canvas compatibility
            var primaryColor = data.primary_color || '#2563eb';
            var encodedColor = encodeURIComponent(primaryColor);
            var svgPattern = "data:image/svg+xml,%3Csvg width='20' height='20' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='2' cy='2' r='1.5' fill='" + encodedColor + "' opacity='0.03' /%3E%3C/svg%3E";
            $('.revora-share-card-bg').css('background-image', 'url("' + svgPattern + '")');
            $('.revora-share-card-glow').css('background', 'linear-gradient(180deg, ' + primaryColor + ' 0%, rgba(255,255,255,0) 100%)');
            
            // Populate Brand Info
            if (data.website_name) {
                $('#revora-share-brand-name').html('<span style="color:#64748b; font-weight:500;">Verified Review on</span> <strong style="color:var(--revora-primary, #2563eb); font-weight:700; margin-left:4px;">' + data.website_name + '</strong>');
            } else {
                $('#revora-share-brand').hide();
            }
            
            // Generate Stars
            var rating = parseFloat(data.rating) || 5;
            var starsHtml = '';
            for(var i=1; i<=5; i++) {
                if (rating >= i) {
                    starsHtml += '<span class="revora-share-star filled">★</span>';
                } else if (rating >= i - 0.5) {
                    starsHtml += '<span class="revora-share-star half">★</span>';
                } else {
                    starsHtml += '<span class="revora-share-star empty">★</span>';
                }
            }
            $('#revora-share-stars').html(starsHtml);
            
            // Add Review Content
            var reviewText = data.content || '';
            if (reviewText.length > 90) {
                reviewText = reviewText.substring(0, 90) + '...';
            }
            if (reviewText) {
                $('#revora-share-content').text('"' + reviewText + '"');
            } else {
                $('#revora-share-content').text('"Highly Recommended!"');
            }
            if (avatarDataUrl) {
                $('#revora-share-avatar').css({'background-image': 'url(' + avatarDataUrl + ')', 'background-size': 'cover'});
            } else if (data.avatar_url) {
                $('#revora-share-avatar').css({'background-image': 'url(' + data.avatar_url + ')', 'background-size': 'cover'});
            } else {
                var inlineSvg = '<svg viewBox="0 0 482.9 482.9" xmlns="http://www.w3.org/2000/svg" style="width:60%;height:60%;flex-shrink:0;"><path fill="#cbd5e1" d="M239.7,260.2c0.5,0,1,0,1.6,0c0.2,0,0.4,0,0.6,0c0.3,0,0.7,0,1,0c29.3-0.5,53-10.8,70.5-30.5c38.5-43.4,32.1-117.8,31.4-124.9c-2.5-53.3-27.7-78.8-48.5-90.7C280.8,5.2,262.7,0.4,242.5,0h-0.7c-0.1,0-0.3,0-0.4,0h-0.6c-11.1,0-32.9,1.8-53.8,13.7c-21,11.9-46.6,37.4-49.1,91.1c-0.7,7.1-7.1,81.5,31.4,124.9C186.7,249.4,210.4,259.7,239.7,260.2z M239.7,260.2 M164.6,107.3c0-0.3,0.1-0.6,0.1-0.8c3.3-71.7,54.2-79.4,76-79.4h0.4c0.2,0,0.5,0,0.8,0c27,0.6,72.9,11.6,76,79.4c0,0.3,0,0.6,0.1,0.8c0.1,0.7,7.1,68.7-24.7,104.5c-12.6,14.2-29.4,21.2-51.5,21.4c-0.2,0-0.3,0-0.5,0l0,0c-0.2,0-0.3,0-0.5,0c-22-0.2-38.9-7.2-51.4-21.4C157.7,176.2,164.5,107.9,164.6,107.3z"/><path fill="#cbd5e1" d="M446.8,383.6c0-0.1,0-0.2,0-0.3c0-0.8-0.1-1.6-0.1-2.5c-0.6-19.8-1.9-66.1-45.3-80.9c-0.3-0.1-0.7-0.2-1-0.3c-45.1-11.5-82.6-37.5-83-37.8c-6.1-4.3-14.5-2.8-18.8,3.3c-4.3,6.1-2.8,14.5,3.3,18.8c1.7,1.2,41.5,28.9,91.3,41.7c23.3,8.3,25.9,33.2,26.6,56c0,0.9,0,1.7,0.1,2.5c0.1,9-0.5,22.9-2.1,30.9c-16.2,9.2-79.7,41-176.3,41c-96.2,0-160.1-31.9-176.4-41.1c-1.6-8-2.3-21.9-2.1-30.9c0-0.8,0.1-1.6,0.1-2.5c0.7-22.8,3.3-47.7,26.6-56c49.8-12.8,89.6-40.6,91.3-41.7c6.1-4.3,7.6-12.7,3.3-18.8c-4.3-6.1-12.7-7.6-18.8-3.3c-0.4,0.3-37.7,26.3-83,37.8c-0.4,0.1-0.7,0.2-1,0.3c-43.4,14.9-44.7,61.2-45.3,80.9c0,0.9,0,1.7-0.1,2.5c0,0.1,0,0.2,0,0.3c-0.1,5.2-0.2,31.9,5.1,45.3c1,2.6,2.8,4.8,5.2,6.3c3,2,74.9,47.8,195.2,47.8s192.2-45.9,195.2-47.8c2.3-1.5,4.2-3.7,5.2-6.3C447,415.5,446.9,388.8,446.8,383.6z"/></svg>';
                $('#revora-share-avatar').html(inlineSvg).css({'display': 'flex', 'align-items': 'center', 'justify-content': 'center', 'overflow': 'hidden'});
                $('#revora-share-avatar-fallback').hide();
            }
        } else {
            $('#revora-modal-share-btn').hide();
        }
        
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
        
        // Manual Validation for Rating
        var $ratingInput = $form.find('.revora-frontend-rating-val');
        if ( $ratingInput.length && $ratingInput.attr('required') && parseFloat($ratingInput.val()) === 0 ) {
            $message.html('<span class="material-symbols-outlined">error</span> Please select a star rating.').removeClass('success').addClass('error').fadeIn();
            return;
        }

        // Manual Validation for Phone Number
        var isValidPhone = true;
        itiInstances.forEach(function(instance) {
            var val = $(instance.input).val().trim();
            if (val !== "") {
                if (!instance.iti.isValidNumber()) {
                    isValidPhone = false;
                } else {
                    // Update input with full international format before submitting
                    $(instance.input).val(instance.iti.getNumber());
                }
            }
        });
        
        if (!isValidPhone) {
            $message.html('<span class="material-symbols-outlined">error</span> Please enter a valid phone number.').removeClass('success').addClass('error').fadeIn();
            return;
        }

        // Manual Validation for File Size (1MB Limit)
        var isFileSizeValid = true;
        $form.find('input[type="file"]').each(function() {
            if (this.files && this.files.length > 0) {
                if (this.files[0].size > 1048576) {
                    isFileSizeValid = false;
                }
            }
        });
        
        if (!isFileSizeValid) {
            $message.html('<span class="material-symbols-outlined">error</span> File size exceeds the 1MB limit. Please choose a smaller image.').removeClass('success').addClass('error').fadeIn();
            return;
        }

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
                    
                    var avatarDataUrl = '';
                    var $avatarInput = $form.find('input[type="file"][name="profile_image"], input[type="file"][name="avatar"]');
                    if ( $avatarInput.length && $avatarInput[0].files && $avatarInput[0].files.length > 0 ) {
                        avatarDataUrl = URL.createObjectURL($avatarInput[0].files[0]);
                    }
                    
                    $form[0].reset();
                    // Reset custom file input text
                    $form.find('.revora-file-text').each(function() {
                        var originalText = $(this).data('placeholder') || 'Choose file...';
                        $(this).text(originalText);
                    });
                    
                    // Reset star rating picker
                    var $picker = $form.find('.revora-frontend-rating-picker');
                    $picker.attr('data-rating', '0');
                    $picker.find('.revora-frontend-rating-val').val('0');
                    updateFrontendStars($picker, 0);
                    
                    // Show Modern Success Popup with Share Card Data
                    showSuccessModal(response.data, avatarDataUrl);
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
        const load_more_limit = parseInt($container.data('load-more-limit')) || limit;
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
                initial_limit: limit,
                limit: load_more_limit,
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
        var $form = $this.closest('form');
        var $message = $form.find('.revora-form-message');
        
        // Immediate File Size Validation (1MB)
        if (this.files && this.files.length > 0) {
            if (this.files[0].size > 1048576) {
                $this.val(''); // Clear the input
                $message.html('<span class="material-symbols-outlined">error</span> File size exceeds the 1MB limit. Please choose a smaller image.').removeClass('success').addClass('error').fadeIn();
                
                // Reset fake text to placeholder
                var $text = $this.siblings('.revora-file-fake').find('.revora-file-text');
                var originalText = $text.data('placeholder') || 'Choose file...';
                $text.text(originalText);
                return;
            } else {
                // Clear any previous error if valid
                if ($message.hasClass('error') && $message.text().indexOf('File size') !== -1) {
                    $message.fadeOut();
                }
            }
        }
        
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
