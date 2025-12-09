let uploadSuccessful = false; // Variable to track if upload was successful

function openReportDetails(report) {
    // Disable edit mode before opening the modal
    disableEditMode();

    // Populate modal fields
    document.getElementById('reportid').innerText = `Report ID: ${report.report_id}`;
    document.getElementById('reportTitle').value = report.report_title;
    document.getElementById('fireLocation').value = report.fire_location;
    document.getElementById('incidentDate').value = report.incident_date;
    document.getElementById('establishment').value = report.establishment;
    document.getElementById('victims').value = report.victims;
    document.getElementById('propertyDamage').value = report.property_damage;
    document.getElementById('fireTypes').value = report.fire_types;
    document.getElementById('fireCause').value = report.fire_cause;

    // Show the modal
    document.getElementById('reportDetails').style.display = 'flex';
}

function closeReportDetails() {
    // Hide the modal
    document.getElementById('reportDetails').style.display = 'none';
}

function disableEditMode() {
    // Disable fields for editing
    document.getElementById('reportTitle').readOnly = true;
    document.getElementById('fireLocation').readOnly = true;
    document.getElementById('incidentDate').readOnly = true;
    document.getElementById('establishment').readOnly = true;
    document.getElementById('victims').readOnly = true;
    document.getElementById('propertyDamage').readOnly = true;
    document.getElementById('fireTypes').readOnly = true;
    document.getElementById('fireCause').readOnly = true;

    // Show the edit button and hide the save button
    document.getElementById('editBtn').style.display = 'inline';
    document.getElementById('saveBtn').style.display = 'none';
}

function enableEditMode() {
    // Enable fields for editing
    document.getElementById('reportTitle').readOnly = false;
    document.getElementById('fireLocation').readOnly = false;
    document.getElementById('incidentDate').readOnly = false;
    document.getElementById('establishment').readOnly = false;
    document.getElementById('victims').readOnly = false;
    document.getElementById('propertyDamage').readOnly = false;
    document.getElementById('fireTypes').readOnly = false;
    document.getElementById('fireCause').readOnly = false;

    // Hide the edit button and show the save button
    document.getElementById('editBtn').style.display = 'none'; 
    document.getElementById('saveBtn').style.display = 'inline'; 
}
function saveReportChanges() {
    const reportId = document.getElementById('reportid').textContent.replace('Report ID: ', ''); // Get numeric ID
    const reportTitle = document.getElementById('reportTitle').value;
    const fireLocation = document.getElementById('fireLocation').value;
    const incidentDate = document.getElementById('incidentDate').value;
    const establishment = document.getElementById('establishment').value;
    const victims = document.getElementById('victims').value; // Victim names (comma-separated)
    const propertyDamage = document.getElementById('propertyDamage').value;
    const fireTypes = document.getElementById('fireTypes').value;
    const fireCause = document.getElementById('fireCause').value;

    fetch('update_fire_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            report_id: reportId,
            report_title: reportTitle,
            fire_location: fireLocation,
            incident_date: incidentDate,
            establishment: establishment,
            victims: victims,
            property_damage: propertyDamage,
            fire_types: fireTypes,
            fire_cause: fireCause
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Convert victims to count before updating the table
            const victimCount = victims ? victims.split(',').length : 0;

            // Update the table row with new data
            updateTableRow(reportId, {
                report_title: reportTitle,
                fire_location: fireLocation,
                incident_date: incidentDate,
                establishment: establishment,
                victims: victimCount, // Pass victim count to the table
                property_damage: propertyDamage,
                fire_types: fireTypes,
                fire_cause: fireCause
            });

            closeReportDetails(); // Close the modal
            showSuccessMessage('Changes saved successfully!'); // Show success message
        } else {
            closeReportDetails();
            showSuccessMessage('Failed to save changes.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        closeReportDetails();
        showSuccessMessage('An error occurred while saving the changes.');
    });
}

function updateTableRow(reportId, updatedData) {
    // Find the row in the table corresponding to the reportId
    const row = document.querySelector(`#report-row${reportId}`);

    if (row) {
        // Update the table cells with new data
        row.cells[1].innerText = updatedData.report_title; // Report Title
        row.cells[2].innerText = updatedData.fire_location; // Fire Location
        row.cells[3].innerText = updatedData.incident_date; // Incident Date
        row.cells[4].innerText = updatedData.establishment; // Establishment
        row.cells[5].innerText = updatedData.victims;
        row.cells[6].innerText = `₱${updatedData.property_damage}`; // Property Damage
        row.cells[7].innerText = updatedData.fire_types; // Fire Types
        row.cells[8].innerText = updatedData.fire_cause; // Fire Cause
    }
}


function showSuccessMessage(message) {
    // Create the success message element
    const successMessage = document.createElement('div');
    
    successMessage.classList.add('success-message'); // Add a class for styling
    successMessage.innerText = message;

    // Append the success message to the body (outside the modal)
    document.body.appendChild(successMessage);

    // Set a timeout to remove the message after 3 seconds
    setTimeout(() => {
        successMessage.remove();
    }, 3000);
}

setTimeout(function() {
    const message = document.querySelector('.message');
    if (message) {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 500); // Remove the message after fade
    }
}, 5000);

function showUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.style.display = 'flex'; // Show modal as flex for proper centering
    uploadSuccessful = false; // Reset upload success flag when the modal opens
}

// Function to close the upload modal
function closeUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.style.display = 'none'; // Just close the modal here

    // Show the success message only if the upload was successful
    if (uploadSuccessful) {
        setTimeout(() => {
            showSuccessMessage('Upload completed successfully!');
        }, 500); // Delay to ensure modal is closed before showing message
    }
}

// Update this function to handle the actual upload process and set the success flag
function handleUploadSuccess() {
    uploadSuccessful = true; // Set the flag to true when upload is completed
    closeUploadModal(); // Close the modal after upload
}

// Close modal when clicking outside the content
window.onclick = function(event) {
    const modal = document.getElementById('uploadModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

let deleteReportId = null;

function deleteReport(reportId) {
    // Store the reportId for later use
    deleteReportId = reportId;
    
    // Show the confirmation modal
    document.getElementById("confirmDeleteModal").style.display = "flex";
}

function closeDeleteModal() {
    // Hide the confirmation modal
    document.getElementById("confirmDeleteModal").style.display = "none";
}

document.getElementById("confirmDeleteBtn").addEventListener("click", function() {
    if (deleteReportId) {
        // Perform the deletion via AJAX or a form submission
        window.location.href = `delete_report.php?report_id=${deleteReportId}`;
    }
    closeDeleteModal();
});

document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('.report-dropdown-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            const dropdown = this.closest('.report-dropdown');
            dropdown.classList.toggle('show');

            // Close other dropdowns
            document.querySelectorAll('.report-dropdown').forEach(item => {
                if (item !== dropdown) {
                    item.classList.remove('show');
                }
            });
        });
    });

    // Close dropdown when clicking outside
    window.addEventListener('click', event => {
        if (!event.target.closest('.report-dropdown')) {
            document.querySelectorAll('.report-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
});

