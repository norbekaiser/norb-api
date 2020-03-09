CREATE OR REPLACE PROCEDURE addlocaluser
(IN new_username VARCHAR(120) , IN new_password VARCHAR(120), OUT new_usr_id INT)
BEGIN
    DECLARE salt TYPE OF users_local.salt;
    START TRANSACTION;
    IF not exists (SELECT 1 FROM users_local WHERE users_local.username= new_username)  THEN
        INSERT INTO users_id (id, member_since) VALUES (NULL, CURRENT_TIMESTAMP);
        SET new_usr_id = ( SELECT LAST_INSERT_ID());
        SET @salt = UNHEX(REPLACE(UUID(), '-', ''));
        INSERT INTO users_local  VALUES (new_username,new_usr_id,UNHEX(SHA2(CONCAT(@salt,new_password),512)),@salt);
    END IF;
    COMMIT;
END;