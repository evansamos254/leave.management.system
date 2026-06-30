<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">System Monitor</p>
            <h2>System Logs</h2>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Record</th>
                <th>IP Address</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$logs): ?>
                <tr>
                    <td colspan="5" class="muted">No logs have been recorded yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= e(date('d M Y H:i', strtotime($log['created_at']))) ?></td>
                        <td>
                            <strong><?= e($log['actor_name'] ?? 'System') ?></strong>
                            <?php if ($log['actor_email']): ?>
                                <small><?= e($log['actor_email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge"><?= e(status_label($log['action'])) ?></span></td>
                        <td>
                            <?= e($log['entity_type'] ?? 'N/A') ?>
                            <?php if ($log['entity_id']): ?>
                                <small>#<?= (int) $log['entity_id'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($log['ip_address'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
