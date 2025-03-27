document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editors
    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link']
            ]
        },
        placeholder: 'Enter detailed information here...'
    });
    
    const newItemQuill = new Quill('#item-detailed-info', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link']
            ]
        },
        placeholder: 'Enter detailed information here...'
    });
    
    const newCriterionQuill = new Quill('#criterion-detailed-info', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link']
            ]
        },
        placeholder: 'Enter detailed information here...'
    });
    
    // Initialize event listeners for edit controls
    initEditControls();
    
    // Initialize modals
    initModals();
    
    // Initialize inline editing for fields that don't need modals
    initInlineEditing();
    
    // Initialize add section button
    document.getElementById('add-section-btn').addEventListener('click', function() {
        openModal('add-section-modal');
    });
    
    // Initialize save protocol button
    document.getElementById('save-protocol').addEventListener('click', function() {
        saveProtocol();
    });
    
    /**
     * Initialize edit control buttons
     */
    function initEditControls() {
        // Edit button click event
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-btn') || e.target.parentElement.classList.contains('edit-btn')) {
                const button = e.target.classList.contains('edit-btn') ? e.target : e.target.parentElement;
                const editableElement = button.closest('.editable');
                
                if (editableElement) {
                    const fieldType = editableElement.dataset.type;
                    const fieldName = editableElement.dataset.field;
                    let idType, idValue;
                    
                    // Determine ID type and value
                    if (editableElement.dataset.itemId) {
                        idType = 'item_id';
                        idValue = editableElement.dataset.itemId;
                    } else if (editableElement.dataset.sectionId) {
                        idType = 'section_id';
                        idValue = editableElement.dataset.sectionId;
                    } else if (editableElement.dataset.subitemId) {
                        idType = 'subitem_id';
                        idValue = editableElement.dataset.subitemId;
                    } else if (editableElement.dataset.id) {
                        idType = 'id';
                        idValue = editableElement.dataset.id;
                    }
                    
                    // Open appropriate edit modal based on field type
                    if (fieldType === 'text') {
                        const content = editableElement.textContent.trim();
                        openTextEditModal(fieldType, fieldName, idType, idValue, content);
                    } else if (fieldType === 'textarea') {
                        const content = editableElement.textContent.trim();
                        openTextareaEditModal(fieldType, fieldName, idType, idValue, content);
                    }
                }
                
                e.stopPropagation();
            }
        });
        
        // Edit info button click event
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-info-btn') || e.target.parentElement.classList.contains('edit-info-btn')) {
                const button = e.target.classList.contains('edit-info-btn') ? e.target : e.target.parentElement;
                const editableItem = button.closest('.editable-item, .editable-subitem');
                
                if (editableItem) {
                    let idType, idValue;
                    
                    // Determine ID type and value
                    if (editableItem.dataset.itemId) {
                        idType = 'item_id';
                        idValue = editableItem.dataset.itemId;
                    } else if (editableItem.dataset.subitemId) {
                        idType = 'subitem_id';
                        idValue = editableItem.dataset.subitemId;
                    }
                    
                    const detailedInfo = editableItem.dataset.info || '';
                    openRichEditModal(idType, idValue, detailedInfo);
                }
                
                e.stopPropagation();
            }
        });
        
        // Edit providers button click event
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-providers-btn') || e.target.parentElement.classList.contains('edit-providers-btn')) {
                const button = e.target.classList.contains('edit-providers-btn') ? e.target : e.target.parentElement;
                const editableItem = button.closest('.editable-item');
                
                if (editableItem) {
                    const itemId = editableItem.dataset.itemId;
                    openProvidersEditModal(itemId);
                }
                
                e.stopPropagation();
            }
        });
        
        // Delete button click event
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-btn') || e.target.parentElement.classList.contains('delete-btn')) {
                const button = e.target.classList.contains('delete-btn') ? e.target : e.target.parentElement;
                let deleteType, deleteId;
                
                if (button.closest('.editable-section')) {
                    deleteType = 'section';
                    deleteId = button.closest('.editable-section').dataset.sectionId;
                } else if (button.closest('.editable-item')) {
                    deleteType = 'item';
                    deleteId = button.closest('.editable-item').dataset.itemId;
                } else if (button.closest('.editable-subitem')) {
                    deleteType = 'subitem';
                    deleteId = button.closest('.editable-subitem').dataset.subitemId;
                }
                
                if (deleteType && deleteId) {
                    confirmDelete(deleteType, deleteId);
                }
                
                e.stopPropagation();
            }
        });
        
        // Move up button click event
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('move-up-btn') || e.target.parentElement.classList.contains('move-up-btn')) {
                const button = e.target.classList.contains('move-up-btn') ? e.target : e.target.parentElement;
                let moveType, moveId, container, currentItem;
                
                if (button.closest('.editable-section')) {
                    moveType = 'section';
                    moveId = button.closest('.editable-section').dataset.sectionId;
                    currentItem = button.closest('.editable-section');
                    container = currentItem.parentElement;
                } else if (button.closest('.editable-item')) {
                    moveType = 'item';
                    moveId = button.closest('.editable-item').dataset.itemId;
                    currentItem = button.closest('.editable-item');
                    container = currentItem.parentElement;
                } else if (button.closest('.editable-subitem')) {
                    moveType = 'subitem';
                    moveId = button.closest('.editable-subitem').dataset.subitemId;
                    currentItem = button.closest('.editable-subitem');
                    container = currentItem.parentElement;
                }
                
if (currentItem && container) {
    const nextItem = currentItem.nextElementSibling;
    if (nextItem && (nextItem.classList.contains('editable-section') || 
                     nextItem.classList.contains('editable-item') || 
                     nextItem.classList.contains('editable-subitem'))) {
        container.insertBefore(nextItem, currentItem);
        saveOrder(moveType, moveId, 'down');
    }
}

e.stopPropagation();
            }
        });
        
        // Move down button click event
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('move-down-btn') || e.target.parentElement.classList.contains('move-down-btn')) {
                const button = e.target.classList.contains('move-down-btn') ? e.target : e.target.parentElement;
                let moveType, moveId, container, currentItem;
                
                if (button.closest('.editable-section')) {
                    moveType = 'section';
                    moveId = button.closest('.editable-section').dataset.sectionId;
                    currentItem = button.closest('.editable-section');
                    container = currentItem.parentElement;
                } else if (button.closest('.editable-item')) {
                    moveType = 'item';
                    moveId = button.closest('.editable-item').dataset.itemId;
                    currentItem = button.closest('.editable-item');
                    container = currentItem.parentElement;
                } else if (button.closest('.editable-subitem')) {
                    moveType = 'subitem';
                    moveId = button.closest('.editable-subitem').dataset.subitemId;
                    currentItem = button.closest('.editable-subitem');
                    container = currentItem.parentElement;
                }
                
                if (currentItem && container) {
                        const nextItem = currentItem.nextElementSibling;
                        if (nextItem && (nextItem.classList.contains('editable-section') || 
                                         nextItem.classList.contains('editable-item') || 
                                         nextItem.classList.contains('editable-subitem'))) {
                            container.insertBefore(nextItem, currentItem);
                            saveOrder(moveType, moveId, 'down');
                        }
                    }
                    
                    e.stopPropagation();
                }
            });
            
            // Add item button click event
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('add-item-btn') || e.target.closest('.add-item-btn')) {
                    const button = e.target.classList.contains('add-item-btn') ? e.target : e.target.closest('.add-item-btn');
                    const sectionId = button.dataset.sectionId;
                    const itemType = button.dataset.itemType;
                    
                    if (sectionId) {
                        openAddItemModal(sectionId, itemType);
                    }
                    
                    e.stopPropagation();
                }
            });
            
            // Add criteria button click event
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('add-criteria-btn') || e.target.closest('.add-criteria-btn')) {
                    const button = e.target.classList.contains('add-criteria-btn') ? e.target : e.target.closest('.add-criteria-btn');
                    const itemId = button.dataset.itemId;
                    
                    if (itemId) {
                        openAddCriterionModal(itemId);
                    }
                    
                    e.stopPropagation();
                }
            });
        }
        
        /**
         * Initialize modals
         */
        function initModals() {
            // Close modal buttons
            const closeButtons = document.querySelectorAll('.edit-modal-close, .cancel-edit');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    closeAllModals();
                });
            });
            
            // Close modals when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('edit-modal')) {
                    closeAllModals();
                }
            });
            
            // Save text edit
            document.getElementById('save-text-edit').addEventListener('click', function() {
                const fieldType = document.getElementById('edit-field-type').value;
                const fieldName = document.getElementById('edit-field-name').value;
                const idType = document.getElementById('edit-id-type').value;
                const idValue = document.getElementById('edit-id-value').value;
                const content = document.getElementById('text-edit-input').value;
                
                saveFieldEdit(fieldType, fieldName, idType, idValue, content);
            });
            
            // Save textarea edit
            document.getElementById('save-textarea-edit').addEventListener('click', function() {
                const fieldType = document.getElementById('textarea-field-type').value;
                const fieldName = document.getElementById('textarea-field-name').value;
                const idType = document.getElementById('textarea-id-type').value;
                const idValue = document.getElementById('textarea-id-value').value;
                const content = document.getElementById('textarea-edit-input').value;
                
                saveFieldEdit(fieldType, fieldName, idType, idValue, content);
            });
            
            // Save rich text edit
            document.getElementById('save-rich-edit').addEventListener('click', function() {
                const idType = document.getElementById('rich-id-type').value;
                const idValue = document.getElementById('rich-id-value').value;
                const content = quill.root.innerHTML;
                
                saveDetailedInfo(idType, idValue, content);
            });
            
            // Save provider levels edit
            document.getElementById('save-providers-edit').addEventListener('click', function() {
                const itemId = document.getElementById('providers-id-value').value;
                const providers = [];
                
                document.querySelectorAll('#providers-edit-modal .provider-checkbox').forEach(checkbox => {
                    if (checkbox.checked) {
                        const providerId = checkbox.dataset.providerId;
                        const percentageInput = checkbox.closest('.provider-option').querySelector('.provider-percentage');
                        const percentage = percentageInput.value || 0;
                        
                        providers.push({
                            provider_id: providerId,
                            percentage: percentage
                        });
                    }
                });
                
                saveProviderLevels(itemId, providers);
            });
            
            // Save new section
            document.getElementById('save-new-section').addEventListener('click', function() {
                const sectionType = document.getElementById('section-type').value;
                const title = document.getElementById('section-title').value;
                
                if (!sectionType) {
                    alert('Please select a section type.');
                    return;
                }
                
                if (!title) {
                    alert('Please enter a section title.');
                    return;
                }
                
                addSection(sectionType, title);
            });
            
            // Save new item
            document.getElementById('save-new-item').addEventListener('click', function() {
                const sectionId = document.getElementById('parent-section-id').value;
                const itemType = document.getElementById('item-type').value;
                const title = document.getElementById('item-title').value;
                const content = document.getElementById('item-content').value;
                const detailedInfo = newItemQuill.root.innerHTML;
                const isDecision = document.getElementById('is-decision').checked;
                
                if (!content) {
                    alert('Please enter item content.');
                    return;
                }
                
                // Get provider levels
                const providers = [];
                document.querySelectorAll('#add-item-modal .provider-checkbox').forEach(checkbox => {
                    if (checkbox.checked) {
                        const providerId = checkbox.dataset.providerId;
                        const percentageInput = checkbox.closest('.provider-option').querySelector('.provider-percentage');
                        const percentage = percentageInput.value || 0;
                        
                        providers.push({
                            provider_id: providerId,
                            percentage: percentage
                        });
                    }
                });
                
                addItem(sectionId, itemType, title, content, detailedInfo, isDecision, providers);
            });
            
            // Save new criterion
            document.getElementById('save-new-criterion').addEventListener('click', function() {
                const parentItemId = document.getElementById('parent-item-id').value;
                const content = document.getElementById('criterion-content').value;
                const detailedInfo = newCriterionQuill.root.innerHTML;
                
                if (!content) {
                    alert('Please enter criterion content.');
                    return;
                }
                
                addCriterion(parentItemId, content, detailedInfo);
            });
            
            // Confirm delete
            document.getElementById('confirm-delete').addEventListener('click', function() {
                const deleteType = document.getElementById('delete-type').value;
                const deleteId = document.getElementById('delete-id').value;
                
                deleteItem(deleteType, deleteId);
            });
            
            // Provider checkbox change event
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('provider-checkbox')) {
                    const percentageInput = e.target.closest('.provider-option').querySelector('.provider-percentage');
                    
                    if (e.target.checked) {
                        percentageInput.style.display = 'inline-block';
                        percentageInput.value = 100;
                    } else {
                        percentageInput.style.display = 'none';
                        percentageInput.value = 0;
                    }
                }
            });
        }
        
        /**
         * Initialize inline editing for simple fields
         */
        function initInlineEditing() {
            document.querySelectorAll('.inline-edit').forEach(field => {
                field.addEventListener('change', function() {
                    const fieldType = this.dataset.type;
                    const fieldName = this.dataset.field;
                    const idType = this.dataset.type === 'checkbox' ? 'id' : 'id';
                    const idValue = this.dataset.id;
                    let content;
                    
                    if (fieldType === 'checkbox') {
                        content = this.checked ? 1 : 0;
                    } else {
                        content = this.value;
                    }
                    
                    saveFieldEdit(fieldType, fieldName, idType, idValue, content);
                });
            });
        }
        
        /**
         * Open text edit modal
         */
        function openTextEditModal(fieldType, fieldName, idType, idValue, content) {
            document.getElementById('edit-field-type').value = fieldType;
            document.getElementById('edit-field-name').value = fieldName;
            document.getElementById('edit-id-type').value = idType;
            document.getElementById('edit-id-value').value = idValue;
            document.getElementById('text-edit-input').value = content;
            
            openModal('text-edit-modal');
        }
        
        /**
         * Open textarea edit modal
         */
        function openTextareaEditModal(fieldType, fieldName, idType, idValue, content) {
            document.getElementById('textarea-field-type').value = fieldType;
            document.getElementById('textarea-field-name').value = fieldName;
            document.getElementById('textarea-id-type').value = idType;
            document.getElementById('textarea-id-value').value = idValue;
            document.getElementById('textarea-edit-input').value = content;
            
            openModal('textarea-edit-modal');
        }
        
        /**
         * Open rich text edit modal
         */
        function openRichEditModal(idType, idValue, content) {
            document.getElementById('rich-id-type').value = idType;
            document.getElementById('rich-id-value').value = idValue;
            quill.root.innerHTML = content;
            
            openModal('rich-edit-modal');
        }
        
        /**
         * Open providers edit modal
         */
        function openProvidersEditModal(itemId) {
            document.getElementById('providers-id-value').value = itemId;
            
            // Fetch current provider levels
            fetch('ajax/get-provider-levels.php?item_id=' + itemId)
                .then(response => response.json())
                .then(data => {
                    // Reset all checkboxes and values
                    document.querySelectorAll('#providers-edit-modal .provider-checkbox').forEach(checkbox => {
                        checkbox.checked = false;
                        const percentageInput = checkbox.closest('.provider-option').querySelector('.provider-percentage');
                        percentageInput.value = 0;
                        percentageInput.style.display = 'none';
                    });
                    
                    // Set checkboxes and values based on data
                    data.forEach(provider => {
                        const checkbox = document.querySelector(`#providers-edit-modal .provider-checkbox[data-provider-id="${provider.provider_id}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            const percentageInput = checkbox.closest('.provider-option').querySelector('.provider-percentage');
                            percentageInput.value = provider.percentage;
                            percentageInput.style.display = 'inline-block';
                        }
                    });
                    
                    openModal('providers-edit-modal');
                })
                .catch(error => {
                    console.error('Error fetching provider levels:', error);
                    alert('Error fetching provider levels. Please try again.');
                });
        }
        
        /**
         * Open add item modal
         */
        function openAddItemModal(sectionId, itemType) {
            document.getElementById('parent-section-id').value = sectionId;
            document.getElementById('item-type').value = itemType;
            
            // Reset form
            document.getElementById('item-title').value = '';
            document.getElementById('item-content').value = '';
            newItemQuill.root.innerHTML = '';
            document.getElementById('is-decision').checked = false;
            
            // Show/hide form fields based on item type
            if (itemType === 'assessment') {
                document.getElementById('item-title-group').style.display = 'block';
                document.getElementById('decision-checkbox-group').style.display = 'none';
            } else if (itemType === 'flow-step') {
                document.getElementById('item-title-group').style.display = 'none';
                document.getElementById('decision-checkbox-group').style.display = 'block';
            } else if (itemType === 'decision') {
                document.getElementById('item-title-group').style.display = 'none';
                document.getElementById('decision-checkbox-group').style.display = 'none';
                document.getElementById('is-decision').checked = true;
            } else {
                document.getElementById('item-title-group').style.display = 'none';
                document.getElementById('decision-checkbox-group').style.display = 'none';
            }
            
            // Reset provider checkboxes
            document.querySelectorAll('#add-item-modal .provider-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                const percentageInput = checkbox.closest('.provider-option').querySelector('.provider-percentage');
                percentageInput.value = 0;
                percentageInput.style.display = 'none';
            });
            
            openModal('add-item-modal');
        }
        
        /**
         * Open add criterion modal
         */
        function openAddCriterionModal(itemId) {
            document.getElementById('parent-item-id').value = itemId;
            
            // Reset form
            document.getElementById('criterion-content').value = '';
            newCriterionQuill.root.innerHTML = '';
            
            openModal('add-criterion-modal');
        }
        
        /**
         * Confirm delete
         */
        function confirmDelete(deleteType, deleteId) {
            document.getElementById('delete-type').value = deleteType;
            document.getElementById('delete-id').value = deleteId;
            
            openModal('delete-confirm-modal');
        }
        
        /**
         * Open a modal by ID
         */
        function openModal(modalId) {
            // Close all modals first
            closeAllModals();
            
            // Open the specified modal
            const modal = document.getElementById(modalId);
            modal.style.display = 'block';
            
            // Add a slight delay before adding the show class for animation
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }
        
        /**
         * Close all modals
         */
        function closeAllModals() {
            document.querySelectorAll('.edit-modal').forEach(modal => {
                modal.classList.remove('show');
                
                // Wait for animation to complete before hiding
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            });
        }
        
        /**
         * Save field edit
         */
        function saveFieldEdit(fieldType, fieldName, idType, idValue, content) {
            const data = new FormData();
            data.append('field_type', fieldType);
            data.append('field_name', fieldName);
            data.append('id_type', idType);
            data.append('id_value', idValue);
            data.append('content', content);
            
            showSavingIndicator();
            
            fetch('ajax/save-field.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the element on the page
                    updateElement(fieldType, fieldName, idType, idValue, content);
                    closeAllModals();
                } else {
                    alert('Error saving content: ' + data.message);
                }
                hideSavingIndicator();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving content. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Save detailed info
         */
        function saveDetailedInfo(idType, idValue, content) {
            const data = new FormData();
            data.append('id_type', idType);
            data.append('id_value', idValue);
            data.append('content', content);
            
            showSavingIndicator();
            
            fetch('ajax/save-detailed-info.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the data-info attribute
                    const element = document.querySelector(`.editable-item[data-${idType}="${idValue}"], .editable-subitem[data-${idType}="${idValue}"]`);
                    if (element) {
                        element.dataset.info = content;
                    }
                    closeAllModals();
                } else {
                    alert('Error saving detailed info: ' + data.message);
                }
                hideSavingIndicator();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving detailed info. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Save provider levels
         */
        function saveProviderLevels(itemId, providers) {
            const data = new FormData();
            data.append('item_id', itemId);
            data.append('providers', JSON.stringify(providers));
            
            showSavingIndicator();
            
            fetch('ajax/save-provider-levels.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the provider bar on the page
                    updateProviderBar(itemId, providers);
                    closeAllModals();
                } else {
                    alert('Error saving provider levels: ' + data.message);
                }
                hideSavingIndicator();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving provider levels. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Save item order
         */
        function saveOrder(type, id, direction) {
            const data = new FormData();
            data.append('type', type);
            data.append('id', id);
            data.append('direction', direction);
            
            showSavingIndicator();
            
            fetch('ajax/save-order.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error saving order: ' + data.message);
                }
                hideSavingIndicator();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving order. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Add a new section
         */
        function addSection(sectionType, title) {
            const data = new FormData();
            data.append('section_type', sectionType);
            data.append('title', title);
            data.append('protocol_id', <?= $protocol_id; ?>);
            
            showSavingIndicator();
            
            fetch('ajax/add-section.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show the new section
                    window.location.reload();
                } else {
                    alert('Error adding section: ' + data.message);
                    hideSavingIndicator();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding section. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Add a new item
         */
        function addItem(sectionId, itemType, title, content, detailedInfo, isDecision, providers) {
            const data = new FormData();
            data.append('section_id', sectionId);
            data.append('item_type', itemType);
            data.append('title', title);
            data.append('content', content);
            data.append('detailed_info', detailedInfo);
            data.append('is_decision', isDecision ? 1 : 0);
            data.append('providers', JSON.stringify(providers));
            
            showSavingIndicator();
            
            fetch('ajax/add-item.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show the new item
                    window.location.reload();
                } else {
                    alert('Error adding item: ' + data.message);
                    hideSavingIndicator();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding item. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Add a new criterion
         */
        function addCriterion(parentItemId, content, detailedInfo) {
            const data = new FormData();
            data.append('parent_item_id', parentItemId);
            data.append('content', content);
            data.append('detailed_info', detailedInfo);
            
            showSavingIndicator();
            
            fetch('ajax/add-criterion.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show the new criterion
                    window.location.reload();
                } else {
                    alert('Error adding criterion: ' + data.message);
                    hideSavingIndicator();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding criterion. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Delete an item
         */
        function deleteItem(deleteType, deleteId) {
            const data = new FormData();
            data.append('type', deleteType);
            data.append('id', deleteId);
            
            showSavingIndicator();
            
            fetch('ajax/delete-item.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the element from the page or reload if it's a section
                    if (deleteType === 'section') {
                        window.location.reload();
                    } else {
                        const element = document.querySelector(`.editable-${deleteType}[data-${deleteType}-id="${deleteId}"]`);
                        if (element) {
                            element.remove();
                        }
                    }
                    closeAllModals();
                } else {
                    alert('Error deleting item: ' + data.message);
                }
                hideSavingIndicator();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting item. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Save the entire protocol
         */
        function saveProtocol() {
            const protocol_number = document.getElementById('protocol_number').value;
            const category_id = document.getElementById('category_id').value;
            const is_published = document.getElementById('is_published').checked ? 1 : 0;
            const description = document.getElementById('description').value;
            
            const data = new FormData();
            data.append('protocol_id', <?= $protocol_id; ?>);
            data.append('protocol_number', protocol_number);
            data.append('category_id', category_id);
            data.append('is_published', is_published);
            data.append('description', description);
            
            showSavingIndicator();
            
            fetch('ajax/save-protocol.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Protocol saved successfully!');
                } else {
                    alert('Error saving protocol: ' + data.message);
                }
                hideSavingIndicator();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving protocol. Please try again.');
                hideSavingIndicator();
            });
        }
        
        /**
         * Update an element on the page after saving
         */
        function updateElement(fieldType, fieldName, idType, idValue, content) {
            let selector;
            
            if (idType === 'id') {
                selector = `[data-field="${fieldName}"][data-id="${idValue}"]`;
            } else {
                selector = `[data-field="${fieldName}"][data-${idType}="${idValue}"]`;
            }
            
            const element = document.querySelector(selector);
            
            if (element) {
                if (fieldType === 'text' || fieldType === 'textarea') {
                    // For text and textarea, update the inner text
                    element.textContent = content;
                } else if (fieldType === 'checkbox') {
                    // For checkboxes, update the checked property
                    element.checked = (content === '1' || content === 1 || content === true);
                } else if (fieldType === 'select') {
                    // For select elements, update the selected option
                    element.value = content;
                }
            }
        }
        
        /**
         * Update provider bar after saving
         */
        function updateProviderBar(itemId, providers) {
            const item = document.querySelector(`.editable-item[data-item-id="${itemId}"]`);
            
            if (item) {
                let providerBar = item.querySelector('.provider-bar');
                
                if (!providerBar && providers.length > 0) {
                    // Create provider bar if it doesn't exist
                    providerBar = document.createElement('div');
                    providerBar.className = 'provider-bar';
                    item.insertBefore(providerBar, item.firstChild);
                }
                
                if (providerBar) {
                    if (providers.length === 0) {
                        // Remove provider bar if no providers
                        providerBar.remove();
                    } else {
                        // Update provider segments
                        providerBar.innerHTML = '';
                        
                        providers.forEach(provider => {
                            const providerLevel = get_provider_level(provider.provider_id);
                            const providerClass = providerLevel.shortname ? providerLevel.shortname.toLowerCase() : '';
                            
                            const segment = document.createElement('div');
                            segment.className = `provider-segment ${providerClass}`;
                            segment.style.width = `${provider.percentage}%`;
                            
                            providerBar.appendChild(segment);
                        });
                    }
                }
            }
        }
        
        /**
         * Show saving indicator
         */
        function showSavingIndicator() {
            const indicator = document.getElementById('saving-indicator');
            indicator.style.display = 'block';
        }
        
        /**
         * Hide saving indicator
         */
        function hideSavingIndicator() {
            const indicator = document.getElementById('saving-indicator');
            indicator.style.display = 'none';
        }
    });