<?php

function unit_render_profile(array $settings, array $unitConfig): void {
    $profiles = [
        'daycare' => [
            'lead' => 'Hari-hari pertama anak di luar rumah perlu terasa aman, akrab, dan menyenangkan. Kami mendampingi anak melalui rutinitas yang hangat sambil memberi ruang untuk bermain, bergerak, bertanya, dan mencoba hal baru.',
            'approach' => 'Pengasuhan menggabungkan perhatian pada kebutuhan dasar anak dengan stimulasi yang sesuai tahap usianya. Aktivitas sensorik, gerak, bahasa, dan interaksi sosial dijalankan melalui permainan serta kebiasaan sederhana yang mudah diikuti anak.',
            'faith' => 'Nilai Islam hadir dalam keseharian melalui doa, salam, tutur kata yang baik, dan teladan pendidik. Anak diajak mengenal kebiasaan baik tanpa kehilangan kesempatan untuk menjadi aktif dan ingin tahu.',
            'family' => 'Orang tua adalah mitra utama. Komunikasi tentang kebiasaan, perkembangan, dan kebutuhan anak membantu pendampingan di rumah dan di Daycare saling terhubung.',
            'points' => ['Pengasuhan yang responsif dan penuh perhatian', 'Stimulasi melalui permainan dan eksplorasi', 'Pembiasaan adab Islami sejak dini'],
        ],
        'tkit' => [
            'lead' => 'Masa taman kanak-kanak adalah saat anak membangun rasa percaya diri, kemandirian, dan kegemaran belajar. TKIT Permata Hati Bekasi menyediakan pengalaman belajar yang aktif dengan permainan sebagai jalan utama anak memahami dunia.',
            'approach' => 'Kegiatan dirancang agar anak dapat mengamati, mengungkapkan gagasan, bekerja sama, dan menghasilkan karya. Guru memberi arahan sesuai tahap perkembangan setiap anak serta merayakan proses, bukan hanya hasil akhir.',
            'faith' => 'Doa harian, adab, tahsin dasar, dan pengenalan Al-Quran dipadukan dengan kegiatan kelas. Pembiasaan dilakukan secara bertahap agar nilai yang dipelajari dekat dengan kehidupan anak.',
            'family' => 'Kami mengajak keluarga menjaga kesinambungan antara pengalaman di sekolah dan kebiasaan di rumah. Percakapan terbuka dengan guru membantu anak mendapat dukungan yang sesuai.',
            'points' => ['Belajar aktif melalui bermain', 'Pembiasaan karakter dan kemandirian', 'Pengenalan Al-Quran yang ramah anak'],
        ],
        'sdit' => [
            'lead' => 'Pendidikan dasar memberi fondasi bagi cara anak berpikir, bersikap, dan berhubungan dengan orang lain. SDIT Permata Hati Bekasi memadukan pembelajaran akademik dengan pembinaan karakter dan kecintaan kepada Al-Quran.',
            'approach' => 'Siswa diajak memahami konsep, mengajukan pertanyaan, berlatih memecahkan masalah, dan menerapkan pengetahuan dalam kegiatan yang bermakna. Literasi, kreativitas, dan kerja sama tumbuh melalui proses belajar yang konsisten.',
            'faith' => 'Adab, ibadah, serta pembelajaran Al-Quran menjadi bagian dari budaya sekolah. Kami ingin siswa melihat hubungan antara pengetahuan, tanggung jawab, dan tindakan sehari-hari.',
            'family' => 'Perjalanan belajar anak berlangsung di sekolah dan rumah. Kami mengutamakan komunikasi dengan keluarga agar perkembangan, tantangan, dan pencapaian siswa dapat didampingi bersama.',
            'points' => ['Dasar akademik yang kuat dan bertahap', 'Pembinaan adab serta Al-Quran', 'Ruang bagi minat, karya, dan prestasi'],
        ],
        'smpit' => [
            'lead' => 'Masa remaja membutuhkan ruang untuk bertumbuh secara mandiri sekaligus bimbingan yang dapat dipercaya. SMPIT Permata Hati Bekasi mendampingi siswa mengembangkan kemampuan akademik, karakter, dan arah diri.',
            'approach' => 'Pembelajaran mendorong siswa berpikir kritis, berdiskusi, menyusun karya, dan bertanggung jawab atas prosesnya. Kegiatan sains, teknologi, seni, dan olahraga memberi kesempatan untuk mengenali kekuatan pribadi.',
            'faith' => 'Pembinaan Al-Quran, adab, dan kebiasaan ibadah menjadi landasan dalam menghadapi pilihan sehari-hari. Siswa didampingi untuk memahami nilai dan menerapkannya dengan kesadaran.',
            'family' => 'Komunikasi sekolah dan keluarga penting saat siswa memasuki masa perubahan. Melalui pendampingan bersama, kami membantu mereka membangun kepercayaan diri, kepedulian, dan kesiapan ke jenjang berikutnya.',
            'points' => ['Akademik dan proyek yang menantang', 'Pendampingan karakter remaja', 'Ruang eksplorasi minat dan kepemimpinan'],
        ],
    ];
    $profile = $profiles[UNIT_SLUG] ?? $profiles['sdit'];
    ?>
    <section class="section"><div class="shell story-grid unit-profile-intro"><img src="<?php echo unit_e(unit_media($unitConfig['building_image'])); ?>" alt="Gedung <?php echo unit_e($settings['name']); ?>"><div><span class="eyebrow">TENTANG KAMI</span><h2><?php echo unit_e($settings['name']); ?></h2><p class="unit-profile-lead"><?php echo unit_e($settings['description']); ?></p><p><?php echo unit_e($profile['lead']); ?></p><a class="button button-primary" href="spmb.php">Informasi Pendaftaran</a></div></div></section>
    <section class="section section-soft"><div class="shell unit-profile-columns"><div><span class="eyebrow">PENDEKATAN KAMI</span><h2>Belajar dengan perhatian pada setiap tahap</h2><p><?php echo unit_e($profile['approach']); ?></p></div><div class="unit-profile-point-card"><h3>Yang kami utamakan</h3><ul><?php foreach ($profile['points'] as $point): ?><li><?php echo unit_e($point); ?></li><?php endforeach; ?></ul></div></div></section>
    <section class="section"><div class="shell unit-profile-columns"><article><span class="eyebrow">NILAI ISLAMI</span><h2>Kebiasaan baik dalam keseharian</h2><p><?php echo unit_e($profile['faith']); ?></p></article><article><span class="eyebrow">BERSAMA KELUARGA</span><h2>Pendampingan yang saling terhubung</h2><p><?php echo unit_e($profile['family']); ?></p></article></div></section>
    <section class="section section-primary"><div class="shell unit-profile-cta"><div><span class="eyebrow light">KENALI LEBIH DEKAT</span><h2>Mari berbincang tentang kebutuhan anak Anda.</h2><p>Tim unit siap membantu menjelaskan program, kegiatan, dan proses pendaftaran.</p></div><div><a class="button button-accent" href="contact.php">Hubungi Kami</a><a class="button button-ghost" href="programs.php">Lihat Program</a></div></div></section>
    <?php
}
