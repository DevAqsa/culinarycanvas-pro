jQuery(document).ready(function($) {
    // Add new ingredient cost
    $('#add-ingredient-cost').on('click', function(e) {
        e.preventDefault();
        
        const name = $('#new-ingredient-name').val().trim();
        const unit = $('#new-ingredient-unit').val();
        const cost = $('#new-ingredient-cost').val();

        if (!name || !unit || !cost) {
            alert('Please fill in all fields');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_ingredient_cost',
                security: costCalculatorData.nonce,
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

    // Delete ingredient
    $(document).on('click', '.delete-ingredient', function() {
        const $row = $(this).closest('tr');
        const id = $row.data('id');

        if (confirm('Are you sure you want to delete this ingredient?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_ingredient_cost',
                    security: costCalculatorData.nonce,
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

    // Edit functionality
    $(document).on('click', '.edit-ingredient', function() {
        const $row = $(this).closest('tr');
        const name = $row.find('td:eq(0)').text();
        const unit = $row.find('td:eq(1)').text();
        const cost = $row.find('td:eq(2)').text().replace(costCalculatorData.currency, '');

        $row.html(`
            <td><input type="text" class="edit-name" value="${name}"></td>
            <td>
                <select class="edit-unit">
                    <option value="g" ${unit === 'g' ? 'selected' : ''}>Grams (g)</option>
                    <option value="kg" ${unit === 'kg' ? 'selected' : ''}>Kilograms (kg)</option>
                    <option value="ml" ${unit === 'ml' ? 'selected' : ''}>Milliliters (ml)</option>
                    <option value="l" ${unit === 'l' ? 'selected' : ''}>Liters (l)</option>
                    <option value="piece" ${unit === 'piece' ? 'selected' : ''}>Piece</option>
                </select>
            </td>
            <td><input type="number" class="edit-cost" value="${cost}" step="0.01"></td>
            <td>
                <button type="button" class="button save-edit">Save</button>
                <button type="button" class="button cancel-edit">Cancel</button>
            </td>
        `);
    });

    // Save edit
    $(document).on('click', '.save-edit', function() {
        const $row = $(this).closest('tr');
        const id = $row.data('id');
        const name = $row.find('.edit-name').val();
        const unit = $row.find('.edit-unit').val();
        const cost = $row.find('.edit-cost').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_ingredient_cost',
                security: costCalculatorData.nonce,
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
});