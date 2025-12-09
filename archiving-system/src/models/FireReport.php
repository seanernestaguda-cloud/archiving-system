<?php

class FireReport {
    private $id;
    private $title;
    private $description;
    private $date;

    public function __construct($id, $title, $description, $date) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->date = $date;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDate() {
        return $this->date;
    }

    public function save() {
        // Code to save the report to the database
    }

    public function update() {
        // Code to update the report in the database
    }

    public function delete() {
        // Code to delete the report from the database
    }

    public static function getAllReports() {
        // Code to retrieve all reports from the database
    }

    public static function findReportById($id) {
        // Code to find a report by its ID
    }
}