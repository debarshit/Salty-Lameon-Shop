// Update button text based on selected radio button
function updateButtonText() {
    var radioButtons = document.querySelectorAll('.form input[type="radio"]');
    var selectedIndex = Array.prototype.indexOf.call(radioButtons, document.querySelector('.form input[type="radio"]:checked'));
    var button = document.querySelector('#submitBtn');

    if (selectedIndex === radioButtons.length - 1) {
        button.textContent = 'Submit';
    } else {
        button.textContent = 'Next';
    }
}

// Initialize button text on page load
updateButtonText();

// Handle click events on stage labels to update button text
document.querySelectorAll('.form .stages label').forEach(function(label) {
    label.addEventListener('click', function() {
        setTimeout(updateButtonText, 0); // Delay to ensure the radio button is updated
    });
});

// Handle click events on the Next/Submit button
document.querySelector('#submitBtn').addEventListener('click', function() {
    var radioButtons = document.querySelectorAll('.form input[type="radio"]');
    var selectedIndex = Array.prototype.indexOf.call(radioButtons, document.querySelector('.form input[type="radio"]:checked'));

    // Move to the next stage
    if (selectedIndex + 1 < radioButtons.length) {
        radioButtons[selectedIndex + 1].checked = true;
        updateButtonText();
    } else {
        // Validate inputs before submission on the last stage
        validateAndSubmit();
    }
});

// Validate inputs on the final step and submit
function validateAndSubmit() {
    const productName = document.getElementById('productName').value.trim();
    const sku = document.getElementById('sku').value.trim();
    const description = document.getElementById('description').value.trim();
    const oldPrice = document.getElementById('oldPrice').value.trim() || null;
    const newPrice = document.getElementById('newPrice').value.trim();
    const stockQuantity = document.getElementById('stockQuantity').value.trim() || null;
    const productImages = document.getElementById('imageUpload').files;
    const categoryName = document.getElementById('categoryName').value.trim();
    const tags = document.getElementById('tags').value.split(',').map(tag => tag.trim()).filter(tag => tag);
    const promotionalLabels = document.getElementById('promotionalLabels').value.split(',').map(label => label.trim()).filter(label => label);
    const discountLabels = document.getElementById('discountLabels').value.split(',').map(label => label.trim()).filter(label => label);
    const specialCategories = document.getElementById('specialCategories').value.split(',').map(cat => cat.trim()).filter(cat => cat);

    // Perform validations
    if (!productName || !sku || !description || !newPrice || !categoryName) {
        alert("Please fill in all required fields.");
        return;
    }

    if (tags.length === 0) {
        alert("Please enter at least one tag.");
        return;
    }

    // if (promotionalLabels.length === 0) {
    //     alert("Please enter at least one promotional label.");
    //     return;
    // }

    // if (discountLabels.length === 0) {
    //     alert("Please enter at least one discount label.");
    //     return;
    // }

	const additionalInfos = Array.from(document.querySelectorAll('#additionalInfosTable tbody tr')).map(row => {
		const key = row.querySelector('.key').value.trim();
		const value = row.querySelector('.value').value.trim();
		return { key, value };
	}).filter(info => info.key && info.value.length > 0);

    // if (additionalInfos.length === 0) {
    //     alert("Please enter at least one additional info item in the correct format.");
    //     return;
    // }

	const customizations = Array.from(document.querySelectorAll('#customizationTable tbody tr')).map(row => {
		const option = row.querySelector('.option').value.trim();
		const values = row.querySelector('.values').value.trim();
		return { option, values };
	}).filter(custom => custom.option && custom.values.length > 0);

    // if (customizations.length === 0) {
    //     alert("Please enter at least one customization item in the correct format.");
    //     return;
    // }

    // if (specialCategories.length === 0) {
    //     alert("Please enter at least one special category.");
    //     return;
    // }

    // Prepare data for submission
//     const finalData = {
//         productName,
//         sku,
//         description,
//         oldPrice,
//         newPrice,
//         stockQuantity,
//         productImage,
//         categoryName,
//         tags: tags.length > 0 ? tags : null,
//         promotionalLabels: promotionalLabels.length > 0 ? promotionalLabels : null,
//         discountLabels: discountLabels.length > 0 ? discountLabels : null,
//         additionalInfos: additionalInfos.length > 0 ? additionalInfos : null,
//         customizations: customizations.length > 0 ? customizations : null,
//         specialCategories: specialCategories.length > 0 ? specialCategories : null
//     };

//     fetch('actions.php?action=insertProductDetails', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//         },
//         body: JSON.stringify(finalData),
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             alert('Product inserted successfully!');
//         } else {
//             alert('Error inserting product: ' + data.message);
//         }
//     })
//     .catch(error => console.error('Error:', error));
// 	console.log(finalData);
// }

document.getElementById('loader').style.display = 'block';

const formData = new FormData();
    formData.append('productName', productName);
    formData.append('sku', sku);
    formData.append('description', description);
    formData.append('oldPrice', oldPrice);
    formData.append('newPrice', newPrice);
    formData.append('stockQuantity', stockQuantity);
    formData.append('categoryName', categoryName);

    // Append each selected image file to FormData
    Array.from(productImages).forEach((image, index) => {
        formData.append(`productImages[]`, image);
    });

    formData.append('tags', JSON.stringify(tags));
    formData.append('promotionalLabels', JSON.stringify(promotionalLabels));
    formData.append('discountLabels', JSON.stringify(discountLabels));
    formData.append('specialCategories', JSON.stringify(specialCategories));
    formData.append('additionalInfos', JSON.stringify(additionalInfos));
    formData.append('customizations', JSON.stringify(customizations));

    fetch('actions.php?action=insertProductDetails', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loader').style.display = 'none';
        if (data.success) {
            alert('Product inserted successfully!');
        } else {
            alert('Error inserting product: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error)
        document.getElementById('loader').style.display = 'none';
    });
}

// Event delegation for removing rows
document.querySelector('#customizationTable tbody').addEventListener('click', function(e) {
    if (e.target.classList.contains('removeCustomizationRow')) {
        e.target.closest('tr').remove();
    }
});

document.querySelector('#additionalInfosTable tbody').addEventListener('click', function(e) {
    if (e.target.classList.contains('removeAdditionalInfoRow')) {
        e.target.closest('tr').remove();
    }
});

//--suggestions requests for categories, tags, labels, additional info, customization and sp categories--//
//--categories--//
document.getElementById("categoryName").addEventListener("input", function() {
    var query = this.value.trim();

    if (query.length > 0) {
        fetchCategories(query);
    } else {
        document.getElementById("categorySuggestions").style.display = 'none';
    }
});

document.getElementById("categoryName").addEventListener("keydown", function(event) {
    var suggestionsDiv = document.getElementById("categorySuggestions");
    var activeItem = suggestionsDiv.querySelector('.suggestion-item.active');
    
    if (event.key === 'ArrowDown') {
        if (activeItem) {
            var nextItem = activeItem.nextElementSibling;
            if (nextItem) {
                activeItem.classList.remove('active');
                nextItem.classList.add('active');
            }
        } else {
            var firstItem = suggestionsDiv.querySelector('.suggestion-item');
            if (firstItem) firstItem.classList.add('active');
        }
    } else if (event.key === 'ArrowUp') {
        if (activeItem) {
            var prevItem = activeItem.previousElementSibling;
            if (prevItem) {
                activeItem.classList.remove('active');
                prevItem.classList.add('active');
            }
        }
    } else if (event.key === 'Enter' && activeItem) {
        activeItem.click();
    }
});

function fetchCategories(query) {
    fetch(`actions.php?action=fetchCategories&query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            var suggestionsDiv = document.getElementById("categorySuggestions");

            if (data && data.length > 0) {
                suggestionsDiv.style.display = 'block';
                suggestionsDiv.innerHTML = '';

                data.forEach(function(category) {
                    var suggestionItem = document.createElement("p");
                    suggestionItem.textContent = category.CategoryName;
                    suggestionItem.classList.add('suggestion-item');
                    
                    suggestionItem.addEventListener("click", function() {
                        document.getElementById("categoryName").value = category.CategoryName;
                        suggestionsDiv.style.display = 'none';
                    });

                    suggestionsDiv.appendChild(suggestionItem);
                });
            } else {
                suggestionsDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error("Error fetching categories:", error);
        });
}

//--tags--//
document.getElementById("tags").addEventListener("input", function() {
    var query = this.value.trim();
    
    var queries = query.split(',').map(item => item.trim()).filter(item => item.length > 0);

    if (queries.length > 0) {
        fetchTags(queries[queries.length - 1]);
    } else {
        document.getElementById("tagSuggestions").style.display = 'none';
    }
});

document.getElementById("tags").addEventListener("keydown", function(event) {
    var suggestionsDiv = document.getElementById("tagSuggestions");
    var activeItem = suggestionsDiv.querySelector('.suggestion-item.active');
    
    if (event.key === 'ArrowDown') {
        if (activeItem) {
            var nextItem = activeItem.nextElementSibling;
            if (nextItem) {
                activeItem.classList.remove('active');
                nextItem.classList.add('active');
            }
        } else {
            var firstItem = suggestionsDiv.querySelector('.suggestion-item');
            if (firstItem) firstItem.classList.add('active');
        }
    } else if (event.key === 'ArrowUp') {
        if (activeItem) {
            var prevItem = activeItem.previousElementSibling;
            if (prevItem) {
                activeItem.classList.remove('active');
                prevItem.classList.add('active');
            }
        }
    } else if (event.key === 'Enter' && activeItem) {
        activeItem.click();
    }
});

let lastTagQuery = '';

function fetchTags(query) {
    // Only fetch if the query is different from the last one
    if (query === lastTagQuery) return;
    lastTagQuery = query;

    fetch(`actions.php?action=fetchTags&query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            var suggestionsDiv = document.getElementById("tagSuggestions");

            if (data && data.length > 0) {
                suggestionsDiv.style.display = 'block';
                suggestionsDiv.innerHTML = '';

                data.forEach(function(tag) {
                    var suggestionItem = document.createElement("p");
                    suggestionItem.textContent = tag.TagName;
                    suggestionItem.classList.add('suggestion-item');
                    
                    suggestionItem.addEventListener("click", function() {
                        var currentValue = document.getElementById("tags").value.trim();
                        var queries = currentValue.split(',').map(item => item.trim()).filter(item => item.length > 0);
                        queries[queries.length - 1] = tag.TagName;

                        var inputField = document.getElementById("tags");
                        inputField.value = queries.join(', ');

                        inputField.setSelectionRange(inputField.value.length, inputField.value.length);

                        suggestionsDiv.style.display = 'none';
                    });

                    suggestionsDiv.appendChild(suggestionItem);
                });
            } else {
                suggestionsDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error("Error fetching tags:", error);
        });
}

//--promotional labels--//
document.getElementById("promotionalLabels").addEventListener("input", function() {
    var query = this.value.trim();
    
    var queries = query.split(',').map(item => item.trim()).filter(item => item.length > 0);

    if (queries.length > 0) {
        fetchPromoLabels(queries[queries.length - 1]);
    } else {
        document.getElementById("promoSuggestions").style.display = 'none';
    }
});

document.getElementById("promotionalLabels").addEventListener("keydown", function(event) {
    var suggestionsDiv = document.getElementById("promoSuggestions");
    var activeItem = suggestionsDiv.querySelector('.suggestion-item.active');
    
    if (event.key === 'ArrowDown') {
        if (activeItem) {
            var nextItem = activeItem.nextElementSibling;
            if (nextItem) {
                activeItem.classList.remove('active');
                nextItem.classList.add('active');
            }
        } else {
            var firstItem = suggestionsDiv.querySelector('.suggestion-item');
            if (firstItem) firstItem.classList.add('active');
        }
    } else if (event.key === 'ArrowUp') {
        if (activeItem) {
            var prevItem = activeItem.previousElementSibling;
            if (prevItem) {
                activeItem.classList.remove('active');
                prevItem.classList.add('active');
            }
        }
    } else if (event.key === 'Enter' && activeItem) {
        activeItem.click();
    }
});

let lastPromoQuery = '';

function fetchPromoLabels(query) {
    // Only fetch if the query is different from the last one
    if (query === lastPromoQuery) return;
    lastPromoQuery = query;

    fetch(`actions.php?action=fetchPromoLabels&query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            var suggestionsDiv = document.getElementById("promoSuggestions");

            if (data && data.length > 0) {
                suggestionsDiv.style.display = 'block';
                suggestionsDiv.innerHTML = '';

                data.forEach(function(label) {
                    var suggestionItem = document.createElement("p");
                    suggestionItem.textContent = label.Name;
                    suggestionItem.classList.add('suggestion-item');
                    
                    suggestionItem.addEventListener("click", function() {
                        var currentValue = document.getElementById("promotionalLabels").value.trim();
                        var queries = currentValue.split(',').map(item => item.trim()).filter(item => item.length > 0);
                        queries[queries.length - 1] = label.Name;

                        var inputField = document.getElementById("promotionalLabels");
                        inputField.value = queries.join(', ');

                        inputField.setSelectionRange(inputField.value.length, inputField.value.length);

                        suggestionsDiv.style.display = 'none';
                    });

                    suggestionsDiv.appendChild(suggestionItem);
                });
            } else {
                suggestionsDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error("Error fetching labels:", error);
        });
}

//--discount labels--//
document.getElementById("discountLabels").addEventListener("input", function() {
    var query = this.value.trim();
    
    var queries = query.split(',').map(item => item.trim()).filter(item => item.length > 0);

    if (queries.length > 0) {
        fetchDiscLabels(queries[queries.length - 1]);
    } else {
        document.getElementById("discSuggestions").style.display = 'none';
    }
});

document.getElementById("discountLabels").addEventListener("keydown", function(event) {
    var suggestionsDiv = document.getElementById("discSuggestions");
    var activeItem = suggestionsDiv.querySelector('.suggestion-item.active');
    
    if (event.key === 'ArrowDown') {
        if (activeItem) {
            var nextItem = activeItem.nextElementSibling;
            if (nextItem) {
                activeItem.classList.remove('active');
                nextItem.classList.add('active');
            }
        } else {
            var firstItem = suggestionsDiv.querySelector('.suggestion-item');
            if (firstItem) firstItem.classList.add('active');
        }
    } else if (event.key === 'ArrowUp') {
        if (activeItem) {
            var prevItem = activeItem.previousElementSibling;
            if (prevItem) {
                activeItem.classList.remove('active');
                prevItem.classList.add('active');
            }
        }
    } else if (event.key === 'Enter' && activeItem) {
        activeItem.click();
    }
});

let lastDiscQuery = '';

function fetchDiscLabels(query) {
    // Only fetch if the query is different from the last one
    if (query === lastDiscQuery) return;
    lastDiscQuery = query;

    fetch(`actions.php?action=fetchDiscLabels&query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            var suggestionsDiv = document.getElementById("discSuggestions");

            if (data && data.length > 0) {
                suggestionsDiv.style.display = 'block';
                suggestionsDiv.innerHTML = '';

                data.forEach(function(label) {
                    var suggestionItem = document.createElement("p");
                    suggestionItem.textContent = label.Name;
                    suggestionItem.classList.add('suggestion-item');
                    
                    suggestionItem.addEventListener("click", function() {
                        var currentValue = document.getElementById("discountLabels").value.trim();
                        var queries = currentValue.split(',').map(item => item.trim()).filter(item => item.length > 0);
                        queries[queries.length - 1] = label.Name;

                        var inputField = document.getElementById("tags");
                        inputField.value = queries.join(', ');

                        inputField.setSelectionRange(inputField.value.length, inputField.value.length);

                        suggestionsDiv.style.display = 'none';
                    });

                    suggestionsDiv.appendChild(suggestionItem);
                });
            } else {
                suggestionsDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error("Error fetching labels:", error);
        });
}

//--additional info keys--//

//--customization options--//

//--special categories--//
document.getElementById("specialCategories").addEventListener("input", function() {
    var query = this.value.trim();
    
    var queries = query.split(',').map(item => item.trim()).filter(item => item.length > 0);

    if (queries.length > 0) {
        fetchSpecialCategories(queries[queries.length - 1]);
    } else {
        document.getElementById("specialCategorySuggestions").style.display = 'none';
    }
});

document.getElementById("specialCategories").addEventListener("keydown", function(event) {
    var suggestionsDiv = document.getElementById("specialCategorySuggestions");
    var activeItem = suggestionsDiv.querySelector('.suggestion-item.active');
    
    if (event.key === 'ArrowDown') {
        if (activeItem) {
            var nextItem = activeItem.nextElementSibling;
            if (nextItem) {
                activeItem.classList.remove('active');
                nextItem.classList.add('active');
            }
        } else {
            var firstItem = suggestionsDiv.querySelector('.suggestion-item');
            if (firstItem) firstItem.classList.add('active');
        }
    } else if (event.key === 'ArrowUp') {
        if (activeItem) {
            var prevItem = activeItem.previousElementSibling;
            if (prevItem) {
                activeItem.classList.remove('active');
                prevItem.classList.add('active');
            }
        }
    } else if (event.key === 'Enter' && activeItem) {
        activeItem.click();
    }
});

let lastSpecialCategQuery = '';

function fetchSpecialCategories(query) {
    // Only fetch if the query is different from the last one
    if (query === lastSpecialCategQuery) return;
    lastSpecialCategQuery = query;

    fetch(`actions.php?action=fetchSpecialCategories&query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            var suggestionsDiv = document.getElementById("specialCategorySuggestions");

            if (data && data.length > 0) {
                suggestionsDiv.style.display = 'block';
                suggestionsDiv.innerHTML = '';

                data.forEach(function(category) {
                    var suggestionItem = document.createElement("p");
                    suggestionItem.textContent = category.Name;
                    suggestionItem.classList.add('suggestion-item');
                    
                    suggestionItem.addEventListener("click", function() {
                        var currentValue = document.getElementById("specialCategories").value.trim();
                        var queries = currentValue.split(',').map(item => item.trim()).filter(item => item.length > 0);
                        queries[queries.length - 1] = category.Name;

                        var inputField = document.getElementById("specialCategories");
                        inputField.value = queries.join(', ');

                        inputField.setSelectionRange(inputField.value.length, inputField.value.length);

                        suggestionsDiv.style.display = 'none';
                    });

                    suggestionsDiv.appendChild(suggestionItem);
                });
            } else {
                suggestionsDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error("Error fetching categories:", error);
        });
}