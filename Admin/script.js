document.addEventListener("DOMContentLoaded", () => {
  const togglePassword = document.getElementById("togglePassword");
  const password = document.getElementById("password");
  const eyeIcon = togglePassword.querySelector("i");

  togglePassword.addEventListener("click", () => {
    // Toggle the type attribute using getAttribute and setAttribute methods
    const type =
      password.getAttribute("type") === "password" ? "text" : "password";
    password.setAttribute("type", type);

    // Toggle the eye icon
    eyeIcon.classList.toggle("fa-eye-slash");
    eyeIcon.classList.toggle("fa-eye");
  });
});

// Toggle visibility of Archive dropdown
const archiveToggle = document.getElementById("archiveDropdownToggle");
const archiveDropdown = document.getElementById("archiveDropdown");

archiveToggle.addEventListener("click", function (event) {
  event.preventDefault();
  // Toggle the "active" class
  archiveToggle.classList.toggle("active");
  archiveDropdown.style.display =
    archiveDropdown.style.display === "block" ? "none" : "block";
});

// Optional: Close dropdown when clicking outside
document.addEventListener("click", function (event) {
  if (
    !archiveToggle.contains(event.target) &&
    !archiveDropdown.contains(event.target)
  ) {
    archiveToggle.classList.remove("active");
    archiveDropdown.style.display = "none";
  }
});
