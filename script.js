document.addEventListener('DOMContentLoaded', function () {
    const editGradesBtn = document.getElementById('editGradesBtn');
    const saveChangesBtn = document.getElementById('saveChangesBtn');
    const gradeTable = document.getElementById('gradeTable');
    let isEditing = false;

    editGradesBtn.addEventListener('click', function () {
        isEditing = !isEditing; // Toggle editing state

        if (isEditing) {
            // Change button text and class
            editGradesBtn.textContent = 'Cancel Edit';
            gradeTable.classList.add('editing');
            saveChangesBtn.style.display = 'block'; // Show Save Changes button

            // Convert table cells to input fields
            const rows = gradeTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                // Midterm is now 7th column, Finals is 8th
                const midtermCell = row.querySelector('td:nth-child(7)');
                const finalsCell = row.querySelector('td:nth-child(8)');

                // Store original values as data attributes (optional, but good practice)
                midtermCell.setAttribute('data-original-value', midtermCell.textContent);
                finalsCell.setAttribute('data-original-value', finalsCell.textContent);

                // Populate input fields, handling 'N/A' to an empty string for number input
                midtermCell.innerHTML = `<input type="number" name="midterm_grade" value="${midtermCell.textContent.trim() === 'N/A' ? '' : midtermCell.textContent.trim()}" min="0" max="100" step="0.01" required>`;
                finalsCell.innerHTML = `<input type="number" name="finals_grade" value="${finalsCell.textContent.trim() === 'N/A' ? '' : finalsCell.textContent.trim()}" min="0" max="100" step="0.01" required>`;
            });
        } else {
            // Revert to original table display
            editGradesBtn.textContent = 'Edit Grades';
            gradeTable.classList.remove('editing');
            saveChangesBtn.style.display = 'none'; // Hide Save Changes button

            // Reload the page to revert to the original table structure (simplest approach for "Cancel")
            window.location.reload();
        }
    });

    saveChangesBtn.addEventListener('click', function () {
        const rows = gradeTable.querySelectorAll('tbody tr');
        const gradesData = [];
        let hasError = false;

        rows.forEach(row => {
            const grade_id = row.dataset.gradeId;
            const midterm_input = row.querySelector('input[name="midterm_grade"]');
            const finals_input = row.querySelector('input[name="finals_grade"]');

            const midterm_grade = midterm_input.value;
            const finals_grade = finals_input.value;

            // Basic client-side validation
            if (midterm_grade === '' || finals_grade === '') {
                alert('All grade fields (Midterm, Final) must be filled for Grade ID: ' + grade_id);
                hasError = true;
                return; // Skip this row, prevent form submission
            }
            if (isNaN(parseFloat(midterm_grade)) || parseFloat(midterm_grade) < 0 || parseFloat(midterm_grade) > 100 ||
                isNaN(parseFloat(finals_grade)) || parseFloat(finals_grade) < 0 || parseFloat(finals_grade) > 100) {
                alert('Midterm and Final grades must be numbers between 0 and 100 for Grade ID: ' + grade_id);
                hasError = true;
                return; // Skip this row, prevent form submission
            }

            gradesData.push({
                grade_id: grade_id,
                midterm_grade: midterm_grade,
                finals_grade: finals_grade
                // Status is no longer included
            });
        });

        if (hasError) {
            return; // Stop if any validation error occurred
        }

        // Send the data to the server using AJAX or by creating a hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_dashboard.php';

        const gradesInput = document.createElement('input');
        gradesInput.type = 'hidden';
        gradesInput.name = 'grades';
        gradesInput.value = JSON.stringify(gradesData); // Convert data to JSON

        form.appendChild(gradesInput);
        document.body.appendChild(form);
        form.submit();
    });
});