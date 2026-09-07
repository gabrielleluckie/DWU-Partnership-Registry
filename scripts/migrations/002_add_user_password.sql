-- Add a password column to users.
-- Login password format: lowercase last name + User_ID (e.g. Wewak campus admin = luke3).

ALTER TABLE users
    ADD COLUMN password VARCHAR(255) NULL AFTER Email;

UPDATE users
SET password = CONCAT(LOWER(Last_Name), User_ID);
