--
-- PostgreSQL database dump
--

\restrict 9L8qIBSLud1RI35x2tXQApS4ex6TrVGZqVTI06AwbLaZRaFdNjyI07XIthRggcj

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

-- Started on 2025-12-20 20:02:36

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 236 (class 1255 OID 19166)
-- Name: fn_calculate_subtotal(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_calculate_subtotal() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_harga_jual DECIMAL(15,2);
    v_total_modal_per_porsi DECIMAL(15,2);
BEGIN
    -- 1. Ambil harga jual menu
    SELECT harga INTO v_harga_jual FROM menu WHERE menu_id = NEW.menu_id;
    
    -- 2. Hitung total modal bahan berdasarkan resep & harga_per_unit di bahan_baku
    SELECT SUM(r.jumlah_bahan * bb.harga_per_unit) 
    INTO v_total_modal_per_porsi
    FROM resep r
    JOIN bahan_baku bb ON r.bahan_id = bb.bahan_id
    WHERE r.menu_id = NEW.menu_id;

    -- 3. Set nilai ke kolom detail_pesanan
    NEW.subtotal := v_harga_jual * NEW.jumlah_menu;
    NEW.subtotal_modal := COALESCE(v_total_modal_per_porsi, 0) * NEW.jumlah_menu;
    
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_calculate_subtotal() OWNER TO postgres;

--
-- TOC entry 234 (class 1255 OID 19162)
-- Name: fn_manage_stock(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_manage_stock() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- JIKA PESANAN BARU (INSERT)
    IF (TG_OP = 'INSERT') THEN
        UPDATE bahan_baku
        SET stok = stok - (r.jumlah_bahan * NEW.jumlah_menu)
        FROM resep r
        WHERE bahan_baku.bahan_id = r.bahan_id AND r.menu_id = NEW.menu_id;

    -- JIKA PESANAN DIUBAH JUMLAHNYA (UPDATE)
    ELSIF (TG_OP = 'UPDATE') THEN
        UPDATE bahan_baku
        SET stok = stok + (r.jumlah_bahan * OLD.jumlah_menu) - (r.jumlah_bahan * NEW.jumlah_menu)
        FROM resep r
        WHERE bahan_baku.bahan_id = r.bahan_id AND r.menu_id = NEW.menu_id;

    -- JIKA PESANAN DIHAPUS/BATAL (DELETE)
    ELSIF (TG_OP = 'DELETE') THEN
        UPDATE bahan_baku
        SET stok = stok + (r.jumlah_bahan * OLD.jumlah_menu)
        FROM resep r
        WHERE bahan_baku.bahan_id = r.bahan_id AND r.menu_id = OLD.menu_id;
    END IF;

    -- PROTEKSI: Batalkan jika stok jadi negatif
    IF EXISTS (SELECT 1 FROM bahan_baku WHERE stok < 0) THEN
        RAISE EXCEPTION 'Stok bahan baku tidak mencukupi untuk transaksi ini!';
    END IF;

    RETURN NULL;
END;
$$;


ALTER FUNCTION public.fn_manage_stock() OWNER TO postgres;

--
-- TOC entry 249 (class 1255 OID 19187)
-- Name: fn_potong_stok_lunas(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_potong_stok_lunas() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    r_item RECORD;
    r_bahan RECORD;
BEGIN
    -- Logika: Jika status berubah dari 'Pending' ke 'Lunas'
    IF (NEW.status_bayar = 'Lunas' AND (OLD.status_bayar IS NULL OR OLD.status_bayar <> 'Lunas')) THEN
        
        -- Ambil semua item di dalam invoice tersebut
        FOR r_item IN (SELECT menu_id, jumlah_menu FROM detail_pesanan WHERE pesanan_id = NEW.pesanan_id) LOOP
            
            -- Cek resep tiap menu dan kurangi stok bahan baku
            FOR r_bahan IN (SELECT bahan_id, jumlah_bahan FROM resep WHERE menu_id = r_item.menu_id) LOOP
                
                UPDATE bahan_baku 
                SET stok = stok - (r_bahan.jumlah_bahan * r_item.jumlah_menu)
                WHERE bahan_id = r_bahan.bahan_id;
                
            END LOOP;
        END LOOP;

        -- PROTEKSI: Jika setelah dipotong stok ada yang minus, batalkan transaksi
        IF EXISTS (SELECT 1 FROM bahan_baku WHERE stok < 0) THEN
            RAISE EXCEPTION 'Gagal Lunas! Stok bahan baku tidak mencukupi untuk pesanan ini.';
        END IF;
        
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_potong_stok_lunas() OWNER TO postgres;

--
-- TOC entry 248 (class 1255 OID 19184)
-- Name: fn_potong_stok_otomatis(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_potong_stok_otomatis() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    r_item RECORD;
    r_bahan RECORD;
BEGIN
    -- Hanya berjalan jika status berubah menjadi 'Lunas'
    -- Dan pastikan sebelumnya statusnya BUKAN 'Lunas' (agar tidak potong stok dua kali)
    IF (NEW.status_bayar = 'Lunas' AND (OLD.status_bayar IS NULL OR OLD.status_bayar <> 'Lunas')) THEN
        
        -- Loop setiap menu yang ada di detail pesanan tersebut
        FOR r_item IN (SELECT menu_id, jumlah_menu FROM detail_pesanan WHERE pesanan_id = NEW.pesanan_id) LOOP
            
            -- Loop setiap bahan baku yang ada di resep menu tersebut
            FOR r_bahan IN (SELECT bahan_id, jumlah_bahan FROM resep WHERE menu_id = r_item.menu_id) LOOP
                
                -- Update stok bahan baku (Stok sekarang - (jumlah di resep * jumlah yang dipesan))
                UPDATE bahan_baku 
                SET stok = stok - (r_bahan.jumlah_bahan * r_item.jumlah_menu)
                WHERE bahan_id = r_bahan.bahan_id;
                
            END LOOP;
        END LOOP;
        
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_potong_stok_otomatis() OWNER TO postgres;

--
-- TOC entry 235 (class 1255 OID 19164)
-- Name: fn_sync_total_harga(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_sync_total_harga() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    target_id VARCHAR;
BEGIN
    IF (TG_OP = 'DELETE') THEN
        target_id := OLD.pesanan_id;
    ELSE
        target_id := NEW.pesanan_id;
    END IF;

    UPDATE pesanan
    SET total_harga = COALESCE((SELECT SUM(subtotal) FROM detail_pesanan WHERE pesanan_id = target_id), 0)
    WHERE pesanan_id = target_id;

    RETURN NULL;
END;
$$;


ALTER FUNCTION public.fn_sync_total_harga() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 227 (class 1259 OID 19049)
-- Name: bahan_baku; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bahan_baku (
    bahan_id character varying(10) NOT NULL,
    nama_bahan character varying(100) NOT NULL,
    satuan character varying(20) NOT NULL,
    stok numeric(10,2) DEFAULT 0,
    staff_supp_id character varying(10),
    harga_per_unit numeric(15,2) DEFAULT 0,
    CONSTRAINT bahan_baku_harga_per_unit_check CHECK ((harga_per_unit >= (0)::numeric)),
    CONSTRAINT bahan_baku_satuan_check CHECK (((satuan)::text = ANY ((ARRAY['gram'::character varying, 'ml'::character varying, 'pcs'::character varying, 'kg'::character varying, 'liter'::character varying])::text[]))),
    CONSTRAINT bahan_baku_stok_check CHECK ((stok >= (0)::numeric))
);


ALTER TABLE public.bahan_baku OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 19103)
-- Name: detail_pesanan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detail_pesanan (
    pesanan_id character varying(15) NOT NULL,
    menu_id character varying(10) NOT NULL,
    jumlah_menu integer NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    subtotal_modal numeric(15,2) DEFAULT 0,
    CONSTRAINT detail_pesanan_jumlah_menu_check CHECK ((jumlah_menu > 0)),
    CONSTRAINT detail_pesanan_subtotal_check CHECK ((subtotal >= (0)::numeric))
);


ALTER TABLE public.detail_pesanan OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 19033)
-- Name: menu; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.menu (
    menu_id character varying(10) NOT NULL,
    nama_menu character varying(100) NOT NULL,
    harga numeric(15,2) NOT NULL,
    deskripsi text,
    staff_prod_id character varying(10),
    CONSTRAINT menu_harga_check CHECK ((harga > (0)::numeric))
);


ALTER TABLE public.menu OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 19023)
-- Name: pelanggan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pelanggan (
    pelanggan_id character varying(10) NOT NULL,
    nama character varying(100) NOT NULL,
    no_handphone character varying(15) NOT NULL
);


ALTER TABLE public.pelanggan OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 19124)
-- Name: pembayaran; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pembayaran (
    pembayaran_id character varying(15) NOT NULL,
    tanggal date NOT NULL,
    metode character varying(20) NOT NULL,
    status_bayar character varying(20) DEFAULT 'Pending'::character varying,
    pesanan_id character varying(15),
    staff_fin_id character varying(10),
    CONSTRAINT pembayaran_metode_check CHECK (((metode)::text = ANY ((ARRAY['Cash'::character varying, 'QRIS'::character varying, 'Transfer'::character varying, 'Debit'::character varying])::text[]))),
    CONSTRAINT pembayaran_status_bayar_check CHECK (((status_bayar)::text = ANY ((ARRAY['Lunas'::character varying, 'Gagal'::character varying, 'Pending'::character varying])::text[])))
);


ALTER TABLE public.pembayaran OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 19145)
-- Name: periklanan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.periklanan (
    periklanan_id character varying(10) NOT NULL,
    media_iklan character varying(50) NOT NULL,
    tanggal_mulai date NOT NULL,
    tanggal_selesai date NOT NULL,
    detail text,
    staff_mkt_id character varying(10),
    CONSTRAINT chk_media_iklan CHECK (((media_iklan)::text = ANY ((ARRAY['Instagram Ads'::character varying, 'Facebook Ads'::character varying, 'TikTok Influencer'::character varying, 'Youtube Shorts'::character varying, 'Banner Offline'::character varying])::text[]))),
    CONSTRAINT chk_tanggal_iklan CHECK ((tanggal_selesai >= tanggal_mulai))
);


ALTER TABLE public.periklanan OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 19084)
-- Name: pesanan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pesanan (
    pesanan_id character varying(15) NOT NULL,
    tanggal date DEFAULT CURRENT_TIMESTAMP,
    total_harga numeric(15,2) DEFAULT 0,
    pelanggan_id character varying(10),
    staff_sales_id character varying(10),
    CONSTRAINT pesanan_total_harga_check CHECK ((total_harga >= (0)::numeric))
);


ALTER TABLE public.pesanan OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 19065)
-- Name: resep; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.resep (
    menu_id character varying(10) NOT NULL,
    bahan_id character varying(10) NOT NULL,
    jumlah_bahan numeric(10,2) NOT NULL,
    CONSTRAINT resep_jumlah_bahan_check CHECK ((jumlah_bahan > (0)::numeric))
);


ALTER TABLE public.resep OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 18946)
-- Name: staff; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.staff (
    staff_id character varying(10) NOT NULL,
    nama character varying(100) NOT NULL,
    no_handphone character varying(15) NOT NULL,
    email character varying(100),
    password character varying(255) DEFAULT 'temporary123'::character varying NOT NULL,
    CONSTRAINT staff_email_check CHECK (((email)::text ~~ '%@%.%'::text)),
    CONSTRAINT staff_nama_check CHECK ((length((nama)::text) > 2)),
    CONSTRAINT staff_no_handphone_check CHECK (((no_handphone)::text !~~ '%[^0-9]%'::text)),
    CONSTRAINT staff_password_check CHECK ((char_length((password)::text) >= 6))
);


ALTER TABLE public.staff OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 18961)
-- Name: staff_keuangan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.staff_keuangan (
    staff_id character varying(10) NOT NULL,
    level_otoritas integer NOT NULL,
    CONSTRAINT staff_keuangan_level_otoritas_check CHECK (((level_otoritas >= 1) AND (level_otoritas <= 5)))
);


ALTER TABLE public.staff_keuangan OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 18999)
-- Name: staff_marketing; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.staff_marketing (
    staff_id character varying(10) NOT NULL,
    keahlian_marketing character varying(100)
);


ALTER TABLE public.staff_marketing OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 18974)
-- Name: staff_pemasok; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.staff_pemasok (
    staff_id character varying(10) NOT NULL,
    level_otoritas_pembelian character varying(50) NOT NULL
);


ALTER TABLE public.staff_pemasok OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 19010)
-- Name: staff_penjualan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.staff_penjualan (
    staff_id character varying(10) NOT NULL,
    target_penjualan numeric(15,2) DEFAULT 0,
    CONSTRAINT staff_penjualan_target_penjualan_check CHECK ((target_penjualan >= (0)::numeric))
);


ALTER TABLE public.staff_penjualan OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 18986)
-- Name: staff_produksi; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.staff_produksi (
    staff_id character varying(10) NOT NULL,
    jumlah_produksi integer DEFAULT 0,
    CONSTRAINT staff_produksi_jumlah_produksi_check CHECK ((jumlah_produksi >= 0))
);


ALTER TABLE public.staff_produksi OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 19171)
-- Name: view_laporan_keuntungan; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.view_laporan_keuntungan AS
 SELECT m.nama_menu,
    sum(dp.jumlah_menu) AS total_terjual,
    sum(dp.subtotal) AS total_pendapatan,
    sum(dp.subtotal_modal) AS total_modal,
    sum((dp.subtotal - dp.subtotal_modal)) AS laba_kotor
   FROM (public.detail_pesanan dp
     JOIN public.menu m ON (((dp.menu_id)::text = (m.menu_id)::text)))
  GROUP BY m.nama_menu;


ALTER VIEW public.view_laporan_keuntungan OWNER TO postgres;

--
-- TOC entry 5153 (class 0 OID 19049)
-- Dependencies: 227
-- Data for Name: bahan_baku; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.bahan_baku VALUES ('B001', 'Nasi', 'kg', 48.86, 'STF002', 9000.00);
INSERT INTO public.bahan_baku VALUES ('B002', 'Ayam Suwir', 'kg', 24.52, 'STF002', 35000.00);
INSERT INTO public.bahan_baku VALUES ('B003', 'Bumbu Balado', 'pcs', 39.88, 'STF002', 5000.00);
INSERT INTO public.bahan_baku VALUES ('B011', 'Nori / Pembungkus Nasi', 'pcs', 81.00, 'STF002', 400.00);
INSERT INTO public.bahan_baku VALUES ('B006', 'Telur', 'pcs', 199.00, 'STF002', 2300.00);
INSERT INTO public.bahan_baku VALUES ('B009', 'Bumbu Dasar', 'pcs', 79.90, 'STF002', 1500.00);


--
-- TOC entry 5156 (class 0 OID 19103)
-- Dependencies: 230
-- Data for Name: detail_pesanan; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.detail_pesanan VALUES ('INV001', 'M001', 2, 10000.00, 4040.00);
INSERT INTO public.detail_pesanan VALUES ('INV002', 'M002', 3, 15000.00, 6120.00);
INSERT INTO public.detail_pesanan VALUES ('INV003', 'M001', 1, 5000.00, 2020.00);
INSERT INTO public.detail_pesanan VALUES ('INV004', 'M003', 2, 8000.00, 4180.00);
INSERT INTO public.detail_pesanan VALUES ('INV005', 'M002', 5, 25000.00, 10200.00);
INSERT INTO public.detail_pesanan VALUES ('INV006', 'M001', 2, 10000.00, 4040.00);
INSERT INTO public.detail_pesanan VALUES ('INV007', 'M002', 2, 10000.00, 4080.00);
INSERT INTO public.detail_pesanan VALUES ('INV008', 'M002', 2, 10000.00, 4080.00);


--
-- TOC entry 5152 (class 0 OID 19033)
-- Dependencies: 226
-- Data for Name: menu; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.menu VALUES ('M002', 'Nasi Kepal Ayam Balado', 5000.00, 'Nasi kepal isi ayam suwir sambal balado spesial', 'STF003');
INSERT INTO public.menu VALUES ('M003', 'Nasi Kepal Telur', 4000.00, 'Nasi kepal isi telur suwir', 'STF003');
INSERT INTO public.menu VALUES ('M001', 'Nasi Kepal Ayam Suwir', 5000.00, 'Nasi kepal isi ayam suwir pedas manis', 'STF003');


--
-- TOC entry 5151 (class 0 OID 19023)
-- Dependencies: 225
-- Data for Name: pelanggan; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.pelanggan VALUES ('CUS001', 'Budi Santoso', '081122334455');
INSERT INTO public.pelanggan VALUES ('CUS002', 'Siti Aminah', '081122334456');
INSERT INTO public.pelanggan VALUES ('CUS003', 'Agus Prayogo', '081122334457');
INSERT INTO public.pelanggan VALUES ('CUS004', 'Dewi Lestari', '081122334458');
INSERT INTO public.pelanggan VALUES ('CUS005', 'Rian Hidayat', '081122334459');
INSERT INTO public.pelanggan VALUES ('CUS006', 'Maya Putri', '081122334460');
INSERT INTO public.pelanggan VALUES ('CUS007', 'Dzulfikar Najib', '086677445543');


--
-- TOC entry 5157 (class 0 OID 19124)
-- Dependencies: 231
-- Data for Name: pembayaran; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.pembayaran VALUES ('PAY001', '2025-12-18', 'QRIS', 'Lunas', 'INV001', 'STF001');
INSERT INTO public.pembayaran VALUES ('PAY002', '2025-12-18', 'Cash', 'Lunas', 'INV002', 'STF001');
INSERT INTO public.pembayaran VALUES ('PAY003', '2025-12-18', 'Transfer', 'Lunas', 'INV003', 'STF001');
INSERT INTO public.pembayaran VALUES ('PAY004', '2025-12-18', 'QRIS', 'Lunas', 'INV004', 'STF001');
INSERT INTO public.pembayaran VALUES ('PAY005', '2025-12-18', 'Cash', 'Lunas', 'INV005', 'STF001');
INSERT INTO public.pembayaran VALUES ('PAY006', '2025-12-18', 'Debit', 'Lunas', 'INV006', 'STF001');
INSERT INTO public.pembayaran VALUES ('PAY007', '2025-12-19', 'QRIS', 'Pending', 'INV007', 'STF001');
INSERT INTO public.pembayaran VALUES ('PAY-INV008', '2025-12-20', 'Cash', 'Pending', 'INV008', NULL);


--
-- TOC entry 5158 (class 0 OID 19145)
-- Dependencies: 232
-- Data for Name: periklanan; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.periklanan VALUES ('AD001', 'Instagram Ads', '2025-12-18', '2025-12-25', 'Promo Launching NYAMHAP', 'STF004');
INSERT INTO public.periklanan VALUES ('AD002', 'Facebook Ads', '2025-12-18', '2026-01-01', 'Diskon Nasi Kepal', 'STF004');
INSERT INTO public.periklanan VALUES ('AD003', 'TikTok Influencer', '2025-12-18', '2025-12-21', 'Review Ayam Suwir', 'STF004');


--
-- TOC entry 5155 (class 0 OID 19084)
-- Dependencies: 229
-- Data for Name: pesanan; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.pesanan VALUES ('INV001', '2025-12-18', 10000.00, 'CUS001', 'STF005');
INSERT INTO public.pesanan VALUES ('INV002', '2025-12-18', 15000.00, 'CUS002', 'STF005');
INSERT INTO public.pesanan VALUES ('INV003', '2025-12-18', 5000.00, 'CUS003', 'STF006');
INSERT INTO public.pesanan VALUES ('INV004', '2025-12-18', 8000.00, 'CUS004', 'STF006');
INSERT INTO public.pesanan VALUES ('INV005', '2025-12-18', 25000.00, 'CUS005', 'STF005');
INSERT INTO public.pesanan VALUES ('INV006', '2025-12-18', 10000.00, 'CUS006', 'STF006');
INSERT INTO public.pesanan VALUES ('INV007', '2025-12-18', 10000.00, 'CUS006', 'STF006');
INSERT INTO public.pesanan VALUES ('INV008', '2025-12-20', 10000.00, 'CUS007', 'STF006');


--
-- TOC entry 5154 (class 0 OID 19065)
-- Dependencies: 228
-- Data for Name: resep; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.resep VALUES ('M001', 'B001', 0.06);
INSERT INTO public.resep VALUES ('M001', 'B002', 0.03);
INSERT INTO public.resep VALUES ('M001', 'B009', 0.02);
INSERT INTO public.resep VALUES ('M001', 'B011', 1.00);
INSERT INTO public.resep VALUES ('M002', 'B001', 0.06);
INSERT INTO public.resep VALUES ('M002', 'B002', 0.03);
INSERT INTO public.resep VALUES ('M002', 'B003', 0.01);
INSERT INTO public.resep VALUES ('M002', 'B011', 1.00);
INSERT INTO public.resep VALUES ('M003', 'B001', 0.06);
INSERT INTO public.resep VALUES ('M003', 'B006', 0.50);
INSERT INTO public.resep VALUES ('M003', 'B011', 1.00);


--
-- TOC entry 5145 (class 0 OID 18946)
-- Dependencies: 219
-- Data for Name: staff; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.staff VALUES ('STF001', 'Andi Keuangan', '081234567801', 'andi.fin@nyamhap.com', 'finance123');
INSERT INTO public.staff VALUES ('STF002', 'Budi Pemasok', '081234567802', 'budi.supp@nyamhap.com', 'supplier123');
INSERT INTO public.staff VALUES ('STF003', 'Citra Produksi', '081234567803', 'citra.prod@nyamhap.com', 'production123');
INSERT INTO public.staff VALUES ('STF004', 'Deni Marketing', '081234567804', 'deni.mkt@nyamhap.com', 'marketing123');
INSERT INTO public.staff VALUES ('STF005', 'Eka Penjualan', '081234567805', 'eka.sales@nyamhap.com', 'sales123');
INSERT INTO public.staff VALUES ('STF006', 'Fani Penjualan', '081234567806', 'fani.sales@nyamhap.com', 'sales123');
INSERT INTO public.staff VALUES ('STF007', 'Fikar', '081234567807', 'Fikar@nyamhap.com', 'temporary123');


--
-- TOC entry 5146 (class 0 OID 18961)
-- Dependencies: 220
-- Data for Name: staff_keuangan; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.staff_keuangan VALUES ('STF001', 5);


--
-- TOC entry 5149 (class 0 OID 18999)
-- Dependencies: 223
-- Data for Name: staff_marketing; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.staff_marketing VALUES ('STF004', 'Social Media Ads');


--
-- TOC entry 5147 (class 0 OID 18974)
-- Dependencies: 221
-- Data for Name: staff_pemasok; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.staff_pemasok VALUES ('STF002', 'Full Access');


--
-- TOC entry 5150 (class 0 OID 19010)
-- Dependencies: 224
-- Data for Name: staff_penjualan; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.staff_penjualan VALUES ('STF005', 10000000.00);
INSERT INTO public.staff_penjualan VALUES ('STF006', 8000000.00);


--
-- TOC entry 5148 (class 0 OID 18986)
-- Dependencies: 222
-- Data for Name: staff_produksi; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.staff_produksi VALUES ('STF003', 0);


--
-- TOC entry 4967 (class 2606 OID 19059)
-- Name: bahan_baku bahan_baku_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bahan_baku
    ADD CONSTRAINT bahan_baku_pkey PRIMARY KEY (bahan_id);


--
-- TOC entry 4973 (class 2606 OID 19113)
-- Name: detail_pesanan detail_pesanan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detail_pesanan
    ADD CONSTRAINT detail_pesanan_pkey PRIMARY KEY (pesanan_id, menu_id);


--
-- TOC entry 4965 (class 2606 OID 19043)
-- Name: menu menu_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT menu_pkey PRIMARY KEY (menu_id);


--
-- TOC entry 4961 (class 2606 OID 19032)
-- Name: pelanggan pelanggan_no_handphone_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pelanggan
    ADD CONSTRAINT pelanggan_no_handphone_key UNIQUE (no_handphone);


--
-- TOC entry 4963 (class 2606 OID 19030)
-- Name: pelanggan pelanggan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pelanggan
    ADD CONSTRAINT pelanggan_pkey PRIMARY KEY (pelanggan_id);


--
-- TOC entry 4975 (class 2606 OID 19134)
-- Name: pembayaran pembayaran_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pembayaran
    ADD CONSTRAINT pembayaran_pkey PRIMARY KEY (pembayaran_id);


--
-- TOC entry 4977 (class 2606 OID 19156)
-- Name: periklanan periklanan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.periklanan
    ADD CONSTRAINT periklanan_pkey PRIMARY KEY (periklanan_id);


--
-- TOC entry 4971 (class 2606 OID 19092)
-- Name: pesanan pesanan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pesanan
    ADD CONSTRAINT pesanan_pkey PRIMARY KEY (pesanan_id);


--
-- TOC entry 4969 (class 2606 OID 19073)
-- Name: resep resep_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resep
    ADD CONSTRAINT resep_pkey PRIMARY KEY (menu_id, bahan_id);


--
-- TOC entry 4945 (class 2606 OID 18960)
-- Name: staff staff_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT staff_email_key UNIQUE (email);


--
-- TOC entry 4951 (class 2606 OID 18968)
-- Name: staff_keuangan staff_keuangan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_keuangan
    ADD CONSTRAINT staff_keuangan_pkey PRIMARY KEY (staff_id);


--
-- TOC entry 4957 (class 2606 OID 19004)
-- Name: staff_marketing staff_marketing_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_marketing
    ADD CONSTRAINT staff_marketing_pkey PRIMARY KEY (staff_id);


--
-- TOC entry 4947 (class 2606 OID 18958)
-- Name: staff staff_no_handphone_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT staff_no_handphone_key UNIQUE (no_handphone);


--
-- TOC entry 4953 (class 2606 OID 18980)
-- Name: staff_pemasok staff_pemasok_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_pemasok
    ADD CONSTRAINT staff_pemasok_pkey PRIMARY KEY (staff_id);


--
-- TOC entry 4959 (class 2606 OID 19017)
-- Name: staff_penjualan staff_penjualan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_penjualan
    ADD CONSTRAINT staff_penjualan_pkey PRIMARY KEY (staff_id);


--
-- TOC entry 4949 (class 2606 OID 18956)
-- Name: staff staff_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT staff_pkey PRIMARY KEY (staff_id);


--
-- TOC entry 4955 (class 2606 OID 18993)
-- Name: staff_produksi staff_produksi_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_produksi
    ADD CONSTRAINT staff_produksi_pkey PRIMARY KEY (staff_id);


--
-- TOC entry 4994 (class 2620 OID 19167)
-- Name: detail_pesanan trg_auto_subtotal; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_auto_subtotal BEFORE INSERT OR UPDATE ON public.detail_pesanan FOR EACH ROW EXECUTE FUNCTION public.fn_calculate_subtotal();


--
-- TOC entry 4996 (class 2620 OID 19188)
-- Name: pembayaran trg_pembayaran_lunas_stok; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_pembayaran_lunas_stok AFTER UPDATE ON public.pembayaran FOR EACH ROW EXECUTE FUNCTION public.fn_potong_stok_lunas();


--
-- TOC entry 4995 (class 2620 OID 19165)
-- Name: detail_pesanan trg_update_total_pesanan; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_update_total_pesanan AFTER INSERT OR DELETE OR UPDATE ON public.detail_pesanan FOR EACH ROW EXECUTE FUNCTION public.fn_sync_total_harga();


--
-- TOC entry 4984 (class 2606 OID 19060)
-- Name: bahan_baku bahan_baku_staff_supp_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bahan_baku
    ADD CONSTRAINT bahan_baku_staff_supp_id_fkey FOREIGN KEY (staff_supp_id) REFERENCES public.staff_pemasok(staff_id);


--
-- TOC entry 4989 (class 2606 OID 19119)
-- Name: detail_pesanan detail_pesanan_menu_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detail_pesanan
    ADD CONSTRAINT detail_pesanan_menu_id_fkey FOREIGN KEY (menu_id) REFERENCES public.menu(menu_id);


--
-- TOC entry 4990 (class 2606 OID 19114)
-- Name: detail_pesanan detail_pesanan_pesanan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detail_pesanan
    ADD CONSTRAINT detail_pesanan_pesanan_id_fkey FOREIGN KEY (pesanan_id) REFERENCES public.pesanan(pesanan_id) ON DELETE CASCADE;


--
-- TOC entry 4983 (class 2606 OID 19044)
-- Name: menu menu_staff_prod_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT menu_staff_prod_id_fkey FOREIGN KEY (staff_prod_id) REFERENCES public.staff_produksi(staff_id);


--
-- TOC entry 4991 (class 2606 OID 19135)
-- Name: pembayaran pembayaran_pesanan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pembayaran
    ADD CONSTRAINT pembayaran_pesanan_id_fkey FOREIGN KEY (pesanan_id) REFERENCES public.pesanan(pesanan_id);


--
-- TOC entry 4992 (class 2606 OID 19140)
-- Name: pembayaran pembayaran_staff_fin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pembayaran
    ADD CONSTRAINT pembayaran_staff_fin_id_fkey FOREIGN KEY (staff_fin_id) REFERENCES public.staff_keuangan(staff_id);


--
-- TOC entry 4993 (class 2606 OID 19157)
-- Name: periklanan periklanan_staff_mkt_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.periklanan
    ADD CONSTRAINT periklanan_staff_mkt_id_fkey FOREIGN KEY (staff_mkt_id) REFERENCES public.staff_marketing(staff_id);


--
-- TOC entry 4987 (class 2606 OID 19093)
-- Name: pesanan pesanan_pelanggan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pesanan
    ADD CONSTRAINT pesanan_pelanggan_id_fkey FOREIGN KEY (pelanggan_id) REFERENCES public.pelanggan(pelanggan_id);


--
-- TOC entry 4988 (class 2606 OID 19098)
-- Name: pesanan pesanan_staff_sales_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pesanan
    ADD CONSTRAINT pesanan_staff_sales_id_fkey FOREIGN KEY (staff_sales_id) REFERENCES public.staff_penjualan(staff_id);


--
-- TOC entry 4985 (class 2606 OID 19079)
-- Name: resep resep_bahan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resep
    ADD CONSTRAINT resep_bahan_id_fkey FOREIGN KEY (bahan_id) REFERENCES public.bahan_baku(bahan_id);


--
-- TOC entry 4986 (class 2606 OID 19074)
-- Name: resep resep_menu_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resep
    ADD CONSTRAINT resep_menu_id_fkey FOREIGN KEY (menu_id) REFERENCES public.menu(menu_id) ON DELETE CASCADE;


--
-- TOC entry 4978 (class 2606 OID 18969)
-- Name: staff_keuangan staff_keuangan_staff_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_keuangan
    ADD CONSTRAINT staff_keuangan_staff_id_fkey FOREIGN KEY (staff_id) REFERENCES public.staff(staff_id) ON DELETE CASCADE;


--
-- TOC entry 4981 (class 2606 OID 19005)
-- Name: staff_marketing staff_marketing_staff_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_marketing
    ADD CONSTRAINT staff_marketing_staff_id_fkey FOREIGN KEY (staff_id) REFERENCES public.staff(staff_id) ON DELETE CASCADE;


--
-- TOC entry 4979 (class 2606 OID 18981)
-- Name: staff_pemasok staff_pemasok_staff_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_pemasok
    ADD CONSTRAINT staff_pemasok_staff_id_fkey FOREIGN KEY (staff_id) REFERENCES public.staff(staff_id) ON DELETE CASCADE;


--
-- TOC entry 4982 (class 2606 OID 19018)
-- Name: staff_penjualan staff_penjualan_staff_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_penjualan
    ADD CONSTRAINT staff_penjualan_staff_id_fkey FOREIGN KEY (staff_id) REFERENCES public.staff(staff_id) ON DELETE CASCADE;


--
-- TOC entry 4980 (class 2606 OID 18994)
-- Name: staff_produksi staff_produksi_staff_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff_produksi
    ADD CONSTRAINT staff_produksi_staff_id_fkey FOREIGN KEY (staff_id) REFERENCES public.staff(staff_id) ON DELETE CASCADE;


-- Completed on 2025-12-20 20:02:37

--
-- PostgreSQL database dump complete
--

\unrestrict 9L8qIBSLud1RI35x2tXQApS4ex6TrVGZqVTI06AwbLaZRaFdNjyI07XIthRggcj

