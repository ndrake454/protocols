/**
 * Main JavaScript
 * 
 * This file contains the JavaScript code for the public-facing pages.
 * 
 * CHAPTER 1: PROTOCOL INTERACTION
 * CHAPTER 2: MODAL FUNCTIONALITY
 * CHAPTER 3: UTILITY FUNCTIONS
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // CHAPTER 1: PROTOCOL INTERACTION
    // ========================================
    
    /**
     * 1.1: Initialize Checklist Items
     * Convert checklist items to use custom checkboxes and handle interactions
     */
    function initChecklistItems() {
        const checklistItems = document.querySelectorAll('.checklist-item');
        
        checklistItems.forEach(item => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (!checkbox) return;
            
            const label = item.querySelector('label');
            if (!label) return;
            
            const labelText = label.textContent;
            
            // Create new checkbox markup
            const checkboxContainer = document.createElement('label');
            checkboxContainer.className = 'checkbox-container';
            checkboxContainer.textContent = labelText;
            
            // Move the original checkbox
            checkbox.removeAttribute('id');
            label.parentNode.replaceChild(checkboxContainer, label);
            checkboxContainer.appendChild(checkbox);
            
            // Add the checkmark span
            const checkmark = document.createElement('span');
            checkmark.className = 'checkmark';
            checkboxContainer.appendChild(checkmark);
            
            // Add info icon if not already present
            if (!item.querySelector('.info-icon')) {
                const infoIcon = document.createElement('span');
                infoIcon.className = 'info-icon';
                infoIcon.innerHTML = 'i'; // info symbol
                infoIcon.title = "Click for details";
                item.appendChild(infoIcon);
            }
            
            // Add event listener for checkbox to toggle completed class
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    item.classList.add('completed');
                } else {
                    item.classList.remove('completed');
                }
            });
        });
    }
    
    /**
     * 1.2: Initialize Clickable Elements
     * Make various elements clickable to show detailed information
     */
    function initClickableElements() {
        // All clickable elements
        const clickableElements = [
            ...document.querySelectorAll('.assessment-box'),
            ...document.querySelectorAll('.assessment-criteria li'),
            ...document.querySelectorAll('.flow-step'),
            ...document.querySelectorAll('.decision-box'),
            ...document.querySelectorAll('.protocol-link'),
            ...document.querySelectorAll('.action-list li'),
            ...document.querySelectorAll('.clickable'),
            ...document.querySelectorAll('.info-icon')
        ];
        
        // Add click event listener to each element
        clickableElements.forEach(item => {
            item.addEventListener('click', handleElementClick);
        });
    }
    
    /**
     * 1.3: Handle Element Click
     * Process clicks on protocol elements to show information
     */
    function handleElementClick(e) {
        // Don't trigger if clicking the checkbox itself or its label
        if (e.target.tagName === 'INPUT' || 
            e.target.classList.contains('checkbox-container') || 
            e.target.classList.contains('checkmark')) return;
        
        // Get the parent element if clicking on an info icon
        const element = this.classList.contains('info-icon') ? this.parentElement : this;
        
        // Get information from data attribute
        const info = element.getAttribute('data-info');
        if (!info) return;
        
        // Determine title based on element type
        let title;
        if (element.querySelector('.assessment-title')) {
            title = element.querySelector('.assessment-title').textContent;
        } else if (element.classList.contains('protocol-link')) {
            title = element.textContent.trim();
        } else if (element.classList.contains('flow-step') || element.classList.contains('decision-box')) {
            title = element.textContent.trim().split('\n').pop().trim();
        } else if (element.classList.contains('clickable')) {
            title = element.textContent.trim();
        } else if (this.classList.contains('info-icon')) {
            title = element.querySelector('.checkbox-container') ? 
                   element.querySelector('.checkbox-container').textContent.trim() :
                   'Details';
        } else {
            title = element.textContent.trim();
        }
        
        // Show the modal with the information
        showModal(title, info);
    }
    
    // ========================================
    // CHAPTER 2: MODAL FUNCTIONALITY
    // ========================================
    
    /**
     * 2.1: Show Modal
     * Display a modal with title and content
     */
    function showModal(title, content) {
        const modal = document.getElementById('infoModal');
        if (!modal) return;
        
        const modalTitle = document.getElementById('modalTitle');
        const modalInfo = document.getElementById('modalInfo');
        
        // Format the info text to enhance certain keywords
        let formattedInfo = content;
        
        // Wrap important terms in <strong> tags
        const importantTerms = [
            'SpO₂', 'O₂', 'hypoxemia', 'tachypnea', 'dyspnea', 'CPAP', 'BiPAP',
            'pulmonary embolism', 'pneumonia', 'pneumothorax', 'anaphylaxis',
            'asthma', 'COPD', 'CHF', 'pulmonary edema', 'accessory muscles',
            'tidal volume', 'ventilation', 'oxygenation', 'capnography',
            'bronchospasm', 'albuterol', 'ETCO2', 'hypercapnia', 'RSI',
            'LEMON', 'ETT', 'PEEP', 'vocal cords', 'glottic', 'laryngeal'
        ];
        
        importantTerms.forEach(term => {
            const regex = new RegExp(`\\b${term}\\b`, 'gi');
            formattedInfo = formattedInfo.replace(regex, `<strong>${term}</strong>`);
        });
        
        modalTitle.textContent = title;
        modalInfo.innerHTML = formattedInfo;
        modal.style.display = 'block';
        
        // Add a slight delay before adding the 'show' class for the animation
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
    
    /**
     * 2.2: Close Modal
     * Hide the modal with animation
     */
    function closeModal() {
        const modal = document.getElementById('infoModal');
        if (!modal) return;
        
        modal.classList.remove('show');
        // Wait for animation to complete before hiding
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
    
    /**
     * 2.3: Setup Modal Event Listeners
     * Configure events for closing the modal
     */
    function setupModalEvents() {
        const modal = document.getElementById('infoModal');
        if (!modal) return;
        
        const closeBtn = modal.querySelector('.close-btn');
        
        // Close modal when clicking X button
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        
        // Close modal when clicking outside of it
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Close modal when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                closeModal();
            }
        });
    }
    
    /**
     * 2.4: Create Modal if Not Present
     * Dynamically add a modal to the page if needed
     */
    function createModalIfNeeded() {
        if (document.getElementById('infoModal')) return;
        
        const modalHTML = `
            <div id="infoModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn">&times;</button>
                    <div class="modal-header">
                        <h3 id="modalTitle">Item Details</h3>
                    </div>
                    <div class="modal-body">
                        <p id="modalInfo">Detailed information will appear here.</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    // ========================================
    // CHAPTER 3: UTILITY FUNCTIONS
    // ========================================
    
    /**
     * 3.1: Initialize Protocol Page
     * Set up all functionality for protocol pages
     */
    function initProtocolPage() {
        createModalIfNeeded();
        initChecklistItems();
        initClickableElements();
        setupModalEvents();
    }
    
    // Initialize if we're on a protocol page
    if (document.querySelector('.protocol-container') || 
        document.querySelector('.checklist-sections') ||
        document.querySelector('.flowchart')) {
        initProtocolPage();
    }
    
    // Initialize modal for any page with modal-triggering elements
    if (document.querySelector('[data-info]')) {
        createModalIfNeeded();
        initClickableElements();
        setupModalEvents();
    }
});