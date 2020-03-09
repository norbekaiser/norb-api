CREATE OR REPLACE PROCEDURE addldapuser
(IN new_dn VARCHAR(120) , OUT new_usr_id INT)
BEGIN
    START TRANSACTION;
    IF not exists (SELECT 1 FROM users_ldap WHERE users_ldap.dn= new_dn)  THEN
        INSERT INTO users_id (id, member_since) VALUES (NULL, CURRENT_TIMESTAMP);
        SET new_usr_id = ( SELECT LAST_INSERT_ID());
        INSERT INTO users_ldap  VALUES (new_dn,new_usr_id);
    END IF;
    COMMIT;
END;