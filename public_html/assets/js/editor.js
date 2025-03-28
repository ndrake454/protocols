/**
 * Editor JavaScript
 * 
 * This file contains the JavaScript code specifically for the WYSIWYG protocol editor.
 * 
 * CHAPTER 1: EDITOR INITIALIZATION
 * CHAPTER 2: BLOCK MANAGEMENT
 * CHAPTER 3: SECTION MANAGEMENT
 * CHAPTER 4: DRAG AND DROP
 * CHAPTER 5: PREVIEW FUNCTIONALITY
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // CHAPTER 1: EDITOR INITIALIZATION
    // ========================================
    
    /**
     * 1.1: Initialize Editor
     * Set up the WYSIWYG protocol editor
     */
    function initEditor() {
        const editor = document.getElementById('protocol-editor');
        if (!editor) return;
        
        initToolbar();
        initCanvas();
        initBlockLibrary();
        initEventListeners();
    }
    
    /**
     * 1.2: Initialize Toolbar
     * Set up the toolbar buttons and functionality
     */
    function initToolbar() {
        const toolbar = document.getElementById('editor-toolbar');
        if (!toolbar) return;
        
        // Add event listeners to toolbar buttons
        const buttons = toolbar.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                handleToolbarAction(action);
            });
        });
    }
    
    /**
     * 1.3: Initialize Canvas
     * Set up the main editing canvas
     */
    function initCanvas() {
        const canvas = document.getElementById('editor-canvas');
        if (!canvas) return;
        
        // Make the canvas droppable for blocks
        makeDroppable(canvas);
    }
    
    /**
     * 1.4: Initialize Block Library
     * Set up the sidebar block library for dragging
     */
    function initBlockLibrary() {
        const library = document.getElementById('block-library');
        if (!library) return;
        
        // Make library blocks draggable
        const blocks = library.querySelectorAll('.block-template');
        blocks.forEach(block => {
            makeDraggable(block);
        });
    }
    
    /**
     * 1.5: Initialize Event Listeners
     * Set up global event listeners for the editor
     */
    function initEventListeners() {
        // Listen for 'Save' button
        const saveBtn = document.getElementById('save-protocol-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveProtocol);
        }
        
        // Listen for 'Preview' button
        const previewBtn = document.getElementById('preview-protocol-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', previewProtocol);
        }
    }
    
    // ========================================
    // CHAPTER 2: BLOCK MANAGEMENT
    // ========================================
    
    /**
     * 2.1: Create Block
     * Create a new block in the editor
     */
    function createBlock(type, position) {
        const canvas = document.getElementById('editor-canvas');
        if (!canvas) return;
        
        const blockId = 'block-' + Date.now();
        
        // Create block HTML based on type
        const blockHTML = generateBlockHTML(type, blockId);
        
        // Add the block to the canvas
        if (position === 'end') {
            canvas.insertAdjacentHTML('beforeend', blockHTML);
        } else {
            canvas.insertAdjacentHTML('afterbegin', blockHTML);
        }
        
        // Initialize the new block
        const block = document.getElementById(blockId);
        if (block) {
            initBlockEvents(block);
        }
        
        // Update block order
        updateBlockOrder();
        
        return blockId;
    }
    
    /**
     * 2.2: Generate Block HTML
     * Generate HTML for a block of specified type
     */
    function generateBlockHTML(type, id) {
        let blockContent = '';
        let blockClass = 'editor-block';
        
        switch (type) {
            case 'text':
                blockContent = `<div class="block-content editable" contenteditable="true">Enter text here...</div>`;
                break;
            case 'heading':
                blockContent = `<div class="block-content editable" contenteditable="true"><h3>Enter heading...</h3></div>`;
                break;
            case 'checklist':
                blockContent = `
                    <div class="block-content">
                        <div class="checklist-item">
                            <label class="checkbox-container">
                                <span class="editable" contenteditable="true">Checklist item...</span>
                                <input type="checkbox">
                                <span class="checkmark"></span>
                            </label>
                            <span class="info-icon">i</span>
                        </div>
                    </div>
                `;
                break;
            case 'decision':
                blockContent = `
                    <div class="block-content">
                        <div class="decision-box">
                            <span class="editable" contenteditable="true">Decision question...</span>
                        </div>
                        <div class="yes-no-container">
                            <div class="yes-path">
                                <div class="path-label">Yes</div>
                                <div class="flow-arrow"></div>
                                <div class="protocol-link editable" contenteditable="true">Yes action...</div>
                            </div>
                            <div class="no-path">
                                <div class="path-label">No</div>
                                <div class="flow-arrow"></div>
                                <div class="protocol-link editable" contenteditable="true">No action...</div>
                            </div>
                        </div>
                    </div>
                `;
                blockClass += ' decision-block';
                break;
            case 'flowstep':
                blockContent = `
                    <div class="block-content">
                        <div class="flow-step">
                            <div class="provider-bar">
                                <div class="provider-segment emr" style="width: 16.66%"></div>
                                <div class="provider-segment emt" style="width: 16.66%"></div>
                                <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                                <div class="provider-segment aemt" style="width: 16.66%"></div>
                                <div class="provider-segment intermediate" style="width: 16.66%"></div>
                                <div class="provider-segment paramedic" style="width: 16.66%"></div>
                            </div>
                            <span class="editable" contenteditable="true">Flow step content...</span>
                        </div>
                        <div class="flow-arrow"></div>
                    </div>
                `;
                break;
            case 'section':
                blockContent = `
                    <div class="block-content">
                        <div class="section-header">
                            <h2 class="editable" contenteditable="true">Section Title</h2>
                        </div>
                        <div class="section-content dropzone" data-section-id="${id}"></div>
                    </div>
                `;
                blockClass += ' section-block';
                break;
            default:
                blockContent = `<div class="block-content editable" contenteditable="true">Enter content here...</div>`;
        }
        
        return `
            <div id="${id}" class="${blockClass}" data-block-type="${type}" draggable="true">
                <div class="block-handle"><i class="fas fa-grip-vertical"></i></div>
                <div class="block-actions">
                    <button type="button" class="btn btn-sm btn-primary edit-block-btn" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-info provider-btn" title="Provider Levels">
                        <i class="fas fa-user-md"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-block-btn" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                ${blockContent}
            </div>
        `;
    }
    
    /**
     * 2.3: Initialize Block Events
     * Set up event listeners for a block
     */
    function initBlockEvents(block) {
        // Make the block draggable
        makeDraggable(block);
        
        // Add edit button event
        const editBtn = block.querySelector('.edit-block-btn');
        if (editBtn) {
            editBtn.addEventListener('click', function() {
                editBlock(block.id);
            });
        }
        
        // Add provider button event
        const providerBtn = block.querySelector('.provider-btn');
        if (providerBtn) {
            providerBtn.addEventListener('click', function() {
                editProviderLevels(block.id);
            });
        }
        
        // Add delete button event
        const deleteBtn = block.querySelector('.delete-block-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                deleteBlock(block.id);
            });
        }
        
        // Initialize any dropzones within the block (for sections)
        const dropzones = block.querySelectorAll('.dropzone');
        dropzones.forEach(dropzone => {
            makeDroppable(dropzone);
        });
    }
    
    /**
     * 2.4: Edit Block
     * Open the editor for a block
     */
    function editBlock(blockId) {
        const block = document.getElementById(blockId);
        if (!block) return;
        
        // Implementation depends on the specific editor UI
        // This could open a modal or sidebar panel with editing controls
        console.log(`Editing block: ${blockId}`);
        
        // Example: Toggle an 'editing' class on the block
        block.classList.toggle('editing');
    }
    
    /**
     * 2.5: Edit Provider Levels
     * Configure which provider levels can perform this step
     */
    function editProviderLevels(blockId) {
        const block = document.getElementById(blockId);
        if (!block) return;
        
        // Implementation depends on the specific editor UI
        // This could open a modal with provider level checkboxes
        console.log(`Editing provider levels for block: ${blockId}`);
    }
    
    /**
     * 2.6: Delete Block
     * Remove a block from the editor
     */
    function deleteBlock(blockId) {
        const block = document.getElementById(blockId);
        if (!block) return;
        
        if (confirm('Are you sure you want to delete this block?')) {
            block.remove();
            updateBlockOrder();
        }
    }
    
    /**
     * 2.7: Update Block Order
     * Update the order of blocks after changes
     */
    function updateBlockOrder() {
        const canvas = document.getElementById('editor-canvas');
        if (!canvas) return;
        
        const blocks = canvas.querySelectorAll('.editor-block');
        blocks.forEach((block, index) => {
            block.setAttribute('data-order', index);
        });
    }
    
    // ========================================
    // CHAPTER 3: SECTION MANAGEMENT
    // ========================================
    
    /**
     * 3.1: Add Section
     * Add a new section to the protocol
     */
    function addSection() {
        return createBlock('section', 'end');
    }
    
    /**
     * 3.2: Add Block to Section
     * Add a block to a specific section
     */
    function addBlockToSection(sectionId, blockType) {
        const section = document.getElementById(sectionId);
        if (!section) return;
        
        const sectionContent = section.querySelector('.section-content');
        if (!sectionContent) return;
        
        const blockId = 'block-' + Date.now();
        
        // Create block HTML
        const blockHTML = generateBlockHTML(blockType, blockId);
        
        // Add the block to the section
        sectionContent.insertAdjacentHTML('beforeend', blockHTML);
        
        // Initialize the new block
        const block = document.getElementById(blockId);
        if (block) {
            initBlockEvents(block);
        }
        
        return blockId;
    }
    
    // ========================================
    // CHAPTER 4: DRAG AND DROP
    // ========================================
    
    /**
     * 4.1: Make Draggable
     * Make an element draggable
     */
    function makeDraggable(element) {
        element.draggable = true;
        
        element.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.id);
            e.dataTransfer.effectAllowed = 'move';
            this.classList.add('dragging');
            
            // If it's a template block, we'll need its type
            if (this.classList.contains('block-template')) {
                const blockType = this.getAttribute('data-block-type');
                e.dataTransfer.setData('block-type', blockType);
            }
        });
        
        element.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
        
        // Add handle functionality if present
        const handle = element.querySelector('.block-handle');
        if (handle) {
            handle.addEventListener('mousedown', function() {
                element.draggable = true;
            });
            
            element.addEventListener('mouseup', function() {
                element.draggable = false;
            });
        }
    }
    
    /**
     * 4.2: Make Droppable
     * Make an element accept dropped elements
     */
    function makeDroppable(element) {
        element.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('drop-target');
        });
        
        element.addEventListener('dragleave', function() {
            this.classList.remove('drop-target');
        });
        
        element.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drop-target');
            
            const id = e.dataTransfer.getData('text/plain');
            
            // If the dragged item is a template from the library
            if (id.startsWith('template-')) {
                const blockType = e.dataTransfer.getData('block-type');
                
                // If this is a section dropzone
                if (this.classList.contains('dropzone')) {
                    const sectionId = this.closest('.editor-block').id;
                    addBlockToSection(sectionId, blockType);
                } else {
                    // Add to main canvas
                    createBlock(blockType, 'end');
                }
                return;
            }
            
            // Moving an existing block
            const draggedItem = document.getElementById(id);
            if (!draggedItem) return;
            
            // Handle dropping into sections
            if (this.classList.contains('dropzone')) {
                this.appendChild(draggedItem);
            } else {
                // Determine drop position
                const rect = this.getBoundingClientRect();
                const middle = (rect.bottom - rect.top) / 2 + rect.top;
                
                if (e.clientY < middle) {
                    this.parentNode.insertBefore(draggedItem, this);
                } else {
                    this.parentNode.insertBefore(draggedItem, this.nextSibling);
                }
            }
            
            updateBlockOrder();
        });
    }
    
    // ========================================
    // CHAPTER 5: PREVIEW FUNCTIONALITY
    // ========================================
    
    /**
     * 5.1: Save Protocol
     * Save the current protocol to the server
     */
    function saveProtocol() {
        // Get all blocks and their content
        const canvas = document.getElementById('editor-canvas');
        if (!canvas) return;
        
        const blocks = canvas.querySelectorAll('.editor-block');
        const protocolData = {
            title: document.getElementById('protocol-title').value,
            description: document.getElementById('protocol-description').value,
            category: document.getElementById('protocol-category').value,
            blocks: []
        };
        
        blocks.forEach((block, index) => {
            const blockType = block.getAttribute('data-block-type');
            const blockData = {
                id: block.id,
                type: blockType,
                order: index,
                content: getBlockContent(block),
                providerLevels: getBlockProviderLevels(block)
            };
            
            // Add to protocol data
            protocolData.blocks.push(blockData);
        });
        
        // Send to server via AJAX
        fetch('api/protocols.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(protocolData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Protocol saved successfully!');
            } else {
                alert('Error saving protocol: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error saving protocol: ' + error.message);
        });
    }
    
    /**
     * 5.2: Preview Protocol
     * Show a preview of the current protocol
     */
    function previewProtocol() {
        // Implementation depends on the specific UI
        // This could open a new window/tab or a modal with the preview
        const previewWindow = window.open('', '_blank');
        
        // Get the protocol content
        const canvas = document.getElementById('editor-canvas');
        if (!canvas) return;
        
        // Generate HTML for the preview
        let previewHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Protocol Preview</title>
                <link rel="stylesheet" href="assets/css/main.css">
                <link rel="stylesheet" href="assets/css/protocol.css">
            </head>
            <body>
                <div class="container py-4">
                    <h1>${document.getElementById('protocol-title').value}</h1>
                    <div class="protocol-container">
                        ${canvas.innerHTML}
                    </div>
                </div>
                <script src="assets/js/main.js"></script>
            </body>
            </html>
        `;
        
        // Write the HTML to the preview window
        previewWindow.document.write(previewHTML);
    }
    
    /**
     * 5.3: Get Block Content
     * Extract content from a block
     */
    function getBlockContent(block) {
        // Implementation depends on the block structure
        const contentElement = block.querySelector('.block-content');
        return contentElement ? contentElement.innerHTML : '';
    }
    
    /**
     * 5.4: Get Block Provider Levels
     * Get the provider levels for a block
     */
    function getBlockProviderLevels(block) {
        // This would retrieve provider level settings from the block
        // Example implementation - real version would get actual settings
        return ['EMR', 'EMT', 'EMT-IV', 'AEMT', 'INTERMEDIATE', 'PARAMEDIC'];
    }
    
    // ========================================
    // CHAPTER 6: TOOLBAR ACTIONS
    // ========================================
    
    /**
     * 6.1: Handle Toolbar Action
     * Process clicks on the toolbar buttons
     */
    function handleToolbarAction(action) {
        switch (action) {
            case 'add-section':
                addSection();
                break;
            case 'add-text':
                createBlock('text', 'end');
                break;
            case 'add-heading':
                createBlock('heading', 'end');
                break;
            case 'add-checklist':
                createBlock('checklist', 'end');
                break;
            case 'add-decision':
                createBlock('decision', 'end');
                break;
            case 'add-flowstep':
                createBlock('flowstep', 'end');
                break;
            case 'save':
                saveProtocol();
                break;
            case 'preview':
                previewProtocol();
                break;
            default:
                console.log(`Unknown action: ${action}`);
        }
    }
    
    // Initialize the editor if we're on the editor page
    if (document.getElementById('protocol-editor')) {
        initEditor();
    }
});