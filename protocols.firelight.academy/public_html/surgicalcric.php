<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surgical Cricothyrotomy Checklist</title>
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
        <h1>Surgical Cricothyrotomy Checklist</h1>
    </header>
    
    <div class="container">
        <div class="checklist-sections">
            <!-- Indications & Assessment -->
            <div class="section">
                <div class="section-header">
                    <h2>Indications & Assessment</h2>
                </div>
                <div class="checklist-items">
                    <div class="checklist-item" data-info="Confirm that all airway management options have been exhausted, including: failed intubation attempts, inability to ventilate with BVM, failed supraglottic airway device, and complete upper airway obstruction. Document all attempts.">
                        <input type="checkbox" id="verify-indication">
                        <label for="verify-indication">Verify Failed Airway</label>
                    </div>
                    <div class="checklist-item" data-info="Ensure that the patient meets 'cannot intubate, cannot oxygenate' (CICO) criteria. Include SpO2 <90% despite maximal attempts at oxygenation, and/or absence of end-tidal CO2, progressive bradycardia, or other signs of critical hypoxemia.">
                        <input type="checkbox" id="confirm-cico">
                        <label for="confirm-cico">Confirm CICO Criteria</label>
                    </div>
                    <div class="checklist-item" data-info="Rapidly assess for contraindications including: age <8 years (consider needle cricothyrotomy instead), inability to identify landmarks, direct trauma to cricothyroid membrane area, laryngeal fracture, tracheal transection, or acute laryngeal disease/cancer.">
                        <input type="checkbox" id="contraindications">
                        <label for="contraindications">Check Contraindications</label>
                    </div>
                    <div class="checklist-item" data-info="Locate the cricothyroid membrane: Identify thyroid cartilage (Adam's apple), move finger inferiorly to the depression between thyroid and cricoid cartilages. This is the cricothyroid membrane. If landmarks are difficult to identify, use laryngeal handshake technique: grasp larynx with thumb and middle finger, then move index finger to CTM.">
                        <input type="checkbox" id="identify-landmarks">
                        <label for="identify-landmarks">Identify Anatomical Landmarks</label>
                    </div>
                    <div class="checklist-item" data-info="Call for additional assistance if available. Confirm team readiness and clearly announce, 'Beginning surgical airway procedure.' Assign roles including: procedure performer, assistant for equipment and suction, vital signs monitoring, and documentation.">
                        <input type="checkbox" id="declare-cric">
                        <label for="declare-cric">Declare Cric Procedure</label>
                    </div>
                </div>
            </div>
            
            <!-- Equipment Preparation -->
            <div class="section">
                <div class="section-header">
                    <h2>Equipment Preparation</h2>
                </div>
                <div class="checklist-items">
                    <div class="checklist-item" data-info="Use commercial kit if available. Otherwise, gather standard equipment: scalpel with #10 or #11 blade, bougie or tracheal hook, 6.0-6.5mm cuffed tracheostomy or endotracheal tube, 10mL syringe, and secure ties or commercial tube holder.">
                        <input type="checkbox" id="gather-equipment">
                        <label for="gather-equipment">Gather Equipment</label>
                    </div>
                    <div class="checklist-item" data-info="Prepare suction equipment with rigid Yankauer tip. Ensure functioning suction with adequate pressure. Position suction device within immediate reach of the operator.">
                        <input type="checkbox" id="suction-ready">
                        <label for="suction-ready">Suction Ready</label>
                    </div>
                    <div class="checklist-item" data-info="Prepare for bleeding control with gauze and hemostats. Have backup method available (finger occlusion or pressure if needed). Consider hemostatic agents if available and bleeding is excessive.">
                        <input type="checkbox" id="hemorrhage-control">
                        <label for="hemorrhage-control">Hemorrhage Control Preparation</label>
                    </div>
                    <div class="checklist-item" data-info="Assemble BVM with PEEP valve connected to oxygen source at maximum flow rate. Have capnography ready to confirm placement. Prepare ventilation equipment for immediate use post-tube placement.">
                        <input type="checkbox" id="ventilation-equipment">
                        <label for="ventilation-equipment">Ventilation Equipment</label>
                    </div>
                    <div class="checklist-item" data-info="If time permits, apply antiseptic solution to anterior neck. Don appropriate PPE including gloves, face shield, and gown if available. Use sterile technique to the extent possible under emergency conditions.">
                        <input type="checkbox" id="antisepsis">
                        <label for="antisepsis">Antisepsis (if time permits)</label>
                    </div>
                </div>
            </div>
            
            <!-- Procedure Steps -->
            <div class="section">
                <div class="section-header">
                    <h2>Procedure Steps</h2>
                </div>
                <div class="checklist-items">
                    <div class="checklist-item" data-info="Stabilize the larynx with non-dominant hand. Place the patient in a supine position with neck in neutral position or slight extension if no cervical spine concerns exist. Continue oxygenation attempts throughout if possible.">
                        <input type="checkbox" id="patient-positioning">
                        <label for="patient-positioning">Patient Positioning</label>
                    </div>
                    <div class="checklist-item" data-info="Make a vertical or horizontal incision (depending on protocol/preference) through skin over the cricothyroid membrane. Vertical incision is ~4cm long centered over CTM. Horizontal incision is ~3cm long directly over CTM. Incise through skin and subcutaneous tissue.">
                        <input type="checkbox" id="skin-incision">
                        <label for="skin-incision">Skin Incision</label>
                    </div>
                    <div class="checklist-item" data-info="Locate cricothyroid membrane using blunt dissection with finger or instrument as needed. Make a horizontal incision through the cricothyroid membrane. Maintain a scalpel depth of approximately 1.5cm to avoid posterior wall injury.">
                        <input type="checkbox" id="membrane-incision">
                        <label for="membrane-incision">Cricothyroid Membrane Incision</label>
                    </div>
                    <div class="checklist-item" data-info="Open the incision with tracheal hook, dilator, or by rotating scalpel handle 90 degrees. Alternatively, insert bougie through opening and into trachea, directing distally. Confirm tracheal placement by feeling tracheal rings and/or carina.">
                        <input type="checkbox" id="dilate-opening">
                        <label for="dilate-opening">Dilate Opening</label>
                    </div>
                    <div class="checklist-item" data-info="Insert tracheostomy or endotracheal tube over bougie or directly into tracheal opening. Advance tube until the cuff is past the incision (approximately 2-3cm into trachea). Remove bougie if used while stabilizing tube.">
                        <input type="checkbox" id="tube-insertion">
                        <label for="tube-insertion">Tube Insertion</label>
                    </div>
                    <div class="checklist-item" data-info="Inflate cuff with 5-10mL of air. Attach BVM with oxygen and ventilate. Confirm placement with: bilateral chest rise, auscultation of bilateral breath sounds, absence of epigastric sounds, and presence of ETCO2 waveform.">
                        <input type="checkbox" id="tube-confirmation">
                        <label for="tube-confirmation">Confirm Tube Placement</label>
                    </div>
                </div>
            </div>
            
            <!-- Post-Procedure Care -->
            <div class="section">
                <div class="section-header">
                    <h2>Post-Procedure Care</h2>
                </div>
                <div class="checklist-items">
                    <div class="checklist-item" data-info="Secure tube with tracheostomy ties, commercial device, or tape. Ensure tube is stable and cannot be easily dislodged. Consider suturing tube in place if circumstances and skills permit.">
                        <input type="checkbox" id="secure-tube">
                        <label for="secure-tube">Secure Tube</label>
                    </div>
                    <div class="checklist-item" data-info="Monitor vital signs continuously: SpO2, heart rate, blood pressure, respiratory rate, ETCO2. Watch for complications including: subcutaneous emphysema, bleeding, tube dislodgement, inadequate ventilation, or barotrauma.">
                        <input type="checkbox" id="monitoring">
                        <label for="monitoring">Monitoring</label>
                    </div>
                    <div class="checklist-item" data-info="Control bleeding with direct pressure, gauze packing, or hemostatic agents as needed. Apply pressure dressings if substantial bleeding occurs. Reassess frequently for expanding hematoma or continued bleeding.">
                        <input type="checkbox" id="manage-bleeding">
                        <label for="manage-bleeding">Manage Bleeding</label>
                    </div>
                    <div class="checklist-item" data-info="Initiate appropriate sedation/analgesia to maintain patient comfort and tube tolerance. Consider paralysis if necessary for ventilator compliance. Titrate to appropriate level based on clinical condition.">
                        <input type="checkbox" id="sedation">
                        <label for="sedation">Sedation & Analgesia</label>
                    </div>
                    <div class="checklist-item" data-info="Document all aspects of procedure: indication, time performed, attempts required, tube size, confirmation methods, ventilator settings, complications, and response to procedure. Include photographs of placement if protocol allows.">
                        <input type="checkbox" id="documentation">
                        <label for="documentation">Documentation</label>
                    </div>
                    <div class="checklist-item" data-info="Provide clear handoff communication to receiving facility including: indication for surgical airway, landmarks used, equipment placed, ventilator settings, complications encountered, and planned next steps for definitive airway management.">
                        <input type="checkbox" id="handoff">
                        <label for="handoff">Transfer & Handoff</label>
                    </div>
                </div>
            </div>
        </div>
        
        <p class="note-disclaimer">This is not intended to be a comprehensive guide for emergency surgical cricothyrotomy. Always follow local protocols and medical direction.</p>
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
                        'CICO', 'cricothyroid membrane', 'CTM', 'BVM', 'bougie', 'PEEP', 'SpO2', 
                        'ETCO2', 'thyroid cartilage', 'cricoid cartilage', 'laryngeal handshake',
                        'subcutaneous emphysema', 'barotrauma', 'hemostats', 'Yankauer', 'PPE'
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