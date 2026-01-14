# CONSTRUCTA PROJECT LIFECYCLE - FIXED STRUCTURE

## Overview
The project lifecycle has been streamlined to **7 essential stages** (indices 0-6), removing unnecessary intermediate stages.

## Stage Configuration

### Stage 0: Data Gathering & Requirements
- **Index**: 0
- **Icon**: fa-file-invoice
- **Purpose**: Comprehensive collection of homeowner requirements and site constraints
- **Key Fields**: Plot area, soil type, floors, budget, BHK, kitchen, location, timeline

### Stage 1: Site Inspection & Survey
- **Index**: 1
- **Icon**: fa-map-marked-alt
- **Purpose**: Physical site inspection, plot measurement, and road access verification
- **Key Fields**: Front width, lateral depth, total area, road width, road type, constraints

### Stage 2: Planning & Design
- **Index**: 2
- **Icon**: fa-pencil-ruler
- **Purpose**: Architectural layout, floor plans, and spatial organization
- **Key Fields**: Bedrooms, bathrooms, floor height, elevation style, structural compliance

### Stage 3: Cost Estimation & BOQ
- **Index**: 3
- **Icon**: fa-file-invoice-dollar
- **Purpose**: Detailed cost estimation and Bill of Quantities preparation
- **Key Fields**: Foundation, RCC, masonry, flooring, MEP, finishes costs

### Stage 4: Approval & Permissions
- **Index**: 4
- **Icon**: fa-file-signature
- **Purpose**: Tracking and managing legal approvals, permits, and NOCs
- **Key Fields**: Plan approval, structural safety, land verification, utility permits, NOCs

### Stage 5: Construction Execution
- **Index**: 5
- **Icon**: fa-hard-hat
- **Purpose**: On-site construction activities and structural work
- **Key Fields**: Phases, material usage, contractor info, issues, timeline

### Stage 6: Handover & Closure
- **Index**: 6
- **Icon**: fa-key
- **Purpose**: Final inspection, documentation handover, and project closure
- **Key Fields**: Walkthrough checklist, possession date, utilities, settlement, DLP

## Key Changes Made

### 1. Stage Configuration (engineer_workspace.php)
- **Lines 28-36**: Removed stages 6-8 (Site Updates, Quality & Safety, Testing & Inspection)
- **Result**: Clean 7-stage lifecycle from Data Gathering to Handover

### 2. PHP Conditional Rendering
- **Line 1920**: Changed `elseif ($current_stage_idx === 9)` to `elseif ($current_stage_idx === 6)`
- **Result**: Handover & Closure now renders at correct index

### 3. JavaScript Navigation Logic
- **Lines 3656-3662**: Updated `updateStageUI()` to handle stages 0-6 (was 0-9)
- **Lines 3682-3689**: Updated `switchStage()` to redirect for stages 0-6 (was 0-9)
- **Result**: Proper stage navigation and redirection

### 4. JavaScript Initialization
- **Lines 3905-3919**: Changed Handover UI initialization from `viewStageIdx === 9` to `viewStageIdx === 6`
- **Result**: Handover stage properly initializes on page load

### 5. Backend Completion Logic (backend/update_project_stage.php)
- **Line 35**: Changed completion condition from `$new_stage >= 10` to `$new_stage >= 7`
- **Result**: Projects marked as completed when reaching Handover stage

## Input Box Functionality

All input boxes across all stages are properly configured with:

1. **PHP Value Binding**: `value="<?php echo htmlspecialchars($project_details['stage']['field'] ?? ''); ?>"`
2. **JavaScript Event Handlers**: `oninput="updateStagePreview('field', this.value)"`
3. **Data Persistence**: Values saved via `saveDraft()` function to backend
4. **Validation**: Client-side validation with visual feedback

### Common Input Patterns

**Text/Number Inputs:**
```html
<input type="number" class="c-input" 
       oninput="updateGatherPreview('plot_area', this.value)" 
       value="<?php echo htmlspecialchars($project_details['gathering']['plot_area'] ?? ''); ?>">
```

**Select Dropdowns:**
```html
<select class="c-input" onchange="updateSurveyPreview('road_type', this.value)">
    <option value="Asphalt" <?php echo (($project_details['survey']['road_type'] ?? '') == 'Asphalt') ? 'selected' : ''; ?>>Asphalt</option>
</select>
```

**Textareas:**
```html
<textarea class="c-input" 
          oninput="updatePlanningData('notes', this.value)"><?php echo htmlspecialchars($project_details['planning']['notes'] ?? ''); ?></textarea>
```

## Data Flow

1. **Page Load**: PHP populates input values from `$project_details` array
2. **User Input**: JavaScript updates corresponding data object (e.g., `gatherData`, `surveyData`)
3. **Save Draft**: `saveDraft()` sends all data to `backend/save_project_details.php`
4. **Stage Completion**: `approveCurrentStage()` increments stage and reloads page
5. **Reload**: New stage renders with saved data from database

## Troubleshooting Input Issues

If input boxes are not working:

1. **Check Browser Console**: Look for JavaScript errors
2. **Verify Data Objects**: Ensure `gatherData`, `surveyData`, etc. are initialized
3. **Test Event Handlers**: Confirm `oninput`/`onchange` functions are defined
4. **Inspect Network Tab**: Check if `save_project_details.php` is being called
5. **Database Check**: Verify `project_details` column contains JSON data

## Testing Checklist

- [ ] All 7 stages appear in sidebar
- [ ] Clicking each stage navigates correctly
- [ ] Input boxes accept and display values
- [ ] Save Draft persists data across reloads
- [ ] Stage completion advances to next stage
- [ ] Handover stage (6) marks project as completed
- [ ] 3D house model updates per stage
- [ ] Document repository works for each stage

## File Changes Summary

**Modified Files:**
1. `engineer_workspace.php` - Stage configuration, PHP rendering, JavaScript logic
2. `backend/update_project_stage.php` - Completion condition

**Lines Changed:**
- Stage array: Lines 28-36
- PHP conditional: Line 1920
- JS navigation: Lines 3656-3662, 3682-3689
- JS initialization: Lines 3905-3919
- Backend logic: Line 35

## Next Steps

1. **Test in Browser**: Navigate through all 7 stages
2. **Verify Input Persistence**: Enter data, save, reload, confirm data persists
3. **Test Stage Progression**: Complete each stage and verify advancement
4. **Test Project Completion**: Reach Handover stage and verify project marked as completed
5. **Report Issues**: Document any remaining input box or navigation issues
