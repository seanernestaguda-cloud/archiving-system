document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    const reportList = document.getElementById('reportList');

    // Function to handle search
    searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(searchForm);
        fetch('src/controllers/searchController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            displayReports(data);
        })
        .catch(error => console.error('Error:', error));
    });

    // Function to display reports
    function displayReports(reports) {
        reportList.innerHTML = '';
        if (reports.length === 0) {
            reportList.innerHTML = '<p>No reports found.</p>';
            return;
        }
        reports.forEach(report => {
            const reportItem = document.createElement('div');
            reportItem.classList.add('report-item');
            reportItem.innerHTML = `
                <h3>${report.title}</h3>
                <p>${report.description}</p>
                <p>Date: ${report.date}</p>
                <a href="src/views/fireReports/view.php?id=${report.id}">View Details</a>
            `;
            reportList.appendChild(reportItem);
        });
    }
});