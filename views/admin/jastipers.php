<?php $pageTitle = 'Verifikasi Jastiper'; ?>

<h2 class="mb-3">Jastiper</h2>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <?php if ($jastipers === []): ?>
            <?php emptyState('Belum ada jastiper terdaftar.', '🧑‍💼'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th class="text-right">Penawaran</th>
                            <th class="text-right">Omzet</th>
                            <th>Verifikasi</th>
                            <th>Akun</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($jastipers as $j): ?>
                        <tr>
                            <td class="small bold"><?= e($j['name']) ?></td>
                            <td class="small"><?= e($j['email']) ?></td>
                            <td class="small"><?= e($j['phone'] ?: '-') ?></td>
                            <td class="text-right"><?= (int) $j['offer_count'] ?></td>
                            <td class="text-right small"><?= money($j['revenue']) ?></td>
                            <td>
                                <?php if ($j['verified_at'] === null): ?>
                                    <span class="badge badge-warning">Belum Verifikasi</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Terverifikasi</span>
                                    <div class="small muted"><?= formatDate($j['verified_at']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $j['is_active'] === 1): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <form method="post" action="/admin/jastiper/<?= (int) $j['id'] ?>/verifikasi" class="inline-form">
                                    <?= csrfField() ?>
                                    <?php if ($j['verified_at'] === null): ?>
                                        <button type="submit" class="btn btn-success btn-sm">✓ Verifikasi</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-outline btn-sm">Cabut Verifikasi</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>