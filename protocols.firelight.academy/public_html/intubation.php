<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMS Intubation Checklist</title>
    <style>
        :root {
            --primary-color: #006699;
            --primary-gradient: linear-gradient(135deg, #006699, #004d80);
            --modal-gradient: linear-gradient(135deg, #2a9d8f, #1d6a67);
            --light-bg: #e6f2f8;
            --border-color: #cccccc;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px;
        }
        
        header {
            background: var(--primary-gradient);
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-bottom: 20px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 12px rgba(0, 77, 128, 0.2);
        }
        
        h1 {
            font-size: 2.5rem;
            margin: 0;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            letter-spacing: 0.5px;
        }
        
        .checklist-sections {
            display: flex;
            flex-direction: column;
            gap: 20px;
            justify-content: center;
        }
        
        .section {
            width: 100%;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .section:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        
        .section-header {
            background: var(--primary-gradient);
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        /* Arrow styling for section headers - only for mobile/vertical layout */
        .section-header::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -15px;
            height: 15px;
            width: 30px;
            margin: 0 auto;
            background-color: var(--primary-color);
            clip-path: polygon(0 0, 100% 0, 50% 100%);
            z-index: 2;
        }
        
        /* Remove arrow from last section */
        .section:last-child .section-header::after {
            display: none;
        }
        
        .checklist-items {
            padding: 20px;
        }
        
        .checklist-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid transparent;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background-color: rgba(255,255,255,0.7);
        }
        
        .checklist-item:hover {
            background-color: #f0f9ff;
            transform: translateX(3px);
            border-left-color: var(--primary-color);
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        
        .checklist-item.completed {
            background-color: #f0fff0;
            border-left-color: #4CAF50;
        }
        
        /* Custom checkbox styling */
        .checkbox-container {
            display: block;
            position: relative;
            padding-left: 30px;
            cursor: pointer;
            user-select: none;
            flex: 1;
        }
        
        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: #eee;
            border-radius: 4px;
            border: 1px solid #ddd;
            transition: all 0.2s ease;
        }
        
        .checkbox-container:hover input ~ .checkmark {
            background-color: #ccc;
        }
        
        .checkbox-container input:checked ~ .checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkbox-container .checkmark:after {
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            margin-left: 10px;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 102, 153, 0.3);
            transition: all 0.2s ease;
            opacity: 0.85;
        }
        
        .checklist-item:hover .info-icon {
            opacity: 1;
            transform: scale(1.1);
            box-shadow: 0 3px 6px rgba(0, 102, 153, 0.4);
        }
        
        .info-icon:hover {
            background-color: #0080bf;
            transform: scale(1.15) !important;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 100;
            overflow-y: auto;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal.show {
            opacity: 1;
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 10;
        }
        
        .close-btn:hover {
            background-color: rgba(255,255,255,0.4);
            transform: rotate(90deg);
        }
        
        .modal-header {
            background: var(--modal-gradient);
            color: white;
            padding: 20px 25px;
            position: relative;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.4rem;
            padding-right: 30px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .modal-body {
            padding: 25px;
            line-height: 1.7;
            color: #444;
            position: relative;
            font-size: 1.05rem;
        }
        
        .modal-body p {
            position: relative;
            padding-left: 28px;
            margin-bottom: 15px;
        }
        
        .modal-body p::before {
            content: '•';
            position: absolute;
            left: 8px;
            color: var(--primary-color);
            font-size: 1.5em;
            top: -0.2em;
        }
        
        .modal-body strong {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .note-disclaimer {
            text-align: center;
            color: #e74c3c;
            margin-top: 25px;
            font-size: 0.95rem;
            font-style: italic;
            padding: 10px;
            border-radius: 5px;
            background-color: rgba(231, 76, 60, 0.05);
            border: 1px dashed rgba(231, 76, 60, 0.3);
        }
        
        /* Responsive adjustments */
        @media (min-width: 992px) {
            .checklist-sections {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 25px;
            }
            
            .section {
                height: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>EMS Intubation Checklist</h1>
    </header>
    
    <div class="container">
        <div class="checklist-sections">
            <!-- Prepare Procedure Section -->
            <div class="section">
                <div class="section-header">
                    <h2>Prepare Procedure</h2>
                </div>
                <div class="checklist-items">
                    <div class="checklist-item" data-info="Ensure clinical indication exists for intubation based on patient assessment, airway compromise, ventilatory failure, or anticipated clinical course.">
                        <input type="checkbox" id="verify-indication">
                        <label for="verify-indication">Verify Indication for Intubation</label>
                    </div>
                    <div class="checklist-item" data-info="LEMON assessment: Look externally, Evaluate 3-3-2 rule, Mallampati score, Obstruction/Obesity, Neck mobility. Document potential difficult airway characteristics.">
                        <input type="checkbox" id="airway-assessment">
                        <label for="airway-assessment">Airway Assessment – LEMON</label>
                    </div>
                    <div class="checklist-item" data-info="Prepare equipment for pre-oxygenation: BVM with PEEP valve, nasal cannula for apneic oxygenation (up to 15 LPM), non-rebreather mask and suction equipment.">
                        <input type="checkbox" id="pre-oxygenation">
                        <label for="pre-oxygenation">Pre-Oxygenation Strategy</label>
                    </div>
                    <div class="checklist-item" data-info="Check all equipment: Laryngoscope (working light), ET tubes (primary + backup sizes), stylet, 10mL syringe, BVM, functioning suction, end-tidal CO2 detector, backup devices (supraglottic airways, surgical airway kit).">
                        <input type="checkbox" id="equipment-ready">
                        <label for="equipment-ready">Equipment Ready</label>
                    </div>
                    <div class="checklist-item" data-info="Prepare sedative agents: Calculate and draw up appropriate doses based on patient's weight, hemodynamic status, and clinical condition. Common options include etomidate, ketamine, or midazolam.">
                        <input type="checkbox" id="medications-drawn">
                        <label for="medications-drawn">Medications Drawn (if RSI)</label>
                    </div>
                    <div class="checklist-item" data-info="Administer appropriate sedative agent at calculated dose. Assess for adequate sedation before proceeding to paralytic administration.">
                        <input type="checkbox" id="sedation">
                        <label for="sedation">Sedation</label>
                    </div>
                    <div class="checklist-item" data-info="Administer paralytic agent (e.g., succinylcholine or rocuronium) if performing RSI. Allow sufficient time for onset of action before attempting laryngoscopy.">
                        <input type="checkbox" id="paralytic">
                        <label for="paralytic">Paralytic</label>
                    </div>
                    <div class="checklist-item" data-info="Prepare post-intubation medications: Long-acting sedative and analgesic medications for ongoing management (e.g., fentanyl, midazolam, propofol).">
                        <input type="checkbox" id="post-intubation-meds">
                        <label for="post-intubation-meds">Post-intubation sedation/analgesia</label>
                    </div>
                    <div class="checklist-item" data-info="Perform a brief pause to confirm all team members are ready, roles are assigned, equipment is prepared, and backup plans are established. Address any concerns before proceeding.">
                        <input type="checkbox" id="final-safety">
                        <label for="final-safety">Final Safety Pause</label>
                    </div>
                </div>
            </div>
            
            <!-- Intubation Procedure Section -->
            <div class="section">
                <div class="section-header">
                    <h2>Intubation Procedure</h2>
                </div>
                <div class="checklist-items">
                    <div class="checklist-item" data-info="Position patient optimally for intubation: Use pillow or towel under occiput to align oral, pharyngeal, and laryngeal axes (sniffing position). Ensure bed/stretcher is at proper height for intubator.">
                        <input type="checkbox" id="positioning">
                        <label for="positioning">Positioning</label>
                    </div>
                    <div class="checklist-item" data-info="Use laryngoscope to visualize airway structures, identifying epiglottis and vocal cords. Apply appropriate technique (direct or video laryngoscopy) to obtain best view of glottic opening.">
                        <input type="checkbox" id="laryngoscopy">
                        <label for="laryngoscopy">Laryngoscopy & Intubation</label>
                    </div>
                    <div class="checklist-item" data-info="Confirm ETT placement at appropriate depth (typically 21-23 cm at teeth for adult males, 19-21 cm for adult females). Note centimeter marking at teeth or gums.">
                        <input type="checkbox" id="verify-depth">
                        <label for="verify-depth">Verify Appropriate Depth</label>
                    </div>
                    <div class="checklist-item" data-info="Verify tube placement using primary (direct visualization through cords) and secondary confirmations (ETCO2, chest rise, absence of epigastric sounds, misting in tube). Document all confirmation methods used.">
                        <input type="checkbox" id="confirm-placement">
                        <label for="confirm-placement">Confirm Placement</label>
                    </div>
                    <div class="checklist-item" data-info="Secure ETT using commercial device or tape. Ensure tube is stable and cannot be easily dislodged during movement or transport.">
                        <input type="checkbox" id="secure-ett">
                        <label for="secure-ett">Secure ETT</label>
                    </div>
                </div>
            </div>
            
            <!-- Post Intubation Section -->
            <div class="section">
                <div class="section-header">
                    <h2>Post Intubation</h2>
                </div>
                <div class="checklist-items">
                    <div class="checklist-item" data-info="Set initial ventilator parameters: Typically 6-8 mL/kg ideal body weight tidal volume, RR 12-16, PEEP 5 cmH2O. Adjust FiO2 based on SpO2 goals. Monitor for compliance with ventilation.">
                        <input type="checkbox" id="ventilation-management">
                        <label for="ventilation-management">Ventilation Management</label>
                    </div>
                    <div class="checklist-item" data-info="Administer ongoing sedation and analgesia to maintain patient comfort and synchrony with ventilator. Titrate to appropriate sedation score (e.g., RASS -2 to -3).">
                        <input type="checkbox" id="sedation-maintained">
                        <label for="sedation-maintained">Sedation & Analgesia Maintained</label>
                    </div>
                    <div class="checklist-item" data-info="Reassess tube position with each patient movement. Monitor ETCO2 waveform, chest rise, SpO2, and auscultate to ensure tube remains properly positioned.">
                        <input type="checkbox" id="reconfirm">
                        <label for="reconfirm">Reconfirm every 5 minutes or with every patient movement.</label>
                    </div>
                    <div class="checklist-item" data-info="Insert orogastric (OG) tube to decompress stomach, reduce aspiration risk, and improve ventilation mechanics. Confirm placement and secure OG tube.">
                        <input type="checkbox" id="og-tube">
                        <label for="og-tube">OG Tube Placed</label>
                    </div>
                    <div class="checklist-item" data-info="Ensure backup airway equipment remains immediately available: Additional ETT sizes, supraglottic airways, surgical cricothyrotomy kit, BVM, laryngoscope.">
                        <input type="checkbox" id="backups-available">
                        <label for="backups-available">Backups Available</label>
                    </div>
                </div>
            </div>
            
            <!-- Transfer & Documentation Section -->
            <div class="section">
                <div class="section-header">
                    <h2>Transfer & Documentation</h2>
                </div>
                <div class="checklist-items">
                    <div class="checklist-item" data-info="Prior to transport, verify ETT position using capnography and auscultation. Confirm tube is secure and ventilator/BVM is functioning properly. Reassess before moving patient.">
                        <input type="checkbox" id="recheck-capnography">
                        <label for="recheck-capnography">Recheck Capnography & Tube Security</label>
                    </div>
                    <div class="checklist-item" data-info="Conduct formal team debrief: Discuss what went well, challenges encountered, and opportunities for improvement. Address any concerns from team members.">
                        <input type="checkbox" id="team-debrief">
                        <label for="team-debrief">Team Debrief</label>
                    </div>
                    <div class="checklist-item" data-info="Document complete procedure details: ETT size, depth, medications used, number of attempts, confirmation methods, ventilator settings, complications encountered, and post-procedure vital signs.">
                        <input type="checkbox" id="document-ett">
                        <label for="document-ett">Document – ETT size/depth, meds, EtCO<sub>2</sub>, difficulties, ventilator settings</label>
                    </div>
                </div>
            </div>
        </div>
        
        <p class="note-disclaimer">This is not intended to be a comprehensive guide for prehospital intubation</p>
    </div>
    
    <!-- Modal Window for Detailed Information -->
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('infoModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalInfo = document.getElementById('modalInfo');
            const closeBtn = document.querySelector('.close-btn');
            const checklistItems = document.querySelectorAll('.checklist-item');
            
            // Convert existing checklist items to use custom checkboxes
            checklistItems.forEach(item => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                const label = item.querySelector('label');
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
                
                // Add info icon
                const infoIcon = document.createElement('span');
                infoIcon.className = 'info-icon';
                infoIcon.innerHTML = 'i'; // info symbol
                infoIcon.title = "Click for details";
                item.appendChild(infoIcon);
                
                // Add event listener for checkbox to toggle completed class
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        item.classList.add('completed');
                    } else {
                        item.classList.remove('completed');
                    }
                });
            });
            
            // Open modal when clicking a checklist item or info icon
            checklistItems.forEach(item => {
                const handleItemClick = function(e) {
                    // Don't trigger if clicking the checkbox itself or its label
                    if (e.target.tagName === 'INPUT' || e.target.classList.contains('checkbox-container') || 
                        e.target.classList.contains('checkmark')) return;
                    
                    const title = item.querySelector('.checkbox-container').textContent;
                    const info = item.getAttribute('data-info');
                    
                    // Format the info text to enhance certain keywords
                    let formattedInfo = info;
                    
                    // Wrap important terms in <strong> tags
                    const importantTerms = [
                        'LEMON', 'ETT', 'PEEP', 'SpO2', 'FiO2', 'OG Tube', 'RSI', 
                        'oral', 'pharyngeal', 'laryngeal', 'epiglottis', 'vocal cords',
                        'glottic', 'cricothyrotomy', 'ETCO2', 'RASS', 'pre-oxygenation',
                        'pre-infusion', 'confirmation', 'secure'
                    ];
                    
                    importantTerms.forEach(term => {
                        const regex = new RegExp(`\\b${term}\\b`, 'g');
                        formattedInfo = formattedInfo.replace(regex, `<strong>${term}</strong>`);
                    });
                    
                    modalTitle.textContent = title;
                    modalInfo.innerHTML = formattedInfo;
                    modal.style.display = 'block';
                    
                    // Add a slight delay before adding the 'show' class for the animation
                    setTimeout(() => {
                        modal.classList.add('show');
                    }, 10);
                };
                
                item.addEventListener('click', handleItemClick);
            });
            
            // Functions to close modal
            function closeModal() {
                modal.classList.remove('show');
                // Wait for animation to complete before hiding
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
            
            // Close modal when clicking X button
            closeBtn.addEventListener('click', closeModal);
            
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
        });
    </script>
</body>
</html>