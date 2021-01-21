
Database SQLite3

sqlite3 data/security.db3
create table users(user char(32) not null primary key, password char(64) not null);
insert into users(user, password) values('admin', '0b70e4b0c800321c922ce78268cb989f');


--user admin
--password 5hmFwG2yJbSWg8wS
-- Encriptado md5