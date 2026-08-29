-- Data contoh khusus pengembangan/testing. Jalankan manual bila dibutuhkan.
-- Aman dijalankan berulang karena registration_number dibuat unik oleh pengecekan WHERE NOT EXISTS.
INSERT INTO `spmb_registrations`
(`registration_number`,`student_name`,`student_nik`,`gender`,`birth_place`,`birth_date`,`parent_name`,`parent_nik`,`family_card_number`,`whatsapp`,`level`,`academic_year`,`admission_track`,`previous_school`,`address`,`registration_status`,`document_status`,`payment_status`)
SELECT 'SPMB-DEMO-0001','Ahmad Fadhil Ramadhan','327501120820180001','L','Bekasi','2018-08-12','Budi Ramadhan','3275010101900001','3275010101900001','081234567890','SDIT','2026/2027','reguler','TK Islam Ceria','Tambun Selatan, Kabupaten Bekasi','verifikasi','lengkap','belum_bayar'
WHERE NOT EXISTS (SELECT 1 FROM `spmb_registrations` WHERE `registration_number`='SPMB-DEMO-0001');

INSERT INTO `spmb_registrations`
(`registration_number`,`student_name`,`gender`,`birth_place`,`birth_date`,`parent_name`,`whatsapp`,`level`,`academic_year`,`admission_track`,`previous_school`,`address`,`registration_status`,`document_status`,`payment_status`)
SELECT 'SPMB-DEMO-0002','Aisyah Nabila Putri','P','Bekasi','2022-02-14','Siti Rahmawati','081298765432','TKIT','2027/2028','waiting_list','Daycare Bintang Kecil','Sumberjaya, Tambun Selatan','baru','belum_lengkap','belum_bayar'
WHERE NOT EXISTS (SELECT 1 FROM `spmb_registrations` WHERE `registration_number`='SPMB-DEMO-0002');
