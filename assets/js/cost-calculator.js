jQuery(document).ready(function($) {
    // Add new ingredient cost
    $('#add-ingredient-cost').on('click', function() {
        const name = $('#new-ingredient-name').val();
        const unit = $('#new-ingredient-unit').val();
        const cost = $('#new-ingredient-cost').val();

        if (!name || !unit || !cost) {
            alert('Please fill in all fields');
            return;
        }

        $.ajax({
            url: costCalculatorData.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_ingredient_cost',
                nonce: costCalculatorData.nonce,
                name: name,
                unit: unit,
                cost: cost
            },
            success: function(response) {
                if (response.success) {
                    const newRow = `
                        <tr data-id="${response.data.id}">
                            <td>${response.data.name}</td>
                            <td>${response.data.unit}</td>
                            <td>${costCalculatorData.currency}${parseFloat(response.data.cost).toFixed(2)}</td>
                            <td>
                                <button type="button" class="button edit-ingredient">Edit</button>
                                <button type="button" class="button delete-ingredient">Delete</button>
                            </td>
                        </tr>
                    `;
                    $(newRow).insertBefore('#add-ingredient-row');
                    
                    // Clear form
                    $('#new-ingredient-name').val('');
                    $('#new-ingredient-unit').val('g');
                    $('#new-ingredient-cost').val('');
                } else {
                    alert('Error saving ingredient cost');
                }
            }
        });
    });

    // Save edited ingredient
    $(document).on('click', '.save-edit', function() {
        const $row = $(this).closest('tr');
        const id = $row.data('id');
        const name = $row.find('.edit-name').val();
        const unit = $row.find('.edit-unit').val();
        const cost = $row.find('.edit-cost').val();

        $.ajax({
            url: costCalculatorData.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_ingredient_cost',
                nonce: costCalculatorData.nonce,
                id: id,
                name: name,
                unit: unit,
                cost: cost
            },
            success: function(response) {
                if (response.success) {
                    $row.html(`
                        <td>${name}</td>
                        <td>${unit}</td>
                        <td>${costCalculatorData.currency}${parseFloat(cost).toFixed(2)}</td>
                        <td>
                            <button type="button" class="button edit-ingredient">Edit</button>
                            <button type="button" class="button delete-ingredient">Delete</button>
                        </td>
                    `);
                } else {
                    alert('Error saving changes');
                }
            }
        });
    });

    // Cancel edit
    $(document).on('click', '.cancel-edit', function() {
        const $row = $(this).closest('tr');
        const name = $row.find('.edit-name').val();
        const unit = $row.find('.edit-unit').val();
        const cost = $row.find('.edit-cost').val();

        $row.html(`
            <td>${name}</td>
            <td>${unit}</td>
            <td>${costCalculatorData.currency}${parseFloat(cost).toFixed(2)}</td>
            <td>
                <button type="button" class="button edit-ingredient">Edit</button>
                <button type="button" class="button delete-ingredient">Delete</button>
            </td>
        `);
    });

    // Delete ingredient
    $(document).on('click', '.delete-ingredient', function() {
        const $row = $(this).closest('tr');
        const id = $row.data('id');

        if (confirm('Are you sure you want to delete this ingredient?')) {
            $.ajax({
                url: costCalculatorData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_ingredient_cost',
                    nonce: costCalculatorData.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        $row.remove();
                    } else {
                        alert('Error deleting ingredient');
                    }
                }
            });
        }
    });

    // Recipe cost calculation
    $('#recipe-select').on('change', function() {
        const recipeId = $(this).val();
        if (!recipeId) {
            $('#recipe-ingredients-cost').hide();
            return;
        }

        $.ajax({
            url: costCalculatorData.ajaxurl,
            type: 'POST',
            data: {
                action: 'calculate_recipe_cost',
                nonce: costCalculatorData.nonce,
                recipe_id: recipeId
            },
            success: function(response) {
                if (response.success) {
                    let tbody = '';
                    response.data.ingredients.forEach(ingredient => {
                        tbody += `
                            <tr>
                                <td>${ingredient.name}</td>
                                <td>${ingredient.quantity} ${ingredient.unit}</td>
                                <td>${costCalculatorData.currency}${ingredient.unit_cost.toFixed(2)}</td>
                                <td>${costCalculatorData.currency}${ingredient.total_cost.toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    $('#recipe-ingredients-list').html(tbody);
                    $('#total-recipe-cost').text(
                        costCalculatorData.currency + response.data.total_cost.toFixed(2)
                    );
                    $('#cost-per-serving').text(
                        costCalculatorData.currency + response.data.cost_per_serving.toFixed(2)
                    );
                    $('#recipe-ingredients-cost').show();
                }
            }
        });
    });

    // Save recipe cost
    $('#save-recipe-cost').on('click', function() {
        const recipeId = $('#recipe-select').val();
        const totalCost = parseFloat($('#total-recipe-cost').text().replace(costCalculatorData.currency, ''));
        const costPerServing = parseFloat($('#cost-per-serving').text().replace(costCalculatorData.currency, ''));

        $.ajax({
            url: costCalculatorData.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_recipe_cost',
                nonce: costCalculatorData.nonce,
                recipe_id: recipeId,
                total_cost: totalCost,
                cost_per_serving: costPerServing
            },
            success: function(response) {
                if (response.success) {
                    alert('Recipe cost saved successfully');
                } else {
                    alert('Error saving recipe cost');
                }
            }
        });
    });

    // Initialize tooltips and other UI elements
    $('[data-toggle="tooltip"]').tooltip();
    $('.currency-input').on('input', function() {
        $(this).val(function(i, v) {
            return v.replace(/[^\d.]/g, '');
        });
    });
});