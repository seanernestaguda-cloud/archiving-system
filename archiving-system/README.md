# Archiving System for Fire-Related Reports

## Overview
This project is an archiving system designed for the Bureau of Fire Protection (BFP) personnel to efficiently store, index, and retrieve fire-related reports. The system provides a user-friendly interface and robust functionality to manage reports and user access.

## Features
1. **Fire Report Management**: 
   - Create, update, delete, and retrieve fire-related reports.
   - Comprehensive data model for fire reports.

2. **Search Functionality**: 
   - Allows BFP personnel to search for reports based on various criteria.
   - Intuitive search interface for ease of use.

3. **User Authentication and Access Control**: 
   - Secure user login and registration process.
   - Role-based access control to ensure only authorized personnel can modify archived data.

4. **User Experience**: 
   - Well-designed interface with a consistent layout.
   - Responsive design for accessibility on various devices.

## Project Structure
```
src/
├── controllers/
│   ├── fireReportsController.php
│   ├── searchController.php
│   └── userController.php
├── models/
│   ├── FireReport.php
│   ├── User.php
│   └── Auth.php
├── views/
│   ├── fireReports/
│   │   ├── index.php
│   │   └── view.php
│   ├── search/
│   │   └── index.php
│   ├── user/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── profile.php
│   └── layout.php
├── public/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── uploads/
├── config/
│   └── database.php
└── index.php
```

## Installation
1. Clone the repository to your local machine.
2. Navigate to the project directory.
3. Set up the database using the configuration in `src/config/database.php`.
4. Run the application using a local server (e.g., XAMPP, WAMP).

## Usage
- Access the application through your web browser.
- Use the login page to authenticate as a user.
- Navigate through the interface to manage fire reports, search for specific reports, and manage user profiles.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.