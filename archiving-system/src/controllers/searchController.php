<?php
class SearchController {
    private $fireReportsModel;

    public function __construct() {
        $this->fireReportsModel = new FireReport();
    }

    public function searchReports($criteria) {
        // Validate criteria
        if (empty($criteria)) {
            return [];
        }

        // Perform search using the FireReport model
        return $this->fireReportsModel->search($criteria);
    }
}
?>