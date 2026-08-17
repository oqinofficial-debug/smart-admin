--
-- PostgreSQL database dump
--

\restrict Iy7YICYrbGcckrUNeQpX24TTZlcvYfENgvJEielR0I5kUoK5yWLXdfJr5i4EXie

-- Dumped from database version 17.10
-- Dumped by pg_dump version 17.10

-- Started on 2026-08-17 22:25:41

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 236 (class 1259 OID 32881)
-- Name: mst_aktivitas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_aktivitas (
    id integer NOT NULL,
    kode character varying(50) NOT NULL,
    nama character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.mst_aktivitas OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 32880)
-- Name: mst_aktivitas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_aktivitas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_aktivitas_id_seq OWNER TO postgres;

--
-- TOC entry 5213 (class 0 OID 0)
-- Dependencies: 235
-- Name: mst_aktivitas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_aktivitas_id_seq OWNED BY public.mst_aktivitas.id;


--
-- TOC entry 226 (class 1259 OID 24692)
-- Name: mst_department; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_department (
    id integer NOT NULL,
    department_code character varying(50) NOT NULL,
    department_name character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.mst_department OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 24691)
-- Name: mst_department_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_department_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_department_id_seq OWNER TO postgres;

--
-- TOC entry 5214 (class 0 OID 0)
-- Dependencies: 225
-- Name: mst_department_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_department_id_seq OWNED BY public.mst_department.id;


--
-- TOC entry 243 (class 1259 OID 33002)
-- Name: mst_import_alias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_import_alias (
    id integer NOT NULL,
    kolom_id integer NOT NULL,
    alias_text character varying(100) NOT NULL
);


ALTER TABLE public.mst_import_alias OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 33005)
-- Name: mst_import_alias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_import_alias_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_import_alias_id_seq OWNER TO postgres;

--
-- TOC entry 5215 (class 0 OID 0)
-- Dependencies: 244
-- Name: mst_import_alias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_import_alias_id_seq OWNED BY public.mst_import_alias.id;


--
-- TOC entry 241 (class 1259 OID 32990)
-- Name: mst_import_kolom; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_import_kolom (
    id integer NOT NULL,
    field_key character varying(50) NOT NULL,
    field_label character varying(100) NOT NULL,
    is_required boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.mst_import_kolom OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 32996)
-- Name: mst_import_kolom_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_import_kolom_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_import_kolom_id_seq OWNER TO postgres;

--
-- TOC entry 5216 (class 0 OID 0)
-- Dependencies: 242
-- Name: mst_import_kolom_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_import_kolom_id_seq OWNED BY public.mst_import_kolom.id;


--
-- TOC entry 248 (class 1259 OID 33053)
-- Name: mst_import_sheet_alias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_import_sheet_alias (
    id integer NOT NULL,
    alias_text character varying(100) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.mst_import_sheet_alias OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 33052)
-- Name: mst_import_sheet_alias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.mst_import_sheet_alias ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mst_import_sheet_alias_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 252 (class 1259 OID 33067)
-- Name: mst_jf; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_jf (
    id integer NOT NULL,
    jf character varying(50) NOT NULL,
    product character varying(500),
    qty numeric,
    bapob character varying(100),
    chip character varying(100),
    customer character varying(100),
    po character varying(100),
    kelompok_produk_id integer,
    status_jf character varying(10) DEFAULT 'AKTIF'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT mst_jf_status_jf_check CHECK (((status_jf)::text = ANY ((ARRAY['AKTIF'::character varying, 'FINAL'::character varying])::text[])))
);


ALTER TABLE public.mst_jf OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 33066)
-- Name: mst_jf_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.mst_jf ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mst_jf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 230 (class 1259 OID 32850)
-- Name: mst_karyawan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_karyawan (
    id integer NOT NULL,
    nik character varying(50) NOT NULL,
    nama character varying(100) NOT NULL,
    status_kepegawaian character varying(20) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_karyawan_status CHECK (((status_kepegawaian)::text = ANY ((ARRAY['BORONG'::character varying, 'HARIAN'::character varying])::text[])))
);


ALTER TABLE public.mst_karyawan OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 32849)
-- Name: mst_karyawan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_karyawan_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_karyawan_id_seq OWNER TO postgres;

--
-- TOC entry 5217 (class 0 OID 0)
-- Dependencies: 229
-- Name: mst_karyawan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_karyawan_id_seq OWNED BY public.mst_karyawan.id;


--
-- TOC entry 250 (class 1259 OID 33060)
-- Name: mst_kelompok_produk; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_kelompok_produk (
    id integer NOT NULL,
    kode character varying(50) NOT NULL,
    nama character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.mst_kelompok_produk OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 33059)
-- Name: mst_kelompok_produk_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.mst_kelompok_produk ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mst_kelompok_produk_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 257 (class 1259 OID 33124)
-- Name: mst_material_raw; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_material_raw (
    id integer NOT NULL,
    kode_material character varying(50) NOT NULL,
    nama_material character varying(150) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.mst_material_raw OWNER TO postgres;

--
-- TOC entry 258 (class 1259 OID 33129)
-- Name: mst_material_raw_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.mst_material_raw ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mst_material_raw_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 220 (class 1259 OID 16404)
-- Name: mst_menu; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_menu (
    id integer NOT NULL,
    parent_id integer DEFAULT 0 NOT NULL,
    menu_code character varying(50) NOT NULL,
    menu_name character varying(100) NOT NULL,
    menu_url character varying(150),
    menu_icon character varying(50) DEFAULT 'circle'::character varying,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.mst_menu OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 16417)
-- Name: mst_menu_access; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_menu_access (
    id integer NOT NULL,
    menu_id integer NOT NULL,
    level smallint NOT NULL,
    can_view boolean DEFAULT true NOT NULL,
    can_input boolean DEFAULT false NOT NULL,
    can_edit boolean DEFAULT false NOT NULL,
    can_delete boolean DEFAULT false NOT NULL,
    CONSTRAINT mst_menu_access_level_check CHECK ((level = ANY (ARRAY[1, 2, 3])))
);


ALTER TABLE public.mst_menu_access OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 16416)
-- Name: mst_menu_access_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_menu_access_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_menu_access_id_seq OWNER TO postgres;

--
-- TOC entry 5218 (class 0 OID 0)
-- Dependencies: 221
-- Name: mst_menu_access_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_menu_access_id_seq OWNED BY public.mst_menu_access.id;


--
-- TOC entry 219 (class 1259 OID 16403)
-- Name: mst_menu_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_menu_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_menu_id_seq OWNER TO postgres;

--
-- TOC entry 5219 (class 0 OID 0)
-- Dependencies: 219
-- Name: mst_menu_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_menu_id_seq OWNED BY public.mst_menu.id;


--
-- TOC entry 234 (class 1259 OID 32871)
-- Name: mst_mesin; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_mesin (
    id integer NOT NULL,
    kode character varying(50) NOT NULL,
    nama character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.mst_mesin OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 32870)
-- Name: mst_mesin_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_mesin_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_mesin_id_seq OWNER TO postgres;

--
-- TOC entry 5220 (class 0 OID 0)
-- Dependencies: 233
-- Name: mst_mesin_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_mesin_id_seq OWNED BY public.mst_mesin.id;


--
-- TOC entry 270 (class 1259 OID 33450)
-- Name: mst_nama_laporan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_nama_laporan (
    id integer NOT NULL,
    department_id integer NOT NULL,
    kode character varying(50) NOT NULL,
    nama character varying(200) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.mst_nama_laporan OWNER TO postgres;

--
-- TOC entry 5221 (class 0 OID 0)
-- Dependencies: 270
-- Name: TABLE mst_nama_laporan; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.mst_nama_laporan IS 'Master identitas laporan yang bisa diimport per departemen. Dipilih user di form Import Data supaya saat diimport ulang dengan nama laporan + periode yang sama, sistem tahu data lama mana yang harus di-replace.';


--
-- TOC entry 269 (class 1259 OID 33449)
-- Name: mst_nama_laporan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_nama_laporan_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_nama_laporan_id_seq OWNER TO postgres;

--
-- TOC entry 5222 (class 0 OID 0)
-- Dependencies: 269
-- Name: mst_nama_laporan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_nama_laporan_id_seq OWNED BY public.mst_nama_laporan.id;


--
-- TOC entry 240 (class 1259 OID 32901)
-- Name: mst_pekerjaan_borong; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_pekerjaan_borong (
    id integer NOT NULL,
    kode character varying(50) NOT NULL,
    nama character varying(200) NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.mst_pekerjaan_borong OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 32900)
-- Name: mst_pekerjaan_borong_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_pekerjaan_borong_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_pekerjaan_borong_id_seq OWNER TO postgres;

--
-- TOC entry 5223 (class 0 OID 0)
-- Dependencies: 239
-- Name: mst_pekerjaan_borong_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_pekerjaan_borong_id_seq OWNED BY public.mst_pekerjaan_borong.id;


--
-- TOC entry 238 (class 1259 OID 32891)
-- Name: mst_proses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_proses (
    id integer NOT NULL,
    kode character varying(50) NOT NULL,
    nama character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.mst_proses OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 32890)
-- Name: mst_proses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_proses_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_proses_id_seq OWNER TO postgres;

--
-- TOC entry 5224 (class 0 OID 0)
-- Dependencies: 237
-- Name: mst_proses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_proses_id_seq OWNED BY public.mst_proses.id;


--
-- TOC entry 232 (class 1259 OID 32861)
-- Name: mst_shift; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_shift (
    id integer NOT NULL,
    kode character varying(20) NOT NULL,
    nama character varying(50) NOT NULL,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.mst_shift OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 32860)
-- Name: mst_shift_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_shift_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_shift_id_seq OWNER TO postgres;

--
-- TOC entry 5225 (class 0 OID 0)
-- Dependencies: 231
-- Name: mst_shift_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_shift_id_seq OWNED BY public.mst_shift.id;


--
-- TOC entry 218 (class 1259 OID 16391)
-- Name: mst_user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_user (
    id integer NOT NULL,
    username character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    fullname character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    last_login timestamp without time zone,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    can_view_all_departments boolean DEFAULT false NOT NULL
);


ALTER TABLE public.mst_user OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 24702)
-- Name: mst_user_department; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_user_department (
    id integer NOT NULL,
    user_id integer NOT NULL,
    department_id integer NOT NULL,
    is_primary boolean DEFAULT false NOT NULL
);


ALTER TABLE public.mst_user_department OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 24701)
-- Name: mst_user_department_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_user_department_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_user_department_id_seq OWNER TO postgres;

--
-- TOC entry 5226 (class 0 OID 0)
-- Dependencies: 227
-- Name: mst_user_department_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_user_department_id_seq OWNED BY public.mst_user_department.id;


--
-- TOC entry 217 (class 1259 OID 16390)
-- Name: mst_user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_user_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_user_id_seq OWNER TO postgres;

--
-- TOC entry 5227 (class 0 OID 0)
-- Dependencies: 217
-- Name: mst_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_user_id_seq OWNED BY public.mst_user.id;


--
-- TOC entry 224 (class 1259 OID 16436)
-- Name: mst_user_menu_access; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mst_user_menu_access (
    id integer NOT NULL,
    user_id integer NOT NULL,
    menu_id integer NOT NULL,
    level smallint DEFAULT 1 NOT NULL,
    CONSTRAINT mst_user_menu_access_level_check CHECK ((level = ANY (ARRAY[0, 1, 2, 3])))
);


ALTER TABLE public.mst_user_menu_access OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16435)
-- Name: mst_user_menu_access_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mst_user_menu_access_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mst_user_menu_access_id_seq OWNER TO postgres;

--
-- TOC entry 5228 (class 0 OID 0)
-- Dependencies: 223
-- Name: mst_user_menu_access_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mst_user_menu_access_id_seq OWNED BY public.mst_user_menu_access.id;


--
-- TOC entry 266 (class 1259 OID 33391)
-- Name: trx_delivery_pemakaian_fg; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trx_delivery_pemakaian_fg (
    id integer NOT NULL,
    delivery_id integer NOT NULL,
    monitoring_id integer NOT NULL,
    qty_pakai numeric NOT NULL,
    inputer_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.trx_delivery_pemakaian_fg OWNER TO postgres;

--
-- TOC entry 5229 (class 0 OID 0)
-- Dependencies: 266
-- Name: TABLE trx_delivery_pemakaian_fg; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.trx_delivery_pemakaian_fg IS 'Pivot antara trx_delivery_record dan trx_monitoring_produksi (baris berstatus FINISH_GOOD_STOK). Sisa stok FG = realisasi_good_qty (atau agg_good_qty kalau belum diedit) dikurangi SUM(qty_pakai) dari tabel ini.';


--
-- TOC entry 265 (class 1259 OID 33390)
-- Name: trx_delivery_pemakaian_fg_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.trx_delivery_pemakaian_fg ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.trx_delivery_pemakaian_fg_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 255 (class 1259 OID 33104)
-- Name: trx_delivery_record; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trx_delivery_record (
    id integer NOT NULL,
    jf_id integer NOT NULL,
    tanggal_kirim date NOT NULL,
    aktual_kirim numeric,
    no_sp character varying(100) NOT NULL,
    jenis_sp character varying(50),
    inputer_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.trx_delivery_record OWNER TO postgres;

--
-- TOC entry 256 (class 1259 OID 33108)
-- Name: trx_delivery_record_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.trx_delivery_record ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.trx_delivery_record_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 246 (class 1259 OID 33027)
-- Name: trx_import_batch; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trx_import_batch (
    id integer NOT NULL,
    nama_file character varying(255) NOT NULL,
    format_file character varying(10) NOT NULL,
    sheet_name character varying(100),
    mode character varying(20) NOT NULL,
    periode character varying(7),
    tanggal_mulai date,
    tanggal_selesai date,
    replace_periode boolean DEFAULT false NOT NULL,
    total_baris integer DEFAULT 0 NOT NULL,
    sukses integer DEFAULT 0 NOT NULL,
    gagal integer DEFAULT 0 NOT NULL,
    dilewati integer DEFAULT 0 NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    user_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    nama_laporan_id integer,
    replace_range boolean DEFAULT false NOT NULL
);


ALTER TABLE public.trx_import_batch OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 33026)
-- Name: trx_import_batch_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.trx_import_batch ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.trx_import_batch_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 254 (class 1259 OID 33085)
-- Name: trx_jf_periode; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trx_jf_periode (
    id integer NOT NULL,
    jf_id integer NOT NULL,
    periode character varying(7) NOT NULL,
    first_seen_at timestamp without time zone DEFAULT now() NOT NULL,
    last_seen_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.trx_jf_periode OWNER TO postgres;

--
-- TOC entry 253 (class 1259 OID 33084)
-- Name: trx_jf_periode_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.trx_jf_periode ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.trx_jf_periode_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 259 (class 1259 OID 33229)
-- Name: trx_laporan_produksi; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trx_laporan_produksi (
    id integer NOT NULL,
    tanggal date NOT NULL,
    department_id integer NOT NULL,
    operator_id integer,
    spv_id integer,
    ll_id integer,
    shift_id integer,
    jf_id integer,
    mesin_id integer,
    kode_aktivitas_id integer,
    proses_id integer,
    pekerjaan_borong_id integer,
    jam_mulai time without time zone,
    jam_selesai time without time zone,
    durasi numeric,
    target_jam numeric,
    input_qty numeric,
    input_pcs numeric,
    input_sheet numeric,
    qc_sampling numeric,
    waste numeric,
    dead numeric,
    error numeric,
    good_qty numeric,
    good_pcs numeric,
    keterangan text,
    is_public boolean DEFAULT false NOT NULL,
    inputer_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    periode character varying(7) GENERATED ALWAYS AS (((lpad((EXTRACT(year FROM tanggal))::text, 4, '0'::text) || '-'::text) || lpad((EXTRACT(month FROM tanggal))::text, 2, '0'::text))) STORED,
    import_batch_id integer,
    nama_laporan_id integer
);


ALTER TABLE public.trx_laporan_produksi OWNER TO postgres;

--
-- TOC entry 260 (class 1259 OID 33237)
-- Name: trx_laporan_produksi_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.trx_laporan_produksi_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.trx_laporan_produksi_id_seq OWNER TO postgres;

--
-- TOC entry 5230 (class 0 OID 0)
-- Dependencies: 260
-- Name: trx_laporan_produksi_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.trx_laporan_produksi_id_seq OWNED BY public.trx_laporan_produksi.id;


--
-- TOC entry 262 (class 1259 OID 33308)
-- Name: trx_monitoring_produksi; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trx_monitoring_produksi (
    id integer NOT NULL,
    jf_id integer NOT NULL,
    periode character varying(7) NOT NULL,
    department_id integer NOT NULL,
    proses_id integer NOT NULL,
    agg_input_qty numeric DEFAULT 0 NOT NULL,
    agg_qc_sampling numeric DEFAULT 0 NOT NULL,
    agg_waste numeric DEFAULT 0 NOT NULL,
    agg_dead numeric DEFAULT 0 NOT NULL,
    agg_error numeric DEFAULT 0 NOT NULL,
    agg_good_qty numeric DEFAULT 0 NOT NULL,
    realisasi_input_qty numeric DEFAULT 0 NOT NULL,
    realisasi_qc_sampling numeric DEFAULT 0 NOT NULL,
    realisasi_waste numeric DEFAULT 0 NOT NULL,
    realisasi_dead numeric DEFAULT 0 NOT NULL,
    realisasi_error numeric DEFAULT 0 NOT NULL,
    realisasi_good_qty numeric DEFAULT 0 NOT NULL,
    is_match boolean DEFAULT true NOT NULL,
    status_output character varying(20),
    keterangan text,
    updated_by integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_monitoring_status_output CHECK (((status_output IS NULL) OR ((status_output)::text = ANY ((ARRAY['PROSES_SELANJUTNYA'::character varying, 'WIP_STOK'::character varying, 'FINISH_GOOD_STOK'::character varying])::text[]))))
);


ALTER TABLE public.trx_monitoring_produksi OWNER TO postgres;

--
-- TOC entry 5231 (class 0 OID 0)
-- Dependencies: 262
-- Name: TABLE trx_monitoring_produksi; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.trx_monitoring_produksi IS 'Ringkasan/grouping trx_laporan_produksi per (jf_id, periode, department_id, proses_id). agg_* = hasil SUM otomatis dari import; realisasi_* = angka yang bisa diedit manual user; is_match = true kalau realisasi_* sama persis dengan agg_* saat ini.';


--
-- TOC entry 261 (class 1259 OID 33307)
-- Name: trx_monitoring_produksi_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.trx_monitoring_produksi ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.trx_monitoring_produksi_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 264 (class 1259 OID 33357)
-- Name: trx_pemakaian_material; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trx_pemakaian_material (
    id integer NOT NULL,
    monitoring_id integer NOT NULL,
    jenis_material character varying(10) NOT NULL,
    material_raw_id integer,
    sumber_monitoring_id integer,
    qty_pakai numeric NOT NULL,
    satuan character varying(20),
    keterangan text,
    inputer_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_pemakaian_jenis_material CHECK (((jenis_material)::text = ANY (ARRAY[('RAW'::character varying)::text, ('WIP'::character varying)::text, ('FG'::character varying)::text]))),
    CONSTRAINT chk_pemakaian_material_sumber CHECK (((((jenis_material)::text = 'RAW'::text) AND (material_raw_id IS NOT NULL) AND (sumber_monitoring_id IS NULL)) OR (((jenis_material)::text = ANY (ARRAY['WIP'::text, 'FG'::text])) AND (sumber_monitoring_id IS NOT NULL) AND (material_raw_id IS NULL))))
);


ALTER TABLE public.trx_pemakaian_material OWNER TO postgres;

--
-- TOC entry 5232 (class 0 OID 0)
-- Dependencies: 264
-- Name: TABLE trx_pemakaian_material; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.trx_pemakaian_material IS 'Transaksi pencantolan bahan (RAW atau WIP) ke satu baris trx_monitoring_produksi. Tidak ada tabel stok awal — sisa/stok selalu dihitung on-the-fly dari akumulasi transaksi.';


--
-- TOC entry 263 (class 1259 OID 33356)
-- Name: trx_pemakaian_material_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.trx_pemakaian_material ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.trx_pemakaian_material_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 267 (class 1259 OID 33428)
-- Name: trx_wip_pemakaian; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trx_wip_pemakaian (
    id integer NOT NULL,
    monitoring_id_asal integer NOT NULL,
    monitoring_id_pakai integer NOT NULL,
    qty_pakai numeric NOT NULL,
    inputer_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.trx_wip_pemakaian OWNER TO postgres;

--
-- TOC entry 5233 (class 0 OID 0)
-- Dependencies: 267
-- Name: TABLE trx_wip_pemakaian; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.trx_wip_pemakaian IS 'Pivot antar dua baris trx_monitoring_produksi: monitoring_id_asal berstatus WIP_STOK, monitoring_id_pakai adalah proses berikutnya yang menyerapnya. Sisa stok WIP = realisasi_good_qty (baris asal) dikurangi SUM(qty_pakai) dari tabel ini.';


--
-- TOC entry 268 (class 1259 OID 33434)
-- Name: trx_wip_pemakaian_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.trx_wip_pemakaian ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.trx_wip_pemakaian_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 4797 (class 2604 OID 32884)
-- Name: mst_aktivitas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_aktivitas ALTER COLUMN id SET DEFAULT nextval('public.mst_aktivitas_id_seq'::regclass);


--
-- TOC entry 4787 (class 2604 OID 24695)
-- Name: mst_department id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_department ALTER COLUMN id SET DEFAULT nextval('public.mst_department_id_seq'::regclass);


--
-- TOC entry 4807 (class 2604 OID 33006)
-- Name: mst_import_alias id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_import_alias ALTER COLUMN id SET DEFAULT nextval('public.mst_import_alias_id_seq'::regclass);


--
-- TOC entry 4803 (class 2604 OID 32997)
-- Name: mst_import_kolom id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_import_kolom ALTER COLUMN id SET DEFAULT nextval('public.mst_import_kolom_id_seq'::regclass);


--
-- TOC entry 4791 (class 2604 OID 32853)
-- Name: mst_karyawan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_karyawan ALTER COLUMN id SET DEFAULT nextval('public.mst_karyawan_id_seq'::regclass);


--
-- TOC entry 4775 (class 2604 OID 16407)
-- Name: mst_menu id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_menu ALTER COLUMN id SET DEFAULT nextval('public.mst_menu_id_seq'::regclass);


--
-- TOC entry 4780 (class 2604 OID 16420)
-- Name: mst_menu_access id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_menu_access ALTER COLUMN id SET DEFAULT nextval('public.mst_menu_access_id_seq'::regclass);


--
-- TOC entry 4795 (class 2604 OID 32874)
-- Name: mst_mesin id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_mesin ALTER COLUMN id SET DEFAULT nextval('public.mst_mesin_id_seq'::regclass);


--
-- TOC entry 4847 (class 2604 OID 33453)
-- Name: mst_nama_laporan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_nama_laporan ALTER COLUMN id SET DEFAULT nextval('public.mst_nama_laporan_id_seq'::regclass);


--
-- TOC entry 4801 (class 2604 OID 32904)
-- Name: mst_pekerjaan_borong id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_pekerjaan_borong ALTER COLUMN id SET DEFAULT nextval('public.mst_pekerjaan_borong_id_seq'::regclass);


--
-- TOC entry 4799 (class 2604 OID 32894)
-- Name: mst_proses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_proses ALTER COLUMN id SET DEFAULT nextval('public.mst_proses_id_seq'::regclass);


--
-- TOC entry 4793 (class 2604 OID 32864)
-- Name: mst_shift id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_shift ALTER COLUMN id SET DEFAULT nextval('public.mst_shift_id_seq'::regclass);


--
-- TOC entry 4771 (class 2604 OID 16394)
-- Name: mst_user id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user ALTER COLUMN id SET DEFAULT nextval('public.mst_user_id_seq'::regclass);


--
-- TOC entry 4789 (class 2604 OID 24705)
-- Name: mst_user_department id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_department ALTER COLUMN id SET DEFAULT nextval('public.mst_user_department_id_seq'::regclass);


--
-- TOC entry 4785 (class 2604 OID 16439)
-- Name: mst_user_menu_access id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_menu_access ALTER COLUMN id SET DEFAULT nextval('public.mst_user_menu_access_id_seq'::regclass);


--
-- TOC entry 4825 (class 2604 OID 33238)
-- Name: trx_laporan_produksi id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi ALTER COLUMN id SET DEFAULT nextval('public.trx_laporan_produksi_id_seq'::regclass);


--
-- TOC entry 5173 (class 0 OID 32881)
-- Dependencies: 236
-- Data for Name: mst_aktivitas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_aktivitas (id, kode, nama, is_active) FROM stdin;
\.


--
-- TOC entry 5163 (class 0 OID 24692)
-- Dependencies: 226
-- Data for Name: mst_department; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_department (id, department_code, department_name, is_active) FROM stdin;
1	DPT-0001	PPR	t
2	DPT-0002	PML	t
3	DPT-0003	PLM	t
4	DPT-0004	PIS-PCB	t
5	DPT-0005	PMC	t
6	DPT-0006	PIS-PME	t
7	DPT-0007	PRS	t
8	DPT-0008	PRN	t
9	DPT-0009	PPP	t
\.


--
-- TOC entry 5180 (class 0 OID 33002)
-- Dependencies: 243
-- Data for Name: mst_import_alias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_import_alias (id, kolom_id, alias_text) FROM stdin;
\.


--
-- TOC entry 5178 (class 0 OID 32990)
-- Dependencies: 241
-- Data for Name: mst_import_kolom; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_import_kolom (id, field_key, field_label, is_required, is_active, sort_order) FROM stdin;
1	tanggal	Tanggal	t	t	10
2	kode_department	Kode Departemen	t	t	20
3	nik_operator	NIK Operator	t	t	30
4	nik_spv	NIK SPV	f	t	40
5	nik_ll	NIK LL / Team Leader	f	t	50
6	kode_shift	Kode Shift	f	t	60
7	kode_jf	Kode JF	f	t	70
8	kode_mesin	Kode Mesin	f	t	80
9	kode_aktivitas	Kode Aktivitas	f	t	90
10	kode_proses	Kode Proses	f	t	100
11	kode_pekerjaan_borong	Kode Pekerjaan Borong	f	t	110
12	jam_mulai	Jam Mulai	f	t	120
13	jam_selesai	Jam Selesai	f	t	130
14	durasi	Durasi	f	t	140
15	target_jam	Target Jam	f	t	150
16	input_qty	Input Qty	f	t	160
17	input_pcs	Input Pcs	f	t	170
18	input_sheet	Input Sheet	f	t	180
19	qc_sampling	QC Sampling	f	t	190
20	waste	Waste	f	t	200
21	dead	Dead	f	t	210
22	error	Error	f	t	220
23	good_qty	Good Qty	f	t	225
24	good_pcs	Good Pcs	f	t	230
25	keterangan	Keterangan	f	t	240
26	is_public	Publik (Y/T)	f	t	250
\.


--
-- TOC entry 5185 (class 0 OID 33053)
-- Dependencies: 248
-- Data for Name: mst_import_sheet_alias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_import_sheet_alias (id, alias_text, created_at) FROM stdin;
1	ENTRY DATA	2026-08-05 07:08:06.415722
\.


--
-- TOC entry 5189 (class 0 OID 33067)
-- Dependencies: 252
-- Data for Name: mst_jf; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_jf (id, jf, product, qty, bapob, chip, customer, po, kelompok_produk_id, status_jf, created_at) FROM stdin;
\.


--
-- TOC entry 5167 (class 0 OID 32850)
-- Dependencies: 230
-- Data for Name: mst_karyawan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_karyawan (id, nik, nama, status_kepegawaian, is_active) FROM stdin;
\.


--
-- TOC entry 5187 (class 0 OID 33060)
-- Dependencies: 250
-- Data for Name: mst_kelompok_produk; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_kelompok_produk (id, kode, nama, is_active) FROM stdin;
1	KLP-0001	Contact Cellular	t
\.


--
-- TOC entry 5194 (class 0 OID 33124)
-- Dependencies: 257
-- Data for Name: mst_material_raw; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_material_raw (id, kode_material, nama_material, is_active, created_at) FROM stdin;
\.


--
-- TOC entry 5157 (class 0 OID 16404)
-- Dependencies: 220
-- Data for Name: mst_menu; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_menu (id, parent_id, menu_code, menu_name, menu_url, menu_icon, sort_order, is_active) FROM stdin;
1	0	dashboard	Dashboard	dashboard	home	1	t
2	0	user	Manajemen User	user	users	2	t
3	0	import	Import Data	import	upload	3	t
5	0	department	Departemen	department	sitemap	100	t
6	0	master_data	Master Data	masterdata	archive	50	t
7	0	karyawan	Karyawan	karyawan	id-badge	51	t
8	0	jf	Master JF	jf	tag	60	t
9	0	delivery	Delivery Record	delivery	truck	61	t
10	0	material_raw	Material RAW	material_raw	cubes	70	t
11	0	material_wip	Material WIP	material_wip	recycle	71	f
13	0	monitoring_produksi	Production Monitoring Report	monitoring_produksi	bar-chart-2	72	t
14	0	kelengkapan_setor	Kelengkapan Setor	kelengkapan-setor	check-circle	73	t
15	0	master_file	Master File	master-file	file-text	15	t
\.


--
-- TOC entry 5159 (class 0 OID 16417)
-- Dependencies: 222
-- Data for Name: mst_menu_access; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_menu_access (id, menu_id, level, can_view, can_input, can_edit, can_delete) FROM stdin;
1	1	1	t	f	f	f
2	1	2	t	f	f	f
3	1	3	t	t	t	t
4	2	3	t	t	t	t
5	3	2	t	t	f	f
6	3	3	t	t	t	t
7	2	1	t	f	f	f
8	2	2	t	t	f	f
9	3	1	t	f	f	f
10	5	1	t	f	f	f
11	5	2	t	t	f	f
12	5	3	t	t	t	t
13	6	1	t	f	f	f
14	6	2	t	t	f	f
15	6	3	t	t	t	t
16	7	1	t	f	f	f
17	7	2	t	t	f	f
18	7	3	t	t	t	t
19	8	1	t	f	f	f
20	8	2	t	t	f	f
21	8	3	t	t	t	t
22	9	1	t	f	f	f
23	9	2	t	t	f	f
24	9	3	t	t	t	t
25	10	1	t	f	f	f
26	10	2	t	t	f	f
27	10	3	t	t	t	t
28	11	1	t	f	f	f
29	11	2	t	t	f	f
30	11	3	t	t	t	t
31	13	1	t	f	f	f
32	13	2	t	t	f	f
33	13	3	t	t	t	t
34	14	1	t	f	f	f
35	14	2	t	f	f	f
36	14	3	t	f	f	f
37	15	1	t	f	f	f
38	15	2	t	t	f	f
39	15	3	t	t	t	t
\.


--
-- TOC entry 5171 (class 0 OID 32871)
-- Dependencies: 234
-- Data for Name: mst_mesin; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_mesin (id, kode, nama, is_active) FROM stdin;
\.


--
-- TOC entry 5207 (class 0 OID 33450)
-- Dependencies: 270
-- Data for Name: mst_nama_laporan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_nama_laporan (id, department_id, kode, nama, is_active, created_at) FROM stdin;
1	1	LAP-0001	LAPORAN CETAK	t	2026-08-15 16:16:31.532482
\.


--
-- TOC entry 5177 (class 0 OID 32901)
-- Dependencies: 240
-- Data for Name: mst_pekerjaan_borong; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_pekerjaan_borong (id, kode, nama, is_active) FROM stdin;
\.


--
-- TOC entry 5175 (class 0 OID 32891)
-- Dependencies: 238
-- Data for Name: mst_proses; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_proses (id, kode, nama, is_active) FROM stdin;
\.


--
-- TOC entry 5169 (class 0 OID 32861)
-- Dependencies: 232
-- Data for Name: mst_shift; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_shift (id, kode, nama, is_active) FROM stdin;
\.


--
-- TOC entry 5155 (class 0 OID 16391)
-- Dependencies: 218
-- Data for Name: mst_user; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_user (id, username, password, fullname, is_active, last_login, created_at, can_view_all_departments) FROM stdin;
1	admin	$2y$10$eCB3b3MFxZs0Hqy2nxBowOvBdlcDmy2.6MZpAJo9D.9sw/rdJNcpS	Administrator	t	2026-08-17 17:05:12	2026-07-09 00:12:03.765824	t
6	ppr	$2y$10$IzmDtrrbNTt67G9ESBbc.eO1jY57bKoiDw1iWSrGSrDcpVp8wmBuy	ppr	t	2026-08-15 11:17:56	2026-08-15 16:16:55.719111	f
\.


--
-- TOC entry 5165 (class 0 OID 24702)
-- Dependencies: 228
-- Data for Name: mst_user_department; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_user_department (id, user_id, department_id, is_primary) FROM stdin;
4	6	1	f
\.


--
-- TOC entry 5161 (class 0 OID 16436)
-- Dependencies: 224
-- Data for Name: mst_user_menu_access; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mst_user_menu_access (id, user_id, menu_id, level) FROM stdin;
36	6	2	0
37	6	3	2
38	6	15	0
39	6	6	0
40	6	7	0
41	6	8	0
42	6	9	0
9	1	1	3
43	6	10	0
44	6	11	0
45	6	13	0
46	6	14	0
47	6	5	0
8	1	2	3
7	1	3	3
35	1	15	3
16	1	6	3
17	1	7	3
18	1	8	3
19	1	9	3
20	1	10	3
21	1	11	3
22	1	13	3
23	1	14	3
13	1	5	3
\.


--
-- TOC entry 5203 (class 0 OID 33391)
-- Dependencies: 266
-- Data for Name: trx_delivery_pemakaian_fg; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trx_delivery_pemakaian_fg (id, delivery_id, monitoring_id, qty_pakai, inputer_id, created_at) FROM stdin;
\.


--
-- TOC entry 5192 (class 0 OID 33104)
-- Dependencies: 255
-- Data for Name: trx_delivery_record; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trx_delivery_record (id, jf_id, tanggal_kirim, aktual_kirim, no_sp, jenis_sp, inputer_id, created_at) FROM stdin;
\.


--
-- TOC entry 5183 (class 0 OID 33027)
-- Dependencies: 246
-- Data for Name: trx_import_batch; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trx_import_batch (id, nama_file, format_file, sheet_name, mode, periode, tanggal_mulai, tanggal_selesai, replace_periode, total_baris, sukses, gagal, dilewati, status, user_id, created_at, nama_laporan_id, replace_range) FROM stdin;
\.


--
-- TOC entry 5191 (class 0 OID 33085)
-- Dependencies: 254
-- Data for Name: trx_jf_periode; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trx_jf_periode (id, jf_id, periode, first_seen_at, last_seen_at) FROM stdin;
\.


--
-- TOC entry 5196 (class 0 OID 33229)
-- Dependencies: 259
-- Data for Name: trx_laporan_produksi; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trx_laporan_produksi (id, tanggal, department_id, operator_id, spv_id, ll_id, shift_id, jf_id, mesin_id, kode_aktivitas_id, proses_id, pekerjaan_borong_id, jam_mulai, jam_selesai, durasi, target_jam, input_qty, input_pcs, input_sheet, qc_sampling, waste, dead, error, good_qty, good_pcs, keterangan, is_public, inputer_id, created_at, import_batch_id, nama_laporan_id) FROM stdin;
\.


--
-- TOC entry 5199 (class 0 OID 33308)
-- Dependencies: 262
-- Data for Name: trx_monitoring_produksi; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trx_monitoring_produksi (id, jf_id, periode, department_id, proses_id, agg_input_qty, agg_qc_sampling, agg_waste, agg_dead, agg_error, agg_good_qty, realisasi_input_qty, realisasi_qc_sampling, realisasi_waste, realisasi_dead, realisasi_error, realisasi_good_qty, is_match, status_output, keterangan, updated_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 5201 (class 0 OID 33357)
-- Dependencies: 264
-- Data for Name: trx_pemakaian_material; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trx_pemakaian_material (id, monitoring_id, jenis_material, material_raw_id, sumber_monitoring_id, qty_pakai, satuan, keterangan, inputer_id, created_at) FROM stdin;
\.


--
-- TOC entry 5204 (class 0 OID 33428)
-- Dependencies: 267
-- Data for Name: trx_wip_pemakaian; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.trx_wip_pemakaian (id, monitoring_id_asal, monitoring_id_pakai, qty_pakai, inputer_id, created_at) FROM stdin;
\.


--
-- TOC entry 5234 (class 0 OID 0)
-- Dependencies: 235
-- Name: mst_aktivitas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_aktivitas_id_seq', 1, false);


--
-- TOC entry 5235 (class 0 OID 0)
-- Dependencies: 225
-- Name: mst_department_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_department_id_seq', 9, true);


--
-- TOC entry 5236 (class 0 OID 0)
-- Dependencies: 244
-- Name: mst_import_alias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_import_alias_id_seq', 60, true);


--
-- TOC entry 5237 (class 0 OID 0)
-- Dependencies: 242
-- Name: mst_import_kolom_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_import_kolom_id_seq', 26, true);


--
-- TOC entry 5238 (class 0 OID 0)
-- Dependencies: 247
-- Name: mst_import_sheet_alias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_import_sheet_alias_id_seq', 1, true);


--
-- TOC entry 5239 (class 0 OID 0)
-- Dependencies: 251
-- Name: mst_jf_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_jf_id_seq', 1, false);


--
-- TOC entry 5240 (class 0 OID 0)
-- Dependencies: 229
-- Name: mst_karyawan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_karyawan_id_seq', 1, false);


--
-- TOC entry 5241 (class 0 OID 0)
-- Dependencies: 249
-- Name: mst_kelompok_produk_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_kelompok_produk_id_seq', 1, true);


--
-- TOC entry 5242 (class 0 OID 0)
-- Dependencies: 258
-- Name: mst_material_raw_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_material_raw_id_seq', 1, false);


--
-- TOC entry 5243 (class 0 OID 0)
-- Dependencies: 221
-- Name: mst_menu_access_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_menu_access_id_seq', 39, true);


--
-- TOC entry 5244 (class 0 OID 0)
-- Dependencies: 219
-- Name: mst_menu_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_menu_id_seq', 15, true);


--
-- TOC entry 5245 (class 0 OID 0)
-- Dependencies: 233
-- Name: mst_mesin_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_mesin_id_seq', 1, false);


--
-- TOC entry 5246 (class 0 OID 0)
-- Dependencies: 269
-- Name: mst_nama_laporan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_nama_laporan_id_seq', 1, true);


--
-- TOC entry 5247 (class 0 OID 0)
-- Dependencies: 239
-- Name: mst_pekerjaan_borong_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_pekerjaan_borong_id_seq', 1, false);


--
-- TOC entry 5248 (class 0 OID 0)
-- Dependencies: 237
-- Name: mst_proses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_proses_id_seq', 1, false);


--
-- TOC entry 5249 (class 0 OID 0)
-- Dependencies: 231
-- Name: mst_shift_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_shift_id_seq', 1, false);


--
-- TOC entry 5250 (class 0 OID 0)
-- Dependencies: 227
-- Name: mst_user_department_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_user_department_id_seq', 4, true);


--
-- TOC entry 5251 (class 0 OID 0)
-- Dependencies: 217
-- Name: mst_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_user_id_seq', 6, true);


--
-- TOC entry 5252 (class 0 OID 0)
-- Dependencies: 223
-- Name: mst_user_menu_access_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mst_user_menu_access_id_seq', 47, true);


--
-- TOC entry 5253 (class 0 OID 0)
-- Dependencies: 265
-- Name: trx_delivery_pemakaian_fg_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trx_delivery_pemakaian_fg_id_seq', 1, false);


--
-- TOC entry 5254 (class 0 OID 0)
-- Dependencies: 256
-- Name: trx_delivery_record_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trx_delivery_record_id_seq', 1, false);


--
-- TOC entry 5255 (class 0 OID 0)
-- Dependencies: 245
-- Name: trx_import_batch_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trx_import_batch_id_seq', 1, false);


--
-- TOC entry 5256 (class 0 OID 0)
-- Dependencies: 253
-- Name: trx_jf_periode_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trx_jf_periode_id_seq', 1, false);


--
-- TOC entry 5257 (class 0 OID 0)
-- Dependencies: 260
-- Name: trx_laporan_produksi_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trx_laporan_produksi_id_seq', 1, false);


--
-- TOC entry 5258 (class 0 OID 0)
-- Dependencies: 261
-- Name: trx_monitoring_produksi_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trx_monitoring_produksi_id_seq', 1, false);


--
-- TOC entry 5259 (class 0 OID 0)
-- Dependencies: 263
-- Name: trx_pemakaian_material_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trx_pemakaian_material_id_seq', 1, false);


--
-- TOC entry 5260 (class 0 OID 0)
-- Dependencies: 268
-- Name: trx_wip_pemakaian_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.trx_wip_pemakaian_id_seq', 1, false);


--
-- TOC entry 4897 (class 2606 OID 32887)
-- Name: mst_aktivitas mst_aktivitas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_aktivitas
    ADD CONSTRAINT mst_aktivitas_pkey PRIMARY KEY (id);


--
-- TOC entry 4874 (class 2606 OID 24698)
-- Name: mst_department mst_department_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_department
    ADD CONSTRAINT mst_department_pkey PRIMARY KEY (id);


--
-- TOC entry 4913 (class 2606 OID 33008)
-- Name: mst_import_alias mst_import_alias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_import_alias
    ADD CONSTRAINT mst_import_alias_pkey PRIMARY KEY (id);


--
-- TOC entry 4909 (class 2606 OID 32999)
-- Name: mst_import_kolom mst_import_kolom_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_import_kolom
    ADD CONSTRAINT mst_import_kolom_pkey PRIMARY KEY (id);


--
-- TOC entry 4919 (class 2606 OID 33058)
-- Name: mst_import_sheet_alias mst_import_sheet_alias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_import_sheet_alias
    ADD CONSTRAINT mst_import_sheet_alias_pkey PRIMARY KEY (id);


--
-- TOC entry 4923 (class 2606 OID 33076)
-- Name: mst_jf mst_jf_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_jf
    ADD CONSTRAINT mst_jf_pkey PRIMARY KEY (id);


--
-- TOC entry 4885 (class 2606 OID 32857)
-- Name: mst_karyawan mst_karyawan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_karyawan
    ADD CONSTRAINT mst_karyawan_pkey PRIMARY KEY (id);


--
-- TOC entry 4921 (class 2606 OID 33065)
-- Name: mst_kelompok_produk mst_kelompok_produk_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_kelompok_produk
    ADD CONSTRAINT mst_kelompok_produk_pkey PRIMARY KEY (id);


--
-- TOC entry 4936 (class 2606 OID 33131)
-- Name: mst_material_raw mst_material_raw_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_material_raw
    ADD CONSTRAINT mst_material_raw_pkey PRIMARY KEY (id);


--
-- TOC entry 4866 (class 2606 OID 16429)
-- Name: mst_menu_access mst_menu_access_menu_id_level_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_menu_access
    ADD CONSTRAINT mst_menu_access_menu_id_level_key UNIQUE (menu_id, level);


--
-- TOC entry 4868 (class 2606 OID 16427)
-- Name: mst_menu_access mst_menu_access_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_menu_access
    ADD CONSTRAINT mst_menu_access_pkey PRIMARY KEY (id);


--
-- TOC entry 4862 (class 2606 OID 16415)
-- Name: mst_menu mst_menu_menu_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_menu
    ADD CONSTRAINT mst_menu_menu_code_key UNIQUE (menu_code);


--
-- TOC entry 4864 (class 2606 OID 16413)
-- Name: mst_menu mst_menu_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_menu
    ADD CONSTRAINT mst_menu_pkey PRIMARY KEY (id);


--
-- TOC entry 4893 (class 2606 OID 32877)
-- Name: mst_mesin mst_mesin_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_mesin
    ADD CONSTRAINT mst_mesin_pkey PRIMARY KEY (id);


--
-- TOC entry 4967 (class 2606 OID 33457)
-- Name: mst_nama_laporan mst_nama_laporan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_nama_laporan
    ADD CONSTRAINT mst_nama_laporan_pkey PRIMARY KEY (id);


--
-- TOC entry 4905 (class 2606 OID 32907)
-- Name: mst_pekerjaan_borong mst_pekerjaan_borong_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_pekerjaan_borong
    ADD CONSTRAINT mst_pekerjaan_borong_pkey PRIMARY KEY (id);


--
-- TOC entry 4901 (class 2606 OID 32897)
-- Name: mst_proses mst_proses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_proses
    ADD CONSTRAINT mst_proses_pkey PRIMARY KEY (id);


--
-- TOC entry 4889 (class 2606 OID 32867)
-- Name: mst_shift mst_shift_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_shift
    ADD CONSTRAINT mst_shift_pkey PRIMARY KEY (id);


--
-- TOC entry 4880 (class 2606 OID 24708)
-- Name: mst_user_department mst_user_department_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_department
    ADD CONSTRAINT mst_user_department_pkey PRIMARY KEY (id);


--
-- TOC entry 4870 (class 2606 OID 16445)
-- Name: mst_user_menu_access mst_user_menu_access_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_menu_access
    ADD CONSTRAINT mst_user_menu_access_pkey PRIMARY KEY (id);


--
-- TOC entry 4872 (class 2606 OID 16447)
-- Name: mst_user_menu_access mst_user_menu_access_user_id_menu_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_menu_access
    ADD CONSTRAINT mst_user_menu_access_user_id_menu_id_key UNIQUE (user_id, menu_id);


--
-- TOC entry 4858 (class 2606 OID 16400)
-- Name: mst_user mst_user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user
    ADD CONSTRAINT mst_user_pkey PRIMARY KEY (id);


--
-- TOC entry 4860 (class 2606 OID 16402)
-- Name: mst_user mst_user_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user
    ADD CONSTRAINT mst_user_username_key UNIQUE (username);


--
-- TOC entry 4962 (class 2606 OID 33398)
-- Name: trx_delivery_pemakaian_fg trx_delivery_pemakaian_fg_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_delivery_pemakaian_fg
    ADD CONSTRAINT trx_delivery_pemakaian_fg_pkey PRIMARY KEY (id);


--
-- TOC entry 4934 (class 2606 OID 33110)
-- Name: trx_delivery_record trx_delivery_record_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_delivery_record
    ADD CONSTRAINT trx_delivery_record_pkey PRIMARY KEY (id);


--
-- TOC entry 4917 (class 2606 OID 33038)
-- Name: trx_import_batch trx_import_batch_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_import_batch
    ADD CONSTRAINT trx_import_batch_pkey PRIMARY KEY (id);


--
-- TOC entry 4927 (class 2606 OID 33091)
-- Name: trx_jf_periode trx_jf_periode_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_jf_periode
    ADD CONSTRAINT trx_jf_periode_pkey PRIMARY KEY (id);


--
-- TOC entry 4946 (class 2606 OID 33240)
-- Name: trx_laporan_produksi trx_laporan_produksi_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_pkey PRIMARY KEY (id);


--
-- TOC entry 4951 (class 2606 OID 33330)
-- Name: trx_monitoring_produksi trx_monitoring_produksi_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_monitoring_produksi
    ADD CONSTRAINT trx_monitoring_produksi_pkey PRIMARY KEY (id);


--
-- TOC entry 4958 (class 2606 OID 33366)
-- Name: trx_pemakaian_material trx_pemakaian_material_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_pemakaian_material
    ADD CONSTRAINT trx_pemakaian_material_pkey PRIMARY KEY (id);


--
-- TOC entry 4964 (class 2606 OID 33436)
-- Name: trx_wip_pemakaian trx_wip_pemakaian_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_wip_pemakaian
    ADD CONSTRAINT trx_wip_pemakaian_pkey PRIMARY KEY (id);


--
-- TOC entry 4899 (class 2606 OID 32889)
-- Name: mst_aktivitas uq_aktivitas_kode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_aktivitas
    ADD CONSTRAINT uq_aktivitas_kode UNIQUE (kode);


--
-- TOC entry 4907 (class 2606 OID 32909)
-- Name: mst_pekerjaan_borong uq_borong_kode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_pekerjaan_borong
    ADD CONSTRAINT uq_borong_kode UNIQUE (kode);


--
-- TOC entry 4876 (class 2606 OID 24700)
-- Name: mst_department uq_department_code; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_department
    ADD CONSTRAINT uq_department_code UNIQUE (department_code);


--
-- TOC entry 4911 (class 2606 OID 33001)
-- Name: mst_import_kolom uq_import_kolom_field_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_import_kolom
    ADD CONSTRAINT uq_import_kolom_field_key UNIQUE (field_key);


--
-- TOC entry 4887 (class 2606 OID 32859)
-- Name: mst_karyawan uq_karyawan_nik; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_karyawan
    ADD CONSTRAINT uq_karyawan_nik UNIQUE (nik);


--
-- TOC entry 4895 (class 2606 OID 32879)
-- Name: mst_mesin uq_mesin_kode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_mesin
    ADD CONSTRAINT uq_mesin_kode UNIQUE (kode);


--
-- TOC entry 4925 (class 2606 OID 33078)
-- Name: mst_jf uq_mst_jf_jf; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_jf
    ADD CONSTRAINT uq_mst_jf_jf UNIQUE (jf);


--
-- TOC entry 4969 (class 2606 OID 33459)
-- Name: mst_nama_laporan uq_nama_laporan_dept_kode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_nama_laporan
    ADD CONSTRAINT uq_nama_laporan_dept_kode UNIQUE (department_id, kode);


--
-- TOC entry 4903 (class 2606 OID 32899)
-- Name: mst_proses uq_proses_kode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_proses
    ADD CONSTRAINT uq_proses_kode UNIQUE (kode);


--
-- TOC entry 4891 (class 2606 OID 32869)
-- Name: mst_shift uq_shift_kode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_shift
    ADD CONSTRAINT uq_shift_kode UNIQUE (kode);


--
-- TOC entry 4929 (class 2606 OID 33093)
-- Name: trx_jf_periode uq_trx_jf_periode; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_jf_periode
    ADD CONSTRAINT uq_trx_jf_periode UNIQUE (jf_id, periode);


--
-- TOC entry 4882 (class 2606 OID 24710)
-- Name: mst_user_department uq_user_department; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_department
    ADD CONSTRAINT uq_user_department UNIQUE (user_id, department_id);


--
-- TOC entry 4953 (class 2606 OID 33332)
-- Name: trx_monitoring_produksi ux_monitoring_produksi_grain; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_monitoring_produksi
    ADD CONSTRAINT ux_monitoring_produksi_grain UNIQUE (jf_id, periode, department_id, proses_id);


--
-- TOC entry 4938 (class 1259 OID 33471)
-- Name: idx_laporan_produksi_laporan_periode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_laporan_produksi_laporan_periode ON public.trx_laporan_produksi USING btree (nama_laporan_id, periode);


--
-- TOC entry 4939 (class 1259 OID 33472)
-- Name: idx_laporan_produksi_laporan_tanggal; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_laporan_produksi_laporan_tanggal ON public.trx_laporan_produksi USING btree (nama_laporan_id, tanggal);


--
-- TOC entry 4965 (class 1259 OID 33465)
-- Name: idx_nama_laporan_department; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_nama_laporan_department ON public.mst_nama_laporan USING btree (department_id);


--
-- TOC entry 4930 (class 1259 OID 33416)
-- Name: ix_delivery_aktual_kirim; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_delivery_aktual_kirim ON public.trx_delivery_record USING btree (aktual_kirim);


--
-- TOC entry 4931 (class 1259 OID 33121)
-- Name: ix_delivery_jf; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_delivery_jf ON public.trx_delivery_record USING btree (jf_id);


--
-- TOC entry 4959 (class 1259 OID 33414)
-- Name: ix_delivery_pemakaian_fg_delivery; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_delivery_pemakaian_fg_delivery ON public.trx_delivery_pemakaian_fg USING btree (delivery_id);


--
-- TOC entry 4960 (class 1259 OID 33415)
-- Name: ix_delivery_pemakaian_fg_monitoring; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_delivery_pemakaian_fg_monitoring ON public.trx_delivery_pemakaian_fg USING btree (monitoring_id);


--
-- TOC entry 4932 (class 1259 OID 33123)
-- Name: ix_delivery_tanggal_kirim; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_delivery_tanggal_kirim ON public.trx_delivery_record USING btree (tanggal_kirim);


--
-- TOC entry 4915 (class 1259 OID 33044)
-- Name: ix_import_batch_periode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_import_batch_periode ON public.trx_import_batch USING btree (periode);


--
-- TOC entry 4940 (class 1259 OID 33241)
-- Name: ix_laporan_department; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_laporan_department ON public.trx_laporan_produksi USING btree (department_id);


--
-- TOC entry 4941 (class 1259 OID 33242)
-- Name: ix_laporan_import_batch; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_laporan_import_batch ON public.trx_laporan_produksi USING btree (import_batch_id);


--
-- TOC entry 4942 (class 1259 OID 33243)
-- Name: ix_laporan_operator; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_laporan_operator ON public.trx_laporan_produksi USING btree (operator_id);


--
-- TOC entry 4943 (class 1259 OID 33244)
-- Name: ix_laporan_periode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_laporan_periode ON public.trx_laporan_produksi USING btree (periode);


--
-- TOC entry 4944 (class 1259 OID 33245)
-- Name: ix_laporan_tanggal; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_laporan_tanggal ON public.trx_laporan_produksi USING btree (tanggal);


--
-- TOC entry 4947 (class 1259 OID 33354)
-- Name: ix_monitoring_produksi_department; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_monitoring_produksi_department ON public.trx_monitoring_produksi USING btree (department_id);


--
-- TOC entry 4948 (class 1259 OID 33353)
-- Name: ix_monitoring_produksi_periode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_monitoring_produksi_periode ON public.trx_monitoring_produksi USING btree (periode);


--
-- TOC entry 4949 (class 1259 OID 33355)
-- Name: ix_monitoring_produksi_status_output; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_monitoring_produksi_status_output ON public.trx_monitoring_produksi USING btree (status_output);


--
-- TOC entry 4954 (class 1259 OID 33387)
-- Name: ix_pemakaian_material_monitoring; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_pemakaian_material_monitoring ON public.trx_pemakaian_material USING btree (monitoring_id);


--
-- TOC entry 4955 (class 1259 OID 33389)
-- Name: ix_pemakaian_material_raw; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_pemakaian_material_raw ON public.trx_pemakaian_material USING btree (material_raw_id);


--
-- TOC entry 4956 (class 1259 OID 33388)
-- Name: ix_pemakaian_material_sumber; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_pemakaian_material_sumber ON public.trx_pemakaian_material USING btree (sumber_monitoring_id);


--
-- TOC entry 4877 (class 1259 OID 24723)
-- Name: ix_user_department_department; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_user_department_department ON public.mst_user_department USING btree (department_id);


--
-- TOC entry 4878 (class 1259 OID 24722)
-- Name: ix_user_department_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_user_department_user ON public.mst_user_department USING btree (user_id);


--
-- TOC entry 4914 (class 1259 OID 33014)
-- Name: ux_import_alias_text; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX ux_import_alias_text ON public.mst_import_alias USING btree (lower((alias_text)::text));


--
-- TOC entry 4937 (class 1259 OID 33132)
-- Name: ux_material_raw_kode; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX ux_material_raw_kode ON public.mst_material_raw USING btree (lower((kode_material)::text));


--
-- TOC entry 4883 (class 1259 OID 24721)
-- Name: ux_user_department_primary; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX ux_user_department_primary ON public.mst_user_department USING btree (user_id) WHERE (is_primary = true);


--
-- TOC entry 4975 (class 2606 OID 33009)
-- Name: mst_import_alias mst_import_alias_kolom_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_import_alias
    ADD CONSTRAINT mst_import_alias_kolom_id_fkey FOREIGN KEY (kolom_id) REFERENCES public.mst_import_kolom(id) ON DELETE CASCADE;


--
-- TOC entry 4978 (class 2606 OID 33079)
-- Name: mst_jf mst_jf_kelompok_produk_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_jf
    ADD CONSTRAINT mst_jf_kelompok_produk_id_fkey FOREIGN KEY (kelompok_produk_id) REFERENCES public.mst_kelompok_produk(id) ON DELETE RESTRICT;


--
-- TOC entry 4970 (class 2606 OID 16430)
-- Name: mst_menu_access mst_menu_access_menu_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_menu_access
    ADD CONSTRAINT mst_menu_access_menu_id_fkey FOREIGN KEY (menu_id) REFERENCES public.mst_menu(id) ON DELETE CASCADE;


--
-- TOC entry 5008 (class 2606 OID 33460)
-- Name: mst_nama_laporan mst_nama_laporan_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_nama_laporan
    ADD CONSTRAINT mst_nama_laporan_department_id_fkey FOREIGN KEY (department_id) REFERENCES public.mst_department(id);


--
-- TOC entry 4973 (class 2606 OID 24716)
-- Name: mst_user_department mst_user_department_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_department
    ADD CONSTRAINT mst_user_department_department_id_fkey FOREIGN KEY (department_id) REFERENCES public.mst_department(id) ON DELETE RESTRICT;


--
-- TOC entry 4974 (class 2606 OID 33479)
-- Name: mst_user_department mst_user_department_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_department
    ADD CONSTRAINT mst_user_department_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.mst_user(id) ON DELETE CASCADE;


--
-- TOC entry 4971 (class 2606 OID 16453)
-- Name: mst_user_menu_access mst_user_menu_access_menu_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_menu_access
    ADD CONSTRAINT mst_user_menu_access_menu_id_fkey FOREIGN KEY (menu_id) REFERENCES public.mst_menu(id) ON DELETE CASCADE;


--
-- TOC entry 4972 (class 2606 OID 16448)
-- Name: mst_user_menu_access mst_user_menu_access_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mst_user_menu_access
    ADD CONSTRAINT mst_user_menu_access_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.mst_user(id) ON DELETE CASCADE;


--
-- TOC entry 5003 (class 2606 OID 33399)
-- Name: trx_delivery_pemakaian_fg trx_delivery_pemakaian_fg_delivery_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_delivery_pemakaian_fg
    ADD CONSTRAINT trx_delivery_pemakaian_fg_delivery_id_fkey FOREIGN KEY (delivery_id) REFERENCES public.trx_delivery_record(id) ON DELETE CASCADE;


--
-- TOC entry 5004 (class 2606 OID 33409)
-- Name: trx_delivery_pemakaian_fg trx_delivery_pemakaian_fg_inputer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_delivery_pemakaian_fg
    ADD CONSTRAINT trx_delivery_pemakaian_fg_inputer_id_fkey FOREIGN KEY (inputer_id) REFERENCES public.mst_user(id);


--
-- TOC entry 5005 (class 2606 OID 33404)
-- Name: trx_delivery_pemakaian_fg trx_delivery_pemakaian_fg_monitoring_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_delivery_pemakaian_fg
    ADD CONSTRAINT trx_delivery_pemakaian_fg_monitoring_id_fkey FOREIGN KEY (monitoring_id) REFERENCES public.trx_monitoring_produksi(id);


--
-- TOC entry 4980 (class 2606 OID 33116)
-- Name: trx_delivery_record trx_delivery_record_inputer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_delivery_record
    ADD CONSTRAINT trx_delivery_record_inputer_id_fkey FOREIGN KEY (inputer_id) REFERENCES public.mst_user(id) ON DELETE SET NULL;


--
-- TOC entry 4981 (class 2606 OID 33111)
-- Name: trx_delivery_record trx_delivery_record_jf_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_delivery_record
    ADD CONSTRAINT trx_delivery_record_jf_id_fkey FOREIGN KEY (jf_id) REFERENCES public.mst_jf(id) ON DELETE RESTRICT;


--
-- TOC entry 4976 (class 2606 OID 33473)
-- Name: trx_import_batch trx_import_batch_nama_laporan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_import_batch
    ADD CONSTRAINT trx_import_batch_nama_laporan_id_fkey FOREIGN KEY (nama_laporan_id) REFERENCES public.mst_nama_laporan(id);


--
-- TOC entry 4977 (class 2606 OID 33039)
-- Name: trx_import_batch trx_import_batch_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_import_batch
    ADD CONSTRAINT trx_import_batch_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.mst_user(id);


--
-- TOC entry 4979 (class 2606 OID 33094)
-- Name: trx_jf_periode trx_jf_periode_jf_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_jf_periode
    ADD CONSTRAINT trx_jf_periode_jf_id_fkey FOREIGN KEY (jf_id) REFERENCES public.mst_jf(id) ON DELETE CASCADE;


--
-- TOC entry 4982 (class 2606 OID 33246)
-- Name: trx_laporan_produksi trx_laporan_produksi_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_department_id_fkey FOREIGN KEY (department_id) REFERENCES public.mst_department(id) ON DELETE RESTRICT;


--
-- TOC entry 4983 (class 2606 OID 33251)
-- Name: trx_laporan_produksi trx_laporan_produksi_import_batch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_import_batch_id_fkey FOREIGN KEY (import_batch_id) REFERENCES public.trx_import_batch(id) ON DELETE SET NULL;


--
-- TOC entry 4984 (class 2606 OID 33256)
-- Name: trx_laporan_produksi trx_laporan_produksi_inputer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_inputer_id_fkey FOREIGN KEY (inputer_id) REFERENCES public.mst_user(id) ON DELETE RESTRICT;


--
-- TOC entry 4985 (class 2606 OID 33261)
-- Name: trx_laporan_produksi trx_laporan_produksi_jf_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_jf_id_fkey FOREIGN KEY (jf_id) REFERENCES public.mst_jf(id) ON DELETE RESTRICT;


--
-- TOC entry 4986 (class 2606 OID 33266)
-- Name: trx_laporan_produksi trx_laporan_produksi_kode_aktivitas_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_kode_aktivitas_id_fkey FOREIGN KEY (kode_aktivitas_id) REFERENCES public.mst_aktivitas(id) ON DELETE RESTRICT;


--
-- TOC entry 4987 (class 2606 OID 33271)
-- Name: trx_laporan_produksi trx_laporan_produksi_ll_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_ll_id_fkey FOREIGN KEY (ll_id) REFERENCES public.mst_karyawan(id) ON DELETE RESTRICT;


--
-- TOC entry 4988 (class 2606 OID 33276)
-- Name: trx_laporan_produksi trx_laporan_produksi_mesin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_mesin_id_fkey FOREIGN KEY (mesin_id) REFERENCES public.mst_mesin(id) ON DELETE RESTRICT;


--
-- TOC entry 4989 (class 2606 OID 33466)
-- Name: trx_laporan_produksi trx_laporan_produksi_nama_laporan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_nama_laporan_id_fkey FOREIGN KEY (nama_laporan_id) REFERENCES public.mst_nama_laporan(id);


--
-- TOC entry 4990 (class 2606 OID 33281)
-- Name: trx_laporan_produksi trx_laporan_produksi_operator_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_operator_id_fkey FOREIGN KEY (operator_id) REFERENCES public.mst_karyawan(id) ON DELETE RESTRICT;


--
-- TOC entry 4991 (class 2606 OID 33286)
-- Name: trx_laporan_produksi trx_laporan_produksi_pekerjaan_borong_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_pekerjaan_borong_id_fkey FOREIGN KEY (pekerjaan_borong_id) REFERENCES public.mst_pekerjaan_borong(id) ON DELETE RESTRICT;


--
-- TOC entry 4992 (class 2606 OID 33291)
-- Name: trx_laporan_produksi trx_laporan_produksi_proses_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_proses_id_fkey FOREIGN KEY (proses_id) REFERENCES public.mst_proses(id) ON DELETE RESTRICT;


--
-- TOC entry 4993 (class 2606 OID 33296)
-- Name: trx_laporan_produksi trx_laporan_produksi_shift_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_shift_id_fkey FOREIGN KEY (shift_id) REFERENCES public.mst_shift(id) ON DELETE RESTRICT;


--
-- TOC entry 4994 (class 2606 OID 33301)
-- Name: trx_laporan_produksi trx_laporan_produksi_spv_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_laporan_produksi
    ADD CONSTRAINT trx_laporan_produksi_spv_id_fkey FOREIGN KEY (spv_id) REFERENCES public.mst_karyawan(id) ON DELETE RESTRICT;


--
-- TOC entry 4995 (class 2606 OID 33338)
-- Name: trx_monitoring_produksi trx_monitoring_produksi_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_monitoring_produksi
    ADD CONSTRAINT trx_monitoring_produksi_department_id_fkey FOREIGN KEY (department_id) REFERENCES public.mst_department(id);


--
-- TOC entry 4996 (class 2606 OID 33333)
-- Name: trx_monitoring_produksi trx_monitoring_produksi_jf_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_monitoring_produksi
    ADD CONSTRAINT trx_monitoring_produksi_jf_id_fkey FOREIGN KEY (jf_id) REFERENCES public.mst_jf(id);


--
-- TOC entry 4997 (class 2606 OID 33343)
-- Name: trx_monitoring_produksi trx_monitoring_produksi_proses_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_monitoring_produksi
    ADD CONSTRAINT trx_monitoring_produksi_proses_id_fkey FOREIGN KEY (proses_id) REFERENCES public.mst_proses(id);


--
-- TOC entry 4998 (class 2606 OID 33348)
-- Name: trx_monitoring_produksi trx_monitoring_produksi_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_monitoring_produksi
    ADD CONSTRAINT trx_monitoring_produksi_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.mst_user(id);


--
-- TOC entry 4999 (class 2606 OID 33382)
-- Name: trx_pemakaian_material trx_pemakaian_material_inputer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_pemakaian_material
    ADD CONSTRAINT trx_pemakaian_material_inputer_id_fkey FOREIGN KEY (inputer_id) REFERENCES public.mst_user(id);


--
-- TOC entry 5000 (class 2606 OID 33372)
-- Name: trx_pemakaian_material trx_pemakaian_material_material_raw_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_pemakaian_material
    ADD CONSTRAINT trx_pemakaian_material_material_raw_id_fkey FOREIGN KEY (material_raw_id) REFERENCES public.mst_material_raw(id);


--
-- TOC entry 5001 (class 2606 OID 33367)
-- Name: trx_pemakaian_material trx_pemakaian_material_monitoring_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_pemakaian_material
    ADD CONSTRAINT trx_pemakaian_material_monitoring_id_fkey FOREIGN KEY (monitoring_id) REFERENCES public.trx_monitoring_produksi(id) ON DELETE CASCADE;


--
-- TOC entry 5002 (class 2606 OID 33377)
-- Name: trx_pemakaian_material trx_pemakaian_material_sumber_monitoring_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_pemakaian_material
    ADD CONSTRAINT trx_pemakaian_material_sumber_monitoring_id_fkey FOREIGN KEY (sumber_monitoring_id) REFERENCES public.trx_monitoring_produksi(id);


--
-- TOC entry 5006 (class 2606 OID 33437)
-- Name: trx_wip_pemakaian trx_wip_pemakaian_monitoring_id_asal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_wip_pemakaian
    ADD CONSTRAINT trx_wip_pemakaian_monitoring_id_asal_fkey FOREIGN KEY (monitoring_id_asal) REFERENCES public.trx_monitoring_produksi(id);


--
-- TOC entry 5007 (class 2606 OID 33442)
-- Name: trx_wip_pemakaian trx_wip_pemakaian_monitoring_id_pakai_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trx_wip_pemakaian
    ADD CONSTRAINT trx_wip_pemakaian_monitoring_id_pakai_fkey FOREIGN KEY (monitoring_id_pakai) REFERENCES public.trx_monitoring_produksi(id);


-- Completed on 2026-08-17 22:25:41

--
-- PostgreSQL database dump complete
--

\unrestrict Iy7YICYrbGcckrUNeQpX24TTZlcvYfENgvJEielR0I5kUoK5yWLXdfJr5i4EXie

