
-- Add remaining products from JSON to database
-- Products 6-15 to match the products.json file

INSERT INTO `produse` (`id`, `nume`, `slug`, `descriere_scurta`, `descriere`, `pret`, `pret_redus`, `stoc`, `cantitate`, `categorie_id`, `regiune_id`, `imagine`, `recomandat`, `data_expirare`, `restrictie_varsta`, `activ`, `data_adaugare`) VALUES
(6, 'Cârnați de Pleșcoi', 'carnati-plescoi', 'Cârnați afumați tradițional din Pleșcoi', 'Cârnați afumați tradițional din Pleșcoi, Muntenia, preparați după rețete străvechi transmise din generație în generație. Acești cârnați sunt afumați cu lemn de fag și au un gust intens și aromat specific zonei.', 24.99, NULL, 18, '400g', 3, 2, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Carnati+Plescoi', 0, '2025-02-28', 0, 1, '2024-01-01 10:00:00'),

(7, 'Telemea de Ibănești', 'telemea-ibanesti', 'Telemea tradițională din lapte de oaie', 'Telemea tradițională din lapte de oaie de la Ibănești, Muntenia. Această brânză sărată are un gust intens și o textură cremoasă, fiind preparată după rețete străvechi transmise din generație în generație.', 19.50, NULL, 22, '300g', 4, 2, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Telemea+Ibanesti', 0, '2025-03-15', 0, 1, '2024-01-01 10:00:00'),

(8, 'Pălincă de Pere Maramureș', 'palinca-pere-maramures', 'Pălincă dublă distilare din pere Williams', 'Pălincă dublă distilare din pere Williams din Maramureș. Această băutură tradițională cu 65% alcool este produsă în cantități limitate folosind doar pere selectate din livezile maramureșene, distilată după rețete străvechi.', 55.00, NULL, 12, '500ml', 5, 3, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Palinca+Pere', 1, NULL, 1, 1, '2024-01-01 10:00:00'),

(9, 'Gem de Caise Banat', 'gem-caise-banat', 'Gem tradițional din caise de Banat', 'Gem tradițional din caise de Banat, preparat după rețete străvechi transmise din generație în generație. Acest gem păstrează bucățile de caise și are un gust intens și aromat specific fructelor coapte la soare din Banat.', 21.50, NULL, 28, '350g', 1, 4, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Gem+Caise', 0, '2025-09-20', 0, 1, '2024-01-01 10:00:00'),

(10, 'Slănină Afumată Oltenia', 'slanina-afumata-oltenia', 'Slănină afumată tradițional cu lemn de fag', 'Slănină afumată tradițional cu lemn de fag din Oltenia. Această slănină este preparată după rețete străvechi, fiind afumată natural timp de 72 de ore pentru a obține gustul și aroma specifică produselor tradiționale oltenești.', 35.00, NULL, 14, '600g', 3, 5, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Slanina+Afumata', 0, '2025-04-10', 0, 1, '2024-01-01 10:00:00'),

(11, 'Miere de Tei Bucovina', 'miere-tei-bucovina', 'Miere cristalizată de tei din pădurile Bucovinei', 'Miere cristalizată de tei din pădurile Bucovinei. Această miere are proprietăți terapeutice excepționale și un gust delicat, fiind recoltată din stupinele amplasate în pădurile seculare de tei din nordul României.', 32.00, NULL, 16, '500g', 1, 8, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Miere+Tei', 1, '2026-01-01', 0, 1, '2024-01-01 10:00:00'),

(12, 'Murături Asortate Crișana', 'muraturi-asortate-crisana', 'Murături tradiționale în oțet de vin', 'Murături tradiționale în oțet de vin din Crișana. Acest amestec de legume murate conține castraveți, gogonele, conopidă și morcovi, toate preparate după rețete străvechi transmise din generație în generație.', 16.99, NULL, 35, '720ml', 2, 7, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Muraturi+Asortate', 0, '2025-06-18', 0, 1, '2024-01-01 10:00:00'),

(13, 'Caș de Capră Maramureș', 'cas-capra-maramures', 'Caș proaspăt din lapte de capră', 'Caș proaspăt din lapte de capră din Maramureș. Această brânză proaspătă are un gust delicat și o textură cremoasă, fiind preparată după metode tradiționale maramureșene din lapte proaspăt de capră.', 26.50, NULL, 8, '400g', 4, 3, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Cas+Capra', 0, '2025-02-25', 0, 1, '2024-01-01 10:00:00'),

(14, 'Dulceață de Trandafiri Dobrogea', 'dulceata-trandafiri-dobrogea', 'Dulceață delicată din petale de trandafiri', 'Dulceață delicată din petale de trandafiri din Dobrogea. Această dulceață premium este preparată din petale de trandafiri Damasc, culese manual în zori de zi și procesate în aceeași zi pentru a păstra aroma și proprietățile naturale.', 42.50, NULL, 12, '250g', 1, 6, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Dulceata+Trandafiri', 1, '2025-08-30', 0, 1, '2024-01-01 10:00:00'),

(15, 'Horincă Maramureș', 'horinca-maramures', 'Horincă tradițională din prune, 65% alcool', 'Horincă tradițională din prune din Maramureș, cu 65% alcool. Această băutură spirituoasă premium este distilată după rețete străvechi maramureșene, fiind considerată una dintre cele mai fine băuturi tradiționale românești.', 65.00, NULL, 8, '500ml', 5, 3, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Horinca+Maramures', 0, NULL, 1, 1, '2024-01-01 10:00:00');

-- Add tags for the new products
INSERT INTO `produse_etichete` (`produs_id`, `eticheta_id`) VALUES
-- Cârnați de Pleșcoi (6)
(6, 3), (6, 5),
-- Telemea de Ibănești (7)
(7, 3), (7, 4),
-- Pălincă de Pere (8)
(8, 3), (8, 4),
-- Gem de Caise (9)
(9, 3), (9, 4),
-- Slănină Afumată (10)
(10, 3), (10, 5),
-- Miere de Tei (11)
(11, 3), (11, 4),
-- Murături Asortate (12)
(12, 1), (12, 3), (12, 4),
-- Caș de Capră (13)
(13, 3), (13, 4),
-- Dulceață de Trandafiri (14)
(14, 3), (14, 4), (14, 5),
-- Horincă (15)
(15, 3), (15, 4);
