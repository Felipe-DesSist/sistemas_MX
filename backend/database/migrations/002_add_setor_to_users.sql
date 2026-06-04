-- Rode apenas se a tabela users já existia sem a coluna setor
ALTER TABLE users
    ADD COLUMN setor VARCHAR(30) NOT NULL DEFAULT '' AFTER password,
    ADD INDEX idx_setor (setor);
