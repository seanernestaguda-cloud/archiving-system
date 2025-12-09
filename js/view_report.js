// JavaScript to show/hide report sections based on selected report type
function showReportFields() {
    var reportType = document.getElementById('report_type').value;
    
    // Hide all sections first
    document.getElementById('spot_report_section').style.display = 'none';
    document.getElementById('progress_report_section').style.display = 'none';
    document.getElementById('final_report_section').style.display = 'none';
    
    // Show selected section
    if (reportType === 'spot') {
        document.getElementById('spot_report_section').style.display = 'block';
    } else if (reportType === 'progress') {
        document.getElementById('progress_report_section').style.display = 'block';
    } else if (reportType === 'final') {
        document.getElementById('final_report_section').style.display = 'block';
    }
}
    function showModal() {
        const modal = document.getElementById('successModal');
        modal.style.display = 'block';

        // Redirect to the desired page after 3 seconds
        setTimeout(() => {
            window.location.href = 'fire_incident_report.php';
        }, 2000);
    }

    // Automatically show the modal if the success message is set
    <?php if (isset($success_message)) { ?>
        showModal();
    <?php } ?>

    function previewReport(event, previewContainerId) {
    const previewContainer = document.getElementById(previewContainerId);
    previewContainer.innerHTML = ''; // Clear previous preview

    const file = event.target.files[0];
    if (!file) return;

    const fileUrl = URL.createObjectURL(file);
    const fileExtension = file.name.split('.').pop().toLowerCase();

    if (fileExtension === 'pdf') {
        previewContainer.innerHTML = `
            <h4>Preview:</h4>
            <iframe src="${fileUrl}" width="100%" height="500px"></iframe>
        `;
    } else if (['doc', 'docx', 'txt', 'rtf'].includes(fileExtension)) {
        previewContainer.innerHTML = `
            <h4>Preview not available.</h4>
            <p><a href="${fileUrl}" target="_blank">Download to view the report.</a></p>
        `;
    } else {
        previewContainer.innerHTML = `<p>Invalid file format.</p>`;
    }
}


    
    function previewImages(event) {
    const previewDiv = document.getElementById('image-previews');
    previewDiv.innerHTML = ''; // Clear previous previews

    const files = event.target.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '200px';
                img.style.maxHeight = '200px';
                img.style.margin = '5px';
                previewDiv.appendChild(img);
            }
            
            reader.readAsDataURL(file); // Read the file as a data URL
        }
    }
}


let pendingDelete = null;

document.addEventListener('click', function (event) {
    if (event.target.classList.contains('delete-photo-btn')) {
        pendingDelete = event.target;
        document.getElementById('confirmDeleteModal').style.display = 'flex';
    }
});

document.getElementById('cancelDeleteBtn').onclick = function() {
    document.getElementById('confirmDeleteModal').style.display = 'none';
    pendingDelete = null;
};

document.getElementById('confirmDeleteBtn').onclick = function() {
    if (!pendingDelete) return;
    const photoPath = pendingDelete.getAttribute('data-path');
    const photoIndex = pendingDelete.getAttribute('data-index');
    fetch('delete_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ path: photoPath, index: photoIndex, report_id: <?php echo $report_id; ?> }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Photo deleted successfully.');
            pendingDelete.parentElement.remove();
        } else {
            alert('Failed to delete photo: ' + data.error);
        }
        document.getElementById('confirmDeleteModal').style.display = 'none';
        pendingDelete = null;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('confirmDeleteModal').style.display = 'none';
        pendingDelete = null;
    });
};

document.getElementById('confirmDeleteBtn').onclick = function() {
    if (!pendingDelete) return;
    const photoPath = pendingDelete.getAttribute('data-path');
    const photoIndex = pendingDelete.getAttribute('data-index');
    fetch('delete_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ path: photoPath, index: photoIndex, report_id: <?php echo $report_id; ?> }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            pendingDelete.parentElement.remove();
            document.getElementById('confirmDeleteModal').style.display = 'none';
            pendingDelete = null;
            showModal(); // Show success modal after deletion
        } else {
            alert('Failed to delete photo: ' + data.error);
            document.getElementById('confirmDeleteModal').style.display = 'none';
            pendingDelete = null;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('confirmDeleteModal').style.display = 'none';
        pendingDelete = null;
    });
};