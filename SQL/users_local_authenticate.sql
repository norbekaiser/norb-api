CREATE OR REPLACE PROCEDURE users_local_authenticate
(IN auth_username VARCHAR(120) , IN auth_password VARCHAR(120))
BEGIN
    IF exists (SELECT 1 FROM users_local WHERE users_local.username= auth_username) then
    SELECT
           A.usr_id as usr_id , A.username as username
    FROM
         users_local A
    INNER JOIN users_local B ON B.usr_id = A.usr_id
    WHERE
          A.username = auth_username
      AND A.password=UNHEX(SHA2(CONCAT(B.salt,auth_password),512))
    LIMIT 1;
    END IF;
END;