# Brand-nyamhap-db-project-
Proyek Basis Data: Perancangan Fisik &amp; Implementasi SQL untuk sistem Brand NyamHap. Berisi rancangan tabel, constraint, dummy data, serta query mission objectives menggunakan PostgreSQL.

# Team 7
- Dzulfikar Najib (M0401241043)
- Valent Trityo Gratia W. (M0401241074)
- Fido Ahmad Amirza Rifqi (M0401241115)
- Fadillah Handayani (M0401241017)

# Struktur file (brand-nyamhap-db-project)
1. README.md              
2. rancangan_fisik.md        → Rancangan fisik database (atribut, tipe data, constraints)
3. sql/                        
- nyamhap_schema.sql      → Script CREATE TABLE (schema database)
- nyamhap_data.sql        → Script INSERT dummy data
- mission_objectives.sql  → Script query mission objectives      
4. docs/                     
- ERD.text                 → Diagram ERD (Entity Relationship Diagram)            

# Cara Menjalankan
1. Import `nyamhap_schema.sql` ke PostgreSQL.
2. Jalankan `nyamhap_data.sql` untuk dummy data.
3. Gunakan `mission_objectives.sql` untuk query sesuai kebutuhan.

# Mission Objectives yang Disediakan
A. Pencarian Data
- Menampilkan daftar menu yang tersedia beserta harga.
- Menampilkan total pesanan pelanggan dalam periode tertentu.
- Menghitung total pemasukan per hari / per bulan.
- Menampilkan stok bahan baku saat ini.
- Menampilkan total bahan baku yang digunakan pada periode tertentu.
- Menampilkan data staff dan posisi masing-masing.
- Menghitung total pengeluaran bahan baku & membandingkan dengan pemasukan.
- Menampilkan iklan yang aktif pada tanggal tertentu.
- Menampilkan pelanggan yang paling sering memesan.

B. Pelacakan Data
- Melacak status pelanggan yang sedang memesan.
- Melacak menu apa saja yang dipesan pada suatu pesanan.
- Melacak status pembayaran.
- Melacak status stok bahan baku.
- Melacak status periklanan.

C. Pelaporan Data
- Laporan data staff.
- Laporan data pelanggan.
- Laporan data pembayaran.
- Laporan data pesanan.
- Laporan data menu.
- Laporan data bahan baku.
- Laporan data periklanan.

# Proyek ini mencakup:
- Perancangan fisik database (PostgreSQL) untuk sistem Brand NyamHap.
- Implementasi SQL: CREATE, INSERT, UPDATE, dengan constraint lengkap.
- Mission objectives: query untuk pencarian, pelacakan, dan pelaporan data.
- Dummy data: untuk simulasi operasional sistem.

# Refence
Modul Praoyek Basis Data IPB Semester Ganjil 2025/2026
