<?php

class DayOfWeek {
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;
    const SUNDAY = 7;

    protected function __construct() {}
}

class DateHelper {
    const INTERNAL_FORMAT = 'Y-m-d';
    const DISPLAY_MONTH_FORMAT = 'M Y';
    const DISPLAY_DATE_FORMAT = 'D d M Y';

    /**
     * Checks if a given date is on the given DayOfWeek
     * @param int $dow  The DayOfWeek, as represented by the DayOfWeek class.
     * @param DateTime $date The date, as a DateTime
     */
    public static function IsDayOfWeek($dow, $date) {
        return DateHelper::GetDayOfWeek($date) == $dow;
    }

    /**
     * Gets the DayOfWeek from the given Date
     * @param DateTime $date The DateTime to check
     */
    public static function GetDayOfWeek($date) {
        $dow = $date->format("w");

        if ($dow === 0) {
            $dow = 7;
        }

        return $dow;
    }

    public static function GetDayString($dow) {
        switch($dow) {
            case DayOfWeek::MONDAY:
                return "Monday";
            case DayOfWeek::TUESDAY:
                return "Tuesday";
            case DayOfWeek::WEDNESDAY:
                return "Wednesday";
            case DayOfWeek::THURSDAY:
                return "Thursday";
            case DayOfWeek::FRIDAY:
                return "Friday";
            case DayOfWeek::SATURDAY:
                return "Saturday";
            case DayOfWeek::SUNDAY:
                return "Sunday";
            default:
                return "ERROR";
        }
    }

    /**
     * Gets all of the dates for the given DayOfWeek
     * @param DateTime $startDate The lower bounds
     * @param DateTime $endDate   The upper bounds
     */
    public static function GetDatesForDow($dow, $startDate, $endDate) {
        $dayDiff = $endDate->diff($startDate)->format("%a");
        $allDates = [];

        foreach (range(0,$dayDiff) as $day) {
            $date = new DateTime($startDate->format('Y-m-d')." +".$day." days");

            if (DateHelper::IsDayOfWeek($dow, $date)) {
                array_push($allDates, $date);
            }
        }

        return $allDates;
    }
}

?>
