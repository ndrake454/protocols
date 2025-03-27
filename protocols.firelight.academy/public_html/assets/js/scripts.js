document.addEventListener('DOMContentLoaded', function() {
    // Initialize info modal functionality
    initInfoModal();
    
    // Initialize checklist functionality
    initChecklistItems();
    
    // Initialize search functionality
    initSearch();
    
    // Save protocol state in localStorage
    saveProtocolState();
});

/**
 * Initialize the information modal functionality
 */
function initInfoModal() {
    const modal = document.getElementById('infoModal');
    
    // If no modal exists on this page, return
    if (!modal) return;
    
    const modalTitle = document.getElementById('modalTitle');
    const modalInfo = document.getElementById('modalInfo');
    const closeBtn = document.querySelector('.close-btn');
    
    // Collect all elements with data-info attribute
    const infoElements = document.querySelectorAll('[data-info]');
    
    // Add click event to all elements with info
    infoElements.forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't trigger modal if clicking on a checkbox or label
            if (e.target.tagName === 'INPUT' || 
                e.target.tagName === 'LABEL' || 
                e.target.classList.contains('checkmark')) {
                return;
            }
            
            // Get info content
            const info = this.getAttribute('data-info');
            if (!info || info.trim() === '') return;
            
            // Determine title based on element type
            let title;
            if (this.querySelector('.assessment-title')) {
                title = this.querySelector('.assessment-title').textContent;
            } else if (this.classList.contains('protocol-link')) {
                title = this.textContent.trim();
            } else if (this.classList.contains('flow-step') || this.classList.contains('decision-box')) {
                title = this.textContent.trim().split('\n').pop().trim();
            } else if (this.tagName === 'LI') {
                title = this.textContent.trim();
            } else {
                title = 'Additional Information';
            }
            
            // Format the info text to enhance certain keywords
            let formattedInfo = formatInfoText(info);
            
            // Set modal content
            modalTitle.textContent = title;
            modalInfo.innerHTML = formattedInfo;
            
            // Display the modal
            modal.style.display = 'block';
            
            // Add a slight delay before adding the 'show' class for the animation
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        });
    });
    
    // Close modal functionality
    function closeModal() {
        modal.classList.remove('show');
        // Wait for animation to complete before hiding
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
    
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
 * Format info text by highlighting important medical terms
 * @param {string} text - The text to format
 * @return {string} Formatted text with highlighted terms
 */
function formatInfoText(text) {
    // List of important medical terms to highlight
    const importantTerms = [
        'SpO₂', 'O₂', 'hypoxemia', 'tachypnea', 'dyspnea', 'CPAP', 'BiPAP',
        'pulmonary embolism', 'pneumonia', 'pneumothorax', 'anaphylaxis',
        'asthma', 'COPD', 'CHF', 'pulmonary edema', 'accessory muscles',
        'tidal volume', 'ventilation', 'oxygenation', 'capnography',
        'bronchospasm', 'albuterol', 'ETCO2', 'hypercapnia', 'ETT', 'PEEP',
        'epiglottis', 'vocal cords', 'glottic', 'cricothyrotomy', 'RSI',
        'CICO', 'cricothyroid membrane', 'CTM', 'BVM', 'bougie', 'thyroid cartilage',
        'cricoid cartilage', 'laryngeal handshake', 'Yankauer', 'airway', 'breathing',
        'circulation', 'LEMON', 'pre-oxygenation', 'RASS', 'FiO2'
    ];
    
    // Replace each important term with a highlighted version
    importantTerms.forEach(term => {
        const regex = new RegExp(`\\b${term}\\b`, 'gi');
        text = text.replace(regex, `<strong>${term}</strong>`);
    });
    
    return text;
}

/**
 * Initialize the checklist functionality
 */
function initChecklistItems() {
    const checklistItems = document.querySelectorAll('.checklist-item');
    
    checklistItems.forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (!checkbox) return;
        
        // Get item identifier (could be based on protocol and item IDs)
        const itemId = getItemIdentifier(item, checkbox);
        
        // Check if this item was previously checked in this session
        if (localStorage.getItem(itemId) === 'true') {
            checkbox.checked = true;
            item.classList.add('completed');
        }
        
        // Add event listener to save state and toggle completed class
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                item.classList.add('completed');
                localStorage.setItem(itemId, 'true');
            } else {
                item.classList.remove('completed');
                localStorage.setItem(itemId, 'false');
            }
        });
    });
}

/**
 * Generate a unique identifier for a checklist item
 * @param {Element} item - The checklist item element
 * @param {Element} checkbox - The checkbox element
 * @return {string} A unique identifier for the item
 */
function getItemIdentifier(item, checkbox) {
    // Get the current page URL or protocol ID
    const pageIdentifier = window.location.pathname;
    
    // Get a unique ID for the checklist item
    let itemId = checkbox.id || '';
    
    // If no ID exists, use the item content as identifier
    if (!itemId && item.querySelector('label')) {
        itemId = item.querySelector('label').textContent.trim();
    }
    
    // Combine page and item identifiers
    return `checklist_${pageIdentifier}_${itemId}`;
}

/**
 * Initialize search functionality
 */
function initSearch() {
    const searchForm = document.querySelector('.search-container form');
    if (!searchForm) return;
    
    searchForm.addEventListener('submit', function(e) {
        const searchInput = this.querySelector('input[name="q"]');
        if (!searchInput || searchInput.value.trim() === '') {
            e.preventDefault();
            
            // Display error message or focus input
            searchInput.focus();
            searchInput.classList.add('error');
            
            // Remove error class after a delay
            setTimeout(() => {
                searchInput.classList.remove('error');
            }, 1000);
        }
    });
}

/**
 * Save the state of the protocol in localStorage
 */
function saveProtocolState() {
    // This function can be expanded to save more state information
    // For now it relies on the checklist state saving in initChecklistItems
    
    // Example: Save timestamp of last visit to this protocol
    const protocolId = getProtocolIdFromURL();
    if (protocolId) {
        localStorage.setItem(`protocol_last_visit_${protocolId}`, Date.now());
    }
}

/**
 * Extract protocol ID from the current URL
 * @return {string|null} The protocol ID or null if not found
 */
function getProtocolIdFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}