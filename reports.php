<?php
/**
 * Attendly Academic Portal - PHP Reports generation Desk View
 */

require_once __DIR__ . '/config.php';

if ($current_user['role'] !== 'admin') {
    echo '<div class="bg-white border p-8 rounded-2xl text-center italic text-slate-400">View accessible to administrative controllers.</div>';
    return;
}

$reports = get_table(REPORTS_FILE);

$blueprints = [
    [
        'id' => 'tpl-1',
        'title' => 'Monthly Student Rollup Report',
        'format' => 'PDF Documents',
        'desc' => 'Aggregated attendance percentage scores and exam eligibility blocks across all sections.'
    ],
    [
        'id' => 'tpl-2',
        'title' => 'Subject-wise Attendance Distribution',
        'format' => 'Microsoft Excel',
        'desc' => 'Complete pivot spreadsheets maps tracking class rooms registers and lectures hours count.'
    ],
    [
        'id' => 'tpl-3',
        'title' => 'Faculty Lectures Compliance Matrix',
        'format' => 'PDF Documents',
        'desc' => 'Academic lecture sessions recorded vs prescribed syllabus metrics of teaching personnel.'
    ]
];
?>
<div class="space-y-6 animate-fade-in">
    
    <!-- Hero Reports Title banner -->
    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
        <div class="flex justify-between items-center pb-2 border-b border-slate-50 dark:border-slate-850">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Academic Reports Terminal</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Compile real-time PDFs and structured tables logs for compliance registrar audits.</p>
            </div>
            <span class="material-symbols-outlined text-indigo-550 text-2xl select-none">receipt_long</span>
        </div>
    </div>

    <!-- Blueprints section grid -->
    <div class="space-y-4">
        <h3 class="font-extrabold text-slate-850 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
            <span class="material-symbols-outlined text-blue-550 text-sm">print</span>
            Available Document Templates
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($blueprints as $tpl): ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 hover:shadow-md transition-shadow flex flex-col justify-between">
                
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-[9px] font-extrabold text-blue-700 bg-blue-50 dark:bg-blue-950/50 px-2 py-0.5 rounded font-mono uppercase">
                            <?php echo htmlspecialchars($tpl['format']); ?>
                        </span>
                        <span class="material-symbols-outlined text-slate-350 text-base">description</span>
                    </div>
                    <h4 class="font-extrabold text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($tpl['title']); ?></h4>
                    <p class="text-slate-500 dark:text-slate-400 text-[11px] leading-relaxed font-sans"><?php echo htmlspecialchars($tpl['desc']); ?></p>
                </div>

                <!-- Form to compile file -->
                <form action="index.php?action=generate_report" method="POST" class="pt-2 select-none">
                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($tpl['title']); ?>" />
                    <input type="hidden" name="format" value="<?php echo htmlspecialchars($tpl['format']); ?>" />
                    
                    <button
                        type="submit"
                        class="w-full py-2 px-4 rounded-xl text-xs font-bold text-center bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-550/10 transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-xs">print</span>
                        Compile Report
                    </button>
                </form>

            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Archives history table list -->
    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
        
        <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-850 mb-4">
            <div>
                <h3 class="font-bold text-slate-850 dark:text-white text-sm">Archived Generations Log</h3>
                <p class="text-xs text-slate-500 font-medium">Download logs of previously generated records.</p>
            </div>
            <span class="material-symbols-outlined text-slate-450">inventory_2</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950 text-slate-500 font-bold border-b border-slate-100 dark:border-slate-850">
                        <th class="py-2.5 px-3">Report Document File</th>
                        <th class="py-2.5 px-3">File Category</th>
                        <th class="py-2.5 px-3">Compiled By</th>
                        <th class="py-2.5 px-3">Timestamp (GMT/UTC)</th>
                        <th class="py-2.5 px-3">Size Scale</th>
                        <th class="py-2.5 px-3 text-right">Payload Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="6" class="py-8 px-3 text-center text-slate-400 italic">No reports have been generated yet.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reports as $rep): ?>
                    <tr class="border-b border-slate-50 dark:border-slate-850 hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-colors">
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600 text-sm">picture_as_pdf</span>
                                <span class="font-extrabold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($rep['title']); ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-3 font-semibold text-slate-450 dark:text-slate-400"><?php echo htmlspecialchars($rep['type']); ?></td>
                        <td class="py-3 px-3 font-bold text-slate-705 dark:text-slate-300"><?php echo htmlspecialchars($rep['generatedBy']); ?></td>
                        <td class="py-3 px-3 font-mono text-slate-450 text-[10px]"><?php echo $rep['generatedAt']; ?></td>
                        <td class="py-3 px-3 font-mono font-bold text-slate-450 text-[11px]"><?php echo $rep['fileSize']; ?></td>
                        <td class="py-3 px-3 text-right select-none">
                            <button
                                onclick="alert('Simulating native browser transmission. downloaded file (<?php echo $rep['fileSize']; ?>) successfully.');"
                                class="text-xs font-black text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 px-3 py-1.5 rounded-lg border border-blue-100 dark:border-blue-800/40 cursor-pointer inline-flex items-center gap-1"
                            >
                                <span class="material-symbols-outlined text-xs">download</span>
                                Download File
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>
