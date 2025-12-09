let currentPermitId = null;

function viewPermitDetails(permit) {
    currentPermitId = permit.id;
    // Populate the fields with the permit details
    document.getElementById('permit_name').value = permit.permit_name;
    document.getElementById('inspection_establishment').value = permit.inspection_establishment;
    document.getElementById('owner').value = permit.owner;
    document.getElementById('establishment_type').value = permit.establishment_type;
    document.getElementById('inspection_address').value = permit.inspection_address;
    document.getElementById('inspection_date').value = permit.inspection_date;
    document.getElementById('inspection_purpose').value = permit.inspection_purpose;
    document.getElementById("inspected_by").value = permit.inspected_by; // Set "Inspected By" field

    // Set radio buttons
    setRadioButton('fire_alarms', permit.fire_alarms);
    setRadioButton('fire_extinguishers', permit.fire_extinguishers);
    setRadioButton('emergency_exits', permit.emergency_exits);
    setRadioButton('sprinkler_systems', permit.sprinkler_systems);
    setRadioButton('fire_drills', permit.fire_drills);
    setRadioButton('exit_signs', permit.exit_signs);
    setRadioButton('electrical_wiring', permit.electrical_wiring);
    setRadioButton('emergency_evacuations', permit.emergency_evacuations);

    // Show the modal
    document.getElementById('PermitDetails').style.display = 'flex';

    // Disable all fields initially
    disableFields();
}

function setRadioButton(name, value) {
    // Enable the radio buttons
    const radios = document.querySelectorAll(`input[name="${name}"]`);
    radios.forEach(radio => {
        radio.removeAttribute('disabled');  // Ensure the radio button is enabled
    });

    // Select the correct radio button based on the value
    const selectedRadio = document.querySelector(`input[name="${name}"][value="${value}"]`);
    if (selectedRadio) {
        selectedRadio.checked = true;  // Check the radio button
    }
}

function closePermitDetails() {
    document.getElementById('PermitDetails').style.display = 'none';
}

function enableEditMode() {
    // Enable all fields for editing
    document.getElementById('permit_name').removeAttribute('readonly');
    document.getElementById('inspection_establishment').removeAttribute('readonly');
    document.getElementById('owner').removeAttribute('readonly');
    document.getElementById('inspection_address').removeAttribute('readonly');
    document.getElementById('inspection_date').removeAttribute('readonly');
    document.getElementById('inspection_purpose').removeAttribute('readonly');

    // Enable radio buttons
    enableRadioButtons();

    // Toggle buttons
    document.getElementById('editBtn').style.display = 'none';
    document.getElementById('saveBtn').style.display = 'inline-block';
}

function disableFields() {
    // Make all fields read-only
    document.getElementById('permit_name').setAttribute('readonly', true);
    document.getElementById('inspection_establishment').setAttribute('readonly', true);
    document.getElementById('owner').setAttribute('readonly', true);
    document.getElementById('inspection_address').setAttribute('readonly', true);
    document.getElementById('inspection_date').setAttribute('readonly', true);
    document.getElementById('inspection_purpose').setAttribute('readonly', true);

    // Disable radio buttons
    disableRadioButtons();

    // Toggle buttons
    document.getElementById('editBtn').style.display = 'inline-block';
    document.getElementById('saveBtn').style.display = 'none';
}

function enableRadioButtons() {
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.removeAttribute('disabled');
    });
}

function disableRadioButtons() {
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.setAttribute('disabled', true);
    });
}

function showSuccessMessage(message) {
    const successMessage = document.createElement('div');
    successMessage.classList.add('message', 'success');
    successMessage.innerText = message;
    document.body.appendChild(successMessage);

    // Hide the success message after a short delay
    setTimeout(() => {
        successMessage.style.opacity = 0;
        setTimeout(() => {
            successMessage.remove();
        }, 1000);  // Wait for fade-out effect
    }, 3000);  // Show message for 3 seconds
}
function savePermitChanges() {
    const updatedPermit = {
        id: currentPermitId,
        permit_name: document.getElementById('permit_name').value,
        inspection_establishment: document.getElementById('inspection_establishment').value,
        owner: document.getElementById('owner').value,
        inspection_address: document.getElementById('inspection_address').value,
        fire_alarms: getRadioValue('fire_alarms'),
        inspection_date: document.getElementById('inspection_date').value,
        inspection_purpose: document.getElementById('inspection_purpose').value,
        fire_extinguishers: getRadioValue('fire_extinguishers'),
        emergency_exits: getRadioValue('emergency_exits'),
        sprinkler_systems: getRadioValue('sprinkler_systems'),
        fire_drills: getRadioValue('fire_drills'),
        exit_signs: getRadioValue('exit_signs'),
        electrical_wiring: getRadioValue('electrical_wiring'),
        emergency_evacuations: getRadioValue('emergency_evacuations'),
        inspected_by: document.getElementById('inspected_by').value,
    };

    // Send the updated permit data via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_permit.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // Save success message to sessionStorage
                sessionStorage.setItem('successMessage', response.message);

                // Update the table row without refreshing the page
                updateTableRow(updatedPermit);

                // Disable editing mode
                disableFields();

                // Close the modal after saving
                closePermitDetails();

                // Reload the page
                location.reload();
            } else {
                alert(response.message);  // If there was an error
            }
        } else {
            alert('Error updating permit details.');
        }
    };

    // Send the updated permit data as JSON
    xhr.send(JSON.stringify(updatedPermit));
}

// On the page load, display the success message from sessionStorage
window.onload = function() {
    const successMessage = sessionStorage.getItem('successMessage');
    if (successMessage) {
        showSuccessMessage(successMessage);
        sessionStorage.removeItem('successMessage');  // Clear after displaying
    }
};




function updateTableRow(updatedPermit) {
    const tableRow = document.querySelector(`#permitRow-${updatedPermit.id}`);
    if (tableRow) {
        // Update table cells with new permit data
        tableRow.querySelector('.permit_name').innerText = updatedPermit.permit_name;
        tableRow.querySelector('.inspection_establishment').innerText = updatedPermit.inspection_establishment;
        tableRow.querySelector('.owner').innerText = updatedPermit.owner;
        tableRow.querySelector('.inspection_address').innerText = updatedPermit.inspection_address;
        tableRow.querySelector('.inspection_date').innerText = updatedPermit.inspection_date;
        tableRow.querySelector('.inspection_purpose').innerText = updatedPermit.inspection_purpose;

        // Update the radio buttons in the table row
        setRadioButtonInTableRow(tableRow, 'fire_alarms', updatedPermit.fire_alarms);
        setRadioButtonInTableRow(tableRow, 'fire_extinguishers', updatedPermit.fire_extinguishers);
        setRadioButtonInTableRow(tableRow, 'emergency_exits', updatedPermit.emergency_exits);
        setRadioButtonInTableRow(tableRow, 'sprinkler_systems', updatedPermit.sprinkler_systems);
        setRadioButtonInTableRow(tableRow, 'fire_drills', updatedPermit.fire_drills);
        setRadioButtonInTableRow(tableRow, 'exit_signs', updatedPermit.exit_signs);
        setRadioButtonInTableRow(tableRow, 'electrical_wiring', updatedPermit.electrical_wiring);
        setRadioButtonInTableRow(tableRow, 'emergency_evacuations', updatedPermit.emergency_evacuations);
    } else {
        console.error(`Row for permit ID ${updatedPermit.id} not found.`);
    }
}

function setRadioButtonInTableRow(row, name, value) {
    const radio = row.querySelector(`input[name="${name}"][value="${value}"]`);
    if (radio) {
        radio.checked = true;
    }
}

function getRadioValue(name) {
    const selectedOption = document.querySelector(`input[name="${name}"]:checked`);
    return selectedOption ? selectedOption.value : null;
}

function deletePermit(id) {
    // Show the custom confirm delete modal for single delete
    selectedToDelete = [id];
    document.getElementById('ConfirmDeleteModal').style.display = 'flex';

    // Change confirm button behavior for single delete
    document.getElementById('confirmDeleteBtn').onclick = function() {
        fetch('delete_selected_permits.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({permit_ids: selectedToDelete})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove deleted row from the table
                const row = document.getElementById('report-row' + id);
                if (row) row.remove();
                openSuccessModal(); // <-- Show success modal
            } else {
                alert('Error deleting permit.');
            }
            closeDeleteConfirmation();
        });
    };
}