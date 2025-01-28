<?php
namespace App\Helpers;

use App\Models\Cpd\CpdAttendanceModel;


class CpdUtils extends Utils
{
    public static function getCpdScore($licenseNumber)
    {
        $attendanceModel = new CpdAttendanceModel();
        return $attendanceModel->where('license_number', $licenseNumber)->countAllResults();
    }




    /**
     * Get the total CPD score for a given category for a given year
     * @param string $licenseNumber
     * @param string $year
     * @param string $category
     * @return int
     */
    public function getDpdCategoryScoreByYear($licenseNumber, $year, $category)
    {
        $res = $this->getCPDAttendanceAndScores($licenseNumber, $year);
        $sum = 0;
        foreach ($res['attendance'] as $value) {
            if ((int) $value['category'] === (int) $category) {
                $sum += $value['credits'];
            }
        }
        return $sum;
    }
}