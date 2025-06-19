
-- Mark some products as recommended/featured
UPDATE produse SET recomandat = 1 WHERE id IN (1, 2, 3, 5, 8, 11, 14);

-- Verify the update
SELECT id, nume, recomandat FROM produse WHERE recomandat = 1;
