--
-- PostgreSQL database dump
--

\restrict d2sBcucx3gcjpQgGjizon7erGSzL8YOpc2ziDmHCbmisaATyaqSCDhkBO7k4nPw

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

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
-- Name: autor; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.autor (
    id_autor integer NOT NULL,
    nome_autor character varying(90),
    autor character varying(90) NOT NULL
);


ALTER TABLE public.autor OWNER TO postgres;

--
-- Name: autor_id_autor_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.autor ALTER COLUMN id_autor ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.autor_id_autor_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: autor_user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.autor_user (
    id_user integer,
    id_autor integer,
    id integer NOT NULL
);


ALTER TABLE public.autor_user OWNER TO postgres;

--
-- Name: autor_user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.autor_user ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.autor_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: bloqueio; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bloqueio (
    id_bloqueador integer,
    id_bloqueado integer,
    status_bloqueio character varying(50),
    data_bloqueio timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.bloqueio OWNER TO postgres;

--
-- Name: capitulo; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.capitulo (
    id_capitulo integer NOT NULL,
    titulo_capitulo character varying(100),
    imagem_url_capitulo character varying(250),
    ordem_capitulo integer,
    id_livro integer
);


ALTER TABLE public.capitulo OWNER TO postgres;

--
-- Name: capitulo_id_capitulo_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.capitulo ALTER COLUMN id_capitulo ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.capitulo_id_capitulo_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cena; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cena (
    id_cena integer NOT NULL,
    nome_cena character varying(150),
    descricao text,
    id_livro integer
);


ALTER TABLE public.cena OWNER TO postgres;

--
-- Name: cena_id_cena_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.cena ALTER COLUMN id_cena ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cena_id_cena_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cenario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cenario (
    id_cenario integer NOT NULL,
    nome_cenario character varying(100),
    descricao_cenario text,
    id_livro integer
);


ALTER TABLE public.cenario OWNER TO postgres;

--
-- Name: cenario_id_cenario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.cenario ALTER COLUMN id_cenario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cenario_id_cenario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: comentario_livro; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.comentario_livro (
    id_comentario integer NOT NULL,
    id_user integer,
    id_livro integer,
    nota_avaliacao real,
    comentario text
);


ALTER TABLE public.comentario_livro OWNER TO postgres;

--
-- Name: comentario_livro_id_comentario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.comentario_livro ALTER COLUMN id_comentario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.comentario_livro_id_comentario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: conta; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conta (
    id_user integer,
    foto_perfil_url character varying(240),
    bio text,
    visibilidade character varying(20) DEFAULT 'publico'::character varying NOT NULL,
    banner_url character varying(250),
    CONSTRAINT conta_visibilidade_check CHECK (((visibilidade)::text = ANY ((ARRAY['publico'::character varying, 'privado'::character varying])::text[])))
);


ALTER TABLE public.conta OWNER TO postgres;

--
-- Name: conversa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conversa (
    id_conversa integer NOT NULL,
    tipo character varying(90),
    nome_conversa character varying(80),
    foto_conversa_url character varying(250),
    id_dono integer,
    criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.conversa OWNER TO postgres;

--
-- Name: conversa_id_conversa_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.conversa ALTER COLUMN id_conversa ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.conversa_id_conversa_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: conversa_participante; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conversa_participante (
    id_conversa integer,
    id_user integer,
    cargo character varying(100),
    joinet_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    last_read_message timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.conversa_participante OWNER TO postgres;

--
-- Name: follow; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.follow (
    id_follower integer,
    id_following integer,
    status_follow character varying(50),
    data_follow timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.follow OWNER TO postgres;

--
-- Name: livro; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.livro (
    id_livro integer CONSTRAINT livro_id_not_null NOT NULL,
    titulo_livro character varying(100) NOT NULL,
    resumo_livro text,
    class_ind integer NOT NULL,
    nome_autor character varying(100),
    id_user integer,
    sinopse_livro text,
    capa_url character varying(250),
    data_publi timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    idioma character varying(90),
    visibilidade character varying(20) DEFAULT 'publico'::character varying NOT NULL,
    CONSTRAINT livro_visibilidade_check CHECK (((visibilidade)::text = ANY ((ARRAY['publico'::character varying, 'privado'::character varying])::text[])))
);


ALTER TABLE public.livro OWNER TO postgres;

--
-- Name: livro_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.livro ALTER COLUMN id_livro ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.livro_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: livros_lidos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.livros_lidos (
    id_user integer,
    id_livro integer,
    atualizado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.livros_lidos OWNER TO postgres;

--
-- Name: mensagem; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mensagem (
    id_mensagem integer NOT NULL,
    id_conversa integer,
    id_envio integer,
    tipo character varying(90),
    conteudo text,
    criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    editado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    delatado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.mensagem OWNER TO postgres;

--
-- Name: mensagem_id_mensagem_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.mensagem ALTER COLUMN id_mensagem ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mensagem_id_mensagem_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: meta_leitura; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.meta_leitura (
    id integer NOT NULL,
    id_user integer,
    periodo character varying(60),
    num_livros integer,
    criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(30) DEFAULT 'andamento'::character varying,
    expiracao date,
    nome_meta character varying(100)
);


ALTER TABLE public.meta_leitura OWNER TO postgres;

--
-- Name: meta_leitura_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.meta_leitura ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.meta_leitura_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: paragrafo; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.paragrafo (
    id_paragrafo integer NOT NULL,
    texto_paragrafo text,
    imagem_paragrafo_url character varying(250),
    ordem_paragrafo integer,
    id_capitulo integer
);


ALTER TABLE public.paragrafo OWNER TO postgres;

--
-- Name: paragrafo_id_paragrafo_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.paragrafo ALTER COLUMN id_paragrafo ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.paragrafo_id_paragrafo_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: paragrafo_resenha; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.paragrafo_resenha (
    id_paragrafo_resenha integer NOT NULL,
    texto_paragrafo_resenha text,
    id_resenha integer,
    ordem_paragrafo_resenha integer
);


ALTER TABLE public.paragrafo_resenha OWNER TO postgres;

--
-- Name: paragrafo_resenha_id_paragrafo_resenha_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.paragrafo_resenha ALTER COLUMN id_paragrafo_resenha ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.paragrafo_resenha_id_paragrafo_resenha_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: personagem; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personagem (
    id_personagem integer NOT NULL,
    nome_personagem character varying(100),
    genero character varying(20),
    idade integer,
    funcao character varying(200),
    descricao_personagem text,
    id_livro integer
);


ALTER TABLE public.personagem OWNER TO postgres;

--
-- Name: personagem_id_personagem_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.personagem ALTER COLUMN id_personagem ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.personagem_id_personagem_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: post; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.post (
    id_post integer NOT NULL,
    titulo_post character varying(90),
    id_user integer,
    url_imagem character varying(250),
    conteudo text,
    criado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    visibilidade character varying(20) DEFAULT 'publico'::character varying NOT NULL,
    CONSTRAINT post_visibilidade_check CHECK (((visibilidade)::text = ANY ((ARRAY['publico'::character varying, 'privado'::character varying])::text[])))
);


ALTER TABLE public.post OWNER TO postgres;

--
-- Name: post_id_post_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.post ALTER COLUMN id_post ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.post_id_post_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: preferencia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.preferencia (
    id_preferencia integer NOT NULL,
    nome_preferencia character varying(90),
    preferencia character varying(90) NOT NULL
);


ALTER TABLE public.preferencia OWNER TO postgres;

--
-- Name: preferencia_id_preferencia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.preferencia ALTER COLUMN id_preferencia ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.preferencia_id_preferencia_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: preferencia_livro; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.preferencia_livro (
    id integer NOT NULL,
    id_preferencia integer,
    id_livro integer
);


ALTER TABLE public.preferencia_livro OWNER TO postgres;

--
-- Name: preferencia_livro_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.preferencia_livro ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.preferencia_livro_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: preferencia_user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.preferencia_user (
    id_user integer,
    id_preferencia integer,
    id integer NOT NULL
);


ALTER TABLE public.preferencia_user OWNER TO postgres;

--
-- Name: preferencia_user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.preferencia_user ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.preferencia_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: progresso_leitura; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.progresso_leitura (
    id_progresso integer NOT NULL,
    id_user integer NOT NULL,
    id_livro integer NOT NULL,
    capitulo_atual integer,
    porcentagem_progresso numeric(5,2),
    ultima_leitura timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.progresso_leitura OWNER TO postgres;

--
-- Name: progresso_leitura_id_progresso_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.progresso_leitura_id_progresso_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.progresso_leitura_id_progresso_seq OWNER TO postgres;

--
-- Name: progresso_leitura_id_progresso_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.progresso_leitura_id_progresso_seq OWNED BY public.progresso_leitura.id_progresso;


--
-- Name: recuperacao_senha; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.recuperacao_senha (
    id_recuperacao integer NOT NULL,
    id_user integer NOT NULL,
    token text NOT NULL,
    expira_em timestamp without time zone NOT NULL,
    usado boolean DEFAULT false NOT NULL
);


ALTER TABLE public.recuperacao_senha OWNER TO postgres;

--
-- Name: recuperacao_senha_id_recuperacao_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.recuperacao_senha ALTER COLUMN id_recuperacao ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.recuperacao_senha_id_recuperacao_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: rel_worldbuild; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rel_worldbuild (
    id_cena integer,
    id_cenario integer,
    id_personagem integer
);


ALTER TABLE public.rel_worldbuild OWNER TO postgres;

--
-- Name: resenha; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.resenha (
    id_resenha integer NOT NULL,
    titulo_resenha character varying(100),
    id_user integer,
    sinopse text,
    class_ind integer,
    data_publi timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    id_livro integer,
    visibilidade character varying(20) DEFAULT 'publico'::character varying NOT NULL,
    CONSTRAINT resenha_visibilidade_check CHECK (((visibilidade)::text = ANY ((ARRAY['publico'::character varying, 'privado'::character varying])::text[])))
);


ALTER TABLE public.resenha OWNER TO postgres;

--
-- Name: resenha_id_resenha_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.resenha ALTER COLUMN id_resenha ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.resenha_id_resenha_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: top5_livros; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.top5_livros (
    id_user integer,
    id_livro integer,
    posicao integer,
    atualizado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.top5_livros OWNER TO postgres;

--
-- Name: usuario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuario (
    id_user integer NOT NULL,
    nome_completo character varying(90) NOT NULL,
    username character varying(90) NOT NULL,
    data_nascimento date NOT NULL,
    email character varying(90) NOT NULL,
    senha character varying(250) NOT NULL,
    criacao_conta timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    google_id character varying(255)
);


ALTER TABLE public.usuario OWNER TO postgres;

--
-- Name: usuario_id_user_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.usuario ALTER COLUMN id_user ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.usuario_id_user_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: whishbook; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.whishbook (
    id integer NOT NULL,
    id_livro integer,
    id_user integer,
    id_whishlist integer
);


ALTER TABLE public.whishbook OWNER TO postgres;

--
-- Name: whishbook_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.whishbook ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.whishbook_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: whishlist; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.whishlist (
    id integer NOT NULL,
    nome_lista character varying(120),
    id_user integer
);


ALTER TABLE public.whishlist OWNER TO postgres;

--
-- Name: whishlist_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.whishlist ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.whishlist_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: progresso_leitura id_progresso; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progresso_leitura ALTER COLUMN id_progresso SET DEFAULT nextval('public.progresso_leitura_id_progresso_seq'::regclass);


--
-- Data for Name: autor; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.autor (id_autor, nome_autor, autor) FROM stdin;
1	Machado de Assis	machado
2	Edgar Allan Paul	edgar
4	Abel Ferreira	abel
5	Rick Riordan	rick-riordan
6	Agatha Christie	agatha-christie
7	George Orwell	george-orwell
8	Stephen King	stephen-king
9	J.R.R. Tolkien	jrr-tolkien
3	J.K Rowling	jk-rowling
\.


--
-- Data for Name: autor_user; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.autor_user (id_user, id_autor, id) FROM stdin;
9	2	1
9	6	2
9	8	3
24	1	4
24	2	5
24	3	6
27	1	10
27	2	11
27	3	12
\.


--
-- Data for Name: bloqueio; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bloqueio (id_bloqueador, id_bloqueado, status_bloqueio, data_bloqueio) FROM stdin;
\.


--
-- Data for Name: capitulo; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.capitulo (id_capitulo, titulo_capitulo, imagem_url_capitulo, ordem_capitulo, id_livro) FROM stdin;
1	DE COMO ITAGUAÖ GANHOU UMA CASA DE ORATES	\N	1	1
2	TORRENTES DE LOUCOS	\N	2	1
3	DEUS SABE O QUE FAZ	\N	3	1
4	UMA TEORIA NOVA	\N	4	1
5	O TERROR	\N	5	1
6	A CARTOMANTE	\N	1	2
7	98	\N	1	3
8	99	\N	2	3
9	100	\N	3	3
10	101	\N	4	3
11	OS CAMPOS	\N	1	4
12	OS CASTELOS	\N	2	4
13	EU NUNCA GUARDEI REBANHOS	\N	1	5
14	MEU OLHAR	\N	2	5
15	AO ENTARDECER	\N	3	5
\.


--
-- Data for Name: cena; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cena (id_cena, nome_cena, descricao, id_livro) FROM stdin;
\.


--
-- Data for Name: cenario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cenario (id_cenario, nome_cenario, descricao_cenario, id_livro) FROM stdin;
\.


--
-- Data for Name: comentario_livro; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.comentario_livro (id_comentario, id_user, id_livro, nota_avaliacao, comentario) FROM stdin;
1	19	1	4.5	livro maneiro
2	19	2	2.5	achei paia
3	19	3	5	SIX SEVEEEN
4	19	4	4	maneiro cheirou muito mas ARRASOU
5	19	5	1	bela bosta
\.


--
-- Data for Name: conta; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.conta (id_user, foto_perfil_url, bio, visibilidade, banner_url) FROM stdin;
3	foto_do_gomez	Zagueiro Paraguaio, dono da sele‡Æo paraguaia e maior capitÆo da hist¢ria do Palmeiras	publico	\N
\.


--
-- Data for Name: conversa; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.conversa (id_conversa, tipo, nome_conversa, foto_conversa_url, id_dono, criacao) FROM stdin;
\.


--
-- Data for Name: conversa_participante; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.conversa_participante (id_conversa, id_user, cargo, joinet_at, last_read_message) FROM stdin;
\.


--
-- Data for Name: follow; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.follow (id_follower, id_following, status_follow, data_follow) FROM stdin;
\.


--
-- Data for Name: livro; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.livro (id_livro, titulo_livro, resumo_livro, class_ind, nome_autor, id_user, sinopse_livro, capa_url, data_publi, idioma, visibilidade) FROM stdin;
2	A Cartomante	"A Cartomante", de Machado de Assis, conta a hist¢ria de Camilo e Rita, dois amigos que se envolvem em um relacionamento amoroso proibido, apesar de Rita ser casada com Vilela, amigo de Camilo. Com medo de que o marido descubra a trai‡Æo, Rita procura uma cartomante, que lhe garante que nada de ruim acontecer . Camilo inicialmente duvida desse tipo de previsÆo, mas, diante de acontecimentos que despertam seu medo e sua inseguran‡a, tamb‚m acaba recorrendo … cartomante. Ap¢s receber uma previsÆo tranquilizadora, ele segue confiante para encontrar Vilela, sem imaginar o destino que o aguarda. A narrativa combina suspense, ironia e cr¡tica … supersti‡Æo, mostrando como os personagens tentam encontrar seguran‡a em cren‡as diante da incerteza.	12	Machado de Assis	\N	"A Cartomante", de Machado de Assis, acompanha Camilo e Rita, dois amantes que vivem um relacionamento secreto, enquanto Vilela, marido de Rita e amigo de Camilo, come‡a a despertar preocupa‡Æo no casal. Em meio ao medo de serem descobertos, Rita procura uma cartomante em busca de respostas. A partir da¡, a hist¢ria conduz os personagens por uma sequˆncia de tensÆo e acontecimentos inesperados, marcada por suspense, ironia e uma reviravolta surpreendente.	../img/capas_livros/semana-capa.jpeg	2026-08-10 12:42:47.362398	Portugues-BR	publico
3	A Semana	""A Semana", de Machado de Assis, re£ne uma s‚rie de cr“nicas publicadas originalmente em jornais, nas quais o autor observa acontecimentos cotidianos, pol¡ticos e sociais de seu tempo. Com uma escrita marcada pela ironia, pelo humor e pela reflexÆo, Machado transforma situa‡äes aparentemente simples em oportunidades para analisar o comportamento humano e as contradi‡äes da sociedade. Ao comentar fatos da vida p£blica e do dia a dia, o autor questiona costumes, valores e atitudes presentes na sociedade brasileira. A obra se destaca pela capacidade de unir cr¡tica social e entretenimento, apresentando uma visÆo inteligente e muitas vezes bem-humorada da realidade.	12	Machado de Assis	\N	""A Semana", de Machado de Assis, re£ne cr“nicas em que o autor aborda acontecimentos cotidianos e questäes sociais e pol¡ticas de sua ‚poca. Com ironia, humor e olhar cr¡tico, Machado transforma fatos comuns em reflexäes sobre a sociedade e o comportamento humano. A obra apresenta um retrato interessante e perspicaz do Brasil de seu tempo.	../img/capas_livros/mensagem-capa.jpeg	2026-08-10 12:53:45.173089	Portugues-BR	publico
4	Mensagem	Mensagem, de Fernando Pessoa, ‚ uma obra po‚tica que revisita a hist¢ria e os s¡mbolos de Portugal, destacando figuras como reis, navegadores e her¢is nacionais. Dividido em trˆs partes, o livro apresenta a forma‡Æo, a realiza‡Æo e a queda simb¢lica do imp‚rio portuguˆs, relacionando o passado glorioso do pa¡s a um futuro de renova‡Æo. Por meio de poemas marcados pelo nacionalismo, pelo simbolismo e pelo misticismo, Pessoa reflete sobre o destino de Portugal e sobre a importƒncia de sua identidade hist¢rica. A obra tamb‚m aborda o sonho de um novo per¡odo de grandeza, representado pelo retorno simb¢lico de D. SebastiÆo e pelo surgimento de um novo imp‚rio, agora ligado … cultura e ao esp¡rito	10	JoÆo Pessoa	\N	Mensagem, de Fernando Pessoa, re£ne poemas que celebram e reinterpretam a hist¢ria, os her¢is e os mitos de Portugal. A obra percorre momentos fundamentais da trajet¢ria portuguesa e transforma o passado em uma reflexÆo sobre o destino e o futuro do pa¡s. Entre simbolismo, patriotismo e misticismo, Pessoa constr¢i uma visÆo po‚tica de renascimento e de uma nova grandeza portuguesa.	../img/capas_livros/alienista-capa.jpeg	2026-08-10 13:06:38.938784	Portugues-BR	publico
1	O Alienista	O Alienista, de Machado de Assis, conta a hist¢ria de SimÆo Bacamarte, um m‚dico que decide estudar a mente humana e compreender os limites entre a razÆo e a loucura. Para realizar suas pesquisas, ele cria a Casa Verde, onde come‡a a internar pessoas consideradas mentalmente desequilibradas. Com o passar do tempo, Bacamarte amplia tanto seus crit‚rios que grande parte da popula‡Æo de Itagua¡ acaba sendo considerada louca. Depois, ele muda sua teoria e passa a acreditar que aqueles que apresentam equil¡brio perfeito sÆo os verdadeiros anormais. No final, conclui que ele pr¢prio possui essa caracter¡stica e decide se internar na Casa Verde. A obra utiliza ironia e humor para criticar o abuso da ciˆncia, do poder e a dificuldade de definir o que ‚ realmente normal.	12	Machado de Assis	\N	O Alienista, de Machado de Assis, acompanha SimÆo Bacamarte, um m‚dico que se dedica a estudar a loucura e cria a Casa Verde, um local destinado … interna‡Æo de pessoas consideradas desequilibradas. Por‚m, sua busca pela defini‡Æo da normalidade faz com que cada vez mais habitantes de Itagua¡ sejam considerados loucos. A obra apresenta, de forma ir“nica e humor¡stica, uma cr¡tica ao excesso de poder, ao cientificismo e … dificuldade de determinar os limites entre a razÆo e a loucura.	../img/capas_livros/cartomante-capa.jpeg	2026-08-10 11:37:10.803001	Portugues-BR	publico
5	Guardador de Rebanhos	Guardador de Rebanhos, de Fernando Pessoa, escrito sob o heter“nimo Alberto Caeiro, ‚ um conjunto de poemas que apresenta uma visÆo simples, direta e profundamente ligada … natureza. O eu l¡rico rejeita interpreta‡äes filos¢ficas e metaf¡sicas do mundo e prefere observar as coisas exatamente como elas sÆo. Para Caeiro, pensar demais sobre a realidade pode afastar o ser humano da experiˆncia verdadeira de simplesmente ver, sentir e existir. A natureza, os animais, as  rvores, as flores, o c‚u e as sensa‡äes cotidianas tornam-se elementos centrais de sua poesia. O poema tamb‚m questiona conceitos tradicionais sobre Deus, espiritualidade e transcendˆncia, defendendo uma esp‚cie de rela‡Æo concreta e imediata com o mundo.	10	JoÆo Pessoa	\N	Guardador de Rebanhos acompanha o olhar de um eu l¡rico que se apresenta como um pastor que guarda rebanhos, embora esses rebanhos sejam, sobretudo, pensamentos e sensa‡äes. Ao observar a natureza, ele desenvolve uma filosofia baseada na simplicidade, recusando explica‡äes abstratas e procurando enxergar o mundo sem atribuir-lhe significados ocultos. Ao longo da obra, a natureza funciona como fonte de conhecimento e verdade, enquanto o pensamento excessivo ‚ visto como algo que complica aquilo que deveria ser simples. A obra constr¢i, assim, uma reflexÆo po‚tica sobre a existˆncia, a percep‡Æo e a rela‡Æo do ser humano com a realidade.	../img/capas_livros/rebanhos-capa.jpg	2026-08-10 13:21:21.325504	Portugues-BR	publico
\.


--
-- Data for Name: livros_lidos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.livros_lidos (id_user, id_livro, atualizado_em) FROM stdin;
19	1	2026-08-10 13:49:14.580055
19	2	2026-08-10 13:49:21.595999
19	3	2026-08-10 13:49:23.684314
19	4	2026-08-10 13:49:26.221843
19	5	2026-08-10 13:49:28.566783
\.


--
-- Data for Name: mensagem; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mensagem (id_mensagem, id_conversa, id_envio, tipo, conteudo, criacao, editado_em, delatado_em) FROM stdin;
\.


--
-- Data for Name: meta_leitura; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.meta_leitura (id, id_user, periodo, num_livros, criacao, status, expiracao, nome_meta) FROM stdin;
3	19	semanal	4	2026-08-13 18:34:54.374628	andamento	2026-08-20	meta de hoje
5	19	mensal	20	2026-08-13 18:36:13.450308	andamento	2026-09-12	meta do mês
6	19	anual	200	2026-08-13 18:36:50.420783	andamento	2027-08-13	meta do ano
\.


--
-- Data for Name: paragrafo; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.paragrafo (id_paragrafo, texto_paragrafo, imagem_paragrafo_url, ordem_paragrafo, id_capitulo) FROM stdin;
1	As cr“nicas da vila de Itagua¡ dizem que em tempos remotos vivera ali um certo m‚dico, o Dr. SimÆo Bacamarte, filho da nobreza da terra e o maior dos m‚dicos do Brasil, de Portugal e das Espanhas. Estudara em Coimbra e P dua. Aos trinta e quatro anos regressou ao Brasil, nÆo podendo el-rei alcan‡ar dele que ficasse em Coimbra, regendo a universidade, ou em Lisboa, expedindo os neg¢cios da monarquia.	\N	1	1
2	-A ciˆncia, disse ele a Sua Majestade, ‚ o meu emprego £nico; Itagua¡ ‚ o meu universo.	\N	2	1
3	Dito isso, meteu-se em Itagua¡, e entregou-se de corpo e alma ao estudo da ciˆncia, alternando as curas com as leituras, e demonstrando os teoremas com cataplasmas. Aos quarenta anos casou com D. Evarista da Costa e Mascarenhas, senhora de vinte e cinco anos, vi£va de um juiz de fora, e nÆo bonita nem simp tica. Um dos tios dele, ca‡ador de pacas perante o Eterno, e nÆo menos franco, admirou-se de semelhante escolha e disse-lho. SimÆo Bacamarte explicou-lhe que D. Evarista reunia condi‡äes fisiol¢gicas e anat“micas de primeira ordem, digeria com facilidade, dormia regularmente, tinha bom pulso, e excelente vista; estava assim apta para dar-lhe filhos robustos, sÆos e inteligentes. Se al‚m dessas prendas,-£nicas dignas da preocupa‡Æo de um s bio, D. Evarista era mal composta de fei‡äes, longe de lastim -lo, agradecia-o a Deus, porquanto nÆo corria o risco de preterir os interesses da ciˆncia na contempla‡Æo exclusiva, mi£da e vulgar da consorte.	\N	3	1
4	D. Evarista mentiu …s esperan‡as do Dr. Bacamarte, nÆo lhe deu filhos robustos nem mofinos. A ¡ndole natural da ciˆncia ‚ a longanimidade; o nosso m‚dico esperou trˆs anos, depois quatro, depois cinco. Ao cabo desse tempo fez um estudo profundo da mat‚ria, releu todos os escritores  rabes e outros, que trouxera para Itagua¡, enviou consultas …s universidades italianas e alemÆs, e acabou por aconselhar … mulher um reg¡men aliment¡cio especial. A ilustre dama, nutrida exclusivamente com a bela carne de porco de Itagua¡, nÆo atendeu …s admoesta‡äes do esposo; e … sua resistˆncia,-explic vel, mas inqualific vel,- devemos a total extin‡Æo da dinastia dos Bacamartes.	\N	4	1
5	Mas a ciˆncia tem o inef vel dom de curar todas as m goas; o nosso m‚dico mergulhou inteiramente no estudo e na pr tica da medicina. Foi entÆo que um dos recantos desta lhe chamou especialmente a aten‡Æo,-o recanto ps¡quico, o exame de patologia cerebral. NÆo havia na col“nia, e ainda no reino, uma s¢ autoridade em semelhante mat‚ria, mal explorada, ou quase inexplorada. SimÆo Bacamarte compreendeu que a ciˆncia lusitana, e particularmente a brasileira, podia cobrir-se de "louros imarcesc¡veis", - expressÆo usada por ele mesmo, mas em um arroubo de intimidade dom‚stica; exteriormente era modesto, segundo conv‚m aos sabedores.	\N	5	1
6	-A sa£de da alma, bradou ele, ‚ a ocupa‡Æo mais digna do m‚dico.	\N	6	1
7	-Do verdadeiro m‚dico, emendou Crispim Soares, botic rio da vila, e um dos seus amigos e comensais.	\N	7	1
8	A verean‡a de Itagua¡, entre outros pecados de que ‚ arguida pelos cronistas, tinha o de nÆo fazer caso dos dementes. Assim ‚ que cada louco furioso era trancado em uma alcova, na pr¢pria casa, e, nÆo curado, mas descurado, at‚ que a morte o vinha defraudar do benef¡cio da vida; os mansos andavam … solta pela rua. SimÆo Bacamarte entendeu desde logo reformar tÆo ruim costume; pediu licen‡a … Cƒmara para agasalhar e tratar no edif¡cio que ia construir todos os loucos de Itagua¡, e das demais vilas e cidades, mediante um estipˆndio, que a Cƒmara lhe daria quando a fam¡lia do enfermo o nÆo pudesse fazer. A proposta excitou a curiosidade de toda a vila, e encontrou grande resistˆncia, tÆo certo ‚ que dificilmente se desarraigam h bitos absurdos, ou ainda maus. A id‚ia de meter os loucos na mesma casa, vivendo em comum, pareceu em si mesma sintoma de demˆncia e nÆo faltou quem o insinuasse … pr¢pria mulher do m‚dico.	\N	8	1
9	-Olhe, D. Evarista, disse-lhe o Padre Lopes, vig rio do lugar, veja se seu marido d  um passeio ao Rio de Janeiro. Isso de estudar sempre, sempre, nÆo ‚ bom, vira o ju¡zo.	\N	9	1
10	D. Evarista ficou aterrada. Foi ter com o marido, disse-lhe "que estava com desejos", um principalmente, o de vir ao Rio de Janeiro e comer tudo o que a ele lhe parecesse adequado a certo fim. Mas aquele grande homem, com a rara sagacidade que o distinguia, penetrou a inten‡Æo da esposa e redarguiu-lhe sorrindo que nÆo tivesse medo. Dali foi … Cƒmara, onde os vereadores debatiam a proposta, e defendeu-a com tanta eloquˆncia, que a maioria resolveu autoriz -lo ao que pedira, votando ao mesmo tempo um imposto destinado a subsidiar o tratamento, alojamento e mantimento dos doidos pobres. A mat‚ria do imposto nÆo foi f cil ach -la; tudo estava tributado em Itagua¡. Depois de longos estudos, assentou-se em permitir o uso de dois penachos nos cavalos dos enterros. Quem quisesse emplumar os cavalos de um coche mortu rio pagaria dois tostäes … Cƒmara, repetindo-se tantas vezes esta quantia quantas fossem as horas decorridas entre a do falecimento e a da £ltima bˆn‡Æo na sepultura. O escrivÆo perdeu-se nos c lculos aritm‚ticos do rendimento poss¡vel da nova taxa; e um dos vereadores, que nÆo acreditava na empresa do m‚dico, pediu que se relevasse o escrivÆo de um trabalho in£til.	\N	10	1
11	- Os c lculos nÆo sÆo precisos, disse ele, porque o Dr. Bacamarte nÆo arranja nada. Quem ‚ que viu agora meter todos os doidos dentro da mesma casa?	\N	11	1
12	Enganava-se o digno magistrado; o m‚dico arranjou tudo. Uma vez empossado da licen‡a come‡ou logo a construir a casa. Era na Rua Nova, a mais bela rua de Itagua¡ naquele tempo; tinha cinquenta janelas por lado, um p tio no centro, e numerosos cub¡culos para os h¢spedes. Como fosse grande arabista, achou no CorÆo que Maom‚ declara vener veis os doidos, pela considera‡Æo de que Al  lhes tira o ju¡zo para que nÆo pequem. A id‚ia pareceu-lhe bonita e profunda, e ele a fez gravar no frontisp¡cio da casa; mas, como tinha medo ao vig rio, e por tabela ao bispo, atribuiu o pensamento a Benedito VIII, merecendo com essa fraude ali s pia, que o Padre Lopes lhe contasse, ao almo‡o, a vida daquele pont¡fice eminente.	\N	12	1
63	-Trata-se de coisa mais alta, trata-se de uma experiˆncia cient¡fica. Digo experiˆncia, porque nÆo me atrevo a assegurar desde j  a minha id‚ia; nem a ciˆncia ‚ outra coisa, Sr. Soares, senÆo uma investiga‡Æo constante. Trata-se, pois, de uma experiˆncia, mas uma experiˆncia que vai mudar a face da Terra. A loucura, objeto dos meus estudos, era at‚ agora uma ilha perdida no oceano da razÆo; come‡o a suspeitar que ‚ um continente.	\N	9	4
132	Este diz Inglaterra onde, afastado,	\N	9	11
133	A mÆo sustenta, em que se apoia o rosto.	\N	10	11
134	Fita, com olhar sphyngico e fatal,	\N	11	11
135	O Ocidente, futuro do passado.	\N	12	11
136	O rosto com que fita ‚ Portugal.	\N	13	11
137	PRIMEIRO / ULISSES	\N	1	12
138	O mytho ‚ o nada que ‚ tudo.	\N	2	12
139	O mesmo sol que abre os c‚us	\N	3	12
140	O corpo morto de Deus,	\N	5	12
141	Vivo e desnudo.	\N	6	12
142		\N	7	12
13	A Casa Verde foi o nome dado ao asilo, por alusÆo … cor das janelas, que pela primeira vez apareciam verdes em Itagua¡. Inaugurou-se com imensa pompa; de todas as vilas e povoa‡äes pr¢ximas, e at‚ remotas, e da pr¢pria cidade do Rio de Janeiro, correu gente para assistir …s cerim“nias, que duraram sete dias. Muitos dementes j  estavam recolhidos; e os parentes tiveram ocasiÆo de ver o carinho paternal e a caridade cristÆ com que eles iam ser tratados. D. Evarista, content¡ssima com a gl¢ria do marido, vestiu-se luxuosamente, cobriu-se de j¢ias, flores e sedas. Ela foi uma verdadeira rainha naqueles dias memor veis; ningu‚m deixou de ir visit -la duas e trˆs vezes, apesar dos costumes caseiros e recatados do s‚culo, e nÆo s¢ a cortejavam como a louvavam; porquanto,-e este fato ‚ um documento altamente honroso para a sociedade do tempo, -porquanto viam nela a feliz esposa de um alto esp¡rito, de um varÆo ilustre, e, se lhe tinham inveja, era a santa e nobre inveja dos admiradores.	\N	13	1
14	Ao cabo de sete dias expiraram as festas p£blicas; Itagua¡, tinha finalmente uma casa de orates.	\N	14	1
16	Trˆs dias depois, numa expansÆo ¡ntima com o botic rio Crispim Soares, desvendou o alienista o mist‚rio do seu cora‡Æo.	\N	1	2
17	-A caridade, Sr. Soares, entra decerto no meu procedimento, mas entra como tempero, como o sal das coisas, que ‚ assim que interpreto o dito de SÆo Paulo aos Cor¡ntios: "Se eu conhecer quanto se pode saber, e nÆo tiver caridade, nÆo sou nada". O principal nesta minha obra da Casa Verde ‚ estudar profundamente a loucura, os seus diversos graus, classificar-lhe os casos, descobrir enfim a causa do fen“meno e o rem‚dio universal. Este ‚ o mist‚rio do meu cora‡Æo. Creio que com isto presto um bom servi‡o … humanidade.	\N	2	2
18	-Um excelente servi‡o, corrigiu o botic rio.	\N	3	2
19	-Sem este asilo, continuou o alienista, pouco poderia fazer; ele d -me, por‚m, muito maior campo aos meus estudos.	\N	4	2
20	-Muito maior, acrescentou o outro.	\N	5	2
21	E tinha razÆo. De todas as vilas e arraiais vizinhos aflu¡am loucos … Casa Verde. Eram furiosos, eram mansos, eram monoman¡acos, era toda a fam¡lia dos deserdados do esp¡rito. Ao cabo de quatro meses, a Casa Verde era uma povoa‡Æo. NÆo bastaram os primeiros cub¡culos; mandou-se anexar uma galeria de mais trinta e sete. O Padre Lopes confessou que nÆo imaginara a existˆncia de tantos doidos no mundo, e menos ainda o inexplic vel de alguns casos. Um, por exemplo, um rapaz bronco e vilÆo, que todos os dias, depois do almo‡o, fazia regularmente um discurso acadˆmico, ornado de tropos, de ant¡teses, de ap¢strofes, com seus recamos de grego e latim, e suas borlas de C¡cero, Apuleio e Tertuliano. O vig rio nÆo queria acabar de crer. Quˆ! um rapaz que ele vira, trˆs meses antes, jogando peteca na rua!	\N	6	2
22	-NÆo digo que nÆo, respondia-lhe o alienista; mas a verdade ‚ o que Vossa Reverend¡ssima est  vendo. Isto ‚ todos os dias	\N	7	2
23	- Quanto a mim, tornou o vig rio, s¢ se pode explicar pela confusÆo das l¡nguas na torre de Babel, segundo nos conta a Escritura; provavelmente, confundidas antigamente as l¡nguas, ‚ f cil troc -las agora, desde que a razÆo nÆo trabalhe...	\N	8	2
24	-Essa pode ser, com efeito, a explica‡Æo divina do fen“meno, concordou o alienista, depois de refletir um instante, mas nÆo ‚ imposs¡vel que haja tamb‚m alguma razÆo humana, e puramente cient¡fica, e disso trato...	\N	9	2
25	-V  que seja, e fico ansioso. Realmente!	\N	10	2
26	Os loucos por amor eram trˆs ou quatro, mas s¢ dois espantavam pelo curioso do del¡rio. O primeiro, um FalcÆo, rapaz de vinte e cinco anos, supunha-se estrela-dalva, abria os bra‡os e alargava as pernas, para dar-lhes certa fei‡Æo de raios, e ficava assim horas esquecidas a perguntar se o sol j  tinha sa¡do para ele recolher-se. O outro andava sempre, sempre, sempre, … roda das salas ou do p tio, ao longo dos corredores, … procura do fim do mundo. Era um desgra‡ado, a quem a mulher deixou por seguir um peralvilho. Mal descobrira a fuga, armou-se de uma garrucha, e saiu-lhes no encal‡o; achou-os duas horas depois, ao p‚ de uma lagoa, matou-os a ambos com os maiores requintes de crueldade.	\N	11	2
27	O ci£me satisfez-se, mas o vingado estava louco. E entÆo come‡ou aquela ƒnsia de ir ao fim do mundo … cata dos fugitivos.	\N	12	2
28	A mania das grandezas tinha exemplares not veis. O mais not vel era um pobre-diabo, filho de um algibebe, que narrava …s paredes ( porque nÆo olhava nunca para nenhuma pessoa ) toda a sua genealogia, que era esta:	\N	13	2
29	-Deus engendrou um ovo, o ovo engendrou a espada, a espada engendrou Davi, Davi engendrou a p£rpura, a p£rpura engendrou o duque, o duque engendrou o marquˆs, o marquˆs engendrou o conde, que sou eu.	\N	14	2
30	Dava uma pancada na testa, um estalo com os dedos, e repetia cinco, seis vezes seguidas:	\N	15	2
31	-Deus engendrou um ovo, o ovo, etc.	\N	16	2
32	Outro da mesma esp‚cie era um escrivÆo, que se vendia por mordomo do rei; outro era um boiadeiro de Minas, cuja mania era distribuir boiadas a toda a gente, dava trezentas cabe‡as a um, seiscentas a outro, mil e duzentas a outro, e nÆo acabava mais. NÆo falo dos casos de monomania religiosa; apenas citarei um sujeito que, chamando-se JoÆo de Deus, dizia agora ser o deus JoÆo, e prometia o reino dos c‚us a quem o adorasse, e as penas do inferno aos outros; e depois desse, o licenciado Garcia, que nÆo dizia nada, porque imaginava que no dia em que chegasse a proferir uma s¢ palavra, todas as estrelas se despegariam do c‚u e abrasariam a terra; tal era o poder que recebera de Deus.	\N	17	2
33	Assim o escrevia ele no papel que o alienista lhe mandava dar, menos por caridade do que por interesse cient¡fico.	\N	18	2
34	Que, na verdade, a paciˆncia do alienista era ainda mais extraordin ria do que todas as manias hospedadas na Casa Verde; nada menos que assombrosa. SimÆo Bacamarte come‡ou por organizar um pessoal de administra‡Æo; e, aceitando essa id‚ia ao botic rio Crispim Soares, aceitou-lhe tamb‚m dois sobrinhos, a quem incumbiu da execu‡Æo de um regimento que lhes deu, aprovado pela Cƒmara, da distribui‡Æo da comida e da roupa, e assim tamb‚m da escrita, etc. Era o melhor que podia fazer, para somente cuidar do seu of¡cio.-A Casa Verde, disse ele ao vig rio, ‚ agora uma esp‚cie de mundo, em que h  o governo temporal e o governo espiritual. E o Padre Lopes ria deste pio trocado,-e acrescentava,-com o £nico fim de dizer tamb‚m uma chala‡a: -Deixe estar, deixe estar, que hei de mand -lo denunciar ao papa.	\N	19	2
35	Uma vez desonerado da administra‡Æo, o alienista procedeu a uma vasta classifica‡Æo dos seus enfermos. Dividiu-os primeiramente em duas classes principais: os furiosos e os mansos; da¡ passou …s subclasses, monomanias, del¡rios, alucina‡äes diversas.	\N	20	2
86	-Isso, nÆo! isso, nÆo! interrompeu a boa senhora com energia. Se ele gastou tÆo depressa o que recebeu, a culpa nÆo ‚ dele.	\N	9	5
36	Isto feito, come‡ou um estudo aturado e cont¡nuo; analisava os h bitos de cada louco, as horas de acesso, as aversäes, as simpatias, as palavras, os gestos, as tendˆncias; inquiria da vida dos enfermos, profissÆo, costumes, circunstƒncias da revela‡Æo m¢rbida, acidentes da infƒncia e da mocidade, doen‡as de outra esp‚cie, antecedentes na fam¡lia, uma devassa, enfim, como a nÆo faria o mais atilado corregedor. E cada dia notava uma observa‡Æo nova, uma descoberta interessante, um fen“meno extraordin rio. Ao mesmo tempo estudava o melhor reg¡men, as substƒncias medicamentosas, os meios curativos e os meios paliativos, nÆo s¢ os que vinham nos seus amados  rabes, como os que ele mesmo descobria, … for‡a de sagacidade e paciˆncia. Ora, todo esse trabalho levava-lhe o melhor e o mais do tempo. Mal dormia e mal comia; e, ainda comendo, era como se trabalhasse, porque ora interrogava um texto antigo, ora ruminava uma questÆo, e ia muitas vezes de um cabo a outro do jantar sem dizer uma s¢ palavra a D. Evarista.	\N	21	2
37	Ilustre dama, no fim de dois meses, achou-se a mais desgra‡ada das mulheres: caiu em profunda melancolia, ficou amarela, magra, comia pouco e suspirava a cada canto. NÆo ousava fazer-lhe nenhuma queixa ou reproche, porque respeitava nele o seu marido e senhor, mas padecia calada, e definhava a olhos vistos. Um dia, ao jantar, como lhe perguntasse o marido o que ‚ que tinha, respondeu tristemente que nada; depois atreveu-se um pouco, e foi ao ponto de dizer que se considerava tÆo vi£va como dantes. E acrescentou:	\N	1	3
38	-Quem diria nunca que meia d£zia de lun ticos...	\N	2	3
39	NÆo acabou a frase; ou antes, acabou-a levantando os olhos ao teto,-os olhos, que eram a sua fei‡Æo mais insinuante,- negros, grandes, lavados de uma luz £mida, como os da aurora. Quanto ao gesto, era o mesmo que empregara no dia em que SimÆo Bacamarte a pediu em casamento. NÆo dizem as cr“nicas se D. Evarista brandiu aquela arma com o perverso intuito de degolar de uma vez a ciˆncia, ou, pelo menos, decepar-lhe as mÆos; mas a conjetura ‚ veross¡mil. Em todo caso, o alienista nÆo lhe atribuiu inten‡Æo. E nÆo se irritou o grande homem, nÆo ficou sequer consternado. O metal de seus olhos nÆo deixou de ser o mesmo metal, duro, liso, eterno, nem a menor prega veio quebrar a superf¡cie da fronte quieta como a  gua de Botafogo. Talvez um sorriso lhe descerrou os l bios, por entre os quais filtrou esta palavra macia como o ¢leo do Cƒntico:	\N	3	3
40	-Consinto que v s dar um passeio ao Rio de Janeiro.	\N	4	3
41	Mas um dardo atravessou o cora‡Æo de D. Evarista. Conteve-se, entretanto; limitou-se a dizer ao marido que, se ele nÆo ia, ela nÆo iria tamb‚m, porque nÆo havia de meter-se sozinha pelas estradas.	\N	6	3
42	-Ir  com sua tia, redarguiu o alienista.	\N	7	3
43	Note-se que D. Evarista tinha pensado nisso mesmo; mas nÆo quisera pedi-lo nem insinu -lo, em primeiro lugar porque seria impor grandes despesas ao marido, em segundo lugar porque era melhor, mais met¢dico e racional que a proposta viesse dele.	\N	8	3
44	-Oh! mas o dinheiro que ser  preciso gastar! suspirou D. Evarista sem convic‡Æo.	\N	9	3
45	-Que importa? Temos ganho muito, disse o marido. Ainda ontem o escritur rio prestou-me contas. Queres ver?	\N	10	3
46	E levou-a aos livros. D. Evarista ficou deslumbrada. Era uma via-l ctea de algarismos. E depois levou-a …s arcas, onde estava o dinheiro.	\N	11	3
47	Deus! eram montes de ouro, eram mil cruzados sobre mil cruzados, dobräes sobre dobräes; era a opulˆncia.	\N	12	3
48	Enquanto ela comia o ouro com os seus olhos negros, o alienista fitava-a, e dizia-lhe ao ouvido com a mais p‚rfida das alusäes:	\N	13	3
49	-Quem diria que meia d£zia de lun ticos...	\N	14	3
50	D. Evarista compreendeu, sorriu e respondeu com muita resigna‡Æo:	\N	15	3
51	-Deus sabe o que faz!	\N	16	3
52	Trˆs meses depois efetuava-se a jornada. D. Evarista, a tia, a mulher do botic rio, um sobrinho deste, um padre que o alienista conhecera em Lisboa, e que de aventura achava-se em Itagua¡ cinco ou seis pajens, quatro mucamas, tal foi a comitiva que a popula‡Æo viu dali sair em certa manhÆ do mˆs de maio. As despedidas foram tristes para todos, menos para o alienista. Conquanto as l grimas de D. Evarista fossem abundantes e sinceras, nÆo chegaram a abal -lo. Homem de ciˆncia, e s¢ de ciˆncia, nada o consternava fora da ciˆncia; e se alguma coisa o preocupava naquela ocasiÆo, se ele deixava correr pela multidÆo um olhar inquieto e policial, nÆo era outra coisa mais do que a id‚ia de que algum demente podia achar-se ali misturado com a gente de ju¡zo	\N	17	3
53	-Adeus! solu‡aram enfim as damas e o botic rio.	\N	18	3
54	E partiu a comitiva. Crispim Soares, ao tornar a casa, trazia os olhos entre as duas orelhas da besta ruana em que vinha montado; SimÆo Bacamarte alongava os seus pelo horizonte adiante, deixando ao cavalo a responsabilidade do regresso. Imagem vivaz do gˆnio e do vulgo! Um fita o presente, com todas as suas l grimas e saudades, outro devassa o futuro com todas as suas auroras.	\N	19	3
55	Ao passo que D. Evarista, em l grimas, vinha buscando o 1 [Rio de Janeiro, SimÆo Bacamarte estudava por todos os lados uma certa id‚ia arrojada e nova, pr¢pria a alargar as bases da psicologia. Todo o tempo que lhe sobrava dos cuidados da Casa Verde, era pouco para andar na rua, ou de casa em casa, conversando as gentes, sobre trinta mil assuntos, e virgulando as falas de um olhar que metia medo aos mais her¢icos.	\N	1	4
56	Um dia de manhÆ,-eram passadas trˆs semanas,-estando Crispim Soares ocupado em temperar um medicamento, vieram dizer-lhe que o alienista o mandava chamar.	\N	2	4
57	-Trata-se de neg¢cio importante, segundo ele me disse, acrescentou o portador.	\N	3	4
58	Crispim empalideceu. Que neg¢cio importante podia ser, se nÆo alguma not¡cia da comitiva, e especialmente da mulher? Porque este t¢pico deve ficar claramente definido, visto insistirem nele os cronistas; Crispim amava a mulher, e, desde trinta anos, nunca estiveram separados um s¢ dia. Assim se explicam os mon¢logos que ele fazia agora, e que os fƒmulos lhe ouviam muita vez:-"Anda, bem feito, quem te mandou consentir na viagem de Ces ria? Bajulador, torpe bajulador! S¢ para adular ao Dr. Bacamarte. Pois agora aguenta-te; anda, aguenta-te, alma de lacaio, fracalhÆo, vil, miser vel. Dizes amem a tudo, nÆo ‚? a¡ tens o lucro, biltre!"-E muitos outros nomes feios, que um homem nÆo deve dizer aos outros, quanto mais a si mesmo. Daqui a imaginar o efeito do recado ‚ um nada. TÆo depressa ele o recebeu como abriu mÆo das drogas e voou … Casa Verde.	\N	4	4
59	SimÆo Bacamarte recebeu-o com a alegria pr¢pria de um s bio, uma alegria abotoada de circunspe‡Æo at‚ o pesco‡o.	\N	5	4
60	-Estou muito contente, disse ele.	\N	6	4
61	-Not¡cias do nosso povo? perguntou o botic rio com a voz trˆmula.	\N	7	4
62	O alienista fez um gesto magn¡fico, e respondeu:	\N	8	4
143	Este, que aqui aportou,	\N	8	12
144	Foi por nÆo ser existindo.	\N	9	12
64	Disse isto, e calou-se, para ruminar o pasmo do botic rio. Depois explicou compridamente a sua id‚ia. No conceito dele a insƒnia abrangia uma vasta superf¡cie de c‚rebros; e desenvolveu isto com grande c¢pia de racioc¡nios, de textos, de exemplos. Os exemplos achou-os na hist¢ria e em Itagua¡ mas, como um raro esp¡rito que era, reconheceu o perigo de citar todos os casos de Itagua¡ e refugiou-se na hist¢ria. Assim, apontou com especialidade alguns personagens c‚lebres, S¢crates, que tinha um dem“nio familiar, Pascal, que via um abismo … esquerda, Maom‚, Caracala, Domiciano, Cal¡gula, etc., uma enfiada de casos e pessoas, em que de mistura vinham entidades odiosas, e entidades rid¡culas. E porque o botic rio se admirasse de uma tal promiscuidade, o alienista disse-lhe que era tudo a mesma coisa, e at‚ acrescentou sentenciosamente:	\N	10	4
65	-A ferocidade, Sr. Soares, ‚ o grotesco a s‚rio.	\N	11	4
66	-Gracioso, muito gracioso! exclamou Crispim Soares levantando as mÆos ao c‚u.	\N	12	4
67	Quanto … id‚ia de ampliar 0 territ¢rio da loucura, achou-a 0 botic rio extravagante; mas a mod‚stia, principal adorno de seu esp¡rito, nÆo lhe sofreu confessar outra coisa al‚m de um nobre entusiasmo; declarou-a sublime e verdadeira, e acrescentou que era "caso de matraca". Esta expressÆo nÆo tem equivalente no estilo moderno. Naquele tempo, Itagua¡ que como as demais vilas, arraiais e povoa‡äes da col“nia, nÆo dispunha de imprensa, tinha dois modos de divulgar uma not¡cia; ou por meio de cartazes manuscritos e pregados na porta da Cƒmara, e da matriz;-ou por meio de matraca.	\N	13	4
68	Eis em que consistia este segundo uso. Contratava-se um homem, por um ou mais dias, para andar as ruas do povoado, com uma matraca na mÆo.	\N	14	4
69	De quando em quando tocava a matraca, reunia-se gente, e ele anunciava o que lhe incumbiam,-um rem‚dio para sezäes, umas terras lavradias, um soneto, um donativo eclesi stico, a melhor tesoura da vila, o mais belo discurso do ano, etc. O sistema tinha inconvenientes para a paz p£blica; mas era conservado pela grande energia de divulga‡Æo que possu¡a. Por exemplo, um dos vereadores,-aquele justamente que mais se opusera … cria‡Æo da Casa Verde,-desfrutava a reputa‡Æo de perfeito educador de cobras e macacos, e ali s nunca domesticara um s¢ desses bichos; mas, tinha o cuidado de fazer trabalhar a matraca todos os meses. E dizem as cr“nicas que algumas pessoas afirmavam ter visto cascav‚is dan‡ando no peito do vereador; afirma‡Æo perfeitamente falsa, mas s¢ devida … absoluta confian‡a no sistema. Verdade, verdade, nem todas as institui‡äes do antigo reg¡men mereciam o desprezo do nosso s‚culo.	\N	15	4
70	-H  melhor do que anunciar a minha id‚ia, ‚ pratic -la, respondeu o alienista … insinua‡Æo do botic rio.	\N	16	4
71	E o botic rio, nÆo divergindo sensivelmente deste modo de ver, disse-lhe que sim, que era melhor come‡ar pela execu‡Æo.	\N	17	4
72	-Sempre haver  tempo de a dar … matraca, concluiu ele.	\N	18	4
73	SimÆo Bacamarte refletiu ainda um instante, e disse:	\N	19	4
74	-Suponho o esp¡rito humano uma vasta concha, o meu fim, Sr. Soares, ‚ ver se posso extrair a p‚rola, que ‚ a razÆo; por outros termos, demarquemos definitivamente os limites da razÆo e da loucura. A razÆo ‚ o perfeito equil¡brio de todas as faculdades; fora da¡ insƒnia, insƒnia e s¢ insƒnia.	\N	20	4
75	O Vig rio Lopes a quem ele confiou a nova teoria, declarou lisamente que nÆo chegava a entendˆ-la, que era uma obra absurda, e, se nÆo era absurda, era de tal modo colossal que nÆo merecia princ¡pio de execu‡Æo.	\N	21	4
76	-Com a defini‡Æo atual, que ‚ a de todos os tempos, acrescentou, a loucura e a razÆo estÆo perfeitamente delimitadas. Sabe-se onde uma acaba e onde a outra come‡a. Para que transpor a cerca?	\N	22	4
77	Sobre o l bio fino e discreto do alienista rogou a vaga sombra de uma inten‡Æo de riso, em que o desd‚m vinha casado … comisera‡Æo; mas nenhuma palavra saiu de suas egr‚gias entranhas.	\N	23	4
78	A ciˆncia contentou-se em estender a mÆo … teologia, - com tal seguran‡a, que a teologia nÆo soube enfim se devia crer em si ou na outra. Itagua¡ e o universo ficavam … beira de uma revolu‡Æo.	\N	24	4
79	Quatro dias depois, a popula‡Æo de Itagua¡ ouviu consternada a not¡cia de que um certo Costa fora recolhido … Casa Verde.	\N	1	5
80	-Imposs¡vel!	\N	2	5
81	-Qual imposs¡vel! foi recolhido hoje de manhÆ.	\N	3	5
82	- Mas, na verdade, ele nÆo merecia... Ainda em cima! depois de tanto que ele fez...	\N	4	5
83	Costa era um dos cidadÆos mais estimados de Itagua¡, Herdara quatrocentos mil cruzados em boa moeda de El-rei Dom JoÆo V, dinheiro cuja renda bastava, segundo lhe declarou o tio no testamento, para viver "at‚ o fim do mundo". TÆo depressa recolheu a heran‡a, como entrou a dividi-la em empr‚stimos, sem *usura, mil cruzados a um, dois mil a outro, trezentos a este, oitocentos …quele, a tal ponto que, no fim de cinco anos, estava sem nada. Se a mis‚ria viesse de chofre, o pasmo de Itagua¡, seria enorme; mas veio devagar; ele foi passando da opulˆncia … abastan‡a, da abastan‡a … mediania, da mediania … pobreza, da pobreza … mis‚ria, gradualmente. Ao cabo daqueles cinco anos, pessoas que levavam o chap‚u ao chÆo, logo que ele assomava no fim da rua, agora batiam-lhe no ombro, com intimidade, davam-lhe piparotes no nariz, diziam-lhe pulhas. E o Costa sempre lhano, risonho. Nem se lhe dava de ver que os menos corteses eram justamente os que tinham ainda a d¡vida em aberto; ao contr rio, parece que os agasalhava com maior prazer, e mais sublime resigna‡Æo. Um dia, como um desses incur veis devedores lhe atirasse uma chala‡a grossa, e ele se risse dela, observou um desafei‡oado, com certa perf¡dia: - "Vocˆ suporta esse sujeito para ver se ele lhe paga". Costa nÆo se deteve um minuto, foi ao devedor e perdoou-lhe a divida.- "NÆo admira, retorquiu o outro; o Costa abriu mÆo de uma estrela, que est  no c‚u". Costa era perspicaz, entendeu que ele negava todo o merecimento ao ato, atribuindo-lhe a inten‡Æo de rejeitar o que nÆo vinham meter-lhe na algibeira. Era tamb‚m pundonoroso e inventivo; duas horas depois achou um meio de provar que lhe nÆo cabia um tal lab‚u: pegou de algumas dobras, e mandou-as de empr‚stimo ao devedor.	\N	5	5
84	-Agora espero que...-pensou ele sem concluir a frase.	\N	6	5
85	Esse £ltimo rasgo do Costa persuadiu a cr‚dulos e incr‚dulos; ningu‚m mais p“s em d£vida os sentimentos cavalheirescos daquele digno cidadÆo. As necessidades mais acanhadas sa¡ram … rua, vieram bater-lhe … porta, com os seus chinelos velhos, com as suas capas remendadas. Um verme, entretanto, rola a alma do Costa: era o conceito do desafeto. Mas isso mesmo acabou; trˆs meses depois veio este pedir-lhe uns cento e vinte cruzados com promessa de restituir-lhos da¡ a dois dias; era o res¡duo da grande heran‡a, mas era tamb‚m uma nobre desforra: Costa emprestou o dinheiro logo, logo, e sem juros. Infelizmente nÆo teve tempo de ser pago; cinco meses depois era recolhido … Casa Verde.	\N	7	5
145	Sem existir nos bastou.	\N	10	12
87	Imagina-se a consterna‡Æo de Itagua¡, quando soube do caso. NÆo se falou em outra coisa, dizia-se que o Costa ensandecera, ao almo‡o, outros que de madrugada; e contavam-se os acessos, que eram furiosos, sombrios, terr¡veis,-ou mansos, e at‚ engra‡ados, conforme as versäes. Muita gente correu … Casa Verde, e achou o pobre Costa, tranquilo, um pouco espantado, falando com muita clareza, e perguntando por que motivo o tinham levado para ali. Alguns foram ter com o alienista. Bacamarte aprovava esses sentimentos de estima e compaixÆo, mas acrescentava que a ciˆncia era a ciˆncia, e que ele nÆo podia deixar na rua um mentecapto. A £ltima pessoa que intercedeu por ele (porque depois do que vou contar ningu‚m mais se atreveu a procurar o terr¡vel m‚dico) foi uma pobre senhora, prima do Costa. O alienista disse-lhe confidencialmente que esse digno homem nÆo estava no perfeito equil¡brio das faculdades mentais, … vista do modo como dissipara os cabedais que...	\N	8	5
88	Hamlet observa a Hor cio que h  mais cousas no c‚u e na terra do que sonha a nossa filosofia. Era a mesma explica‡Æo que dava a bela Rita ao mo‡o Camilo, numa sexta-feira de novembro de 1869, quando este ria dela, por ter ido na v‚spera consultar uma cartomante; a diferen‡a ‚ que o fazia por outras palavras.	\N	1	6
89	- Ria, ria. Os homens sÆo assim; nÆo acreditam em nada. Pois saiba que fui, e que ela adivinhou o motivo da consulta, antes mesmo que eu lhe dissesse o que era. Apenas come‡ou a botar as cartas, disse-me: "A senhora gosta de uma pessoa..." Confessei que sim, e entÆo ela continuou a botar as cartas, combinou-as, e no fim declarou-me que eu tinha medo de que vocˆ me esquecesse, mas que nÆo era verdade...	\N	2	6
90	- Errou! interrompeu Camilo, rindo. - NÆo diga isso, Camilo. Se vocˆ soubesse como eu tenho andado, por sua causa. Vocˆ sabe; j  lhe disse. NÆo ria de mim, nÆo ria...	\N	3	6
91	Camilo pegou-lhe nas mÆos, e olhou para ela s‚rio e fixo. Jurou que lhe queria muito, que os seus sustos pareciam de crian‡a; em todo o caso, quando tivesse algum receio, a melhor cartomante era ele mesmo. Depois, repreendeu-a; disse-lhe que era imprudente andar por essas casas. Vilela podia sabˆ-lo, e depois...	\N	4	6
92	- Qual saber! tive muita cautela, ao entrar na casa.	\N	5	6
93	- Onde ‚ a casa? - Aqui perto, na Rua da Guarda Velha; nÆo passava ningu‚m nessa ocasiÆo. Descansa; eu nÆo sou maluca.	\N	6	6
94	Camilo riu outra vez: - Tu crˆs deveras nessas cousas? perguntou-lhe.	\N	7	6
95	Foi entÆo que ela, sem saber que traduzia Hamlet em vulgar, disse-lhe que havia muita cousa misteriosa e verdadeira neste mundo. Se ele nÆo acreditava, paciˆncia; mas o certo ‚ que a cartomante adivinhara tudo. Que mais? A prova ‚ que ela agora estava tranquila e satisfeita.	\N	8	6
96	Cuido que ele ia falar, mas reprimiu-se. NÆo queria arrancar-lhe as ilusäes. Tamb‚m ele, em crian‡a, e ainda depois, foi supersticioso, teve um arsenal inteiro de crendices, que a mÆe lhe incutiu e que aos vinte anos desapareceram. No dia em que deixou cair toda essa vegeta‡Æo parasita, e ficou s¢ o tronco da religiÆo, ele, como tivesse recebido da mÆe ambos os ensinos, envolveu-os na mesma d£vida, e logo depois em uma s¢ nega‡Æo total. Camilo nÆo acreditava em nada. Por quˆ? NÆo poderia dizˆ-lo, nÆo possu¡a um s¢ argumento: limitava-se a negar tudo. E digo mal, porque negar ‚ ainda afirmar, e ele nÆo formulava a incredulidade; diante do mist‚rio, contentou-se em levantar os ombros, e foi andando.	\N	9	6
97	Separaram-se contentes, ele ainda mais que ela. Rita estava certa de ser amada; Camilo, nÆo s¢ o estava, mas via-a estremecer e arriscar-se por ele, correr …s cartomantes, e, por mais que a repreendesse, nÆo podia deixar de sentir-se lisonjeado. A casa do encontro era na antiga Rua dos Barbonos, onde morava uma comprovinciana de Rita. Esta desceu pela Rua das Mangueiras, na dire‡Æo de Botafogo, onde residia; Camilo desceu pela da Guarda Velha, olhando de passagem para a casa da cartomante.	\N	10	6
98	Vilela, Camilo e Rita, trˆs nomes, uma aventura e nenhuma explica‡Æo das origens. Vamos a ela. Os dois primeiros eram amigos de infƒncia. Vilela seguiu a carreira de magistrado. Camilo entrou no funcionalismo, contra a vontade do pai, que queria vˆ-lo m‚dico; mas o pai morreu, e Camilo preferiu nÆo ser nada, at‚ que a mÆe lhe arranjou um emprego p£blico. No princ¡pio de 1869, voltou Vilela da prov¡ncia, onde casara com uma dama formosa e tonta; abandonou a magistratura e veio abrir banca de advogado. Camilo arranjou-lhe casa para os lados de Botafogo, e foi a bordo recebˆ-lo.	\N	11	6
99	Camilo e Vilela olharam-se com ternura. Eram amigos deveras. Depois, Camilo confessou de si para si que a mulher do Vilela nÆo desmentia as cartas do marido. Realmente, era graciosa e viva nos gestos, olhos c lidos, boca fina e interrogativa. Era um pouco mais velha que ambos: contava trinta anos, Vilela vinte e nove e Camilo vinte e seis. Entretanto, o porte grave de Vilela fazia-o parecer mais velho que a mulher, enquanto Camilo era um ingˆnuo na vida moral e pr tica. Faltava-lhe tanto a a‡Æo do tempo, como os ¢culos de cristal, que a natureza päe no ber‡o de alguns para adiantar os anos. Nem experiˆncia, nem intui‡Æo.	\N	13	6
100	Uniram-se os trˆs. Convivˆncia trouxe intimidade. Pouco depois morreu a mÆe de Camilo, e nesse desastre, que o foi, os dois mostraram-se grandes amigos dele. Vilela cuidou do enterro, dos sufr gios e do invent rio; Rita tratou especialmente do cora‡Æo, e ningu‚m o faria melhor.	\N	14	6
101	Como da¡ chegaram ao amor, nÆo o soube ele nunca. A verdade ‚ que gostava de passar as horas ao lado dela, era a sua enfermeira moral, quase uma irmÆ, mas principalmente era mulher e bonita. Odor di feminina: eis o que ele aspirava nela, e em volta dela, para incorpor -lo em si pr¢prio. Liam os mesmos livros, iam juntos a teatros e passeios. Camilo ensinou-lhe as damas e o xadrez e jogavam …s noites; - ela mal, - ele, para lhe ser agrad vel, pouco menos mal. At‚ a¡ as cousas. Agora a a‡Æo da pessoa, os olhos teimosos de Rita, que procuravam muita vez os dele, que os consultavam antes de o fazer ao marido, as mÆos frias, as atitudes ins¢litas. Um dia, fazendo ele anos, recebeu de Vilela uma rica bengala de presente e de Rita apenas um cartÆo com um vulgar cumprimento a l pis, e foi entÆo que ele p“de ler no pr¢prio cora‡Æo, nÆo conseguia arrancar os olhos do bilhetinho. Palavras vulgares; mas h  vulgaridades sublimes, ou, pelo menos, deleitosas. A velha cale‡a de pra‡a, em que pela primeira vez passeaste com a mulher amada, fechadinhos ambos, vale o carro de Apolo. Assim ‚ o homem, assim sÆo as cousas que o cercam.	\N	15	6
121	Voltemos ao carrilhÆo. J  referi que entrara na igreja, nÆo contei; mas entende-se, que na igreja nÆo entram revolu‡äes, por isso nÆo falo da do Rio Grande do Sul. Pode entrar a anarquia, ‚ verdade, como a daquele singular p roco da Bahia, que, mandado calar e declarado suspenso de ordens, segundo dizem telegramas, nÆo obedece, nÆo se cala, e continua a paroquiar. Os clavinoteiros tamb‚m nÆo entram; por isso amea‡am Porto Seguro, conforme outros telegramas. NÆo entram discursos parlamentares, nem lutas ¡talo - santistas, nem aux¡lios …s ind£strias, nem nada. H  ali um ref£gio contra os tumultos exteriores e contra os boatos, que recome‡am. Voltemos ao carrilhÆo.	\N	3	10
102	Camilo quis sinceramente fugir, mas j  nÆo p“de. Rita, como uma serpente, foi-se acercando dele, envolveu-o todo, fez-lhe estalar os ossos num espasmo, e pingou-lhe o veneno na boca. Ele ficou atordoado e subjugado. Vexame, sustos, remorsos, desejos, tudo sentiu de mistura, mas a batalha foi curta e a vit¢ria delirante. Adeus, escr£pulos! NÆo tardou que o sapato se acomodasse ao p‚, e a¡ foram ambos, estrada fora, bra‡os dados, pisando folgadamente por cima de ervas e pedregulhos, sem padecer nada mais que algumas saudades, quando estavam ausentes um do outro. A confian‡a e estima de Vilela continuavam a ser as mesmas. Um dia, por‚m, recebeu Camilo uma carta an“nima, que lhe chamava imoral e p‚rfido, e dizia que a aventura era sabida de todos. Camilo teve medo, e, para desviar as suspeitas, come‡ou a rarear as visitas … casa de Vilela. Este notou-lhe as ausˆncias. Camilo respondeu que o motivo era uma paixÆo fr¡vola de rapaz. Candura gerou ast£cia. As ausˆncias prolongaram-se, e as visitas cessaram inteiramente. Pode ser que entrasse tamb‚m nisso um pouco de amor-pr¢prio, uma inten‡Æo de diminuir os obs‚quios do marido, para tornar menos dura a aleivosia do ato.	\N	16	6
103	[24 abril] NA SEGUNDA-FEIRA da semana que findou, acordei cedo, pouco depois das galinhas, e dei-me ao gosto de propor a mim mesmo um problema. Verdadeiramente era uma charada, mas o nome de problema d  dignidade, e excita para logo a aten‡Æo dos leitores austeros. Sou como as atrizes, que j  nÆo fazem benef¡cio, mas festa art¡stica. A cousa ‚ a mesma, os bilhetes crescem de igual modo, seja em n£mero, seja em pre‡o; o resto, com‚dia, drama, opereta, uma polca entre dous atos, uma poesia, v rias ramalhetes, lampiäes fora, e os colegas em grande gala, oferecendo em cena o retrato … beneficiada. Tudo pede certa eleva‡Æo. Conheci dous velhos estim veis, vizinhos, que esses tinham todos os dias a sua festa art¡stica. Um era Cavaleiro da Ordem da Rosa, por servi‡os em rela‡Æo … guerra do Paraguai; o outro tinha o posto de tenente da guarda nacional da reserva, a que prestava bons servi‡os. Jogavam xadrez, e dormiam no intervalo das jogadas. Despertavam-se um ao outro desta maneira: "Caro major!" -"Pronto, comendador!" - Variavam …s vezes: - "Caro comendador!" -"A¡ vou, Major". Tudo pede certa eleva‡Æo. Para nÆo ir mais longe.	\N	1	7
104	Tiradentes. Aqui est  um exemplo. Tivemos esta semana o centen rio do grande m rtir. A prisÆo do her¢ico alferes ‚ das que devem ser comemoradas por todos os filhos deste pa¡s, se h  nele patriotismo, ou se esse patriotismo ‚ outra cousa mais que um simples motivo de palavras grossas e rotundas. A capital portou-se bem. Dos Estados estÆo vindo boas not¡cias. O instinto popular, de acordo com o exame da razÆo, fez da figura do alferes Xavier o principal dos Inconfidentes, e colocou os seus parceiros a meia ra‡Æo da gl¢ria. Merecem, decerto, a nossa estima‡Æo aqueles outros; eram patriotas. Mas o que se ofereceu a carregar com os pecados de Israel, o que chorou de alegria quando viu comutada a pena de morte dos seus companheiros, pena que s¢ ia ser executada nele, o enforcado, o esquartejado, o decapitado, esse tem de receber o prˆmio na propor‡Æo do mart¡rio, e ganhar por todos, visto que pagou por todos.	\N	2	7
105	Entretanto, o alferes Joaquim Jos‚ tem ainda contra si uma cousa: a alcunha. H  pessoas que o amam, que o admiram, patri¢ticas e humanas, mas que nÆo podem tolerar esse nome de Tiradentes. Certamente que o tempo trar  a familiaridade do nome e a harmonia das s¡labas; imaginemos, por‚m, que o alferes tem podido galgar pela imagina‡Æo um s‚culo e despachar-se cirurgiÆo-dentista. Era o mesmo her¢i, e o of¡cio era o mesmo; mas traria outra dignidade. Podia ser at‚ que, com o tempo, viesse a perder a segunda parte, dentista, e quedar-se apenas cirurgiÆo.	\N	4	7
106	H  muitos anos, um rapaz-por sinal que bonito-estava para casar com uma linda mo‡a-a aprazimento de todos, pais e mÆes, irmÆos, tios e primos. Mas o noivo demorava o cons¢rcio; adiava de um s bado para outro, depois quinta-feira, logo ter‡a, mais tarde s bado;-dou meses de espera. Ao fim desse tempo, o futuro sogro comunicou … mulher os seus receios. Talvez o rapaz nÆo quisesse casar. A sogra, que antes de o ser j  era, pegou o pau moral, e foi ter com o esquisito genro. Que hist¢rias eram aquelas de adiamento?	\N	5	7
107	-PerdÆo, minha senhora, ‚ uma nobre e alta razÆo; espero apenas . . . -Apenas...? -Apenas o meu t¡tulo de agrimensor. -De agrimensor? Mas quem lhe diz que minha filha precisa do seu of¡cio para comer? Case, que nÆo morrer  de fome; o t¡tulo vir  depois. -PerdÆo, mas nÆo ‚ pelo t¡tulo de agrimensor, propriamente dito, que estou demorando o casamento. L  na ro‡a d -se ao agrimensor, por cortesia, o t¡tulo de doutor, e eu quisera casar j  doutor . . .	\N	6	7
108	Sogra, sogro, noiva, parentes, todos entenderam esta sutileza, e aprovaram o mo‡o. Em boa hora o fizeram. Dali a trˆs meses recebia o noivo os t¡tulos de agrimensor, de doutor e de marido.	\N	7	7
109	Daqui ao caso eleitoral ‚ menos que um passo; mas, nÆo entendendo eu de pol¡tica, ignoro se a ausˆncia de tÆo grande parte do eleitorado na elei‡Æo do dia 20 quer dizer descren‡a, como afirmam uns, ou absten‡Æo como outros juram. A descren‡a ‚ fen“meno alheio … vontade do eleitor: a absten‡Æo ‚ prop¢sito. H  quem nÆo veja em tudo isto mais de ignorƒncia do poder daquele fogo que Tiradentes legou aos seus patr¡cios.	\N	8	7
110	O que sei, ‚ que fui … minha se‡Æo para votar, mas achei a porta fechada e a urna na rua, com os livros e of¡cios. Outra casa os acolheu compassiva, mas os mes rios nÆo tinham sido avisados e os eleitores eram cinco. Discutimos a questÆo de saber o que ‚ que nasceu primeiro, se a galinha, se o ovo. Era o problema, a charada, a adivinha‡Æo de segunda-feira. Dividiram-se as opiniäes; uns foram pelo ovo outros pela galinha; o pr¢prio galo teve um voto. Os candidatos ‚ que nÆo tiveram nem um, porque os mes rios nÆo vieram e bateram dez horas.	\N	9	7
111	Podia acabar em prosa, mas prefiro o verso: Sara, belle dindolence, Se balance Dans un hamac...	\N	10	7
122	Criado, como ia dizendo, com os pobres sinos das nossas igrejas, nÆo provei at‚ certa idade as aventuras de um carrilhÆo. Ouvia falar de carrilhÆo, como das ilhas Filipinas, uma cousa que eu nunca havia de ver nem ouvir. Um dia, anuncia-se a chegada de um carrilhÆo. T¡nhamos carrilhÆo na terra. Outro dia, indo a passar por uma rua, ou‡o uns sons alegres e animados. Conhecia a toada, mas nÆo lembrava a letra. Perguntei a um menino, que me indicou a igreja pr¢xima e disse-me que era o carrilhÆo. E, nÆo contente com a resposta, p“s a letra na m£sica: era o Amor Tem Fogo.	\N	4	10
123	Geralmente, nÆo dou f‚ a crian‡as. Fui a um homem que estava … porta de uma loja e o homem confirmou o caso, e cantou do mesmo modo; depois calou-se e disse convencidamente: parece incr¡vel como se possa, sem o prest¡gio do teatro, as saias das mulheres, os requebrados, etc., dar uma impressÆo tÆo exata da opereta. Feche os olhos, ou‡a-me a mim e ao carrilhÆo, e diga-me se nÆo ouve a opereta em carne e osso: Amor tem fogo, Tem fogo amor. - Carne sem osso, meu rico senhor, carne sem osso.	\N	5	10
124	PRIMEIRO / O DOS CASTELOS	\N	1	11
125	A Europa jaz, posta nos cotovelos:	\N	2	11
126	De Oriente a Ocidente jaz, fitando,	\N	3	11
112	[19 junho] O BANCO INICIADOR de Melhoramentos acaba de iniciar um melhoramento, que vem mudar essencialmente a composi‡Æo das atas das assembl‚ias gerais de acionistas. Estes documentos (toda a gente o sabe) sÆo o resumo das delibera‡äes dos acionistas, quer dizer uma narra‡Æo sum ria, em estilo indireto e seco, do que se passou entre eles, relativamente ao objeto que os congregou. NÆo dÆo a menor sensa‡Æo dos movimentos e da vida dos debates. As narra‡äes liter rias, quando se regem por esse processo, podem vencer o t‚dio, … for‡a de talento, mas ‚ evidentemente melhor que as cousas e pessoas se exponham por si mesmas, dando-se a palavra a todos, e a cada um a sua natural linguagem. Tal ‚ o melhoramento a que aludo. A ata que aquela associa‡Æo publicou esta semana, ‚ um modelo novo, de extraordin rio efeito. Nada falta do que se disse, e pela boca de quem disse, … maneira dos debates congressionais.-"Pe‡o a palavra pela ordem"-"Est  encerrada a discussÆo e vai-se proceder … vota‡Æo. Os senhores que aprovam queiram ficar sentados." Tudo assim, qual se passou, se ouviu, se replicou e se acabou. E basta um exemplo para mostrar a vantagem da reforma. Tratando-se de resolver sobre o balan‡o, consultou o presidente … assembl‚ia se a vota‡Æo seria por a‡äes, ou nÆo. Um s¢ acionista adotou a afirmativa; e tanto bastava para que os votos se contassem por a‡äes, como declarou o presidente, mas outro acionista pediu a palavra pela ordem. "Tem a palavra pela ordem." E o acionista: "Pe‡o a V. Ex.a Sr. Presidente, que consulte ao Sr. acionista que se levantou, se ele desiste, visto que a vota‡Æo por a‡äes, exigindo a chamada, tomar  muito tempo". Consultado o divergente, este desistiu, e a vota‡Æo se fez per capita. Assim ficamos sabendo que o tempo ‚ a causa da supressÆo de certas formalidades exteriores; e assim tamb‚m vemos que cada um, desde que a mat‚ria nÆo seja essencial, sacrifica facilmente o seu parecer em benef¡cio comum.	\N	1	8
113	-Como esta esp‚cie corresponde j  … sua ¡ndole! diria Deus consigo. H  de ser assim sempre, impaciente, incapaz de esperar a hora pr¢pria. Nunca os rel¢gios, que h  de inventar, andarÆo todos certos. Por um exato, contar-se-Æo milhäes divergentes, e a casa em que dous marearem o mesmo minuto. nÆo apresentar  igual fen“meno vinte e quatro horas depois. Esp‚cie inquieta, que formar  reinos para devor -los, rep£blicas para dissolvˆ-las, democracias, aristocracias, oligarquias, plutocracias, autocracias, para acabar com elas, … procura do ¢timo, que nÆo achar  nunca.	\N	4	8
114	E, bocejando outra vez, ter  Deus acrescentado: - O bocejo, que em mim ‚ o sinal do fastio que me d  este espet culo futuro, tamb‚m a esp‚cie humana o ter , mas por impaciˆncia. O tempo lhe parecer  a eternidade. Tudo que lhe durar mais de algumas horas, dias, semanas, meses ou anos (porque ela dividir  o tempo e inventar  almanaques, h  de torn -la impaciente de ver outra cousa e desfazer o que acabou de fazer, …s vezes antes de o ter acabado. Compreender  as vacas gordas, porque a gordura d  que comer, mas nÆo entender  as vacas magras; e nÆo saber  (exceto no Egito, onde porei um mancebo chamado Jos‚) encher os celeiros dos anos gra£dos, para acudir … pen£ria dos anos mi£dos. Falar  muitas l¡nguas, beresith, anank‚, habeas corpus, sem se fixar de vez em uma s¢, e quando chegar a entender que uma l¡ngua £nica ‚ precisa, e inventar o volapuk, sucessor do parlamentarismo, ter  come‡ado a decadˆncia e a transforma‡Æo. Pode ser entÆo que eu povoe o mundo de can rios.	\N	5	8
115	Mas se assim explicarmos o primeiro bocejo divino, como acharmos o primeiro bocejo humano? Trevas tudo. O mesmo se d  com o nome que encima estas linhas. Nem me lembra em que ano apareceu a f¢rmula. Bonita era, e o verbo encimar nÆo era feio. Entrou a reproduzir-se de um modo infinito. Toda a gente tinha um nome que encimar algumas linhas. NÆo havia anivers rio, nomea‡Æo, embarque, desembarque, esmola, inaugura‡Æo, nÆo havia nada que nÆo inspirasse algumas linhas a algu‚m, - …s vezes com o maior fim de encim -las por um nome.	\N	6	8
116	Como era natural, a f¢rmula foi-se gastando-mas gastando pelo mesmo modo por que se gastam os sapatos econ“micos, que envelhecem tarde. E todos os nomes do calend rio foram encimando todas as linhas; depois, repetiram-se: Si cette histoire vous embˆte Nous allons la recommencer.	\N	7	8
117	Era aqui na Cƒmara dos Deputados, que um honrado membro, quando desconfiava do governo pedia a palavra ao presidente, e, obtida a palavra, erguia-se. Curto ou extenso, mas geralmente t‚trico, proferia um discurso em que resumia todos os erros e crimes do minist‚rio, e acabava sacando um papel do bolso. Esse papel era a mo‡Æo. De confidˆncias que recebi, sei que h  poucas sensa‡äes na vida iguais … que tinha o orador, quando sacava o papel do bolso. A alguns tremiam os dedos. Os olhos percorriam a sala, depois baixavam ao papel e liam o conte£do. Em seguida a mo‡Æo era enviada ao presidente, e o orador descia da tribuna, isto ‚, das pernas que sÆo a £nica tribuna que h  no nosso parlamento, nÆo contando uns dous p£lpitos que l  puseram uma vez, e nÆo serviram para nada. A¡ tˆm o que era a mo‡Æo. Nunca as assembl‚ias provinciais tiveram esse regalo; menos ainda as tristes cƒmaras municipais. Mudado o reg¡men, acabou a mo‡Æo; mas, nÆo se morre por decreto. A mo‡Æo nÆo s¢ vive ainda, mas passou dos deuses centrais aos semideuses locais, e viver  algum tempo, at‚ que acabe de todo, se acabar algum dia. O caso grego ‚ sintom tico; o caso japonˆs nÆo menos. H  mo‡äes japonesas. Quando as houver chinesas, chegou o fim do mundo; nÆo haver  mais que fechar as malas e ir para o diabo.	\N	3	9
118	Dizem telegramas de S. Paulo que foi ali achado, em certa casa que se demolia, um esqueleto algemado. NÆo tenho amor a esqueletos; mas este esqueleto algemado diz-me alguma cousa, e ‚ dif¡cil que eu o mandasse embora, sem trˆs ou quatro perguntas. Talvez ele me contasse uma hist¢ria grave, longa e naturalmente triste, porque as algemas nÆo sÆo alegres. Alegres eram umas m scaras de lata que vi em pequeno na cara de escravos dados … cacha‡a; alegres ou grotescas, nÆo sei bem, porque l  vÆo muitos anos, e eu era tÆo crian‡a, que nÆo distinguia bem. A verdade ‚ que as m scaras faziam rir, mais que as do recente carnaval. O ferro das algemas, sendo mais duro que a lata, a hist¢ria devia ser mais sombria.	\N	5	9
119	H  um telegrama... Diabo! acabou-se o espa‡o, e ainda aqui tenho uma d£zia. Cesta com eles! VÆo para onde foi a questÆo do benzimento da bandeira, os guarda-livros que fogem levando a caixa (outro telegrama), e o resto dos restos, que nÆo dura mais de uma semana, nem tanto. VÆo para onde j  foi esta cr“nica. Fale o leitor a sua verdade. e diga-me se lhe ficou alguma cousa do que acabou de ler. Talvez uma s¢, a palavra clavinoteiros, que parece exprimir um costume ou um of¡cio. C  vai para o vocabul rio.	\N	6	9
120	Entretanto, pergunto eu: nÆo se dar  o progresso, algumas vezes, na pr¢pria terra? Citarei um fato. Conheci h  anos um velho, bastante alquebrado e assaz culto, que me afirmava estar na segunda encarna‡Æo. Antes disso, tinha existido no corpo de um soldado romano, e, como tal, havia assistido … morte de Cristo. Referia-me tudo, e at‚ circunstƒncias que nÆo constam das escrituras. Esse bom velho nÆo falava da terceira e pr¢xima encarna‡Æo sem grande alegria, pela certeza que tinha de que lhe caberia um grande cargo. Pensava na coroa da Alemanha... E quem nos pode afirmar que o Guilherme II. que a¡ est , nÆo seja ele? H , repetimos, cousas na vida que ‚ mais acertado crer que desmentir; e quem nÆo puder - crer, que se cale.	\N	2	10
127	E toldam-lhe romanticos cabelos	\N	4	11
128	Olhos gregos, lembrando.	\N	5	11
129	O cotovelo esquerdo ‚ recuado;	\N	6	11
130	O direito ‚ em ƒngulo disposto.	\N	7	11
131	Aquele diz It lia onde ‚ pousado;	\N	8	11
146	Por nÆo ter vindo foi vindo	\N	11	12
147	E nos criou.	\N	12	12
148		\N	13	12
149	Assim a lenda se escorre	\N	14	12
150	A entrar na realidade,	\N	15	12
151	E a fecund -la decorre.	\N	16	12
152	Em baixo, a vida, metade	\N	17	12
153	De nada, morre.	\N	18	12
154		\N	19	12
155	SEGUNDO / VIRIATO	\N	20	12
156	Se a alma que sente e faz conhece	\N	21	12
157	S¢ porque lembra o que esqueceu,	\N	22	12
158	Vivemos, ra‡a, porque houvesse	\N	23	12
159	Mem¢ria em n¢s do instinto teu.	\N	24	12
160		\N	25	12
161	Na‡Æo porque reencarnaste,	\N	26	12
162	Povo porque ressuscitou	\N	27	12
163	Ou tu, ou o de que eras a haste -	\N	28	12
164	Assim se Portugal formou.	\N	29	12
165		\N	30	12
166	Teu ser ‚ como aquela fria	\N	31	12
167	Luz que precede a madrugada,	\N	32	12
168	E ‚ ja o ir a haver o dia	\N	33	12
169	Na antemanhÆ, confuso nada.	\N	34	12
170		\N	35	12
171	TERCEIRO / O CONDE D. HENRIOUE	\N	36	12
172	Todo come‡o ‚ involunt ario.	\N	37	12
173	Deus ‚ o agente.	\N	38	12
174	O her¢i a si assiste, v rio	\N	39	12
175	E inconsciente.	\N	40	12
176		\N	41	12
177	· espada em tuas mÆos achada	\N	42	12
178	Teu olhar desce.	\N	43	12
179	®Que farei eu com esta espada?¯	\N	44	12
180	Ergueste-a, e fez-se.	\N	45	12
181		\N	46	12
182	QUARTO / D. TAREJA	\N	47	12
183	As na‡äes todas sÆo myst‚rios.	\N	48	12
184	Cada uma ‚ todo o mundo a s¢s.	\N	49	12
185	à mÆe de reis e av¢ de imp‚rios,	\N	50	12
186	Vela por n¢s!	\N	51	12
187		\N	52	12
188	Teu seio augusto amamentou	\N	53	12
189	Com bruta e natural certeza	\N	54	12
190	O que, imprevisto, Deus fadou.	\N	55	12
191	Por ele reza!	\N	56	12
192		\N	57	12
193	Dˆ tua prece outro destino	\N	58	12
194	A quem fadou o instinto teu!	\N	59	12
195	O homem que foi o teu menino	\N	60	12
196	Envelheceu.	\N	61	12
197		\N	62	12
198	Mas todo vivo ‚ eterno infante	\N	63	12
199	Onde est s e nÆo h  o dia.	\N	64	12
200	No antigo seio, vigilante,	\N	65	12
201	De novo o cria!	\N	66	12
202		\N	67	12
203	QUINTO / D. AFONSO HENRIQUES	\N	68	12
204	Pai, foste cavaleiro.	\N	69	12
205	Hoje a vig¡lia ‚ nossa.	\N	70	12
206	D -nos o exemplo inteiro	\N	71	12
207	E a tua inteira for‡a!	\N	72	12
208		\N	73	12
209	D , contra a hora em que, errada,	\N	74	12
210	Novos infi‚is ven‡am,	\N	75	12
211	A bˆn‡Æo como espada,	\N	76	12
212	A espada como ben‡Æo!	\N	77	12
213		\N	78	12
214	SEXTO / D. DINIS	\N	79	12
215	Na noite escreve um seu Cantar de Amigo	\N	80	12
216	O plantador de naus a haver,	\N	81	12
217	E ouve um silˆncio murmuro consigo:	\N	82	12
218	De Imp‚rio, ondulam sem se poder ver.	\N	84	12
219		\N	85	12
220	Arroio, esse cantar, jovem e puro,	\N	86	12
221	Busca o oceano por achar;	\N	87	12
222	E a fala dos pinhais, marulho obscuro,	\N	88	12
223		\N	91	12
224	O homem e a hora sÆo um s¢	\N	93	12
225	Quando Deus faz e a hist¢ria ‚ feita.	\N	94	12
226	O mais ‚ carne, cujo p¢	\N	95	12
227	A terra espreita.	\N	96	12
228		\N	97	12
229	Mestre, sem o saber, do Templo	\N	98	12
230	Que Portugal foi feito ser,	\N	99	12
231	Que houveste a gl¢ria e deste o exemplo	\N	100	12
232	De o defender.	\N	101	12
233		\N	102	12
234	Teu nome, eleito em sua fama,	\N	103	12
235	A que repele, eterna chama,	\N	105	12
236	A sombra eterna.	\N	106	12
237		\N	107	12
238	Que enigma havia em teu seio	\N	109	12
239	Que s¢ gˆnios concebia?	\N	110	12
240	Que arcanjo teus sonhos veio	\N	111	12
241	Velar, maternos, um dia?	\N	112	12
242		\N	113	12
243	Volve a n¢s teu rosto s‚rio,	\N	114	12
244	Princesa do Santo Graal,	\N	115	12
245	Humano ventre do Imp‚rio,	\N	116	12
246	Madrinha de Portugal!	\N	117	12
247	Eu nunca guardei rebanhos,	\N	1	13
248	Mas ‚ como se os guardasse.	\N	2	13
249	Minha alma ‚ como um pastor,	\N	3	13
250	Conhece o vento e o sol	\N	4	13
251	E anda pela mÆo das Esta‡äes	\N	5	13
252	A seguir e a olhar.	\N	6	13
253	Toda a paz da Natureza sem gente	\N	7	13
254	Vem sentar-se a meu lado.	\N	8	13
255	Mas eu fico triste como um p“r de sol	\N	9	13
256	Para a nossa imagina‡Æo,	\N	10	13
257	Quando esfria no fundo da plan¡cie	\N	11	13
258	E se sente a noite entrada	\N	12	13
259	Como uma borboleta pela janela.	\N	13	13
260		\N	14	13
261	Mas a minha tristeza ‚ sossego	\N	15	13
262	Porque ‚ natural e justa	\N	16	13
263	E ‚ o que deve estar na alma	\N	17	13
264	Quando j  pensa que existe	\N	18	13
265	E as mÆos colhem flores sem ela dar por isso.	\N	19	13
266		\N	20	13
267	Como um ru¡do de chocalhos	\N	21	13
268	Para al‚m da curva da estrada,	\N	22	13
269	Os meus pensamentos sÆo contentes.	\N	23	13
270	S¢ tenho pena de saber que eles sÆo contentes,	\N	24	13
271	Porque, se o nÆo soubesse,	\N	25	13
272	Em vez de serem contentes e tristes,	\N	26	13
273	Seriam alegres e contentes.	\N	27	13
274		\N	28	13
275	Pensar incomoda como andar … chuva	\N	29	13
276	Quando o vento cresce e parece que chove mais.	\N	30	13
277		\N	31	13
278	NÆo tenho ambi‡äes nem desejos	\N	32	13
279	Ser poeta nÆo ‚ uma ambi‡Æo minha	\N	33	13
280		\N	35	13
281	E se desejo …s vezes	\N	36	13
282	Por imaginar, ser cordeirinho	\N	37	13
283	(Ou ser o rebanho todo	\N	38	13
284	Para andar espalhado por toda a encosta	\N	39	13
285	A ser muita cousa feliz ao mesmo tempo),	\N	40	13
286		\N	41	13
287	Ou quando uma nuvem passa a mÆo por cima da luz	\N	43	13
288	E corre um silˆncio pela erva fora.	\N	44	13
289		\N	45	13
290	Quando me sento a escrever versos	\N	46	13
291	Ou, passeando pelos caminhos ou pelos atalhos,	\N	47	13
292	Escrevo versos num papel que est  no meu pensamento,	\N	48	13
293	Sinto um cajado nas mÆos	\N	49	13
294	E vejo um recorte de mim	\N	50	13
295	No cimo dum outeiro,	\N	51	13
296	Olhando para o meu rebanho e vendo as minhas id‚ias,	\N	52	13
297	Ou olhando para as minhas id‚ias e vendo o meu rebanho,	\N	53	13
298	E sorrindo vagamente como quem nÆo compreende o que se diz	\N	54	13
299	E quer fingir que compreende.	\N	55	13
300		\N	56	13
301	Sa£do todos os que me lerem,	\N	57	13
302	Tirando-lhes o chap‚u largo	\N	58	13
303	Quando me vˆem … minha porta	\N	59	13
304	Mal a diligˆncia levanta no cimo do outeiro.	\N	60	13
305	Sa£do-os e desejo-lhes sol,	\N	61	13
306	E chuva, quando a chuva ‚ precisa,	\N	62	13
307	E que as suas casas tenham	\N	63	13
308	Ao p‚ duma janela aberta	\N	64	13
309	Uma cadeira predileta	\N	65	13
310	Onde se sentem, lendo os meus versos.	\N	66	13
311	E ao lerem os meus versos pensem	\N	67	13
312	Que sou qualquer cousa natural -	\N	68	13
313	Por exemplo, a  rvore antiga	\N	69	13
314	· sombra da qual quando crian‡as	\N	70	13
315	Se sentavam com um baque, cansados de brincar,	\N	71	13
316	E limpavam o suor da testa quente	\N	72	13
317	Com a manga do bibe riscado.	\N	73	13
318	O meu olhar ‚ n¡tido como um girassol.	\N	1	14
319	Tenho o costume de andar pelas estradas	\N	2	14
320	Olhando para a direita e para a esquerda,	\N	3	14
321	E de, vez em quando olhando para tr s...	\N	4	14
322	E o que vejo a cada momento	\N	5	14
323	E eu sei dar por isso muito bem...	\N	7	14
324	Sei ter o pasmo essencial	\N	8	14
325	Que tem uma crian‡a se, ao nascer,	\N	9	14
326	Reparasse que nascera deveras...	\N	10	14
327	Sinto-me nascido a cada momento	\N	11	14
328	Para a eterna novidade do Mundo...	\N	12	14
329		\N	13	14
330	Creio no mundo como num malmequer,	\N	14	14
331	Porque o vejo.Mas nÆo penso nele	\N	15	14
332	Porque pensar ‚ nÆo compreender ...	\N	16	14
333		\N	17	14
334	O Mundo nÆo se fez para pensarmos nele	\N	18	14
335	(Pensar ‚ estar doente dos olhos)	\N	19	14
336	Mas para olharmos para ele e estarmos de acordo...	\N	20	14
337		\N	21	14
338	Eu nÆo tenho filosofia: tenho sentidos...	\N	22	14
339	Se falo na Natureza nÆo ‚ porque saiba o que ela ‚,	\N	23	14
340	Mas porque a amo, e amo-a por isso,	\N	24	14
341	Porque quem ama nunca sabe o que ama	\N	25	14
342	Nem sabe por que ama, nem o que ‚ amar ...	\N	26	14
343	Amar ‚ a eterna inocˆncia,	\N	27	14
344	E a £nica inocˆncia nÆo pensar...	\N	28	14
345	Ao entardecer, debru‡ado pela janela,	\N	1	15
346	E sabendo de soslaio que h  campos em frente,	\N	2	15
347	Leio at‚ me arderem os olhos	\N	3	15
348	O livro de Ces rio Verde.	\N	4	15
349		\N	5	15
350	Que pena que tenho dele! Ele era um camponˆs	\N	6	15
351	Que andava preso em liberdade pela cidade.	\N	7	15
352	Mas o modo como olhava para as casas,	\N	8	15
353	E o modo como reparava nas ruas,	\N	9	15
354	E a maneira como dava pelas cousas,	\N	10	15
355	E de quem desce os olhos pela estrada por onde vai andando	\N	12	15
356	E anda a reparar nas flores que h  pelos campos ...	\N	13	15
357		\N	14	15
358	Por isso ele tinha aquela grande tristeza	\N	15	15
359	Que ele nunca disse bem que tinha,	\N	16	15
360	Mas andava na cidade como quem anda no campo	\N	17	15
361	E triste como esmagar flores em livros	\N	18	15
362	E p“r plantas em jarros...	\N	19	15
\.


--
-- Data for Name: paragrafo_resenha; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.paragrafo_resenha (id_paragrafo_resenha, texto_paragrafo_resenha, id_resenha, ordem_paragrafo_resenha) FROM stdin;
\.


--
-- Data for Name: personagem; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.personagem (id_personagem, nome_personagem, genero, idade, funcao, descricao_personagem, id_livro) FROM stdin;
\.


--
-- Data for Name: post; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.post (id_post, titulo_post, id_user, url_imagem, conteudo, criado_em, visibilidade) FROM stdin;
\.


--
-- Data for Name: preferencia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.preferencia (id_preferencia, nome_preferencia, preferencia) FROM stdin;
1	Terror	terror
2	Com‚dia	comedia
3	Romance	romance
6	Dark Romance	dark-romance
7	Drama	drama
8	Suspense	suspense
9	A‡Æo	acao
10	Aventura	aventura
11	Fantasia	fantasia
12	Novela	novela
13	Mang 	manga
14	Hist¢ria em Quadrinhos	hq
15	Webtoons	webtoons
16	Fic‡Æo Cient¡fica	ficcao-cientifica
17	Policial	policial
18	Medico	medico
20	Biografia	biografia
21	Document rio	documentario
23	Hist¢ria	historia
24	Filosofia	filosofia
25	Psicologia	psicologia
26	Pol¡tica	politica
27	Poesia	poesia
28	Gastronomia	gastronomia
29	Mitologia	mitologia
\.


--
-- Data for Name: preferencia_livro; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.preferencia_livro (id, id_preferencia, id_livro) FROM stdin;
\.


--
-- Data for Name: preferencia_user; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.preferencia_user (id_user, id_preferencia, id) FROM stdin;
9	1	1
9	6	2
9	13	3
24	1	4
24	2	5
24	3	6
27	1	10
27	2	11
27	3	12
\.


--
-- Data for Name: progresso_leitura; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.progresso_leitura (id_progresso, id_user, id_livro, capitulo_atual, porcentagem_progresso, ultima_leitura) FROM stdin;
\.


--
-- Data for Name: recuperacao_senha; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.recuperacao_senha (id_recuperacao, id_user, token, expira_em, usado) FROM stdin;
3	19	8727e2129763237457fe0177f3988048fc2747d3bafdcb5a4cb3d6cf451fc9fd	2026-08-07 15:40:13	t
4	27	8aa12f41246f5fbb5025766e44d11cc25eaef9f4d0c3dda780c2510c23ff9d68	2026-08-10 22:34:27	t
\.


--
-- Data for Name: rel_worldbuild; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rel_worldbuild (id_cena, id_cenario, id_personagem) FROM stdin;
\.


--
-- Data for Name: resenha; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.resenha (id_resenha, titulo_resenha, id_user, sinopse, class_ind, data_publi, id_livro, visibilidade) FROM stdin;
\.


--
-- Data for Name: top5_livros; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.top5_livros (id_user, id_livro, posicao, atualizado_em) FROM stdin;
19	3	2	2026-08-10 14:02:21.28066
19	1	4	2026-08-11 21:18:28.213477
19	5	5	2026-08-12 10:51:44.127281
19	2	3	2026-08-10 14:02:13.520825
19	4	1	2026-08-10 14:02:16.897917
\.


--
-- Data for Name: usuario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuario (id_user, nome_completo, username, data_nascimento, email, senha, criacao_conta, google_id) FROM stdin;
3	Gustavo Gomez	Xerife	1994-07-13	gugo@gmail.com	gomez15	2026-07-21 21:58:42.374901	\N
9	Luiz Antonio Ventura Passoli	bambas	1967-05-05	lpassoli@gmail.com	bamba	2026-07-31 21:58:30.643892	\N
14	Miguel Campos	viadinho	1960-03-12	miguel@gmail.com	$2y$10$eZYaMKmWYI.NoM0j3EHHwuB5ijcjeLB1hfYDQsMGw//VeTPl4ogGO	2026-08-05 08:50:11.4769	\N
16	Pedro	podpedro	2001-09-11	pedro@gmail.com	$2y$10$OxRjZpneEul97RzqRrvsd.Cx/fLqgLz9IBk9pVMiwPgVGSXlhm9Yi	2026-08-06 11:15:00.456644	\N
17	Nook	NOOKADMIN	2026-02-02	tccnook@gmail.com	$2y$10$edZ3VgguFJoAOX7zbZTWY.Ie05aWBEjhQ83TfH8rOt.raSUHm7McW	2026-08-06 11:16:14.282838	115543703945018518444
18	Paulo	pauladentro	1967-07-12	paulo@gmail.com	$2y$10$U8sBW4gzzPpXLYn2pUNYY.Trzvz8eyge8br3b7TS3rKGBdq6csmW6	2026-08-06 11:17:13.697667	\N
20	Jeuca	jeuquinha	1870-05-12	jeuca@gmail.com	$2y$10$662GeaJa96f.QPgjlImNJOG/1lu0GdUyJ.r9GimSBq8kermA5zZxW	2026-08-06 15:37:05.928915	\N
21	Gabriel	gaybriel	2004-06-22	gabriel@gmail.com	$2y$10$kgtF.n/i.6lRjnBDUOapRelcJ89mlzhYUhRnPpSzN0U3ohbFA/6FK	2026-08-06 15:43:54.598026	\N
22	Mirian	mii	1912-12-12	mirian@gmail.com	$2y$10$RJQzpg1wk5gYoBeOfPVa0eBeLoraIIeVYrX8V8Pbl/ad9N9i2aVx.	2026-08-06 15:45:03.121692	\N
23	fsdf	fsdf	1999-09-21	fsdfsdfsdfsdfsdf	$2y$10$JEIPcLrkYrwip/Gl/n/tJO9utlt1RdnbIKzYyHrosCyNYHEnRdC5m	2026-08-06 15:46:16.596591	\N
24	Usuario1	usuario1	0205-03-12	usuario1@gmail.com	$2y$10$GIFMX/YuDNE18m1ym7WORuAbg58lpeUOj7UKH7001b8Ez5Hr2lVJG	2026-08-06 15:53:11.91489	\N
19	Caio Passoli	caico	2008-09-16	passolicaio@gmail.com	$2y$10$JiSiDnS7fSc9ZnJqrcuVVuO6kfEs2aVP8Tt3OEgF2/nOLfAnhTOG.	2026-08-06 11:19:29.329523	114771290742095317837
27	Projeto LITTERA	luizviado	2000-03-12	projetolittera2026@gmail.com	$2y$10$1K5vbJhwufuVWYg5CberAO6hmFLX/zR8pQJYcNZ6sxuidzf9lgcdS	2026-08-10 17:04:04.083766	109725691836031282460
\.


--
-- Data for Name: whishbook; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.whishbook (id, id_livro, id_user, id_whishlist) FROM stdin;
1	1	19	1
2	2	19	1
3	3	19	1
4	4	19	1
6	5	19	1
\.


--
-- Data for Name: whishlist; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.whishlist (id, nome_lista, id_user) FROM stdin;
1	J  li	19
\.


--
-- Name: autor_id_autor_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.autor_id_autor_seq', 9, true);


--
-- Name: autor_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.autor_user_id_seq', 12, true);


--
-- Name: capitulo_id_capitulo_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.capitulo_id_capitulo_seq', 15, true);


--
-- Name: cena_id_cena_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cena_id_cena_seq', 1, false);


--
-- Name: cenario_id_cenario_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cenario_id_cenario_seq', 1, false);


--
-- Name: comentario_livro_id_comentario_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.comentario_livro_id_comentario_seq', 5, true);


--
-- Name: conversa_id_conversa_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.conversa_id_conversa_seq', 1, false);


--
-- Name: livro_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.livro_id_seq', 5, true);


--
-- Name: mensagem_id_mensagem_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mensagem_id_mensagem_seq', 1, false);


--
-- Name: meta_leitura_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.meta_leitura_id_seq', 6, true);


--
-- Name: paragrafo_id_paragrafo_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.paragrafo_id_paragrafo_seq', 362, true);


--
-- Name: paragrafo_resenha_id_paragrafo_resenha_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.paragrafo_resenha_id_paragrafo_resenha_seq', 1, false);


--
-- Name: personagem_id_personagem_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.personagem_id_personagem_seq', 1, false);


--
-- Name: post_id_post_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.post_id_post_seq', 1, false);


--
-- Name: preferencia_id_preferencia_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.preferencia_id_preferencia_seq', 29, true);


--
-- Name: preferencia_livro_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.preferencia_livro_id_seq', 1, false);


--
-- Name: preferencia_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.preferencia_user_id_seq', 12, true);


--
-- Name: progresso_leitura_id_progresso_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.progresso_leitura_id_progresso_seq', 1, false);


--
-- Name: recuperacao_senha_id_recuperacao_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.recuperacao_senha_id_recuperacao_seq', 4, true);


--
-- Name: resenha_id_resenha_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.resenha_id_resenha_seq', 1, false);


--
-- Name: usuario_id_user_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuario_id_user_seq', 27, true);


--
-- Name: whishbook_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.whishbook_id_seq', 6, true);


--
-- Name: whishlist_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.whishlist_id_seq', 1, true);


--
-- Name: autor autor_autor_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autor
    ADD CONSTRAINT autor_autor_key UNIQUE (autor);


--
-- Name: autor autor_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autor
    ADD CONSTRAINT autor_pkey PRIMARY KEY (id_autor);


--
-- Name: autor_user autor_user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autor_user
    ADD CONSTRAINT autor_user_pkey PRIMARY KEY (id);


--
-- Name: capitulo capitulo_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capitulo
    ADD CONSTRAINT capitulo_pkey PRIMARY KEY (id_capitulo);


--
-- Name: cena cena_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cena
    ADD CONSTRAINT cena_pkey PRIMARY KEY (id_cena);


--
-- Name: cenario cenario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cenario
    ADD CONSTRAINT cenario_pkey PRIMARY KEY (id_cenario);


--
-- Name: comentario_livro comentario_livro_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.comentario_livro
    ADD CONSTRAINT comentario_livro_pkey PRIMARY KEY (id_comentario);


--
-- Name: conversa conversa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversa
    ADD CONSTRAINT conversa_pkey PRIMARY KEY (id_conversa);


--
-- Name: livro livro_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.livro
    ADD CONSTRAINT livro_pkey PRIMARY KEY (id_livro);


--
-- Name: mensagem mensagem_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_pkey PRIMARY KEY (id_mensagem);


--
-- Name: meta_leitura meta_leitura_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.meta_leitura
    ADD CONSTRAINT meta_leitura_pkey PRIMARY KEY (id);


--
-- Name: paragrafo paragrafo_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paragrafo
    ADD CONSTRAINT paragrafo_pkey PRIMARY KEY (id_paragrafo);


--
-- Name: paragrafo_resenha paragrafo_resenha_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paragrafo_resenha
    ADD CONSTRAINT paragrafo_resenha_pkey PRIMARY KEY (id_paragrafo_resenha);


--
-- Name: personagem personagem_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personagem
    ADD CONSTRAINT personagem_pkey PRIMARY KEY (id_personagem);


--
-- Name: post post_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post
    ADD CONSTRAINT post_pkey PRIMARY KEY (id_post);


--
-- Name: preferencia_livro preferencia_livro_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_livro
    ADD CONSTRAINT preferencia_livro_pkey PRIMARY KEY (id);


--
-- Name: preferencia preferencia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia
    ADD CONSTRAINT preferencia_pkey PRIMARY KEY (id_preferencia);


--
-- Name: preferencia preferencia_preferencia_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia
    ADD CONSTRAINT preferencia_preferencia_key UNIQUE (preferencia);


--
-- Name: preferencia_user preferencia_user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT preferencia_user_pkey PRIMARY KEY (id);


--
-- Name: progresso_leitura progresso_leitura_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progresso_leitura
    ADD CONSTRAINT progresso_leitura_pkey PRIMARY KEY (id_progresso);


--
-- Name: recuperacao_senha recuperacao_senha_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recuperacao_senha
    ADD CONSTRAINT recuperacao_senha_pkey PRIMARY KEY (id_recuperacao);


--
-- Name: resenha resenha_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_pkey PRIMARY KEY (id_resenha);


--
-- Name: usuario usuario_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_email_key UNIQUE (email);


--
-- Name: usuario usuario_google_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_google_id_key UNIQUE (google_id);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id_user);


--
-- Name: usuario usuario_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_username_key UNIQUE (username);


--
-- Name: whishbook whishbook_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishbook
    ADD CONSTRAINT whishbook_pkey PRIMARY KEY (id);


--
-- Name: whishlist whishlist_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishlist
    ADD CONSTRAINT whishlist_pkey PRIMARY KEY (id);


--
-- Name: bloqueio bloqueio_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bloqueio
    ADD CONSTRAINT bloqueio_usuario FOREIGN KEY (id_bloqueador) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: bloqueio bloqueio_usuario2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bloqueio
    ADD CONSTRAINT bloqueio_usuario2 FOREIGN KEY (id_bloqueado) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: capitulo capitulo_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capitulo
    ADD CONSTRAINT capitulo_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: cena cena_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cena
    ADD CONSTRAINT cena_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild cena_rel; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT cena_rel FOREIGN KEY (id_cena) REFERENCES public.cena(id_cena) ON DELETE CASCADE;


--
-- Name: cenario cenario_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cenario
    ADD CONSTRAINT cenario_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild cenario_rel; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT cenario_rel FOREIGN KEY (id_cenario) REFERENCES public.cenario(id_cenario) ON DELETE CASCADE;


--
-- Name: comentario_livro coment_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.comentario_livro
    ADD CONSTRAINT coment_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: comentario_livro coment_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.comentario_livro
    ADD CONSTRAINT coment_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: conversa conversa_dono; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversa
    ADD CONSTRAINT conversa_dono FOREIGN KEY (id_dono) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: autor_user fk_autor; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autor_user
    ADD CONSTRAINT fk_autor FOREIGN KEY (id_autor) REFERENCES public.autor(id_autor) ON DELETE CASCADE;


--
-- Name: conta fk_conta_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conta
    ADD CONSTRAINT fk_conta_usuario FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishbook fk_livro_book; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishbook
    ADD CONSTRAINT fk_livro_book FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: preferencia_livro fk_livro_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_livro
    ADD CONSTRAINT fk_livro_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: preferencia_user fk_preferencia; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT fk_preferencia FOREIGN KEY (id_preferencia) REFERENCES public.preferencia(id_preferencia) ON DELETE CASCADE;


--
-- Name: preferencia_livro fk_preferencia_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_livro
    ADD CONSTRAINT fk_preferencia_livro FOREIGN KEY (id_preferencia) REFERENCES public.preferencia(id_preferencia);


--
-- Name: recuperacao_senha fk_recuperacao_senha; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recuperacao_senha
    ADD CONSTRAINT fk_recuperacao_senha FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: autor_user fk_user_autor; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autor_user
    ADD CONSTRAINT fk_user_autor FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishbook fk_user_book; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishbook
    ADD CONSTRAINT fk_user_book FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: preferencia_user fk_user_pref; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT fk_user_pref FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishlist fk_user_whish; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishlist
    ADD CONSTRAINT fk_user_whish FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: whishbook fk_whish_book; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishbook
    ADD CONSTRAINT fk_whish_book FOREIGN KEY (id_whishlist) REFERENCES public.whishlist(id);


--
-- Name: follow follow_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.follow
    ADD CONSTRAINT follow_usuario FOREIGN KEY (id_follower) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: follow follow_usuario2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.follow
    ADD CONSTRAINT follow_usuario2 FOREIGN KEY (id_following) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: livros_lidos lidos_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.livros_lidos
    ADD CONSTRAINT lidos_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: livros_lidos lidos_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.livros_lidos
    ADD CONSTRAINT lidos_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: progresso_leitura livro_progresso; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progresso_leitura
    ADD CONSTRAINT livro_progresso FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: livro livro_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.livro
    ADD CONSTRAINT livro_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: mensagem mensagem_conversa; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_conversa FOREIGN KEY (id_conversa) REFERENCES public.conversa(id_conversa) ON DELETE CASCADE;


--
-- Name: mensagem mensagem_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_user FOREIGN KEY (id_envio) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: meta_leitura meta_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.meta_leitura
    ADD CONSTRAINT meta_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: paragrafo paragrafo_capitulo; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paragrafo
    ADD CONSTRAINT paragrafo_capitulo FOREIGN KEY (id_capitulo) REFERENCES public.capitulo(id_capitulo) ON DELETE CASCADE;


--
-- Name: paragrafo_resenha paragrafo_idresenha; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paragrafo_resenha
    ADD CONSTRAINT paragrafo_idresenha FOREIGN KEY (id_resenha) REFERENCES public.resenha(id_resenha) ON DELETE CASCADE;


--
-- Name: conversa_participante participante_conversa; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversa_participante
    ADD CONSTRAINT participante_conversa FOREIGN KEY (id_conversa) REFERENCES public.conversa(id_conversa) ON DELETE CASCADE;


--
-- Name: conversa_participante participante_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversa_participante
    ADD CONSTRAINT participante_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: personagem personagem_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personagem
    ADD CONSTRAINT personagem_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild personagem_rel; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT personagem_rel FOREIGN KEY (id_personagem) REFERENCES public.personagem(id_personagem) ON DELETE CASCADE;


--
-- Name: post post_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post
    ADD CONSTRAINT post_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: resenha resenha_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: resenha resenha_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: top5_livros top5_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.top5_livros
    ADD CONSTRAINT top5_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: top5_livros top5_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.top5_livros
    ADD CONSTRAINT top5_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: progresso_leitura user_progresso; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progresso_leitura
    ADD CONSTRAINT user_progresso FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- PostgreSQL database dump complete
--

\unrestrict d2sBcucx3gcjpQgGjizon7erGSzL8YOpc2ziDmHCbmisaATyaqSCDhkBO7k4nPw

