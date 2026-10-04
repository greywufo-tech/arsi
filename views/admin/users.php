<?php $pageTitle = 'Kelola Pengguna'; ?>

<div class="flex-between mb-3">
    <h2 class="mb-0">Pengguna</h2>
    <span class="muted small"><?= count($users) ?> pengguna ditampilkan</span>
</div>

<form method="get" action="/admin/pengguna" class="card mb-3">
    <div class="card-body">
        <div class="filters">
            <div class="form-group">
                <label>Cari</label>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Nama atau email…">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="">Semua role</option>
                    <?php foreach (['PELANGGAN', 'JASTIPER', 'ADMIN'] as $r): ?>
                        <option value="<?= e($r) ?>" <?= $role === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a href="/admin/pengguna" class="btn btn-outline">Reset</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Telepon</th>
                        <th class="text-right">Pesanan</th>
                        <th class="text-right">Penawaran</th>
                        <th>Status</th>
                        <th>Daftar</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="small bold"><?= e($u['name']) ?></td>
                        <td class="small"><?= e($u['email']) ?></td>
                        <td><span class="badge badge-gray"><?= e($u['role']) ?></span></td>
                        <td class="small"><?= e($u['phone'] ?: '-') ?></td>
                        <td class="text-right"><?= (int) $u['order_count'] ?></td>
                        <td class="text-right"><?= (int) $u['offer_count'] ?></td>
                        <td>
                            <?php if ((int) $u['is_active'] === 1): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="small muted"><?= formatDate($u['created_at']) ?></td>
                        <td class="actions">
                            <form method="post" action="/admin/pengguna/<?= (int) $u['id'] ?>/toggle" class="inline-form">
                                <?= csrfField() ?>
                                <button type="submit" class="btn <?= (int) $u['is_active'] === 1 ? 'btn-danger' : 'btn-success' ?> btn-sm">
                                    <?= (int) $u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>