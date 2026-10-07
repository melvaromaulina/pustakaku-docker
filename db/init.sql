SET NAMES utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  no_hp VARCHAR(20),
  alamat TEXT,
  role ENUM('admin','anggota') NOT NULL DEFAULT 'anggota',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  penulis VARCHAR(100) NOT NULL,
  penerbit VARCHAR(100),
  tahun INT,
  kategori VARCHAR(50),
  stok INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  tgl_ajuan DATE NOT NULL,
  tgl_pinjam DATE NULL,
  tgl_jatuh_tempo DATE NULL,
  tgl_kembali DATE NULL,
  status ENUM('Diajukan','Dipinjam','Dikembalikan','Ditolak') NOT NULL DEFAULT 'Diajukan',
  denda INT NOT NULL DEFAULT 0,
  sudah_perpanjang TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Akun contoh: admin@pustaka.id / admin123  dan  budi@pustaka.id / anggota123
INSERT INTO users (nama,email,password,no_hp,alamat,role) VALUES
('Admin Perpustakaan','admin@pustaka.id','$2b$10$BiCi5oaOXCvHO/l1MSp45OysiIPkLgeilWwVIOOIa3KU2y4v8309u','081200000001','Medan','admin'),
('Budi Santoso','budi@pustaka.id','$2b$10$eUr7yhj.utmQTZZKm82JKuenkD7bJMxVUQauLf0tSMqVIsfUZPAQi','081200000002','Jl. Setia Budi, Medan','anggota');

INSERT INTO books (judul,penulis,penerbit,tahun,kategori,stok) VALUES
('Laskar Pelangi','Andrea Hirata','Bentang Pustaka',2005,'Novel',5),
('Bumi Manusia','Pramoedya Ananta Toer','Hasta Mitra',1980,'Novel',3),
('Filosofi Teras','Henry Manampiring','Kompas',2018,'Pengembangan Diri',4),
('Atomic Habits','James Clear','Gramedia',2019,'Pengembangan Diri',0),
('Sapiens','Yuval Noah Harari','KPG',2017,'Sejarah',2),
('Laut Bercerita','Leila S. Chudori','KPG',2017,'Novel',3),
('Dasar-Dasar Pemrograman Web','Tim Penulis','Informatika',2021,'Teknologi',6),
('Pergi','Tere Liye','Sabak Grip',2018,'Novel',2);

INSERT INTO loans (user_id,book_id,tgl_ajuan,tgl_pinjam,tgl_jatuh_tempo,status) VALUES
(2,1,CURDATE() - INTERVAL 12 DAY,CURDATE() - INTERVAL 11 DAY,CURDATE() - INTERVAL 4 DAY,'Dipinjam'),
(2,3,CURDATE(),NULL,NULL,'Diajukan');
