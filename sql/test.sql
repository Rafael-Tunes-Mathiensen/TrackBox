USE trackbox;

INSERT INTO disks
(user_id, type, artist, album_name, year, label, country_id, is_imported,
 edition, condition_disk, condition_cover, is_sealed, image_path,
 is_favorite, observations)
VALUES

-- Clássicos de Rock
(1, 'CD', 'Pink Floyd', 'Wish You Were Here', 1975, 'Harvest', 2, 1,
 'primeira_edicao', 'VG+', 'VG+', 0, NULL, 0, 'Som excelente.'),

(1, 'LP', 'Led Zeppelin', 'Led Zeppelin IV', 1971, 'Atlantic', 1, 0,
 'primeira_edicao', 'G+', 'G', 0, NULL, 1, 'Edição muito procurada.'),

(1, 'LP', 'The Rolling Stones', 'Sticky Fingers', 1971, 'Rolling Stones Records', 3, 1,
 'especial', 'VG', 'VG', 0, NULL, 0, 'Capa com zíper funcional.'),

(1, 'CD', 'Queen', 'A Night at the Opera', 1975, 'EMI', 2, 0,
 'reedicao', 'E', 'E', 0, NULL, 1, 'Contém Bohemian Rhapsody.'),

-- Heavy Metal
(1, 'CD', 'Iron Maiden', 'The Number of the Beast', 1982, 'EMI', 1, 0,
 'primeira_edicao', 'VG', 'G+', 0, NULL, 0, 'Clássico do metal.'),

(1, 'LP', 'Black Sabbath', 'Paranoid', 1970, 'Warner', 1, 0,
 'primeira_edicao', 'G+', 'VG', 0, NULL, 0, 'Leve chiado, mas tocável.'),

(1, 'CD', 'Judas Priest', 'Painkiller', 1990, 'Columbia', 1, 1,
 'reedicao', 'E', 'VG+', 0, NULL, 0, 'Importado.'),

-- Indie / Alternativo
(1, 'CD', 'Radiohead', 'OK Computer', 1997, 'Parlophone', 2, 1,
 'especial', 'Mint', 'Mint', 1, NULL, 1, 'Como novo.'),

(1, 'LP', 'Arctic Monkeys', 'AM', 2013, 'Domino', 6, 1,
 'especial', 'Mint', 'Mint', 1, NULL, 0, 'Vinil branco edição limitada.'),

(1, 'CD', 'Nirvana', 'Nevermind', 1991, 'DGC', 1, 0,
 'reedicao', 'VG+', 'VG', 0, NULL, 1, 'Clássico absoluto.'),

-- Box Sets
(1, 'BoxSet', 'Pink Floyd', 'The Early Years 1965–1972', 2016, 'Pink Floyd Records', 2, 1,
 'boxset_completo', 'Mint', 'Mint', 1, NULL, 1, 'Contém vários materiais raros.'),

(1, 'BoxSet', 'Metallica', 'Master of Puppets Deluxe Box', 2017, 'Blackened', 1, 1,
 'deluxe', 'Mint', 'Mint', 1, NULL, 1, 'Box enorme, completo.'),

(1, 'BoxSet', 'Beatles', 'Beatles Stereo Box Set', 2009, 'Apple', 2, 1,
 'colecionador', 'Mint', 'Mint', 1, NULL, 0, 'Importado UK.'),

-- Hard Rock / Classic Rock
(1, 'LP', 'AC/DC', 'Back in Black', 1980, 'Atlantic', 1, 0,
 'primeira_edicao', 'VG', 'VG', 0, NULL, 1, 'Grande clássico.'),

(1, 'CD', 'Guns N’ Roses', 'Appetite for Destruction', 1987, 'Geffen', 1, 0,
 'reedicao', 'E', 'E', 0, NULL, 0, 'Ótimo estado.'),

(1, 'LP', 'Kiss', 'Destroyer', 1976, 'Casablanca', 1, 0,
 'primeira_edicao', 'G+', 'G+', 0, NULL, 0, 'Algum desgaste natural.'),

-- Prog Rock
(1, 'CD', 'Yes', 'Fragile', 1971, 'Atlantic', 1, 0,
 'reedicao', 'E', 'VG+', 0, NULL, 0, 'Booklet em ótimo estado.'),

(1, 'LP', 'King Crimson', 'In the Court of the Crimson King', 1969, 'Island', 2, 1,
 'especial', 'VG+', 'VG', 0, NULL, 1, 'Capa icônica.'),

-- Pop/Rock
(1, 'CD', 'Michael Jackson', 'Thriller', 1982, 'Epic', 1, 0,
 'reedicao', 'Mint', 'Mint', 1, NULL, 1, 'Maior sucesso da carreira.'),

(1, 'LP', 'David Bowie', 'The Rise and Fall of Ziggy Stardust', 1972, 'RCA', 2, 1,
 'primeira_edicao', 'G+', 'G', 0, NULL, 0, 'Vinil raro.'),

-- Punk Rock
(1, 'CD', 'The Clash', 'London Calling', 1979, 'CBS', 2, 1,
 'reedicao', 'VG+', 'VG+', 0, NULL, 1, 'Ótima prensagem.'),

(1, 'LP', 'Ramones', 'Ramones', 1976, 'Sire', 1, 0,
 'primeira_edicao', 'G', 'G', 0, NULL, 0, 'Capa com desgaste.'),

-- Grunge
(1, 'LP', 'Alice in Chains', 'Dirt', 1992, 'Columbia', 1, 1,
 'especial', 'VG+', 'VG+', 0, NULL, 0, 'Vinil colorido.'),

(1, 'CD', 'Soundgarden', 'Superunknown', 1994, 'A&M', 1, 0,
 'reedicao', 'E', 'E', 0, NULL, 1, 'Som poderoso.'),

-- Extras variados
(1, 'CD', 'Deep Purple', 'Machine Head', 1972, 'EMI', 2, 1,
 'reedicao', 'VG+', 'VG', 0, NULL, 0, 'Smoke on the Water.'),

(1, 'LP', 'Fleetwood Mac', 'Rumours', 1977, 'Warner', 1, 0,
 'especial', 'Mint', 'Mint', 1, NULL, 0, 'Vinil 180g.'),

(1, 'CD', 'U2', 'The Joshua Tree', 1987, 'Island', 2, 1,
 'reedicao', 'E', 'E', 0, NULL, 0, 'Ótima masterização.'),

(1, 'LP', 'Bruce Springsteen', 'Born to Run', 1975, 'Columbia', 1, 0,
 'primeira_edicao', 'VG', 'VG-', 0, NULL, 0, 'Capa levemente amarelada.'),

(1, 'CD', 'Coldplay', 'Parachutes', 2000, 'Parlophone', 2, 1,
 'reedicao', 'Mint', 'Mint', 1, NULL, 0, 'Importado UK.')
;
