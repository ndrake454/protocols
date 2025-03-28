/**
 * Admin JavaScript
 * 
 * This file contains the JavaScript code for the admin panel.
 * 
 * CHAPTER 1: SIDEBAR FUNCTIONALITY
 * CHAPTER 2: EDITOR INITIALIZATION
 * CHAPTER 3: PROTOCOL BUILDER
 * CHAPTER 4: SORTABLE FUNCTIONALITY
 * CHAPTER 5: FORM VALIDATION
 * CHAPTER 6: UTILITY FUNCTIONS
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // CHAPTER 1: SIDEBAR FUNCTIONALITY
    // ========================================
    
    /**
     * 1.1: Toggle Sidebar
     */
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    }
    
    /**
     * 1.2: Auto-collapse sidebar on mobile
     */
    function handleSidebarOnResize() {
        if (window.innerWidth < 992) {
            document.getElementById('sidebar').classList.add('active');
        } else {
            document.getElementById('sidebar').classList.remove('active');
        }
    }
    
    // Add resize listener
    window.addEventListener('resize', handleSidebarOnResize);
    
    // Initial check
    if (window.innerWidth < 992) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.add('active');
        }
    }
    
    // ========================================
    // CHAPTER 2: EDITOR INITIALIZATION
    // ========================================
    
    /**
     * 2.1: Initialize Quill Editors
     * Setup Quill rich text editors for all .editor elements
     */
    function initEditors() {
        const editorElements = document.querySelectorAll('.editor');
        
        editorElements.forEach(element => {
            const editorId = element.id;
            const hiddenInput = document.querySelector(`input[data-editor="${editorId}"]`);
            
            // Skip if already initialized
            if (element.classList.contains('ql-container')) return;
            
            // Configure toolbar options
            const toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                [{ 'align': [] }],
                ['clean']
            ];
            
            // Create editor instance
            const quill = new Quill(`#${editorId}`, {
                modules: {
                    toolbar: toolbarOptions
                },
                placeholder: 'Compose content here...',
                theme: 'snow'
            });
            
            // If there's initial content, set it
            if (hiddenInput && hiddenInput.value) {
                quill.root.innerHTML = hiddenInput.value;
            }
            
            // Update the hidden input when editor changes
            quill.on('text-change', function() {
                if (hiddenInput) {
                    hiddenInput.value = quill.root.innerHTML;
                }
            });
        });
    }
    
    // Initialize editors if they exist on the page
    if (document.querySelector('.editor')) {
        initEditors();
    }
    
    // ========================================
    // CHAPTER 3: PROTOCOL BUILDER
    // ========================================
    
    /**
     * 3.1: Initialize Protocol Builder
     * Setup the protocol builder interface
     */
    function initProtocolBuilder() {
        const builder = document.getElementById('protocol-builder');
        if (!builder) return;
        
        initSectionControls();
        initBlockControls();
        initBlockTypeSelectors();
        initProviderLevelSelectors();
        initPreview();
    }
    
    /**
     * 3.2: Initialize Section Controls
     * Setup controls for adding, editing, and removing sections
     */
    function initSectionControls() {
        // Add section button
        const addSectionBtn = document.getElementById('add-section-btn');
        if (addSectionBtn) {
            addSectionBtn.addEventListener('click', addNewSection);
        }
        
        // Edit section buttons
        const editSectionBtns = document.querySelectorAll('.edit-section-btn');
        editSectionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const sectionId = this.closest('.section-container').getAttribute('data-section-id');
                editSection(sectionId);
            });
        });
        
        // Remove section buttons
        const removeSectionBtns = document.querySelectorAll('.remove-section-btn');
        removeSectionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const sectionContainer = this.closest('.section-container');
                if (confirm('Are you sure you want to remove this section?')) {
                    sectionContainer.remove();
                    updateSectionOrder();
                }
            });
        });
    }
    
    /**
     * 3.3: Add New Section
     * Add a new section to the protocol builder
     */
    function addNewSection() {
        const sectionCount = document.querySelectorAll('.section-container').length;
        const sectionId = 'new-section-' + Date.now();
        
        const sectionHTML = `
            <div class="section-container" data-section-id="${sectionId}">
                <div class="section-header">
                    <h3 class="section-title">New Section</h3>
                    <div class="section-actions">
                        <button type="button" class="btn btn-sm btn-primary edit-section-btn">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-danger remove-section-btn">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="section-content">
                    <input type="hidden" name="sections[${sectionCount}][id]" value="${sectionId}">
                    <input type="hidden" name="sections[${sectionCount}][title]" value="New Section">
                    <input type="hidden" name="sections[${sectionCount}][order]" value="${sectionCount}">
                    <input type="hidden" name="sections[${sectionCount}][type]" value="standard">
                    <p class="text-muted">No blocks added yet. Click "Add Block" to add content.</p>
                    <div class="blocks-container" data-section-id="${sectionId}"></div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-success add-block-btn" data-section-id="${sectionId}">
                            <i class="fas fa-plus"></i> Add Block
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        const sectionsContainer = document.getElementById('sections-container');
        sectionsContainer.insertAdjacentHTML('beforeend', sectionHTML);
        
        // Reinitialize controls
        initSectionControls();
        initBlockControls();
        
        // Update section order
        updateSectionOrder();
    }
    
    /**
     * 3.4: Edit Section
     * Open modal to edit a section's properties
     */
    function editSection(sectionId) {
        const section = document.querySelector(`.section-container[data-section-id="${sectionId}"]`);
        if (!section) return;
        
        const titleInput = section.querySelector('input[name*="[title]"]');
        const typeInput = section.querySelector('input[name*="[type]"]');
        
        // Create modal if it doesn't exist
        let modal = document.getElementById('edit-section-modal');
        if (!modal) {
            const modalHTML = `
                <div class="modal fade" id="edit-section-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Section</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="edit-section-id">
                                <div class="mb-3">
                                    <label for="edit-section-title" class="form-label">Section Title</label>
                                    <input type="text" class="form-control" id="edit-section-title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-section-type" class="form-label">Section Type</label>
                                    <select class="form-select" id="edit-section-type">
                                        <option value="standard">Standard</option>
                                        <option value="checklist">Checklist</option>
                                        <option value="flowchart">Flowchart</option>
                                        <option value="assessment">Assessment</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="save-section-btn">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('edit-section-modal');
            
            // Add event listener to save button
            document.getElementById('save-section-btn').addEventListener('click', saveSection);
        }
        
        // Fill the modal with section data
        document.getElementById('edit-section-id').value = sectionId;
        document.getElementById('edit-section-title').value = titleInput.value;
        document.getElementById('edit-section-type').value = typeInput.value;
        
        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    /**
     * 3.5: Save Section
     * Save changes to a section
     */
    function saveSection() {
        const modal = document.getElementById('edit-section-modal');
        const sectionId = document.getElementById('edit-section-id').value;
        const title = document.getElementById('edit-section-title').value;
        const type = document.getElementById('edit-section-type').value;
        
        if (!title) return;
        
        const section = document.querySelector(`.section-container[data-section-id="${sectionId}"]`);
        if (!section) return;
        
        // Update section values
        section.querySelector('.section-title').textContent = title;
        section.querySelector('input[name*="[title]"]').value = title;
        section.querySelector('input[name*="[type]"]').value = type;
        
        // Close the modal
        const bsModal = bootstrap.Modal.getInstance(modal);
        bsModal.hide();
    }
    
    /**
     * 3.6: Initialize Block Controls
     * Setup controls for adding, editing, and removing blocks
     */
    function initBlockControls() {
        // Add block buttons
        const addBlockBtns = document.querySelectorAll('.add-block-btn');
        addBlockBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const sectionId = this.getAttribute('data-section-id');
                addNewBlock(sectionId);
            });
        });
        
        // Edit block buttons
        const editBlockBtns = document.querySelectorAll('.edit-block-btn');
        editBlockBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const blockId = this.closest('.block-container').getAttribute('data-block-id');
                editBlock(blockId);
            });
        });
        
        // Remove block buttons
        const removeBlockBtns = document.querySelectorAll('.remove-block-btn');
        removeBlockBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const blockContainer = this.closest('.block-container');
                if (confirm('Are you sure you want to remove this block?')) {
                    blockContainer.remove();
                    updateBlockOrder();
                }
            });
        });
    }
    
    /**
     * 3.7: Add New Block
     * Add a new block to a section
     */
    function addNewBlock(sectionId) {
        openBlockTypeSelector(sectionId);
    }
    
    /**
     * 3.8: Open Block Type Selector
     * Show the block type selection modal
     */
    function openBlockTypeSelector(sectionId) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('block-type-modal');
        if (!modal) {
            const modalHTML = `
                <div class="modal fade" id="block-type-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Choose Block Type</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="block-section-id">
                                <div class="block-type-selector">
                                    <button type="button" class="btn" data-block-type="text">
                                        <i class="fas fa-paragraph"></i>
                                        <span>Text</span>
                                    </button>
                                    <button type="button" class="btn" data-block-type="checklist">
                                        <i class="fas fa-tasks"></i>
                                        <span>Checklist Item</span>
                                    </button>
                                    <button type="button" class="btn" data-block-type="action">
                                        <i class="fas fa-play"></i>
                                        <span>Action Step</span>
                                    </button>
                                    <button type="button" class="btn" data-block-type="decision">
                                        <i class="fas fa-question-circle"></i>
                                        <span>Decision Point</span>
                                    </button>
                                    <button type="button" class="btn" data-block-type="flowstep">
                                        <i class="fas fa-arrow-down"></i>
                                        <span>Flow Step</span>
                                    </button>
                                    <button type="button" class="btn" data-block-type="provider">
                                        <i class="fas fa-user-md"></i>
                                        <span>Provider Level</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('block-type-modal');
            
            initBlockTypeSelectors();
        }
        
        // Set the section ID
        document.getElementById('block-section-id').value = sectionId;
        
        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    /**
     * 3.9: Initialize Block Type Selectors
     * Setup handlers for block type selection buttons
     */
    function initBlockTypeSelectors() {
        const blockTypeButtons = document.querySelectorAll('.block-type-selector .btn');
        blockTypeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const blockType = this.getAttribute('data-block-type');
                const sectionId = document.getElementById('block-section-id').value;
                
                // Close the modal
                const modal = document.getElementById('block-type-modal');
                const bsModal = bootstrap.Modal.getInstance(modal);
                bsModal.hide();
                
                // Create the new block
                createBlock(sectionId, blockType);
            });
        });
    }
    
    /**
     * 3.10: Create Block
     * Create a new block of the selected type
     */
    function createBlock(sectionId, blockType) {
        const section = document.querySelector(`.section-container[data-section-id="${sectionId}"]`);
        if (!section) return;
        
        const blocksContainer = section.querySelector('.blocks-container');
        const blockCount = blocksContainer.querySelectorAll('.block-container').length;
        const blockId = 'new-block-' + Date.now();
        
        // Remove placeholder text
        const placeholder = section.querySelector('.text-muted');
        if (placeholder) {
            placeholder.remove();
        }
        
        // Create block HTML based on type
        let blockHTML = `
            <div class="block-container" data-block-id="${blockId}" data-block-type="${blockType}">
                <div class="block-header">
                    <span class="sortable-handle"><i class="fas fa-grip-vertical"></i></span>
                    <h4 class="block-title">${getBlockTypeTitle(blockType)}</h4>
                    <div class="block-actions">
                        <button type="button" class="btn btn-sm btn-primary edit-block-btn">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-danger remove-block-btn">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="block-content">
                    <input type="hidden" name="sections[${section.getAttribute('data-section-index')}][blocks][${blockCount}][id]" value="${blockId}">
                    <input type="hidden" name="sections[${section.getAttribute('data-section-index')}][blocks][${blockCount}][type]" value="${blockType}">
                    <input type="hidden" name="sections[${section.getAttribute('data-section-index')}][blocks][${blockCount}][order]" value="${blockCount}">
                    <input type="hidden" name="sections[${section.getAttribute('data-section-index')}][blocks][${blockCount}][content]" value="">
                    <input type="hidden" name="sections[${section.getAttribute('data-section-index')}][blocks][${blockCount}][detailed_info]" value="">
                    <input type="hidden" name="sections[${section.getAttribute('data-section-index')}][blocks][${blockCount}][is_decision]" value="${blockType === 'decision' ? '1' : '0'}">
        `;
        
        // Add block-specific content
        if (blockType === 'decision') {
            blockHTML += `
                    <input type="hidden" name="sections[${section.getAttribute('data-section-index')}][blocks][${blockCount}][yes_target]" value="">
                    <input type="hidden" name="sections[${section.getAttribute('data-section-index')}][blocks][${blockCount}][no_target]" value="">
            `;
        }
        
        // Add provider level checkboxes
        blockHTML += `
                    <div class="provider-levels-container">
                        <p><strong>Available to Provider Levels:</strong></p>
                        <div class="provider-checkbox-group">
                            ${getProviderCheckboxes(section.getAttribute('data-section-index'), blockCount)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add the block to the container
        blocksContainer.insertAdjacentHTML('beforeend', blockHTML);
        
        // Reinitialize controls
        initBlockControls();
        initProviderLevelSelectors();
        
        // Initialize sortable if not already done
        initSortable();
        
        // Update block order
        updateBlockOrder();
        
        // Edit the block immediately
        editBlock(blockId);
    }
    
    /**
     * 3.11: Get Block Type Title
     * Get a human-readable title for a block type
     */
    function getBlockTypeTitle(blockType) {
        const titles = {
            'text': 'Text Block',
            'checklist': 'Checklist Item',
            'action': 'Action Step',
            'decision': 'Decision Point',
            'flowstep': 'Flow Step',
            'provider': 'Provider Level'
        };
        
        return titles[blockType] || 'Block';
    }
    
    /**
     * 3.12: Get Provider Checkboxes
     * Generate HTML for provider level checkboxes
     */
    function getProviderCheckboxes(sectionIndex, blockIndex) {
        const providerLevels = [
            { id: 1, name: 'EMR', color: '#a0a0a0' },
            { id: 2, name: 'EMT', color: '#ffdb58' },
            { id: 3, name: 'EMT-IV', color: '#ff69b4' },
            { id: 4, name: 'AEMT', color: '#90ee90' },
            { id: 5, name: 'INTERMEDIATE', color: '#ffa500' },
            { id: 6, name: 'PARAMEDIC', color: '#87ceeb' }
        ];
        
        let checkboxesHTML = '';
        
        providerLevels.forEach(level => {
            checkboxesHTML += `
                <div class="provider-checkbox">
                    <input type="checkbox" name="sections[${sectionIndex}][blocks][${blockIndex}][providers][]" value="${level.id}" id="provider-${sectionIndex}-${blockIndex}-${level.id}" checked>
                    <label for="provider-${sectionIndex}-${blockIndex}-${level.id}">
                        ${level.name} <span class="color-preview" style="background-color: ${level.color};"></span>
                    </label>
                </div>
            `;
        });
        
        return checkboxesHTML;
    }
    
    /**
     * 3.13: Edit Block
     * Open the editor for a block
     */
    function editBlock(blockId) {
        const block = document.querySelector(`.block-container[data-block-id="${blockId}"]`);
        if (!block) return;
        
        const blockType = block.getAttribute('data-block-type');
        const contentInput = block.querySelector('input[name*="[content]"]');
        const detailedInfoInput = block.querySelector('input[name*="[detailed_info]"]');
        
        // Create modal if it doesn't exist
        let modal = document.getElementById('edit-block-modal');
        if (!modal) {
            const modalHTML = `
                <div class="modal fade" id="edit-block-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Block</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="edit-block-id">
                                <input type="hidden" id="edit-block-type">
                                
                                <div class="mb-3" id="content-container">
                                    <label for="edit-block-content" class="form-label">Content</label>
                                    <div id="edit-block-content" class="editor"></div>
                                    <input type="hidden" id="edit-block-content-hidden" data-editor="edit-block-content">
                                </div>
                                
                                <div class="mb-3" id="detailed-info-container">
                                    <label for="edit-block-detailed-info" class="form-label">Detailed Information</label>
                                    <div id="edit-block-detailed-info" class="editor"></div>
                                    <input type="hidden" id="edit-block-detailed-info-hidden" data-editor="edit-block-detailed-info">
                                </div>
                                
                                <div class="mb-3" id="decision-container" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="edit-block-yes-target" class="form-label">Yes Target</label>
                                            <input type="text" class="form-control" id="edit-block-yes-target">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="edit-block-no-target" class="form-label">No Target</label>
                                            <input type="text" class="form-control" id="edit-block-no-target">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="preview-container">
                                    <h4 class="preview-title">Preview</h4>
                                    <div id="block-preview"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="save-block-btn">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('edit-block-modal');
            
            // Add event listener to save button
            document.getElementById('save-block-btn').addEventListener('click', saveBlock);
        }
        
        // Show/hide decision fields based on block type
        const decisionContainer = document.getElementById('decision-container');
        if (blockType === 'decision') {
            decisionContainer.style.display = 'block';
            const yesTargetInput = block.querySelector('input[name*="[yes_target]"]');
            const noTargetInput = block.querySelector('input[name*="[no_target]"]');
            
            if (yesTargetInput && noTargetInput) {
                document.getElementById('edit-block-yes-target').value = yesTargetInput.value;
                document.getElementById('edit-block-no-target').value = noTargetInput.value;
            }
        } else {
            decisionContainer.style.display = 'none';
        }
        
        // Fill the modal with block data
        document.getElementById('edit-block-id').value = blockId;
        document.getElementById('edit-block-type').value = blockType;
        document.getElementById('edit-block-content-hidden').value = contentInput.value;
        document.getElementById('edit-block-detailed-info-hidden').value = detailedInfoInput.value;
        
        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Initialize editors after modal is shown
        modal.addEventListener('shown.bs.modal', function() {
            initEditors();
            updatePreview();
        }, { once: true });
    }
    
    /**
     * 3.14: Update Preview
     * Update the block preview based on current editor content
     */
    function updatePreview() {
        const blockType = document.getElementById('edit-block-type').value;
        const content = document.getElementById('edit-block-content-hidden').value;
        const previewContainer = document.getElementById('block-preview');
        
        if (!previewContainer) return;
        
        let previewHTML = '';
        
        switch (blockType) {
            case 'text':
                previewHTML = `<div class="preview-text">${content}</div>`;
                break;
            case 'checklist':
                previewHTML = `
                    <div class="checklist-item">
                        <label class="checkbox-container">
                            ${content}
                            <input type="checkbox">
                            <span class="checkmark"></span>
                        </label>
                        <span class="info-icon">i</span>
                    </div>
                `;
                break;
            case 'action':
                previewHTML = `
                    <div class="action-list">
                        <ul>
                            <li>${content}</li>
                        </ul>
                    </div>
                `;
                break;
            case 'decision':
                previewHTML = `
                    <div class="decision-box">
                        ${content}
                    </div>
                    <div class="yes-no-container">
                        <div class="yes-path">
                            <div class="path-label">Yes</div>
                            <div class="flow-arrow"></div>
                            <div class="protocol-link">Yes Action</div>
                        </div>
                        <div class="no-path">
                            <div class="path-label">No</div>
                            <div class="flow-arrow"></div>
                            <div class="protocol-link">No Action</div>
                        </div>
                    </div>
                `;
                break;
            case 'flowstep':
                previewHTML = `
                    <div class="flow-step">
                        <div class="provider-bar">
                            <div class="provider-segment emr" style="width: 16.66%"></div>
                            <div class="provider-segment emt" style="width: 16.66%"></div>
                            <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                            <div class="provider-segment aemt" style="width: 16.66%"></div>
                            <div class="provider-segment intermediate" style="width: 16.66%"></div>
                            <div class="provider-segment paramedic" style="width: 16.66%"></div>
                        </div>
                        ${content}
                    </div>
                    <div class="flow-arrow"></div>
                `;
                break;
            case 'provider':
                previewHTML = `
                    <div class="provider-levels">
                        <div class="provider-level emr">EMR</div>
                        <div class="provider-level emt">EMT</div>
                        <div class="provider-level emt-iv">EMT-IV</div>
                        <div class="provider-level aemt">AEMT</div>
                        <div class="provider-level intermediate">INTERMEDIATE</div>
                        <div class="provider-level paramedic">PARAMEDIC</div>
                    </div>
                `;
                break;
            default:
                previewHTML = content;
        }
        
        previewContainer.innerHTML = previewHTML;
    }
    
    /**
     * 3.15: Save Block
     * Save changes to a block
     */
    function saveBlock() {
        const modal = document.getElementById('edit-block-modal');
        const blockId = document.getElementById('edit-block-id').value;
        const blockType = document.getElementById('edit-block-type').value;
        const content = document.getElementById('edit-block-content-hidden').value;
        const detailedInfo = document.getElementById('edit-block-detailed-info-hidden').value;
        
        const block = document.querySelector(`.block-container[data-block-id="${blockId}"]`);
        if (!block) return;
        
        // Update block values
        block.querySelector('input[name*="[content]"]').value = content;
        block.querySelector('input[name*="[detailed_info]"]').value = detailedInfo;
        
        // Update decision values if applicable
        if (blockType === 'decision') {
            const yesTarget = document.getElementById('edit-block-yes-target').value;
            const noTarget = document.getElementById('edit-block-no-target').value;
            
            block.querySelector('input[name*="[yes_target]"]').value = yesTarget;
            block.querySelector('input[name*="[no_target]"]').value = noTarget;
        }
        
        // Close the modal
        const bsModal = bootstrap.Modal.getInstance(modal);
        bsModal.hide();
    }
    
    /**
     * 3.16: Initialize Provider Level Selectors
     * Setup provider level checkbox functionality
     */
    function initProviderLevelSelectors() {
        const providerCheckboxes = document.querySelectorAll('.provider-checkbox input[type="checkbox"]');
        providerCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Update the preview of provider availability
                const blockContainer = this.closest('.block-container');
                if (blockContainer) {
                    updateBlockProviderDisplay(blockContainer);
                }
            });
        });
    }
    
    /**
     * 3.17: Update Block Provider Display
     * Update visual representation of provider level access
     */
    function updateBlockProviderDisplay(blockContainer) {
        // This would update any visual indicators of provider levels
        // Not implemented in this version
    }
    
    // ========================================
    // CHAPTER 4: SORTABLE FUNCTIONALITY
    // ========================================
    
    /**
     * 4.1: Initialize Sortable
     * Setup drag-and-drop sorting for sections and blocks
     */
    function initSortable() {
        // Make sections sortable
        const sectionsContainer = document.getElementById('sections-container');
        if (sectionsContainer && typeof Sortable !== 'undefined') {
            // Check if already initialized
            if (!sectionsContainer.classList.contains('sortable-initialized')) {
                Sortable.create(sectionsContainer, {
                    handle: '.section-header',
                    animation: 150,
                    ghostClass: 'drag-ghost',
                    onEnd: updateSectionOrder
                });
                sectionsContainer.classList.add('sortable-initialized');
            }
        }
        
        // Make blocks sortable within each section
        const blocksContainers = document.querySelectorAll('.blocks-container');
        blocksContainers.forEach(container => {
            // Check if already initialized
            if (!container.classList.contains('sortable-initialized')) {
                Sortable.create(container, {
                    handle: '.sortable-handle',
                    animation: 150,
                    ghostClass: 'drag-ghost',
                    onEnd: updateBlockOrder
                });
                container.classList.add('sortable-initialized');
            }
        });
    }
    
    /**
     * 4.2: Update Section Order
     * Update section order after drag-and-drop reordering
     */
    function updateSectionOrder() {
        const sectionContainers = document.querySelectorAll('.section-container');
        
        sectionContainers.forEach((section, index) => {
            section.setAttribute('data-section-index', index);
            
            // Update order input
            const orderInput = section.querySelector('input[name*="[order]"]');
            if (orderInput) {
                orderInput.value = index;
            }
            
            // Update name attributes for all inputs in this section
            const inputs = section.querySelectorAll('input[name^="sections["]');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                const newName = name.replace(/sections\[\d+\]/, `sections[${index}]`);
                input.setAttribute('name', newName);
            });
            
            // Update block order within the section
            updateBlockOrder(section);
        });
    }
    
    /**
     * 4.3: Update Block Order
     * Update block order after drag-and-drop reordering
     */
    function updateBlockOrder(sectionContainer) {
        // If called from Sortable onEnd, we get an event object
        if (sectionContainer && sectionContainer.to) {
            sectionContainer = sectionContainer.to.closest('.section-container');
        }
        
        // If no specific section provided, update all sections
        if (!sectionContainer) {
            const sections = document.querySelectorAll('.section-container');
            sections.forEach(section => {
                updateBlockOrder(section);
            });
            return;
        }
        
        const sectionIndex = sectionContainer.getAttribute('data-section-index');
        const blocksContainer = sectionContainer.querySelector('.blocks-container');
        const blockContainers = blocksContainer.querySelectorAll('.block-container');
        
        blockContainers.forEach((block, index) => {
            // Update order input
            const orderInput = block.querySelector('input[name*="[order]"]');
            if (orderInput) {
                orderInput.value = index;
            }
            
            // Update name attributes for all inputs in this block
            const inputs = block.querySelectorAll('input[name^="sections["]');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                const newName = name.replace(/sections\[\d+\]\[blocks\]\[\d+\]/, `sections[${sectionIndex}][blocks][${index}]`);
                input.setAttribute('name', newName);
            });
        });
    }
    
    // ========================================
    // CHAPTER 5: FORM VALIDATION
    // ========================================
    
    /**
     * 5.1: Initialize Form Validation
     * Setup validation for admin forms
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.prototype.slice.call(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // Initialize form validation if needed
    if (document.querySelector('.needs-validation')) {
        initFormValidation();
    }
    
    // ========================================
    // CHAPTER 6: UTILITY FUNCTIONS
    // ========================================
    
    /**
     * 6.1: Initialize Protocol Page
     * Setup all functionality for the protocol creation/editing page
     */
    function initProtocolPage() {
        initProtocolBuilder();
        initSortable();
        initPreview();
    }
    
    /**
     * 6.2: Initialize Preview
     * Setup real-time preview functionality
     */
    function initPreview() {
        // Add event listeners to update preview when block content changes
        const contentEditor = document.getElementById('edit-block-content');
        if (contentEditor) {
            contentEditor.addEventListener('input', updatePreview);
        }
    }
    
    // Initialize protocol page if we're on a protocol edit page
    if (document.getElementById('protocol-builder')) {
        initProtocolPage();
    }
});