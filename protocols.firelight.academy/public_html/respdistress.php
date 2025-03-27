<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adult Respiratory Distress Protocol</title>
    <style>
        :root {
            --primary-color: #006699;
            --primary-gradient: linear-gradient(135deg, #006699, #004d80);
            --modal-gradient: linear-gradient(135deg, #2a9d8f, #1d6a67);
            --light-bg: #e6f2f8;
            --border-color: #cccccc;
            
            /* Provider level colors */
            --emr-color: #a0a0a0;
            --emt-color: #ffdb58;
            --emt-iv-color: #ff69b4;
            --aemt-color: #90ee90;
            --intermediate-color: #ffa500;
            --paramedic-color: #87ceeb;
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
            margin: 0 0 10px 0;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            letter-spacing: 0.5px;
        }
        
        .provider-levels {
            display: flex;
            justify-content: center;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        
        .provider-level {
            padding: 8px 15px;
            color: #000;
            font-weight: bold;
            margin: 5px;
            border-radius: 5px;
        }
        
        .emr { background-color: var(--emr-color); }
        .emt { background-color: var(--emt-color); }
        .emt-iv { background-color: var(--emt-iv-color); }
        .aemt { background-color: var(--aemt-color); }
        .intermediate { background-color: var(--intermediate-color); }
        .paramedic { background-color: var(--paramedic-color); }
        
        .protocol-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .assessment-box {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .assessment-title {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .assessment-criteria {
            list-style-type: none;
        }
        
        .assessment-criteria li {
            margin-bottom: 8px;
            padding-left: 15px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .assessment-criteria li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--primary-color);
        }
        
        .assessment-criteria li:hover {
            color: var(--primary-color);
            transform: translateX(3px);
        }
        
        .flowchart {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .flow-step {
            width: 100%;
            max-width: 450px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .flow-step:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            border-left-color: var(--primary-color);
        }
        
        .flow-step.clickable {
            font-weight: bold;
            color: #0066cc;
            text-decoration: underline;
        }
        
        .flow-arrow {
            height: 20px;
            width: 20px;
            margin: 0 auto;
            position: relative;
        }
        
        .flow-arrow:after {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 10px solid var(--primary-color);
        }
        
        .decision-box {
            width: 100%;
            max-width: 450px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid #ff9800;
        }
        
        .decision-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        .yes-no-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 450px;
            margin-top: 5px;
        }
        
        .yes-path {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 45%;
        }
        
        .no-path {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 45%;
        }
        
        .path-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .protocol-link {
            width: 100%;
            padding: 10px;
            background-color: #e3f2fd;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .protocol-link:hover {
            background-color: #bbdefb;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .provider-bar {
            height: 10px;
            width: 100%;
            display: flex;
            margin-bottom: 5px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .provider-segment {
            height: 100%;
        }
        
        .provider-segment.emr { background-color: var(--emr-color); }
        .provider-segment.emt { background-color: var(--emt-color); }
        .provider-segment.emt-iv { background-color: var(--emt-iv-color); }
        .provider-segment.aemt { background-color: var(--aemt-color); }
        .provider-segment.intermediate { background-color: var(--intermediate-color); }
        .provider-segment.paramedic { background-color: var(--paramedic-color); }
        
        .action-list {
            width: 100%;
            max-width: 450px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 15px;
        }
        
        .action-list ul {
            list-style-type: none;
        }
        
        .action-list li {
            position: relative;
            padding-left: 20px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .action-list li:before {
            content: "•";
            position: absolute;
            left: 5px;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .action-list li:hover {
            color: var(--primary-color);
            transform: translateX(3px);
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
        @media (max-width: 992px) {
            .protocol-container {
                grid-template-columns: 1fr;
            }
            
            .yes-no-container {
                flex-direction: column;
                align-items: center;
            }
            
            .yes-path, .no-path {
                width: 100%;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Adult Respiratory Distress Protocol</h1>
        <div class="provider-levels">
            <div class="provider-level emr">EMR</div>
            <div class="provider-level emt">EMT</div>
            <div class="provider-level emt-iv">EMT-IV</div>
            <div class="provider-level aemt">AEMT</div>
            <div class="provider-level intermediate">INTERMEDIATE</div>
            <div class="provider-level paramedic">PARAMEDIC</div>
        </div>
    </header>
    
    <div class="container">
        <div class="protocol-container">
            <!-- Left Column - Assessment Boxes -->
            <div class="assessment-column">
                <div class="assessment-box" data-info="Inadequate oxygenation may present with various clinical signs including hypoxemia, cyanosis, and increased work of breathing. Early intervention is critical to prevent deterioration.">
                    <div class="assessment-title">Inadequate Oxygenation</div>
                    <ul class="assessment-criteria">
                        <li data-info="Pulse oximetry readings below 90% despite administration of high-flow oxygen indicate severe hypoxemia and require immediate intervention. This suggests significant V/Q mismatch, shunt, or diffusion abnormality.">SpO₂ less than 90% despite high flow O₂</li>
                    </ul>
                </div>
                
                <div class="assessment-box" data-info="Inadequate ventilation refers to the inability to effectively move air in and out of the lungs, which can lead to carbon dioxide retention and respiratory acidosis.">
                    <div class="assessment-title">Inadequate Ventilation</div>
                    <ul class="assessment-criteria">
                        <li data-info="Patients with dyspnea severe enough to impair speech are demonstrating significant respiratory distress. The inability to speak in full sentences indicates severe air hunger and increased work of breathing.">Dyspnea with verbal impairment – i.e. cannot speak in full sentences</li>
                        <li data-info="Use of accessory muscles (sternocleidomastoid, scalenes, intercostals) indicates increased work of breathing as the patient attempts to overcome increased airway resistance or decreased compliance.">Accessory muscle use</li>
                        <li data-info="Tachypnea (rapid breathing) in adults is typically defined as >20 breaths per minute. Persistent tachypnea despite oxygen administration indicates significant respiratory compromise.">Respiratory rate greater than 24/minute despite O₂</li>
                        <li data-info="Decreased tidal volume (volume of air moved in one breath) leads to inadequate alveolar ventilation. Often observed as shallow, rapid breathing pattern.">Diminished tidal volume</li>
                    </ul>
                </div>
                
                <div class="assessment-box" data-info="Respiratory distress can be caused by primary lung pathology or by conditions affecting other body systems. A systematic approach to differential diagnosis is essential.">
                    <div class="assessment-title">Consider pulmonary and non-pulmonary causes of respiratory distress:</div>
                    <ul class="assessment-criteria">
                        <li data-info="Pulmonary embolism occurs when a blood clot lodges in the pulmonary arterial system, blocking blood flow. Classic presentation includes sudden-onset dyspnea, pleuritic chest pain, tachycardia, and hypoxemia often refractory to oxygen therapy.">Pulmonary embolism</li>
                        <li data-info="Bacterial, viral, or fungal infection of the lung parenchyma presents with fever, productive cough, dyspnea, decreased breath sounds, and crackles on auscultation.">Pneumonia</li>
                        <li data-info="Myocardial infarction can cause respiratory distress due to decreased cardiac output and resultant pulmonary congestion. Associated symptoms include chest pain, diaphoresis, nausea, and anxiety.">Heart attack</li>
                        <li data-info="Accumulation of air in the pleural space can cause lung collapse and respiratory distress. Typically presents with sudden-onset dyspnea, pleuritic chest pain, decreased breath sounds, and hyperresonance to percussion on the affected side.">Pneumothorax</li>
                        <li data-info="Obstruction of the upper or lower airways can cause respiratory distress. Etiologies include foreign body, laryngospasm, bronchospasm, or tumor. Presentation varies based on location and degree of obstruction.">Sepsis</li>
                        <li data-info="Metabolic acidosis leads to compensatory hyperventilation (Kussmaul respirations). Common causes include diabetic ketoacidosis, lactic acidosis, renal failure, and toxic ingestions.">Metabolic acidosis (e.g. DKA)</li>
                        <li data-info="Hyperventilation syndrome due to anxiety can mimic serious respiratory conditions. Typically presents with paresthesias, lightheadedness, and carpopedal spasm. It is a diagnosis of exclusion.">Anxiety</li>
                    </ul>
                </div>
                
                <div class="assessment-box" data-info="In complex respiratory presentations, multiple pathophysiological processes may be occurring simultaneously. This requires a comprehensive approach to management addressing all contributing factors.">
                    <div class="assessment-title">Mixed picture may exist</div>
                    <ul class="assessment-criteria">
                        <li data-info="Regardless of etiology, improving oxygenation and ventilation is the primary goal. This may require supplemental oxygen, assisted ventilation, medication administration, or a combination of interventions.">Goal is maximization of oxygenation and ventilation in all cases</li>
                        <li data-info="CPAP is beneficial for improving gas exchange by increasing functional residual capacity and reducing work of breathing. However, it's contraindicated in patients with mixed respiratory failure presenting with hypoxemia and hypercapnia.">CPAP may be particularly useful in mixed picture with hypoxia and/or hyperventilation</li>
                        <li data-info="Albuterol (a beta-2 agonist) can worsen pulmonary edema by increasing myocardial oxygen demand. In cases of suspected cardiogenic pulmonary edema, avoid albuterol unless there's a clear bronchospasm component.">Avoid albuterol in suspected pulmonary edema</li>
                    </ul>
                </div>
            </div>
            
            <!-- Right Column - Flowchart -->
            <div class="flowchart-column">
                <div class="flowchart">
                    <!-- Initial Step -->
                    <div class="flow-step" data-info="Respiratory distress is a clinical syndrome characterized by increased work of breathing, abnormal respiratory rate or pattern, and signs of inadequate gas exchange. Early recognition and intervention are crucial.">
                        <div class="provider-bar">
                            <div class="provider-segment emr" style="width: 16.66%"></div>
                            <div class="provider-segment emt" style="width: 16.66%"></div>
                            <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                            <div class="provider-segment aemt" style="width: 16.66%"></div>
                            <div class="provider-segment intermediate" style="width: 16.66%"></div>
                            <div class="provider-segment paramedic" style="width: 16.66%"></div>
                        </div>
                        Respiratory Distress
                    </div>
                    
                    <div class="flow-arrow"></div>
                    
                    <!-- For all patients -->
                    <div class="flow-step" data-info="All patients with respiratory distress require continuous assessment of airway, breathing, and circulation. Oxygen supplementation should be titrated based on oxygen saturation targets, with a goal of maintaining SpO₂ >94% in most cases.">
                        <div class="provider-bar">
                            <div class="provider-segment emr" style="width: 16.66%"></div>
                            <div class="provider-segment emt" style="width: 16.66%"></div>
                            <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                            <div class="provider-segment aemt" style="width: 16.66%"></div>
                            <div class="provider-segment intermediate" style="width: 16.66%"></div>
                            <div class="provider-segment paramedic" style="width: 16.66%"></div>
                        </div>
                        For all patients:<br>
                        While assessing ABCs, Ref. <span class="clickable" data-info="Oxygen therapy is crucial in respiratory distress. Options include nasal cannula (1-6 LPM, delivers 24-44% FiO2), simple face mask (6-10 LPM, delivers 35-60% FiO2), non-rebreather mask (10-15 LPM, delivers 60-95% FiO2), or high-flow systems. Titrate to maintain SpO₂ >94% unless contraindicated.">Oxygen</span>, monitor vital signs
                    </div>
                    
                    <div class="flow-arrow"></div>
                    
                    <!-- Monitor SpO2 -->
                    <div class="flow-step" data-info="Continuous pulse oximetry monitoring provides real-time data on oxygen saturation. Waveform capnography measures end-tidal CO2 and can assist in monitoring ventilatory status, confirming airway placement, and detecting hypoventilation or bronchospasm.">
                        <div class="provider-bar">
                            <div class="provider-segment emr" style="width: 16.66%"></div>
                            <div class="provider-segment emt" style="width: 16.66%"></div>
                            <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                            <div class="provider-segment aemt" style="width: 16.66%"></div>
                            <div class="provider-segment intermediate" style="width: 16.66%"></div>
                            <div class="provider-segment paramedic" style="width: 16.66%"></div>
                        </div>
                        Monitor SpO₂ and waveform capnography
                    </div>
                    
                    <div class="flow-arrow"></div>
                    
                    <!-- Monitor cardiac rhythm -->
                    <div class="flow-step" data-info="Cardiac monitoring is essential as respiratory distress may be a manifestation of cardiac pathology, and cardiorespiratory interactions can lead to arrhythmias. Hypoxemia can cause tachycardia, bradycardia, PVCs, or more serious arrhythmias.">
                        <div class="provider-bar">
                            <div class="provider-segment intermediate" style="width: 50%"></div>
                            <div class="provider-segment paramedic" style="width: 50%"></div>
                        </div>
                        Monitor cardiac rhythm
                    </div>
                    
                    <div class="flow-arrow"></div>
                    
                    <!-- Patent airway decision -->
                    <div class="decision-box" data-info="Airway patency assessment: Look for signs of obstruction (stridor, gurgling, or snoring sounds), Listen for abnormal breath sounds, Feel for air movement, and Observe for adequate chest rise and fall with respirations.">
                        <div class="provider-bar">
                            <div class="provider-segment emr" style="width: 16.66%"></div>
                            <div class="provider-segment emt" style="width: 16.66%"></div>
                            <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                            <div class="provider-segment aemt" style="width: 16.66%"></div>
                            <div class="provider-segment intermediate" style="width: 16.66%"></div>
                            <div class="provider-segment paramedic" style="width: 16.66%"></div>
                        </div>
                        Patent airway?
                    </div>
                    
                    <div class="yes-no-container">
                        <div class="no-path">
                            <div class="path-label">No</div>
                            <div class="flow-arrow"></div>
                            <div class="protocol-link" data-info="Follow the Obstructed Airway protocol for management of airway obstruction, which includes assessment of obstruction severity, positioning, back blows/chest thrusts for complete obstruction, and appropriate airway maneuvers or adjuncts based on provider level.">
                                Obstructed Airway protocol
                            </div>
                        </div>
                        
                        <div class="yes-path">
                            <div class="path-label">Yes</div>
                            <div class="flow-arrow"></div>
                            
                            <!-- Adequate ventilations decision -->
                            <div class="decision-box" data-info="Assess adequacy of ventilations by evaluating respiratory rate, depth, pattern, use of accessory muscles, air movement, and chest rise. Signs of inadequate ventilation include tachypnea, shallow breathing, asymmetric chest rise, decreased air movement, and abnormal breath sounds.">
                                <div class="provider-bar">
                                    <div class="provider-segment emr" style="width: 16.66%"></div>
                                    <div class="provider-segment emt" style="width: 16.66%"></div>
                                    <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                                    <div class="provider-segment aemt" style="width: 16.66%"></div>
                                    <div class="provider-segment intermediate" style="width: 16.66%"></div>
                                    <div class="provider-segment paramedic" style="width: 16.66%"></div>
                                </div>
                                Are ventilations adequate for physiologic state?
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assisted ventilations -->
                    <div class="flow-step" data-info="Assisted ventilations are indicated for inadequate respiratory effort. Bag-valve-mask ventilation should be performed with proper technique: E-C clamp grip, two-person technique when possible, appropriate mask size, and adequate tidal volume (6-8 mL/kg). Airway adjuncts that may be needed include oropharyngeal airways, nasopharyngeal airways, and supraglottic devices.">
                        <div class="provider-bar">
                            <div class="provider-segment emr" style="width: 16.66%"></div>
                            <div class="provider-segment emt" style="width: 16.66%"></div>
                            <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                            <div class="provider-segment aemt" style="width: 16.66%"></div>
                            <div class="provider-segment intermediate" style="width: 16.66%"></div>
                            <div class="provider-segment paramedic" style="width: 16.66%"></div>
                        </div>
                        Assist ventilations with BVM and airway adjuncts as needed
                    </div>
                    
                    <div class="flow-arrow"></div>
                    
                    <!-- Anaphylaxis decision -->
                    <div class="decision-box" data-info="Anaphylaxis is a severe, potentially life-threatening allergic reaction. Signs include: airway - stridor, swelling of lips/tongue/uvula; breathing - dyspnea, wheezing, bronchospasm; circulation - hypotension, tachycardia; skin - urticaria, flushing, angioedema; GI - cramping, vomiting, diarrhea.">
                        <div class="provider-bar">
                            <div class="provider-segment emr" style="width: 16.66%"></div>
                            <div class="provider-segment emt" style="width: 16.66%"></div>
                            <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                            <div class="provider-segment aemt" style="width: 16.66%"></div>
                            <div class="provider-segment intermediate" style="width: 16.66%"></div>
                            <div class="provider-segment paramedic" style="width: 16.66%"></div>
                        </div>
                        Is anaphylaxis likely?
                    </div>
                    
                    <div class="yes-no-container">
                        <div class="yes-path">
                            <div class="path-label">Yes</div>
                            <div class="flow-arrow"></div>
                            <div class="protocol-link" data-info="The Allergy and Anaphylaxis protocol includes: administration of epinephrine (primary treatment), airway management, oxygen therapy, IV fluid resuscitation for hypotension, antihistamines, and corticosteroids. Prompt recognition and treatment are essential.">
                                Allergy and Anaphylaxis protocol
                            </div>
                        </div>
                        
                        <div class="no-path">
                            <div class="path-label">No</div>
                            <div class="flow-arrow"></div>
                            
                            <!-- Asthma/COPD decision -->
                            <div class="decision-box" data-info="Asthma presents with episodic wheezing, chest tightness, dyspnea, and cough. COPD presents with progressive dyspnea, chronic cough with sputum production, and history of smoking. Both exhibit expiratory wheezing and prolonged expiratory phase due to airflow obstruction.">
                                <div class="provider-bar">
                                    <div class="provider-segment emr" style="width: 16.66%"></div>
                                    <div class="provider-segment emt" style="width: 16.66%"></div>
                                    <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                                    <div class="provider-segment aemt" style="width: 16.66%"></div>
                                    <div class="provider-segment intermediate" style="width: 16.66%"></div>
                                    <div class="provider-segment paramedic" style="width: 16.66%"></div>
                                </div>
                                Is asthma or COPD likely?
                            </div>
                        </div>
                    </div>
                    
                    <div class="yes-no-container">
                        <div class="yes-path">
                            <div class="path-label">Yes</div>
                            <div class="flow-arrow"></div>
                            <div class="protocol-link" data-info="The Adult Wheezing protocol includes: inhaled bronchodilators (albuterol, ipratropium), systemic corticosteroids, consideration of magnesium sulfate for severe cases, and CPAP or assisted ventilation if needed. Continuous reassessment of response to therapy is essential.">
                                Adult Wheezing protocol
                            </div>
                        </div>
                        
                        <div class="no-path">
                            <div class="path-label">No</div>
                            <div class="flow-arrow"></div>
                            
                            <!-- CHF/Pulmonary Edema decision -->
                            <div class="decision-box" data-info="CHF/Pulmonary edema presents with dyspnea, orthopnea, paroxysmal nocturnal dyspnea, bilateral crackles on auscultation, JVD, peripheral edema, frothy pink sputum (in severe cases), and cardiac history. Differentiating from COPD can be challenging but is critical for proper management.">
                                <div class="provider-bar">
                                    <div class="provider-segment emr" style="width: 16.66%"></div>
                                    <div class="provider-segment emt" style="width: 16.66%"></div>
                                    <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                                    <div class="provider-segment aemt" style="width: 16.66%"></div>
                                    <div class="provider-segment intermediate" style="width: 16.66%"></div>
                                    <div class="provider-segment paramedic" style="width: 16.66%"></div>
                                </div>
                                Is CHF/pulmonary edema likely?
                            </div>
                        </div>
                    </div>
                    
                    <div class="yes-no-container">
                        <div class="yes-path">
                            <div class="path-label">Yes</div>
                            <div class="flow-arrow"></div>
                            <div class="protocol-link" data-info="The CHF/Pulmonary Edema protocol includes: positioning (upright), oxygen therapy, noninvasive positive pressure ventilation (CPAP/BiPAP), nitrates (nitroglycerin), diuretics (furosemide), and possibly morphine. The goal is to reduce preload and afterload while improving oxygenation and ventilation.">
                                CHF/Pulmonary Edema protocol
                            </div>
                        </div>
                        
                        <div class="no-path">
                            <div class="path-label">No</div>
                            <div class="flow-arrow"></div>
                            
                            <!-- Final recommendations -->
                            <div class="action-list">
                                <div class="provider-bar">
                                    <div class="provider-segment emr" style="width: 16.66%"></div>
                                    <div class="provider-segment emt" style="width: 16.66%"></div>
                                    <div class="provider-segment emt-iv" style="width: 16.66%"></div>
                                    <div class="provider-segment aemt" style="width: 16.66%"></div>
                                    <div class="provider-segment intermediate" style="width: 16.66%"></div>
                                    <div class="provider-segment paramedic" style="width: 16.66%"></div>
                                </div>
                                <ul>
                                    <li data-info="Transport decisions should consider patient stability, transport time, and capability of the receiving facility. Position the patient for maximum comfort and respiratory efficiency, typically upright or semi-Fowler's position unless contraindicated.">Transport</li>
                                    <li data-info="Supportive care includes maintaining airway, breathing, and circulation. Keep the patient calm, minimize exertion, maintain appropriate temperature, position for comfort and optimal respiratory mechanics, and reassess frequently.">Provide supportive care</li>
                                    <li data-info="Titrate oxygen therapy to maintain SpO₂ >94% (or 88-92% in COPD patients). Assist ventilations as needed with appropriate rate and tidal volume. Consider noninvasive positive pressure ventilation if available and indicated.">Maximize oxygenation and ventilation</li>
                                    <li data-info="Base consultation may be necessary for patients with complex presentations, refractory symptoms, or those requiring interventions beyond local protocols. Early notification of the receiving facility allows for preparation of necessary resources.">Contact Base if needed for consult</li>
                                </ul>
                            </div>
                            
                            <div class="flow-arrow"></div>
                            
                            <!-- ECG -->
                            <div class="flow-step" data-info="A 12-lead ECG can help identify cardiac causes of respiratory distress (MI, heart failure), detect arrhythmias that may contribute to symptoms, and establish a baseline for comparison. Look for signs of right heart strain in pulmonary embolism, ischemia or infarction, and rate/rhythm abnormalities.">
                                <div class="provider-bar">
                                    <div class="provider-segment intermediate" style="width: 50%"></div>
                                    <div class="provider-segment paramedic" style="width: 50%"></div>
                                </div>
                                Acquire 12 lead ECG
                            </div>
                            
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <p class="note-disclaimer">This is not intended to be a comprehensive guide for adult respiratory distress management. Always follow local protocols and medical direction.</p>
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
            
            // All clickable elements
            const clickableElements = [
                ...document.querySelectorAll('.assessment-box'),
                ...document.querySelectorAll('.assessment-criteria li'),
                ...document.querySelectorAll('.flow-step'),
                ...document.querySelectorAll('.decision-box'),
                ...document.querySelectorAll('.protocol-link'),
                ...document.querySelectorAll('.action-list li'),
                ...document.querySelectorAll('.clickable')
            ];
            
            // Open modal when clicking a clickable element
            clickableElements.forEach(item => {
                item.addEventListener('click', function(e) {
                    const info = this.getAttribute('data-info');
                    if (!info) return;
                    
                    let title;
                    if (this.querySelector('.assessment-title')) {
                        title = this.querySelector('.assessment-title').textContent;
                    } else if (this.classList.contains('protocol-link')) {
                        title = this.textContent.trim();
                    } else if (this.classList.contains('flow-step') || this.classList.contains('decision-box')) {
                        title = this.textContent.trim().split('\n').pop().trim();
                    } else if (this.classList.contains('clickable')) {
                        title = this.textContent.trim();
                    } else {
                        title = this.textContent.trim();
                    }
                    
                    // Format the info text to enhance certain keywords
                    let formattedInfo = info;
                    
                    // Wrap important terms in <strong> tags
                    const importantTerms = [
                        'SpO₂', 'O₂', 'hypoxemia', 'tachypnea', 'dyspnea', 'CPAP', 'BiPAP',
                        'pulmonary embolism', 'pneumonia', 'pneumothorax', 'anaphylaxis',
                        'asthma', 'COPD', 'CHF', 'pulmonary edema', 'accessory muscles',
                        'tidal volume', 'ventilation', 'oxygenation', 'capnography',
                        'bronchospasm', 'albuterol', 'ETCO2', 'hypercapnia'
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
                });
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