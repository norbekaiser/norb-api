CREATE TABLE IF NOT EXISTS users_local (
    username VARCHAR(200) NOT NULL,
    usr_id INT NOT NULL,
    password BINARY(64) NOT NULL,
    salt BINARY(16) NOT NULL,
    PRIMARY KEY (username),
    FOREIGN KEY(usr_id) REFERENCES users_id(id)
);