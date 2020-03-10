CREATE OR REPLACE PROCEDURE users_local_change_password
(IN auth_username VARCHAR(120) , IN auth_password VARCHAR(120))
BEGIN
    DECLARE salt TYPE OF users_local.salt;
    START TRANSACTION;
    IF exists (SELECT 1 FROM users_local WHERE users_local.username= auth_username)  THEN
        SET @salt = UNHEX(REPLACE(UUID(), '-', ''));
        UPDATE users_local
        SET
            users_local.password = UNHEX(SHA2(CONCAT(@salt,auth_password),512)),
            users_local.salt = @salt
        WHERE users_local.username = auth_username;
    END IF;
    COMMIT;
END;