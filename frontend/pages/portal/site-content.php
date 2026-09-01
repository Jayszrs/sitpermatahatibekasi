<?php
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/helpers/functions.php';
require_once __DIR__ . '/../../../backend/auth.php';
portal_require_auth(['admin', 'humas']);

$contentTypes = [
    'unit' => ['label' => 'Unit Sekolah', 'title' => 'Nama unit', 'subtitle' => 'Singkatan/jenjang', 'extra' => 'Program unggulan (satu per baris)', 'help' => 'Kelola tepat empat unit resmi: Daycare, TKIT, SDIT, dan SMPIT.'],
    'achievement' => ['label' => 'Prestasi', 'title' => 'Nama prestasi', 'subtitle' => 'Tingkat perlombaan', 'extra' => null, 'help' => 'Publikasikan prestasi siswa lengkap dengan tingkat, tahun, dan dokumentasi.'],
    'leadership' => ['label' => 'Struktur Pimpinan', 'title' => 'Nama lengkap', 'subtitle' => 'Jabatan', 'extra' => null, 'help' => 'Kelola pimpinan per unit lengkap dengan foto, pendidikan, tempat mengajar, dan bidang/mata pelajaran.'],
    'foundation' => ['label' => 'Struktur Yayasan', 'title' => 'Nama lengkap', 'subtitle' => 'Jabatan yayasan', 'extra' => 'Riwayat dan pengalaman organisasi', 'help' => 'Kelola profil pengurus yayasan lengkap dengan foto, jabatan, pendidikan, pengalaman, dan tanggung jawab. Setiap data memiliki halaman profil publik.'],
    'program' => ['label' => 'Program Unggulan', 'title' => 'Nama program', 'subtitle' => 'Unit/kategori', 'extra' => null, 'help' => 'Kelola program per unit pendidikan beserta gambar, penjelasan, dan tautan publikasinya.'],
    'activity' => ['label' => 'Kegiatan', 'title' => 'Nama kegiatan', 'subtitle' => 'Periode/kategori', 'extra' => null, 'help' => 'Kelola agenda dan dokumentasi kegiatan sekolah.'],
];
$type = $_GET['type'] ?? 'unit';
if ($type !== 'profile' && !isset($contentTypes[$type])) $type = 'unit';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    portal_verify_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_item') {
            $itemType = $_POST['type'] ?? '';
            if (!isset($contentTypes[$itemType])) throw new RuntimeException('Jenis konten tidak valid.');
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $subtitle = trim($_POST['subtitle'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $badge = trim($_POST['badge'] ?? '');
            $year = trim($_POST['year'] ?? '');
            $extra = trim($_POST['extra'] ?? '');
            $linkUrl = trim($_POST['link_url'] ?? '');
            $linkLabel = trim($_POST['link_label'] ?? '');
            $unitSlug = strtolower(trim($_POST['unit_slug'] ?? ''));
            $education = trim($_POST['education'] ?? '');
            $teachingScope = trim($_POST['teaching_scope'] ?? '');
            $whatsapp = preg_replace('/\D+/', '', trim($_POST['whatsapp'] ?? ''));
            $instagramUrl = trim($_POST['instagram_url'] ?? '');
            $youtubeUrl = trim($_POST['youtube_url'] ?? '');
            $sortOrder = max(0, (int)($_POST['sort_order'] ?? 0));
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($title === '' || $description === '') throw new RuntimeException('Judul/nama dan deskripsi wajib diisi.');
            if ($itemType === 'unit') {
                $unitSlug = school_unit_slug($subtitle ?: $title);
                $catalog = school_unit_catalog();
                if (!isset($catalog[$unitSlug])) throw new RuntimeException('Unit sekolah hanya boleh Daycare, TKIT, SDIT, atau SMPIT.');
                $subtitle = $catalog[$unitSlug]['subtitle'];
                $duplicate = $pdo->prepare("SELECT COUNT(*) FROM site_content_items WHERE type='unit' AND LOWER(subtitle)=LOWER(?) AND id<>?");
                $duplicate->execute([$subtitle,$id]);
                if ((int)$duplicate->fetchColumn() > 0) throw new RuntimeException('Unit '.$subtitle.' sudah tersedia. Silakan edit data yang ada.');
                if ($whatsapp !== '' && (strlen($whatsapp) < 9 || strlen($whatsapp) > 16)) throw new RuntimeException('Nomor WhatsApp unit harus berisi 9-16 digit dengan kode negara, contoh 6281234567890.');
                foreach (['Instagram' => $instagramUrl, 'YouTube' => $youtubeUrl] as $socialName => $socialUrl) {
                    $scheme = $socialUrl !== '' ? strtolower((string)parse_url($socialUrl, PHP_URL_SCHEME)) : '';
                    if ($socialUrl !== '' && (!filter_var($socialUrl, FILTER_VALIDATE_URL) || !in_array($scheme, ['http','https'], true))) throw new RuntimeException('Tautan '.$socialName.' harus berupa URL http atau https yang valid.');
                }
            }
            if ($itemType === 'leadership' && (!in_array($unitSlug,['daycare','tkit','sdit','smpit'],true) || $education==='' || $teachingScope==='')) {
                throw new RuntimeException('Unit, riwayat pendidikan, dan tempat/bidang mengajar pimpinan wajib diisi.');
            }
            $image = ''; $previousImage = null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT image FROM site_content_items WHERE id=? AND type=?'); $stmt->execute([$id,$itemType]);
                $previousImage = $stmt->fetchColumn() ?: null; $image = $previousImage ?: '';
            }
            if (!empty($_FILES['image']['name'])) $image = portal_upload_image($_FILES['image'], $itemType);
            if ($itemType === 'foundation' && !$id && $image === '') throw new RuntimeException('Foto pengurus yayasan wajib diunggah untuk data baru.');
            if ($id) {
                $stmt = $pdo->prepare('UPDATE site_content_items SET title=?,subtitle=?,description=?,image=?,badge=?,year=?,extra=?,link_url=?,link_label=?,unit_slug=?,education=?,teaching_scope=?,whatsapp=?,instagram_url=?,youtube_url=?,sort_order=?,is_active=? WHERE id=? AND type=?');
                $stmt->execute([$title,$subtitle?:null,$description,$image?:null,$badge?:null,$year?:null,$extra?:null,$linkUrl?:null,$linkLabel?:null,$unitSlug?:null,$education?:null,$teachingScope?:null,$whatsapp?:null,$instagramUrl?:null,$youtubeUrl?:null,$sortOrder,$isActive,$id,$itemType]);
                if ($previousImage && $previousImage !== $image) portal_delete_uploaded_image($previousImage);
                portal_log($pdo, 'update_content', 'Memperbarui ' . $contentTypes[$itemType]['label'] . ': ' . $title);
            } else {
                $stmt = $pdo->prepare('INSERT INTO site_content_items (type,title,subtitle,description,image,badge,year,extra,link_url,link_label,unit_slug,education,teaching_scope,whatsapp,instagram_url,youtube_url,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$itemType,$title,$subtitle?:null,$description,$image?:null,$badge?:null,$year?:null,$extra?:null,$linkUrl?:null,$linkLabel?:null,$unitSlug?:null,$education?:null,$teachingScope?:null,$whatsapp?:null,$instagramUrl?:null,$youtubeUrl?:null,$sortOrder,$isActive]);
                portal_log($pdo, 'create_content', 'Menambahkan ' . $contentTypes[$itemType]['label'] . ': ' . $title);
            }
            portal_flash('success', 'Konten berhasil disimpan dan diperbarui di website.');
            $type = $itemType;
        } elseif ($action === 'delete_item') {
            $id = (int)($_POST['id'] ?? 0); $itemType = $_POST['type'] ?? '';
            if (!isset($contentTypes[$itemType])) throw new RuntimeException('Jenis konten tidak valid.');
            $stmt = $pdo->prepare('SELECT title,image FROM site_content_items WHERE id=? AND type=?'); $stmt->execute([$id,$itemType]); $item=$stmt->fetch();
            if ($item) {
                $pdo->prepare('DELETE FROM site_content_items WHERE id=?')->execute([$id]);
                portal_delete_uploaded_image($item['image']);
                portal_log($pdo, 'delete_content', 'Menghapus ' . $contentTypes[$itemType]['label'] . ': ' . $item['title']);
            }
            portal_flash('success', 'Konten berhasil dihapus.'); $type = $itemType;
        } elseif ($action === 'save_profile') {
            $title = trim($_POST['history_title'] ?? ''); $history = trim($_POST['history_content'] ?? '');
            $vision = trim($_POST['vision'] ?? ''); $mission = trim($_POST['mission'] ?? '');
            if ($title === '' || $history === '' || $vision === '' || $mission === '') throw new RuntimeException('Seluruh kolom profil wajib diisi.');
            $stmt=$pdo->query('SELECT image FROM site_profile WHERE id=1'); $previous=$stmt->fetchColumn() ?: null; $image=$previous;
            if (!empty($_FILES['image']['name'])) $image=portal_upload_image($_FILES['image'],'profil');
            $stmt=$pdo->prepare('UPDATE site_profile SET history_title=?,history_content=?,vision=?,mission=?,image=? WHERE id=1');
            $stmt->execute([$title,$history,$vision,$mission,$image]);
            if ($previous && $previous !== $image) portal_delete_uploaded_image($previous);
            portal_log($pdo,'update_profile','Memperbarui profil, sejarah, visi, dan misi sekolah');
            portal_flash('success','Profil sekolah berhasil diperbarui.'); $type='profile';
        }
    } catch (Throwable $e) { portal_flash('danger',$e->getMessage()); }
    header('Location: ' . SITE_URL . '/portal/site-content?type=' . urlencode($type)); exit;
}

$editItem = null;
if ($type !== 'profile' && isset($_GET['edit'])) { $stmt=$pdo->prepare('SELECT * FROM site_content_items WHERE id=? AND type=?'); $stmt->execute([(int)$_GET['edit'],$type]); $editItem=$stmt->fetch() ?: null; }
$showForm = $type !== 'profile' && (isset($_GET['new']) || $editItem);
$items = [];
if ($type !== 'profile') { $stmt=$pdo->prepare('SELECT * FROM site_content_items WHERE type=? ORDER BY sort_order,id'); $stmt->execute([$type]); $items=$stmt->fetchAll(); }
$profile = $type === 'profile' ? $pdo->query('SELECT * FROM site_profile WHERE id=1')->fetch() : null;
$portalTitle = $type === 'profile' ? 'Profil Sekolah' : $contentTypes[$type]['label'];
$portalActive = 'site-' . $type;
require __DIR__ . '/../../components/portal-header.php';
?>
<div class="content-tabs">
    <?php foreach ($contentTypes as $key=>$config): ?><a class="<?php echo $type===$key?'active':''; ?>" href="<?php echo SITE_URL; ?>/portal/site-content?type=<?php echo $key; ?>"><?php echo esc($config['label']); ?></a><?php endforeach; ?>
    <a class="<?php echo $type==='profile'?'active':''; ?>" href="<?php echo SITE_URL; ?>/portal/site-content?type=profile">Profil, Visi &amp; Misi</a>
</div>

<?php if ($type === 'profile'): ?>
<div class="portal-welcome"><div><h2>Profil Sekolah</h2><p>Perbarui sejarah, foto utama, visi, dan misi yang tampil pada halaman Tentang Kami.</p></div></div>
<section class="portal-panel"><form class="portal-form portal-form-grid" method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_profile"><div class="field"><label>Judul sejarah</label><input name="history_title" value="<?php echo esc($profile['history_title']); ?>" required></div><div class="field"><label>Foto profil/sejarah</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><?php if($profile['image']): ?><small style="color:var(--portal-muted)">Foto saat ini sudah tersimpan.</small><?php endif; ?></div><div class="field full"><label>Sejarah sekolah</label><textarea name="history_content" required><?php echo esc($profile['history_content']); ?></textarea></div><div class="field"><label>Visi</label><textarea name="vision" required><?php echo esc($profile['vision']); ?></textarea></div><div class="field"><label>Misi</label><textarea name="mission" required><?php echo esc($profile['mission']); ?></textarea></div><div class="form-actions field full"><button class="portal-action">Simpan Profil</button></div></form></section>
<?php else: $config=$contentTypes[$type]; ?>
<div class="portal-welcome"><div><h2><?php echo esc($config['label']); ?></h2><p><?php echo esc($config['help']); ?></p></div><a class="portal-action" href="<?php echo SITE_URL; ?>/portal/site-content?type=<?php echo $type; ?>&new=1">+ Tambah Data</a></div>
<?php if ($showForm): ?>
<section class="portal-panel" style="margin-bottom:22px"><div class="panel-head"><h3><?php echo $editItem?'Edit':'Tambah'; ?> <?php echo esc($config['label']); ?></h3><a href="<?php echo SITE_URL; ?>/portal/site-content?type=<?php echo $type; ?>">Tutup</a></div>
<form class="portal-form portal-form-grid" method="post" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_item"><input type="hidden" name="type" value="<?php echo $type; ?>"><input type="hidden" name="id" value="<?php echo (int)($editItem['id']??0); ?>">
<div class="field"><label><?php echo esc($config['title']); ?></label><input name="title" value="<?php echo esc($editItem['title']??''); ?>" required></div><div class="field"><label><?php echo esc($config['subtitle']); ?></label><input name="subtitle" value="<?php echo esc($editItem['subtitle']??''); ?>"></div>
<?php if($type==='achievement'): ?><div class="field"><label>Label prestasi</label><input name="badge" value="<?php echo esc($editItem['badge']??''); ?>" placeholder="Nasional / Provinsi / Kota"></div><div class="field"><label>Tahun</label><input name="year" maxlength="10" value="<?php echo esc($editItem['year']??date('Y')); ?>"></div><div class="field"><label>Unit sekolah</label><select name="extra"><option value="">Pilih unit</option><?php foreach(['Daycare','TKIT','SDIT','SMPIT'] as $unitOption): ?><option value="<?php echo esc($unitOption); ?>" <?php echo (($editItem['extra']??'')===$unitOption)?'selected':''; ?>><?php echo esc($unitOption); ?></option><?php endforeach; ?></select></div><?php endif; ?>
<?php if($type==='leadership'): ?><div class="field"><label>Unit sekolah</label><select name="unit_slug" required><option value="">Pilih unit</option><?php foreach(['daycare'=>'Daycare','tkit'=>'TKIT','sdit'=>'SDIT','smpit'=>'SMPIT'] as $slug=>$label): ?><option value="<?php echo $slug; ?>" <?php echo (($editItem['unit_slug']??'')===$slug)?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div><div class="field"><label>Riwayat pendidikan</label><input name="education" value="<?php echo esc($editItem['education']??''); ?>" placeholder="Contoh: S2 Manajemen Pendidikan" required></div><div class="field full"><label>Mengajar di mana dan bidang/mata pelajaran</label><input name="teaching_scope" value="<?php echo esc($editItem['teaching_scope']??''); ?>" placeholder="Contoh: SDIT - Literasi, Numerasi, dan Kurikulum" required></div><?php endif; ?>
<?php if($type==='foundation'): ?><div class="field"><label>Riwayat pendidikan</label><input name="education" value="<?php echo esc($editItem['education']??''); ?>" placeholder="Contoh: S2 Manajemen Pendidikan"></div><div class="field full"><label>Bidang dan tanggung jawab yayasan</label><input name="teaching_scope" value="<?php echo esc($editItem['teaching_scope']??''); ?>" placeholder="Contoh: Tata kelola, pengembangan mutu, dan kemitraan"></div><?php endif; ?>
<?php if($type==='unit'): ?><?php $unitDefaults=school_unit_catalog();$editUnitSlug=school_unit_slug((string)($editItem['subtitle']??$editItem['title']??''));$unitDefault=$unitDefaults[$editUnitSlug]??[]; ?><div class="field"><label>WhatsApp unit</label><input name="whatsapp" inputmode="numeric" value="<?php echo esc($editItem['whatsapp']??$unitDefault['whatsapp']??''); ?>" placeholder="Contoh: 6281234567890"><small style="color:var(--portal-muted)">Gunakan kode negara tanpa tanda +, spasi, atau strip.</small></div><div class="field"><label>Instagram unit</label><input type="url" name="instagram_url" value="<?php echo esc($editItem['instagram_url']??$unitDefault['instagram']??''); ?>" placeholder="https://instagram.com/..."></div><div class="field"><label>YouTube unit</label><input type="url" name="youtube_url" value="<?php echo esc($editItem['youtube_url']??$unitDefault['youtube']??''); ?>" placeholder="https://youtube.com/..."></div><?php endif; ?>
<div class="field"><label>Gambar/foto <?php echo $editItem?'(kosongkan jika tetap)':''; ?></label><input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?php echo $type==='foundation'&&!$editItem?'required':''; ?>><small style="color:var(--portal-muted)"><?php echo $type==='foundation'?'Gunakan foto portrait resmi. ':''; ?>JPG, PNG, atau WebP. Maksimal 5 MB.</small></div><div class="field"><label>Urutan tampil</label><input type="number" min="0" name="sort_order" value="<?php echo (int)($editItem['sort_order']??count($items)+1); ?>"></div>
<div class="field full"><label>Deskripsi lengkap</label><textarea name="description" required><?php echo esc($editItem['description']??''); ?></textarea></div>
<?php if(in_array($type,['program','achievement','activity'],true)): ?><div class="field"><label>Tautan publikasi / tujuan</label><input type="url" name="link_url" value="<?php echo esc($editItem['link_url']??''); ?>" placeholder="https://instagram.com/... atau halaman berita"></div><div class="field"><label>Teks tombol</label><input name="link_label" value="<?php echo esc($editItem['link_label']??''); ?>" placeholder="Lihat Publikasi"></div><?php endif; ?>
<?php if($config['extra']): ?><div class="field full"><label><?php echo esc($config['extra']); ?></label><textarea name="extra" style="min-height:90px"><?php echo esc($editItem['extra']??''); ?></textarea></div><?php endif; ?>
<div class="field full"><label style="display:flex;align-items:center;gap:8px;text-transform:none"><input style="width:auto" type="checkbox" name="is_active" <?php echo !isset($editItem['is_active'])||$editItem['is_active']?'checked':''; ?>> Tampilkan di website</label></div><div class="form-actions field full"><a class="portal-action secondary" href="<?php echo SITE_URL; ?>/portal/site-content?type=<?php echo $type; ?>">Batal</a><button class="portal-action">Simpan Konten</button></div></form></section>
<?php endif; ?>
<section class="portal-panel"><div class="panel-head"><h3>Semua <?php echo esc($config['label']); ?> (<?php echo count($items); ?>)</h3></div><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Gambar</th><th>Nama/Judul</th><th>Detail</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php if(!$items): ?><tr><td colspan="6" class="empty-state">Belum ada data.</td></tr><?php endif; ?><?php foreach($items as $item): ?><tr><td><?php if($item['image']): ?><img src="<?php echo esc($item['image']); ?>" alt=""><?php else: ?><span style="color:var(--portal-muted)">Belum ada</span><?php endif; ?></td><td><strong><?php echo esc($item['title']); ?></strong><br><small><?php echo esc($item['subtitle']?:'-'); ?></small></td><td><?php echo esc(mb_strimwidth($item['description'],0,85,'...')); ?></td><td><?php echo (int)$item['sort_order']; ?></td><td><span class="status <?php echo $item['is_active']?'active':'inactive'; ?>"><?php echo $item['is_active']?'Tampil':'Disembunyikan'; ?></span></td><td><div class="table-actions"><?php if($item['is_active']&&$type==='leadership'): ?><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/detail-pimpinan.php?id=<?php echo (int)$item['id']; ?>" target="_blank" rel="noopener">Lihat</a><?php elseif($item['is_active']&&$type==='foundation'): ?><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/detail-pengurus.php?id=<?php echo (int)$item['id']; ?>" target="_blank" rel="noopener">Lihat</a><?php endif; ?><a class="portal-action secondary small" href="<?php echo SITE_URL; ?>/portal/site-content?type=<?php echo $type; ?>&edit=<?php echo (int)$item['id']; ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus data ini?')"><input type="hidden" name="_token" value="<?php echo esc(portal_csrf_token()); ?>"><input type="hidden" name="action" value="delete_item"><input type="hidden" name="type" value="<?php echo $type; ?>"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><button class="portal-action danger small">Hapus</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php endif; ?>
<?php require __DIR__ . '/../../components/portal-footer.php'; ?>

