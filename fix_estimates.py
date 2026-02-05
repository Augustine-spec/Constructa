
import os

file_path = r'c:\xampp\htdocs\Constructa\active_estimates.php'

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

start_marker = '            <!-- =============================================='
start_marker_2 = '                 STEP 4: AI STUDY EXPERIENCE (FULL OVERLAY)'
end_marker = '        function formatMoney(amount) {'

start_idx = -1
end_idx = -1

# Find the SECOND occurrence of the start marker (the invalid one)
# actually, in the current file, the valid Step 4 is titled "Design Style" in my new code?
# Wait, I replaced Step 4 HTML with "Design Style".
# BUT, I pasted the ORIGINAL Step 4 AI HTML (which was titled Step 4) at the end.
# The Valid AI HTML (Step 5 now) is also in the file.
# Let's check Step 107 replacement again.
# I had:
# <!-- Step 4: Design Style -->
# ...
# <!-- ============================================== STEP 5: AI STUDY EXPERIENCE ... -->
# ...
# And then the accidental append:
# <!-- ============================================== STEP 4: AI STUDY EXPERIENCE ... -->

# So I should look for "STEP 4: AI STUDY EXPERIENCE".
# The valid one is "STEP 5".
# The invalid one is "STEP 4".

start_idx = -1
for i, line in enumerate(lines):
    if 'STEP 4: AI STUDY EXPERIENCE' in line:
        # Check if previous line matches marker
        if i > 0 and start_marker.strip() in lines[i-1]:
            start_idx = i - 1
            break

if start_idx == -1:
    print("Could not find start marker")
    exit(1)

# Find end marker after start_idx
for i, line in enumerate(lines):
    if i > start_idx and end_marker in line:
        end_idx = i
        break

if end_idx == -1:
    print("Could not find end marker")
    exit(1)

print(f"Removing lines {start_idx+1} to {end_idx}")
new_lines = lines[:start_idx] + lines[end_idx:]

with open(file_path, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print("Successfully removed duplicate block.")
