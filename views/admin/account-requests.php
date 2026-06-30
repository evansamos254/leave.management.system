<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">ICT</p>
            <h2>Pending Account Requests</h2>
        </div>
    </div>

    <?php if (!$requests): ?>
        <p class="muted">No account requests are waiting for ICT approval.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Payroll / ID</th>
                    <th>Department</th>
                    <th>Directorate</th>
                    <th>Requested Role</th>
                    <th>Submitted</th>
                    <th><?= !empty($canAct) ? 'Review' : 'Details' ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>
                            <strong><?= e($request['full_name']) ?></strong>
                            <small><?= e($request['email']) ?></small>
                            <small>ID: <?= e($request['national_id'] ?? 'N/A') ?></small>
                            <small>Gender: <?= e(gender_label($request['gender'] ?? null)) ?></small>
                            <?php if ($request['phone']): ?>
                                <small><?= e($request['phone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($request['staff_id']) ?>
                            <small><?= e(designation_label($request['designation'] ?? null, $request['role'] ?? null)) ?></small>
                        </td>
                        <td><?= e($request['directorate_name'] ?? 'N/A') ?></td>
                        <td><?= e($request['department_name'] ?? 'N/A') ?></td>
                        <td><span class="badge"><?= e(role_label($request['role'])) ?></span></td>
                        <td><?= e(format_date($request['created_at'])) ?></td>
                        <td>
                            <a class="btn btn-small <?= !empty($canAct) ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e(url('admin/account-requests/view')) ?>&id=<?= (int) $request['id'] ?>">
                                <?= !empty($canAct) ? 'Review Request' : 'View Details' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
