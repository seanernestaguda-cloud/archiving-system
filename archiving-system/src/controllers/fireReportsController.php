<?php

class FireReportsController {
    private $fireReportModel;

    public function __construct() {
        $this->fireReportModel = new FireReport();
    }

    public function createReport($data) {
        // Validate and sanitize input data
        // Call the model method to save the report
        return $this->fireReportModel->create($data);
    }

    public function updateReport($id, $data) {
        // Validate and sanitize input data
        // Call the model method to update the report
        return $this->fireReportModel->update($id, $data);
    }

    public function deleteReport($id) {
        // Call the model method to delete the report
        return $this->fireReportModel->delete($id);
    }

    public function getReports() {
        // Call the model method to retrieve all reports
        return $this->fireReportModel->getAll();
    }
}