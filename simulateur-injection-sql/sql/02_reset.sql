-- =====================================================================
--  Restauration de la base "banque_test" apres un test destructeur
--  (technique 8 : DROP DATABASE, ou technique 9 : UPDATE des mots de passe).
--
--  Il suffit de re-importer 01_install.sql : il fait lui-meme un
--  DROP DATABASE IF EXISTS puis recree tout. Ce fichier n'est qu'un
--  raccourci qui rappelle la marche a suivre.
-- =====================================================================

-- Rejouer simplement :  SOURCE 01_install.sql;
-- ou re-importer 01_install.sql via phpMyAdmin.
SOURCE 01_install.sql;
