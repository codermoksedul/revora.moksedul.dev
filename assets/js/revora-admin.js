jQuery(document).ready(function($) {
    // Star Rating Selector
    $('.revora-rating-selector .dashicons').on('click', function() {
        var rating = $(this).data('rating');
        $('#rating_input').val(rating);
        
        $('.revora-rating-selector .dashicons').removeClass('active');
        $(this).addClass('active').prevAll().addClass('active');
    });

    // Initialize stars on page load (for edit page)
    var initialRating = $('#rating_input').val();
    if (initialRating) {
        $('.revora-rating-selector .dashicons[data-rating="' + initialRating + '"]').click();
    }
});
