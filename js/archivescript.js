// Sidebar toggle logic
document.getElementById("toggleSidebar").addEventListener("click", function () {
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  sidebar.classList.toggle("collapsed"); // Toggle the 'collapsed' class
  mainContent.classList.toggle("collapsed"); // Adjust main content margin
  mainContent.classList.toggle("expanded"); // Adjust expanded class
});

// Function to toggle the Profile Dropdown
function toggleProfileDropdown(event) {
  event.stopPropagation(); // Prevent click event from propagating
  const profileDropdown = document.getElementById("profileDropdown");

  // Close other dropdowns if open
  closeAllDropdownsExcept(profileDropdown);

  // Toggle the profile dropdown visibility
  profileDropdown.style.display =
    profileDropdown.style.display === "block" ? "none" : "block";
}

// Function to toggle the Actions Dropdown
function toggleActionDropdown(event) {
  event.stopPropagation(); // Prevent click event from propagating
  const actionDropdown = event.target
    .closest(".action-dropdown")
    .querySelector(".dropdown-content");

  // Close other dropdowns if open
  closeAllDropdownsExcept(actionDropdown);

  // Toggle the action dropdown visibility
  actionDropdown.style.display =
    actionDropdown.style.display === "block" ? "none" : "block";
}

// Close all dropdowns except the one passed as argument
function closeAllDropdownsExcept(exceptDropdown) {
  const allDropdowns = document.querySelectorAll(".dropdown-content");
  allDropdowns.forEach(function (dropdown) {
    if (dropdown !== exceptDropdown) {
      dropdown.style.display = "none";
    }
  });
}

// Close dropdowns when clicking outside
window.onclick = function (event) {
  if (
    !event.target.matches(".user-icon") &&
    !event.target.matches(".action-btn")
  ) {
    const dropdowns = document.querySelectorAll(".dropdown-content");
    dropdowns.forEach(function (dropdown) {
      if (dropdown.style.display === "block") {
        dropdown.style.display = "none";
      }
    });
  }

  // Close modal if clicked outside
  const uploadModal = document.getElementById("uploadModal");
  if (uploadModal && event.target === uploadModal) {
    closeUploadForm();
  }

  const createArchiveModal = document.getElementById("createArchiveModal");
  if (createArchiveModal && event.target === createArchiveModal) {
    closeCreateArchiveModal();
  }
};

// Function to close the upload modal
function closeUploadForm() {
  document.getElementById("uploadModal").style.display = "none";
}

// Show modal for document upload
function showModal() {
  document.getElementById("uploadModal").style.display = "flex";
}

// Show "Create Archive" modal
function showCreateArchiveModal() {
  document.getElementById("createArchiveModal").style.display = "flex";
}

// Close the "Create Archive" modal
function closeCreateArchiveModal() {
  document.getElementById("createArchiveModal").style.display = "none";
}

// Set the file name label
function setFileName() {
  const fileInput = document.getElementById("file");
  const label = document.querySelector(".custom-file-label");
  const fileName = fileInput.files[0]?.name || "Choose File";
  label.textContent = fileName;
}

// Filter archives table
function filterArchives() {
  const input = document.querySelector(".search-input"); // Get the input field
  const filter = input.value.toLowerCase(); // Get the search query and convert to lowercase
  const table = document.querySelector(".archive-table"); // Get the table
  const rows = table.getElementsByTagName("tr"); // Get all the table rows

  // Loop through all table rows (except the first one, which is the header)
  for (let i = 1; i < rows.length; i++) {
    const cells = rows[i].getElementsByTagName("td");
    let match = false; // Flag to check if the row matches the search query

    // Loop through each cell in the row
    for (let j = 0; j < cells.length; j++) {
      const cell = cells[j];
      if (cell) {
        // If the cell text matches the search query, set the match flag to true
        if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
          match = true;
          break;
        }
      }
    }

    // If there's a match, show the row, otherwise hide it
    rows[i].style.display = match ? "" : "none";
  }
}

// Function to handle form submission and redirect to the report form
function redirectToReportForm() {
  var reportType = document.getElementById("reportType").value;

  // Check if a report type is selected
  if (reportType) {
    // Redirect to specific report form based on selected report type
    switch (reportType) {
      case "Fire Incident Report":
        window.location.href = "fire_incident_report.php";
        break;
      case "Permit Report":
        window.location.href = "permit_report.php";
        break;
      case "Inspection Report":
        window.location.href = "inspection_report.php";
        break;
      case "Investigation Report":
        window.location.href = "investigation_report.php";
        break;
      default:
        alert("Please select a valid report type.");
        break;
    }
  } else {
    alert("Please select a report type before submitting.");
  }
}
