document.addEventListener('DOMContentLoaded', function() {
    // Initialize dropdown menus
    initDropdowns();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize sortable lists
    initSortable();
    
    // Initialize WYSIWYG editor for rich text fields
    initRichTextEditor();
    
    // Initialize protocol editor
    initProtocolEditor();
});

/**
 * Initialize dropdown menu functionality
 */
function initDropdowns() {
    const dropdownTriggers = document.querySelectorAll('.dropdown-trigger');
    
    dropdownTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const dropdown = this.nextElementSibling;
            
            // Toggle dropdown visibility
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            } else {
                // Close other open dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
                
                dropdown.style.display = 'block';
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            
            // Get all required fields
            const requiredFields = form.querySelectorAll('[required]');
            
            // Clear previous error messages
            form.querySelectorAll('.field-error').forEach(error => {
                error.remove();
            });
            
            // Check each required field
            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    hasErrors = true;
                    
                    // Add error message
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'field-error';
                    errorMsg.textContent = 'This field is required';
                    
                    // Insert after field
                    field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    
                    // Highlight field
                    field.classList.add('error');
                }
            });
            
            // If there are errors, prevent form submission
            if (hasErrors) {
                e.preventDefault();
                
                // Scroll to first error
                const firstError = form.querySelector('.field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
}

/**
 * Initialize sortable lists functionality
 */
function initSortable() {
    const sortableLists = document.querySelectorAll('.sortable-list');
    
    sortableLists.forEach(list => {
        // Implementation depends on additional libraries like SortableJS
        // This is a placeholder for where you would initialize sortable functionality
        if (typeof Sortable !== 'undefined') {
            new Sortable(list, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    // Update sort order in hidden inputs
                    updateSortOrder(list);
                }
            });
        }
    });
}

/**
 * Update sort order in hidden inputs after sorting
 * @param {Element} list - The sorted list element
 */
function updateSortOrder(list) {
    const items = list.querySelectorAll('.sortable-item');
    
    items.forEach((item, index) => {
        const orderInput = item.querySelector('input[name*="sort_order"]');
        if (orderInput) {
            orderInput.value = index;
        }
    });
}

/**
 * Initialize WYSIWYG editor for rich text fields
 */
function initRichTextEditor() {
    const richTextFields = document.querySelectorAll('.rich-text-editor');
    
    richTextFields.forEach(field => {
        // This is a placeholder for where you would initialize a WYSIWYG editor
        // You could use libraries like TinyMCE, CKEditor, or Quill
        if (typeof ClassicEditor !== 'undefined') {
            ClassicEditor
                .create(field)
                .catch(error => {
                    console.error('WYSIWYG editor initialization failed:', error);
                });
        }
    });
}

/**
 * Initialize the protocol editor functionality
 */
function initProtocolEditor() {
    const protocolEditor = document.getElementById('protocol-editor');
    
    if (!protocolEditor) return;
    
    // Add section button
    const addSectionBtn = document.getElementById('add-section');
    if (addSectionBtn) {
        addSectionBtn.addEventListener('click', function() {
            addNewSection();
        });
    }
    
    // Section type change handler
    const sectionTypes = document.querySelectorAll('.section-type-select');
    sectionTypes.forEach(select => {
        select.addEventListener('change', function() {
            updateSectionFields(this);
        });
    });
    
    // Add item buttons
    const addItemBtns = document.querySelectorAll('.add-item-btn');
    addItemBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-section-id');
            addNewItem(sectionId);
        });
    });
    
    // Remove buttons
    const removeBtns = document.querySelectorAll('.remove-btn');
    removeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            document.getElementById(target).remove();
        });
    });
    
    // Provider level checkboxes
    const providerCheckboxes = document.querySelectorAll('.provider-checkbox');
    providerCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleProviderPercentage(this);
        });
    });
}

/**
 * Add a new section to the protocol editor
 */
function addNewSection() {
    const sectionsContainer = document.getElementById('sections-container');
    const sectionTemplate = document.getElementById('section-template');
    
    if (!sectionsContainer || !sectionTemplate) return;
    
    // Clone the template
    const newSection = sectionTemplate.content.cloneNode(true);
    
    // Update IDs and attributes
    const sectionId = 'section-' + Date.now();
    newSection.querySelector('.section').id = sectionId;
    
    // Setup event listeners for the new section
    const removeBtn = newSection.querySelector('.remove-btn');
    removeBtn.setAttribute('data-target', sectionId);
    removeBtn.addEventListener('click', function() {
        document.getElementById(sectionId).remove();
    });
    
    const typeSelect = newSection.querySelector('.section-type-select');
    typeSelect.addEventListener('change', function() {
        updateSectionFields(this);
    });
    
    const addItemBtn = newSection.querySelector('.add-item-btn');
    addItemBtn.setAttribute('data-section-id', sectionId);
    addItemBtn.addEventListener('click', function() {
        addNewItem(sectionId);
    });
    
    // Add to the container
    sectionsContainer.appendChild(newSection);
}

/**
 * Update section fields based on selected section type
 * @param {Element} select - The section type select element
 */
function updateSectionFields(select) {
    const section = select.closest('.section');
    const sectionType = select.value;
    
    // Hide all type-specific fields
    section.querySelectorAll('.type-specific').forEach(field => {
        field.style.display = 'none';
    });
    
    // Show fields specific to the selected type
    section.querySelectorAll('.type-' + sectionType).forEach(field => {
        field.style.display = 'block';
    });
}

/**
 * Add a new item to a section
 * @param {string} sectionId - The ID of the section to add the item to
 */
function addNewItem(sectionId) {
    const itemsContainer = document.querySelector('#' + sectionId + ' .items-container');
    const itemTemplate = document.getElementById('item-template');
    
    if (!itemsContainer || !itemTemplate) return;
    
    // Clone the template
    const newItem = itemTemplate.content.cloneNode(true);
    
    // Update IDs and attributes
    const itemId = 'item-' + Date.now();
    newItem.querySelector('.item').id = itemId;
    
    // Setup event listeners for the new item
    const removeBtn = newItem.querySelector('.remove-btn');
    removeBtn.setAttribute('data-target', itemId);
    removeBtn.addEventListener('click', function() {
        document.getElementById(itemId).remove();
    });
    
    const providerCheckboxes = newItem.querySelectorAll('.provider-checkbox');
    providerCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleProviderPercentage(this);
        });
    });
    
    // Add to the container
    itemsContainer.appendChild(newItem);
}

/**
 * Toggle provider percentage input based on checkbox state
 * @param {Element} checkbox - The provider checkbox element
 */
function toggleProviderPercentage(checkbox) {
    const percentageInput = checkbox.closest('.provider-option').querySelector('.provider-percentage');
    
    if (checkbox.checked) {
        percentageInput.style.display = 'inline-block';
    } else {
        percentageInput.style.display = 'none';
        percentageInput.value = '0';
    }
}