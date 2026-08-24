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
                                <!-- Minimal & Clean Single Wave Shapes -->
                                <svg class="revora-share-card-bg-shapes" viewBox="0 0 380 500" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="position:absolute; inset:0; pointer-events:none; z-index:1;">
                                    <defs>
                                        <linearGradient id="revoraTopLeftGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#93c5fd" stop-opacity="0.25" />
                                            <stop offset="60%" stop-color="#bfdbfe" stop-opacity="0.1" />
                                            <stop offset="100%" stop-color="#eff6ff" stop-opacity="0" />
                                        </linearGradient>
                                        <linearGradient id="revoraBottomGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#93c5fd" stop-opacity="0.32" />
                                            <stop offset="50%" stop-color="#bfdbfe" stop-opacity="0.18" />
                                            <stop offset="100%" stop-color="#dbeafe" stop-opacity="0.06" />
                                        </linearGradient>
                                    </defs>
                                    <!-- Top Left Single Soft Curve -->
                                    <path d="M 0 0 L 0 90 C 35 75, 85 45, 160 0 Z" fill="url(#revoraTopLeftGrad)"/>
                                    <!-- Bottom Single Soft Elegant Wave -->
                                    <path d="M 0 455 C 70 440, 160 480, 380 468 L 380 500 L 0 500 Z" fill="url(#revoraBottomGrad)"/>
                                </svg>

                                <div class="revora-share-card-inner">
                                    <!-- Top Brand Logo -->
                                    <div class="revora-share-card-logo-wrap" id="revora-share-logo-wrap">
                                        <img src="" id="revora-share-brand-logo" alt="Logo" style="display:none;" />
                                        <span id="revora-share-brand-logo-text" style="display:none;"></span>
                                    </div>

                                    <!-- Profile Avatar -->
                                    <div class="revora-share-card-avatar" id="revora-share-avatar">
                                        <img id="revora-share-avatar-fallback" src="" alt="" style="display:none; width:55%; height:55%; object-fit:contain;">
                                    </div>

                                    <!-- Reviewer Name -->
                                    <h3 class="revora-share-card-name" id="revora-share-name"></h3>

                                    <!-- Star Ratings -->
                                    <div class="revora-share-card-stars" id="revora-share-stars"></div>

                                    <!-- Review Text -->
                                    <p class="revora-share-card-footer" id="revora-share-content"></p>

                                    <!-- Footer Verified Badge -->
                                    <div class="revora-share-card-brand" id="revora-share-brand">
                                        <span class="material-symbols-outlined revora-verified-icon">verified</span>
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
            
            // Close Success Modal ONLY on close icon click
            $(document).on('click', '#revora-modal-close-icon, .revora-success-close-icon', function(e) {
                e.stopPropagation();
                $('#revora-success-modal-overlay').removeClass('active');
            });
            
            $('#revora-modal-share-btn').on('click', function() {
                $('#revora-success-modal-overlay').removeClass('active');
                $('#revora-share-card-modal').addClass('active');
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

                    // Automatically close share card dialog after successful download
                    setTimeout(function() {
                        $('#revora-share-card-modal').removeClass('active');
                    }, 500);
                }).catch(function() {
                    $btn.html(originalHtml).prop('disabled', false);
                });
            }
        }
        
        $modal.find('#revora-modal-desc-text').html(message || 'Your review has been submitted and is awaiting moderation.');
        
        if (hasShareCard) {
            $('#revora-modal-share-btn').show();
            $('#revora-share-name').text(data.reviewer_name);
            
            var primaryColor = data.primary_color || '#2563eb';
            $('#revora-share-bottom-wave-primary').attr('fill', primaryColor);
            
            // Handle Brand Logo at Top
            if (data.website_logo) {
                $('#revora-share-brand-logo').attr('src', data.website_logo).show();
                $('#revora-share-brand-logo-text').hide();
                $('#revora-share-logo-wrap').show();
            } else if (data.website_name) {
                $('#revora-share-brand-logo').hide();
                $('#revora-share-brand-logo-text').text(data.website_name).show();
                $('#revora-share-logo-wrap').show();
            } else {
                $('#revora-share-logo-wrap').hide();
            }

            // Populate Brand Info Footer
            if (data.website_name) {
                $('#revora-share-brand-name').html('<span style="color:#64748b; font-weight:500;">Verified Review on</span> <strong style="color:' + primaryColor + '; font-weight:700; margin-left:4px;">' + data.website_name + '</strong>');
                $('#revora-share-brand').show();
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
            if (reviewText.length > 100) {
                reviewText = reviewText.substring(0, 100) + '...';
            }
            if (reviewText) {
                $('#revora-share-content').text('"' + reviewText + '"');
            } else {
                $('#revora-share-content').text('"Highly Recommended!"');
            }
            if (avatarDataUrl) {
                $('#revora-share-avatar').css({'background-image': 'url(' + avatarDataUrl + ')', 'background-size': 'cover'});
                $('#revora-share-avatar').empty();
            } else if (data.avatar_url) {
                $('#revora-share-avatar').css({'background-image': 'url(' + data.avatar_url + ')', 'background-size': 'cover'});
                $('#revora-share-avatar').empty();
            } else {
                var inlineSvg = '<svg viewBox="0 0 482.9 482.9" xmlns="http://www.w3.org/2000/svg" style="width:60%;height:60%;flex-shrink:0;"><path fill="#cbd5e1" d="M239.7,260.2c0.5,0,1,0,1.6,0c0.2,0,0.4,0,0.6,0c0.3,0,0.7,0,1,0c29.3-0.5,53-10.8,70.5-30.5c38.5-43.4,32.1-117.8,31.4-124.9c-2.5-53.3-27.7-78.8-48.5-90.7C280.8,5.2,262.7,0.4,242.5,0h-0.7c-0.1,0-0.3,0-0.4,0h-0.6c-11.1,0-32.9,1.8-53.8,13.7c-21,11.9-46.6,37.4-49.1,91.1c-0.7,7.1-7.1,81.5,31.4,124.9C186.7,249.4,210.4,259.7,239.7,260.2z M239.7,260.2 M164.6,107.3c0-0.3,0.1-0.6,0.1-0.8c3.3-71.7,54.2-79.4,76-79.4h0.4c0.2,0,0.5,0,0.8,0c27,0.6,72.9,11.6,76,79.4c0,0.3,0,0.6,0.1,0.8c0.1,0.7,7.1,68.7-24.7,104.5c-12.6,14.2-29.4,21.2-51.5,21.4c-0.2,0-0.3,0-0.5,0l0,0c-0.2,0-0.3,0-0.5,0c-22-0.2-38.9-7.2-51.4-21.4C157.7,176.2,164.5,107.9,164.6,107.3z"/><path fill="#cbd5e1" d="M446.8,383.6c0-0.1,0-0.2,0-0.3c0-0.8-0.1-1.6-0.1-2.5c-0.6-19.8-1.9-66.1-45.3-80.9c-0.3-0.1-0.7-0.2-1-0.3c-45.1-11.5-82.6-37.5-83-37.8c-6.1-4.3-14.5-2.8-18.8,3.3c-4.3,6.1-2.8,14.5,3.3,18.8c1.7,1.2,41.5,28.9,91.3,41.7c23.3,8.3,25.9,33.2,26.6,56c0,0.9,0,1.7,0.1,2.5c0.1,9-0.5,22.9-2.1,30.9c-16.2,9.2-79.7,41-176.3,41c-96.2,0-160.1-31.9-176.4-41.1c-1.6-8-2.3-21.9-2.1-30.9c0-0.8,0.1-1.6,0.1-2.5c0.7-22.8,3.3-47.7,26.6-56c49.8-12.8,89.6-40.6,91.3-41.7c6.1-4.3,7.6-12.7,3.3-18.8c-4.3-6.1-12.7-7.6-18.8-3.3c-0.4,0.3-37.7,26.3-83,37.8c-0.4,0.1-0.7,0.2-1,0.3c-43.4,14.9-44.7,61.2-45.3,80.9c0,0.9,0,1.7-0.1,2.5c0,0.1,0,0.2,0,0.3c-0.1,5.2-0.2,31.9,5.1,45.3c1,2.6,2.8,4.8,5.2,6.3c3,2,74.9,47.8,195.2,47.8s192.2-45.9,195.2-47.8c2.3-1.5,4.2-3.7,5.2-6.3C447,415.5,446.9,388.8,446.8,383.6z"/></svg>';
                $('#revora-share-avatar').html(inlineSvg).css({'display': 'flex', 'align-items': 'center', 'justify-content': 'center', 'overflow': 'hidden', 'background-image': 'none'});
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
        var originalBtnText = $submitBtn.attr('data-original-text') || $submitBtn.find('.btn-text').text() || $submitBtn.text() || 'Submit Review';
        originalBtnText = originalBtnText.trim();
        if (originalBtnText === 'Submitting...' || originalBtnText === 'Loading...' || !originalBtnText) {
            originalBtnText = 'Submit Review';
        }
        $submitBtn.attr('data-original-text', originalBtnText);
        const $message = $form.find('.revora-form-message');
        $message.hide().removeClass('success error');

        function setSubmitBtnState(isLoading) {
            var orig = $submitBtn.attr('data-original-text') || originalBtnText || 'Submit Review';
            if (orig === 'Submitting...' || orig === 'Loading...' || !orig.trim()) {
                orig = 'Submit Review';
            }
            if (isLoading) {
                $submitBtn.prop('disabled', true);
                if ($submitBtn.find('.btn-text').length) {
                    $submitBtn.find('.btn-text').text('Submitting...');
                } else {
                    $submitBtn.html('<span class="btn-text">Submitting...</span><span class="revora-spinner"></span>');
                }
                $submitBtn.find('.revora-spinner').show();
            } else {
                $submitBtn.prop('disabled', false);
                if ($submitBtn.find('.btn-text').length) {
                    $submitBtn.find('.btn-text').text(orig);
                } else {
                    $submitBtn.html('<span class="btn-text">' + orig + '</span><span class="revora-spinner" style="display:none;"></span>');
                }
                $submitBtn.find('.revora-spinner').hide();
            }
        }
        
        // Manual Validation for Rating
        var $ratingInput = $form.find('.revora-frontend-rating-val');
        if ( $ratingInput.length && $ratingInput.attr('required') && parseFloat($ratingInput.val()) === 0 ) {
            setSubmitBtnState(false);
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
            setSubmitBtnState(false);
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
            setSubmitBtnState(false);
            $message.html('<span class="material-symbols-outlined">error</span> File size exceeds the 1MB limit. Please choose a smaller image.').removeClass('success').addClass('error').fadeIn();
            return;
        }

        const formData = new FormData(this);

        // Append cropped blobs if present
        $form.find('input[type="file"]').each(function() {
            var croppedBlob = $(this).data('cropped-blob');
            var inputName = $(this).attr('name');
            if (croppedBlob && inputName) {
                formData.set(inputName, croppedBlob, 'avatar.webp');
            }
        });

        formData.append('action', 'revora_submit');

        setSubmitBtnState(true);

        $.ajax({
            url: revora_vars.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                setSubmitBtnState(false);

                if (response.success) {
                    var successMsg = (response.data && response.data.message) ? response.data.message : 'Thank you! Your review has been submitted successfully.';
                    $message.html('<span class="material-symbols-outlined">check_circle</span> ' + successMsg).removeClass('error').addClass('success').fadeIn();
                    
                    var avatarDataUrl = '';
                    var $avatarInput = $form.find('input[type="file"][name="profile_image"], input[type="file"][name="avatar"], input[type="file"].revora-file-input');
                    if ( $avatarInput.length ) {
                        if ( $avatarInput.data('cropped-url') ) {
                            avatarDataUrl = $avatarInput.data('cropped-url');
                        } else if ( $avatarInput[0].files && $avatarInput[0].files.length > 0 ) {
                            avatarDataUrl = URL.createObjectURL($avatarInput[0].files[0]);
                        }
                    }
                    
                    $form[0].reset();
                    // Reset custom file input text and data
                    $form.find('input[type="file"]').removeData('cropped-url').removeData('cropped-blob');
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
                    $message.addClass('error').html('<span class="material-symbols-outlined">error</span> ' + (response.data.message || 'Error occurred.')).fadeIn();
                }
            },
            error: function() {
                setSubmitBtnState(false);
                $message.addClass('error').html('<span class="material-symbols-outlined">error</span> Server error. Please try again later.').fadeIn();
            },
            complete: function() {
                setSubmitBtnState(false);
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
        $btn.find('.revora-spinner').show();

        $.ajax({
            url: revora_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'revora_load_more',
                form_id: form_id,
                category: category,
                page: page + 1,
                limit: load_more_limit,
                card_style: card_style,
                nonce: revora_vars.nonce
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $grid.append(response.data.html);
                    $btn.data('page', page + 1);

                    if (!response.data.has_more) {
                        $btn.parent().fadeOut();
                    }
                } else {
                    $btn.parent().fadeOut();
                }
            },
            error: function() {
                $btn.parent().fadeOut();
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.btn-text').text('Load More');
                $btn.find('.revora-spinner').hide();
            }
        });
    });

    /**
     * Initialize Swiper Sliders
     */
    function initRevoraSliders() {
        if (typeof Swiper === 'undefined') return;

        $('.revora-slider-container').each(function() {
            const $container = $(this);
            if ($container.hasClass('swiper-initialized')) return;

            const autoplay = $container.data('autoplay') === 'yes';
            const speed = parseInt($container.data('speed')) || 3000;
            const loop = $container.data('loop') === 'yes';
            const slidesPerView = parseInt($container.data('slides-per-view')) || 3;
            const spaceBetween = parseInt($container.data('space-between')) || 24;

            new Swiper(this, {
                slidesPerView: 1,
                spaceBetween: 16,
                loop: loop,
                autoplay: autoplay ? { delay: speed, disableOnInteraction: false } : false,
                pagination: {
                    el: $container.find('.swiper-pagination')[0],
                    clickable: true,
                },
                navigation: {
                    nextEl: $container.find('.swiper-button-next')[0],
                    prevEl: $container.find('.swiper-button-prev')[0],
                },
                breakpoints: {
                    640: {
                        slidesPerView: Math.min(2, slidesPerView),
                        spaceBetween: spaceBetween,
                    },
                    1024: {
                        slidesPerView: slidesPerView,
                        spaceBetween: spaceBetween,
                    }
                }
            });
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

    /* ==========================================================================
       Image Cropper Modal & WebP Compression Logic
       ========================================================================== */
    var revoraCropper = null;
    var $activeCropInput = null;

    function ensureCropperModal() {
        var $modal = $('#revora-cropper-modal');
        if ( ! $modal.length ) {
            var cropperHtml = `
                <div id="revora-cropper-modal" class="revora-modal-overlay">
                    <div class="revora-cropper-modal-content">
                        <div class="revora-cropper-header">
                            <div class="revora-cropper-title-wrap">
                                <div class="revora-cropper-icon-box">
                                    <span class="material-symbols-outlined">crop</span>
                                </div>
                                <div class="revora-cropper-title-text">
                                    <h3>Crop Profile Photo</h3>
                                    <p>Position and adjust your avatar photo</p>
                                </div>
                            </div>
                            <button type="button" class="revora-cropper-close-btn" id="revora-cropper-close" title="Close">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        <div class="revora-cropper-body">
                            <div class="revora-cropper-img-container">
                                <img id="revora-cropper-image" src="" alt="Crop Preview" />
                            </div>
                            <div class="revora-cropper-toolbar">
                                <button type="button" class="revora-crop-tool-btn" id="revora-crop-zoom-in" title="Zoom In">
                                    <span class="material-symbols-outlined">zoom_in</span>
                                </button>
                                <button type="button" class="revora-crop-tool-btn" id="revora-crop-zoom-out" title="Zoom Out">
                                    <span class="material-symbols-outlined">zoom_out</span>
                                </button>
                                <div class="revora-crop-tool-sep"></div>
                                <button type="button" class="revora-crop-tool-btn" id="revora-crop-rotate-left" title="Rotate Left 90°">
                                    <span class="material-symbols-outlined">rotate_left</span>
                                </button>
                                <button type="button" class="revora-crop-tool-btn" id="revora-crop-rotate-right" title="Rotate Right 90°">
                                    <span class="material-symbols-outlined">rotate_right</span>
                                </button>
                                <div class="revora-crop-tool-sep"></div>
                                <button type="button" class="revora-crop-tool-btn" id="revora-crop-reset" title="Reset">
                                    <span class="material-symbols-outlined">restart_alt</span>
                                </button>
                            </div>
                        </div>
                        <div class="revora-cropper-footer">
                            <button type="button" class="revora-crop-cancel-btn" id="revora-crop-cancel">Cancel</button>
                            <button type="button" class="revora-crop-apply-btn" id="revora-crop-apply">
                                <span class="material-symbols-outlined">check</span>
                                <span>Crop & Save</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(cropperHtml);
            $modal = $('#revora-cropper-modal');
            
            // Toolbar handlers
            $(document).on('click', '#revora-crop-zoom-in', function(e) {
                e.preventDefault();
                if (revoraCropper) revoraCropper.zoom(0.1);
            });
            $(document).on('click', '#revora-crop-zoom-out', function(e) {
                e.preventDefault();
                if (revoraCropper) revoraCropper.zoom(-0.1);
            });
            $(document).on('click', '#revora-crop-rotate-left', function(e) {
                e.preventDefault();
                if (revoraCropper) revoraCropper.rotate(-90);
            });
            $(document).on('click', '#revora-crop-rotate-right', function(e) {
                e.preventDefault();
                if (revoraCropper) revoraCropper.rotate(90);
            });
            $(document).on('click', '#revora-crop-reset', function(e) {
                e.preventDefault();
                if (revoraCropper) {
                    revoraCropper.reset();
                }
            });
            $(document).on('click', '#revora-crop-cancel, #revora-cropper-close', function(e) {
                e.preventDefault();
                closeCropperModal();
            });
            $(document).on('click', '#revora-crop-apply', function(e) {
                e.preventDefault();
                applyCroppedImage();
            });
        }
        return $modal;
    }

    function openCropperModal(imageSrc, $input) {
        $activeCropInput = $input;
        var $modal = ensureCropperModal();
        
        if (revoraCropper) {
            revoraCropper.destroy();
            revoraCropper = null;
        }

        var $container = $modal.find('.revora-cropper-img-container');
        $container.html('<img id="revora-cropper-image" src="' + imageSrc + '" alt="Crop Preview" />');
        var imageEl = document.getElementById('revora-cropper-image');
        
        $modal.addClass('active');

        function startCropper() {
            if (typeof Cropper === 'undefined') return;
            if (revoraCropper) {
                revoraCropper.destroy();
                revoraCropper = null;
            }
            revoraCropper = new Cropper(imageEl, {
                aspectRatio: 1,
                viewMode: 0,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                movable: true,
                zoomable: true,
                zoomOnTouch: true,
                zoomOnWheel: true,
                wheelZoomRatio: 0.1,
                toggleDragModeOnDblclick: false,
                responsive: true,
                checkOrientation: false,
                background: true
            });
        }

        function loadAndStart() {
            if (typeof Cropper === 'undefined') {
                $.getScript('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js', function() {
                    setTimeout(startCropper, 100);
                });
            } else {
                setTimeout(startCropper, 100);
            }
        }

        if (imageEl.complete) {
            loadAndStart();
        } else {
            imageEl.onload = loadAndStart;
        }
    }

    function closeCropperModal() {
        var $modal = $('#revora-cropper-modal');
        $modal.removeClass('active');
        if (revoraCropper) {
            revoraCropper.destroy();
            revoraCropper = null;
        }
        if ($activeCropInput && !$activeCropInput.data('cropped-url')) {
            $activeCropInput.val('');
            var $text = $activeCropInput.siblings('.revora-file-fake').find('.revora-file-text');
            var orig = $text.data('placeholder') || 'Choose file...';
            $text.text(orig);
        }
    }

    function applyCroppedImage() {
        if (!revoraCropper || !$activeCropInput) {
            closeCropperModal();
            return;
        }
        
        var $applyBtn = $('#revora-crop-apply');
        $applyBtn.prop('disabled', true).find('span:last').text('Processing...');
        
        var canvas = revoraCropper.getCroppedCanvas({
            width: 512,
            height: 512,
            minWidth: 256,
            minHeight: 256,
            maxWidth: 512,
            maxHeight: 512,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        if (!canvas) {
            $applyBtn.prop('disabled', false).find('span:last').text('Crop & Save');
            closeCropperModal();
            return;
        }
        
        function handleBlob(blob) {
            if (!blob) {
                $applyBtn.prop('disabled', false).find('span:last').text('Crop & Save');
                closeCropperModal();
                return;
            }
            
            var webpFile;
            try {
                webpFile = new File([blob], 'avatar.webp', { type: blob.type || 'image/webp' });
            } catch(e) {
                webpFile = blob;
                webpFile.name = 'avatar.webp';
            }
            webpFile._revoraCropped = true;
            
            if (window.DataTransfer) {
                var dt = new DataTransfer();
                dt.items.add(webpFile);
                $activeCropInput[0].files = dt.files;
            }
            
            var objectUrl = URL.createObjectURL(blob);
            $activeCropInput.data('cropped-url', objectUrl);
            $activeCropInput.data('cropped-blob', blob);
            
            var sizeKB = Math.round(blob.size / 1024);
            var $fakeWrap = $activeCropInput.siblings('.revora-file-fake');
            var $text = $fakeWrap.find('.revora-file-text');
            $text.html('<img src="' + objectUrl + '" class="revora-file-thumb-badge" alt="Avatar"/> avatar.webp (' + sizeKB + ' KB)');
            
            $applyBtn.prop('disabled', false).find('span:last').text('Crop & Save');
            closeCropperModal();
        }
        
        if (canvas.toBlob) {
            canvas.toBlob(function(blob) {
                if (blob && blob.size > 0) {
                    handleBlob(blob);
                } else {
                    canvas.toBlob(handleBlob, 'image/jpeg', 0.85);
                }
            }, 'image/webp', 0.85);
        } else {
            var dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            var arr = dataUrl.split(','), mime = arr[0].match(/:(.*?);/)[1],
                bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
            while(n--){
                u8arr[n] = bstr.charCodeAt(n);
            }
            handleBlob(new Blob([u8arr], {type:mime}));
        }
    }

    // Custom File Input Sync & Image Cropper Trigger
    $(document).on('change', '.revora-file-input', function() {
        var $this = $(this);
        var $form = $this.closest('form');
        var $message = $form.find('.revora-form-message');
        
        if (this.files && this.files.length > 0) {
            var file = this.files[0];
            
            // If already cropped programmatically, just update label and exit
            if (file._revoraCropped) {
                return;
            }
            
            var isImage = file.type && file.type.indexOf('image/') === 0;
            if (!isImage && file.name) {
                var ext = file.name.split('.').pop().toLowerCase();
                isImage = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif', 'heic'].indexOf(ext) !== -1;
            }
            
            // For images (Avatar / Profile Photo): Open interactive cropper popup
            if (isImage) {
                if (window.FileReader) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        openCropperModal(e.target.result, $this);
                    };
                    reader.readAsDataURL(file);
                    return;
                }
            }
            
            // Non-image files: check 1MB limit
            if (file.size > 1048576) {
                $this.val('');
                $message.html('<span class="material-symbols-outlined">error</span> File size exceeds the 1MB limit. Please choose a smaller file.').removeClass('success').addClass('error').fadeIn();
                var $text = $this.siblings('.revora-file-fake').find('.revora-file-text');
                var originalText = $text.data('placeholder') || 'Choose file...';
                $text.text(originalText);
                return;
            }
        }
        
        var fileName = $this.val().split('\\').pop();
        var $text = $this.siblings('.revora-file-fake').find('.revora-file-text');
        
        if (fileName) {
            $text.text(fileName);
        } else {
            var originalText = $text.data('placeholder') || 'Choose file...';
            $text.text(originalText);
        }
    });
    
    // Store original placeholder on load
    $('.revora-file-text').each(function() {
        $(this).data('placeholder', $(this).text());
    });

    // Store original button text on load
    $('.revora-submit-btn').each(function() {
        var $btn = $(this);
        var t = $btn.attr('data-original-text') || $btn.find('.btn-text').text().trim() || 'Submit Review';
        if (t === 'Submitting...' || t === 'Loading...' || !t) {
            t = 'Submit Review';
        }
        $btn.attr('data-original-text', t);
        $btn.find('.btn-text').text(t);
        $btn.prop('disabled', false);
        $btn.find('.revora-spinner').hide();
    });
});
