jQuery(document).ready(function($) {
    // Star rating hover effect
    $('.stars-input label').hover(
        function() {
            $(this).prevAll('label').andSelf().addClass('hover');
        },
        function() {
            $('.stars-input label').removeClass('hover');
        }
    );

    // Update rating text based on selection
    $('.stars-input input').change(function() {
        const ratingText = {
            5: 'Excellent!',
            4: 'Very Good',
            3: 'Good',
            2: 'Fair',
            1: 'Poor'
        };
        const rating = $(this).val();
        $('.rating-text').text(ratingText[rating]);
    });

    // Handle recipe rating submission
    $('#submit-recipe-rating').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const originalButtonText = $submitButton.text();

        // Validate rating
        if (!$form.find('input[name="rating"]:checked').val()) {
            showMessage('error', 'Please select a rating');
            return;
        }

        $submitButton.text('Submitting...').prop('disabled', true);

        const formData = {
            action: 'submit_recipe_rating',
            recipe_id: $form.find('input[name="recipe_id"]').val(),
            rating: $form.find('input[name="rating"]:checked').val(),
            review: $form.find('textarea[name="review"]').val(),
            nonce: $form.find('input[name="rating_nonce"]').val()
        };

        $.ajax({
            url: recipeRatings.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Update the average rating display
                    const average = response.data.average;
                    updateRatingDisplay(average);
                    
                    // Show success message
                    showMessage('success', response.data.message);
                    
                    // Update rating count
                    updateRatingCount();
                    
                    // Reset form if it's a new rating
                    if ($submitButton.text().includes('Submit')) {
                        $form.find('textarea[name="review"]').val('');
                        $form.find('input[name="rating"]').prop('checked', false);
                        $('.rating-text').text('');
                    }
                } else {
                    showMessage('error', response.data);
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'An error occurred. Please try again.');
                console.error('Rating submission error:', error);
            },
            complete: function() {
                $submitButton.text(originalButtonText).prop('disabled', false);
            }
        });
    });

    // Function to update rating display
    function updateRatingDisplay(average) {
        const $starsContainer = $('.recipe-rating .stars');
        $starsContainer.find('.star').each(function(index) {
            $(this).toggleClass('filled', index < Math.round(average));
        });
    }

    // Function to update rating count
    function updateRatingCount() {
        const $ratingCount = $('.rating-count');
        let count = parseInt($ratingCount.text().match(/\d+/)[0]);
        count++;
        $ratingCount.text(`(${count} ${count === 1 ? 'rating' : 'ratings'})`);
    }

    // Function to show messages
    function showMessage(type, message) {
        const messageClass = type === 'success' ? 'success-message' : 'error-message';
        const $messageDiv = $('<div>', {
            class: `message ${messageClass}`,
            text: message
        });

        // Remove any existing messages
        $('.message').remove();

        // Add new message
        $('#recipe-rating-form').prepend($messageDiv);

        // Auto-remove message after 3 seconds
        setTimeout(() => {
            $messageDiv.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Print recipe functionality
    $('.print-recipe').on('click', function(e) {
        e.preventDefault();
        
        const $recipePrint = $('.recipe-content').clone();
        
        // Remove unnecessary elements
        $recipePrint.find('.recipe-rating-form, .social-share, .print-recipe').remove();
        
        const printWindow = window.open('', 'Print Recipe', 'height=600,width=800');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${document.title}</title>
                <link rel="stylesheet" href="${recipeRatings.printStyles}">
            </head>
            <body>
                ${$recipePrint.html()}
                <script>window.onload = function() { window.print(); window.close(); }</script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    });

    // Recipe servings adjustment
    $('.servings-adjust').on('click', '.adjust-button', function() {
        const $button = $(this);
        const $servingsInput = $button.siblings('.servings-count');
        const currentServings = parseInt($servingsInput.val());
        const originalServings = parseInt($servingsInput.data('original'));
        
        let newServings = currentServings;
        
        if ($button.hasClass('increase')) {
            newServings = currentServings + 1;
        } else if ($button.hasClass('decrease') && currentServings > 1) {
            newServings = currentServings - 1;
        }
        
        if (newServings !== currentServings) {
            $servingsInput.val(newServings);
            adjustIngredientQuantities(originalServings, newServings);
        }
    });

    // Function to adjust ingredient quantities
    function adjustIngredientQuantities(originalServings, newServings) {
        $('.ingredient-quantity').each(function() {
            const $quantity = $(this);
            const originalQuantity = parseFloat($quantity.data('original'));
            const newQuantity = (originalQuantity * newServings) / originalServings;
            
            // Round to 2 decimal places
            $quantity.text(Math.round(newQuantity * 100) / 100);
        });
    }

    // Initialize ingredient quantity data
    $('.ingredient-quantity').each(function() {
        const $quantity = $(this);
        $quantity.data('original', parseFloat($quantity.text()));
    });

    // Recipe save/bookmark functionality
    $('.save-recipe').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const recipeId = $button.data('recipe-id');
        
        $.ajax({
            url: recipeRatings.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_recipe',
                recipe_id: recipeId,
                nonce: recipeRatings.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.toggleClass('saved');
                    $button.find('.save-text').text(
                        $button.hasClass('saved') ? 'Saved' : 'Save Recipe'
                    );
                    showMessage('success', response.data.message);
                } else {
                    showMessage('error', response.data);
                }
            },
            error: function() {
                showMessage('error', 'Failed to save recipe. Please try again.');
            }
        });
    });
});