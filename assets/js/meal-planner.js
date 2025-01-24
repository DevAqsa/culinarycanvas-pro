jQuery(document).ready(function($) {
    // Make recipe items draggable
    $('.recipe-item').draggable({
        helper: 'clone',
        revert: 'invalid',
        cursor: 'move'
    });

    // Make meal slots droppable
    $('.meal-slot').droppable({
        accept: '.recipe-item',
        hoverClass: 'drop-hover',
        drop: function(event, ui) {
            const $slot = $(this);
            const recipeId = ui.draggable.data('recipe-id');
            const recipeName = ui.draggable.text();
            
            $slot.html(`
                <div class="planned-recipe" data-recipe-id="${recipeId}">
                    ${recipeName}
                    <button type="button" class="remove-recipe">&times;</button>
                </div>
            `);
        }
    });

    // Recipe search functionality
    $('#recipe-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.recipe-item').each(function() {
            const recipeName = $(this).text().toLowerCase();
            $(this).toggle(recipeName.includes(searchTerm));
        });
    });

    // Remove planned recipe
    $(document).on('click', '.remove-recipe', function() {
        $(this).closest('.planned-recipe').remove();
    });

    // Week navigation
    let currentWeekStart = new Date();
    currentWeekStart.setDate(currentWeekStart.getDate() - currentWeekStart.getDay() + 1);

    function updateCalendarDates() {
        $('.meal-calendar th[data-date]').each(function(index) {
            const date = new Date(currentWeekStart);
            date.setDate(date.getDate() + index);
            $(this).attr('data-date', date.toISOString().split('T')[0])
                  .html(`${date.toLocaleDateString('en-US', { weekday: 'long' })}<br>${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`);
        });

        $('.meal-calendar td[data-date]').each(function(index) {
            const date = new Date(currentWeekStart);
            date.setDate(date.getDate() + Math.floor(index / 3));
            $(this).attr('data-date', date.toISOString().split('T')[0]);
        });

        $('.current-week').text(
            `${currentWeekStart.toLocaleDateString('en-US', { month: 'long', day: 'numeric' })} - ${new Date(currentWeekStart.getTime() + 6 * 24 * 60 * 60 * 1000).toLocaleDateString('en-US', { month: 'long', day: 'numeric' })}`
        );
    }

    $('.prev-week').on('click', function() {
        currentWeekStart.setDate(currentWeekStart.getDate() - 7);
        updateCalendarDates();
    });

    $('.next-week').on('click', function() {
        currentWeekStart.setDate(currentWeekStart.getDate() + 7);
        updateCalendarDates();
    });

    // Save meal plan
    $('#save-meal-plan').on('click', function() {
        const mealPlan = {};
        
        $('.meal-calendar td[data-date]').each(function() {
            const date = $(this).data('date');
            const meal = $(this).data('meal');
            const $recipe = $(this).find('.planned-recipe');
            
            if ($recipe.length) {
                if (!mealPlan[date]) {
                    mealPlan[date] = {};
                }
                mealPlan[date][meal] = {
                    id: $recipe.data('recipe-id'),
                    name: $recipe.text().trim()
                };
            }
        });

        $.ajax({
            url: mealPlannerData.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_meal_plan',
                nonce: mealPlannerData.nonce,
                meal_plan: JSON.stringify(mealPlan)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error saving meal plan');
                }
            }
        });
    });

    // Generate shopping list
    $('#generate-shopping-list').on('click', function() {
        const mealPlan = {};
        $('.meal-calendar .planned-recipe').each(function() {
            const recipeId = $(this).data('recipe-id');
            if (!mealPlan[recipeId]) {
                mealPlan[recipeId] = 1;
            } else {
                mealPlan[recipeId]++;
            }
        });

        const recipeIds = Object.keys(mealPlan);
        let shoppingList = {};

        function processRecipe(recipeId) {
            return $.ajax({
                url: mealPlannerData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_recipe_details',
                    nonce: mealPlannerData.nonce,
                    recipe_id: recipeId
                }
            }).then(function(response) {
                if (response.success && response.data.ingredients) {
                    response.data.ingredients.forEach(function(ingredient) {
                        if (!shoppingList[ingredient]) {
                            shoppingList[ingredient] = mealPlan[recipeId];
                        } else {
                            shoppingList[ingredient] += mealPlan[recipeId];
                        }
                    });
                }
            });
        }

        Promise.all(recipeIds.map(processRecipe)).then(function() {
            let listHtml = '<ul>';
            Object.entries(shoppingList).forEach(([ingredient, count]) => {
                listHtml += `<li>${ingredient} (×${count})</li>`;
            });
            listHtml += '</ul>';
            
            $('#shopping-list-content').html(listHtml);
            $('#shopping-list-modal').show();
        });
    });

    // Shopping list modal controls
    $('.modal-close').on('click', function() {
        $('#shopping-list-modal').hide();
    });

    $('#print-shopping-list').on('click', function() {
        const content = $('#shopping-list-content').html();
        const printWindow = window.open('', '', 'height=500,width=800');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Shopping List</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        li { margin-bottom: 10px; }
                    </style>
                </head>
                <body>
                    <h2>Shopping List</h2>
                    ${content}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    });

    // Initialize calendar dates
    updateCalendarDates();
});