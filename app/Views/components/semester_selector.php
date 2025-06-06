<?php

/**
 * Semester Selector Component
 * 
 * @var array $semesters List of available semesters
 * @var array $activeSemester Currently active semester
 */

$semesters = $semesters ?? [];
$semesterModel = $semesterModel ?? new \App\Models\SemesterModel();

// If no active semester is set or session is empty, use the dynamic logic
if (!$activeSemester || !session()->get('activeSemesterId')) {
    $activeSemester = $semesterModel->getCurrentSemester();

    // Debug current month logic
    if (ENVIRONMENT === 'development') {
        $currentMonth = (int)date('n');
        log_message('debug', 'Semester Selector - Current month: ' . $currentMonth);
        log_message('debug', 'Semester Selector - Auto-selected semester: ' . json_encode($activeSemester));
    }
}

// Get active semester ID from session
$activeSemesterId = session()->get('activeSemesterId');

// Get current URI for redirect after semester change
$currentUri = current_url(true)->getPath();

// Debug: Add some logging to check values
if (ENVIRONMENT === 'development') {
    log_message('debug', 'Semester Selector - Semesters count: ' . count($semesters));
    log_message('debug', 'Semester Selector - Active semester: ' . json_encode($activeSemester));
    // Debug: Log CSRF token information
    log_message('debug', 'CSRF Token: ' . csrf_hash());
    log_message('debug', 'CSRF Name: ' . csrf_token());
    log_message('debug', 'Base URL: ' . base_url());
}
?>

<div class="semester-selector" data-base-url="<?= base_url() ?>"
    data-active-semester-id="<?= $activeSemester ? $activeSemester['id'] : '' ?>" data-csrf-token="<?= csrf_hash() ?>"
    data-csrf-name="<?= csrf_token() ?>" data-current-month="<?= date('n') ?>" data-current-year="<?= date('Y') ?>">
    <div class="dropdown">
        <button class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center" type="button"
            id="semesterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-calendar-alt mr-2"></i>
            <span class="semester-text">
                <?php if ($activeSemester && isset($activeSemester['year'], $activeSemester['term'])): ?>
                    <?= $semesterModel->formatSemester($activeSemester) ?>
                <?php else: ?>
                    Pilih Semester
                <?php endif; ?>
            </span>
        </button>
        <div class="dropdown-menu dropdown-menu-right shadow-sm" aria-labelledby="semesterDropdown"
            style="min-width: 280px;">
            <h6 class="dropdown-header bg-light text-primary font-weight-bold">
                <i class="fas fa-calendar-alt mr-2"></i>
                Pilih Semester Aktif
            </h6>
            <?php if (!empty($semesters)): ?>
                <?php foreach ($semesters as $semester): ?>
                    <?php $isActive = ($activeSemester && $activeSemester['id'] == $semester['id']); ?>
                    <a class="dropdown-item semester-option d-flex align-items-center py-2 <?= $isActive ? 'active bg-primary text-white' : '' ?>"
                        href="#" data-semester-id="<?= $semester['id'] ?>"
                        data-semester-text="<?= $semesterModel->formatSemester($semester) ?>">
                        <div class="mr-3">
                            <?php if ($isActive): ?>
                                <i class="fas fa-check-circle text-success fa-lg"></i>
                            <?php else: ?>
                                <i class="far fa-circle text-muted fa-lg"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">
                                <?= $semesterModel->formatSemester($semester) ?>
                            </div>
                            <small class="text-muted">
                                <?= $semester['term'] == '1' ? 'Semester Ganjil' : 'Semester Genap' ?>
                            </small>
                        </div>
                        <?php if ($isActive): ?>
                            <span class="badge badge-success ml-2">Aktif</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item d-flex align-items-center py-2 text-info" href="<?= base_url('semester') ?>">
                    <i class="fas fa-cog mr-3 fa-lg"></i>
                    <span>Kelola Semester</span>
                </a>
            <?php else: ?>
                <div class="dropdown-item-text text-center py-3">
                    <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                    <div class="text-muted">Tidak ada semester tersedia</div>
                    <small class="text-info">
                        Semester akan dipilih otomatis berdasarkan bulan saat ini (<?= date('F Y') ?>)
                    </small>
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item d-flex align-items-center py-2 text-success"
                    href="<?= base_url('semesters/create') ?>">
                    <i class="fas fa-plus mr-3 fa-lg"></i>
                    <span>Tambah Semester</span>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item d-flex align-items-center py-2 text-primary" href="#"
                    onclick="initializeSemester()">
                    <i class="fas fa-sync mr-3 fa-lg"></i>
                    <span>Atur Semester Otomatis</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Remove the Bootstrap modal - replaced with SweetAlert -->

<!-- Add some debug info in development -->
<?php if (ENVIRONMENT === 'development'): ?>
    <script>
        console.log('Debug Info:');
        console.log('CSRF Token:', '<?= csrf_hash() ?>');
        console.log('CSRF Name:', '<?= csrf_token() ?>');
        console.log('Base URL:', '<?= base_url() ?>');
        console.log('Active Semester:', <?= json_encode($activeSemester) ?>);
    </script>
<?php endif; ?>

<script src="<?= base_url('assets/js/semester_selector.js') ?>"></script>

<!-- Add initialization function -->
<script>
    function initializeSemester() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Mengatur Semester Otomatis...',
                text: 'Memilih semester berdasarkan tanggal saat ini',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        fetch(baseUrl + 'semesters/initializeSemester', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 1000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        alert(data.message);
                        location.reload();
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message || 'Terjadi kesalahan'
                        });
                    } else {
                        alert('Error: ' + (data.message || 'Terjadi kesalahan'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat mengatur semester'
                    });
                } else {
                    alert('Terjadi kesalahan saat mengatur semester');
                }
            });
    }
</script>