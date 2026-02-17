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
});
