<script>
        let lineCount = 1;  // Keeps track of the current line number

        function addFirstNumber() {
            var textarea = document.getElementById('victims');
            if (textarea.value.trim() === '') {
                textarea.value = '1. ';
            }
            lineCount = 1; // Reset line count when focus is gained
        }

        function autoNumber() {
            var textarea = document.getElementById('victims');
            var lines = textarea.value.split('\n');
            
            // Iterate through each line to add numbers
            for (let i = 0; i < lines.length; i++) {
                // If a line is empty, skip adding a number
                if (lines[i].trim() !== '') {
                    lines[i] = (i + 1) + '. ' + lines[i].replace(/^\d+\.\s*/, ''); // Add number and clean existing numbers
                }
            }

            // Join the lines back together and update the textarea
            textarea.value = lines.join('\n');
        }

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

// Function to show the modal
// Function to show the modal
function showSuccessModal(message) {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModal').style.display = "block";

    // Redirect after 2 seconds
    setTimeout(() => {
        window.location.href = "fire_incident_report.php"; // Change URL as needed
    }, 2000);
}

// Function to close the modal
function closeModal() {
    document.getElementById('successModal').style.display = "none";
}

// Trigger the modal if a success message is set
<?php if (isset($_SESSION['success_message'])): ?>
    showSuccessModal("<?php echo $_SESSION['success_message']; ?>");
    <?php unset($_SESSION['success_message']); // Clear the session message ?>
<?php endif; ?>

// Check for error message
<?php if (isset($_SESSION['error_message'])): ?>
    alert("<?php echo $_SESSION['error_message']; ?>");
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

function toggleReportInputs() {
    // Get the selected report type
    var reportType = document.getElementById('report_type').value;
    
    // Hide all report input fields
    document.getElementById('spot_report_input').style.display = 'none';
    document.getElementById('progress_report_input').style.display = 'none';
    document.getElementById('final_report_input').style.display = 'none';

    // Show the corresponding input field based on the selected report type
    if (reportType === 'spot') {
        document.getElementById('spot_report_input').style.display = 'block';
    } else if (reportType === 'progress') {
        document.getElementById('progress_report_input').style.display = 'block';
    } else if (reportType === 'final') {
        document.getElementById('final_report_input').style.display = 'block';
    }
}


    </script>