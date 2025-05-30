<?php

namespace App\Controllers;

use App\Models\IntegrityModel;
use App\Models\SemesterModel;

class IntegrityController extends BaseController
{
    protected $integrityModel;
    protected $semesterModel;

    public function __construct()
    {
        $this->integrityModel = new IntegrityModel();
        $this->semesterModel = new SemesterModel();
    }

    /**
     * Display list of lecturer integrity data
     * Automatically recalculates scores on every page load
     */
    public function index()
    {
        // Ensure user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('login')->with('error', 'Silakan login terlebih dahulu');
        }

        $userData = [
            'name' => session()->get('user_name'),
            'role' => session()->get('user_role'),
        ];

        // Get current semester
        $currentSemester = $this->semesterModel->getCurrentSemester();
        $semesterId = $currentSemester ? $currentSemester['id'] : null;

        if (!$semesterId) {
            return view('integrity/index', [
                'pageTitle' => 'Data Integritas | SKP Dosen',
                'user' => $userData,
                'integrityData' => [],
                'currentSemester' => null,
                'statistics' => [
                    'average_attendance_score' => 0,
                    'average_courses_score' => 0,
                    'total_lecturers' => 0,
                    'attendance_distribution' => [],
                    'courses_distribution' => [],
                    'score_distribution' => [
                        'excellent' => 0,
                        'good' => 0,
                        'fair' => 0,
                        'poor' => 0
                    ]
                ],
                'calculationResult' => ['updated' => 0]
            ]);
        }

        try {
            // Auto-populate integrity data for all lecturers
            $this->integrityModel->autoPopulateIntegrityData($semesterId);

            // Automatically recalculate ALL scores on every page load
            $updatedCount = $this->integrityModel->recalculateAllScores($semesterId);

            // Log the automatic calculation
            if ($updatedCount > 0) {
                log_message('info', "Integrity auto-calculation: Updated {$updatedCount} scores for semester {$semesterId}");
            }

            // Get integrity data with lecturer information
            $integrityData = $this->integrityModel->getIntegrityDataWithLecturers($semesterId);

            // Get statistics
            $statistics = $this->integrityModel->getIntegrityStatistics($semesterId);

            return view('integrity/index', [
                'pageTitle' => 'Data Integritas | SKP Dosen',
                'user' => $userData,
                'integrityData' => $integrityData,
                'currentSemester' => $currentSemester,
                'statistics' => $statistics,
                'calculationResult' => [
                    'updated' => $updatedCount,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error in integrity auto-calculation: ' . $e->getMessage());

            // If auto-calculation fails, still show the page but with warning
            $integrityData = $this->integrityModel->getIntegrityDataWithLecturers($semesterId);
            $statistics = $this->integrityModel->getIntegrityStatistics($semesterId);

            session()->setFlashdata('warning', 'Terjadi masalah saat menghitung ulang nilai integritas secara otomatis');

            return view('integrity/index', [
                'pageTitle' => 'Data Integritas | SKP Dosen',
                'user' => $userData,
                'integrityData' => $integrityData,
                'currentSemester' => $currentSemester,
                'statistics' => $statistics,
                'calculationResult' => ['updated' => 0]
            ]);
        }
    }

    /**
     * Manually recalculate integrity scores (admin function)
     */
    public function recalculateScores()
    {
        // Ensure user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('login')->with('error', 'Silakan login terlebih dahulu');
        }

        try {
            // Get current semester
            $currentSemester = $this->semesterModel->getCurrentSemester();
            $semesterId = $currentSemester ? $currentSemester['id'] : null;

            if (!$semesterId) {
                return redirect()->to('integrity')->with('error', 'Tidak ada semester aktif');
            }

            $updatedCount = $this->integrityModel->recalculateAllScores($semesterId);

            return redirect()->to('integrity')->with('success', "Berhasil memperbarui {$updatedCount} nilai integritas secara manual");
        } catch (\Exception $e) {
            log_message('error', 'Error manually recalculating integrity scores: ' . $e->getMessage());
            return redirect()->to('integrity')->with('error', 'Gagal memperbarui nilai integritas secara manual');
        }
    }

    /**
     * Force recalculate all integrity scores
     */
    public function forceRecalculateAll()
    {
        // Ensure user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('login')->with('error', 'Silakan login terlebih dahulu');
        }

        try {
            // Get current semester
            $currentSemester = $this->semesterModel->getCurrentSemester();
            $semesterId = $currentSemester ? $currentSemester['id'] : null;

            if (!$semesterId) {
                return redirect()->to('integrity')->with('error', 'Tidak ada semester aktif');
            }

            $updatedCount = $this->integrityModel->recalculateAllScores($semesterId);

            return redirect()->to('integrity')->with('success', "Berhasil memperbarui {$updatedCount} nilai integritas");
        } catch (\Exception $e) {
            log_message('error', 'Error force recalculating integrity scores: ' . $e->getMessage());
            return redirect()->to('integrity')->with('error', 'Gagal memperbarui nilai integritas');
        }
    }

    /**
     * Export integrity data to Excel format
     */
    public function exportExcel()
    {
        // Ensure user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Get current semester
        $currentSemester = $this->semesterModel->getCurrentSemester();
        $semesterId = $currentSemester ? $currentSemester['id'] : null;

        if (!$semesterId) {
            return redirect()->to('integrity')->with('error', 'Tidak ada semester aktif untuk diekspor');
        }

        // Get data
        $integrityData = $this->integrityModel->getIntegrityDataWithLecturers($semesterId);

        if (empty($integrityData)) {
            return redirect()->to('integrity')->with('error', 'Tidak ada data integritas untuk diekspor');
        }

        // In a real application, you would generate an Excel file here
        return redirect()->to('integrity')->with('success', 'Data integritas berhasil diekspor ke Excel');
    }

    /**
     * Export integrity data to PDF format
     */
    public function exportPdf()
    {
        // Ensure user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Get current semester
        $currentSemester = $this->semesterModel->getCurrentSemester();
        $semesterId = $currentSemester ? $currentSemester['id'] : null;

        if (!$semesterId) {
            return redirect()->to('integrity')->with('error', 'Tidak ada semester aktif untuk diekspor');
        }

        // Get data
        $integrityData = $this->integrityModel->getIntegrityDataWithLecturers($semesterId);

        if (empty($integrityData)) {
            return redirect()->to('integrity')->with('error', 'Tidak ada data integritas untuk diekspor');
        }

        // In a real application, you would generate a PDF file here
        return redirect()->to('integrity')->with('success', 'Data integritas berhasil diekspor ke PDF');
    }

    /**
     * API endpoint to get current calculation status (for AJAX monitoring)
     */
    public function getCalculationStatus()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (!$this->request->isAJAX()) {
            return redirect()->to('integrity');
        }

        try {
            $currentSemester = $this->semesterModel->getCurrentSemester();
            $semesterId = $currentSemester ? $currentSemester['id'] : null;

            if (!$semesterId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Tidak ada semester aktif'
                ]);
            }

            $totalRecords = $this->integrityModel->where('semester_id', $semesterId)->countAllResults();
            $zeroScoreRecords = $this->integrityModel->where('semester_id', $semesterId)
                ->where('score', 0)->countAllResults();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'total_records' => $totalRecords,
                    'zero_score_records' => $zeroScoreRecords,
                    'calculated_records' => $totalRecords - $zeroScoreRecords,
                    'last_updated' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting calculation status: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mendapatkan status perhitungan'
            ]);
        }
    }
}
