-- Run after inserting users with placeholder passwords:
--   php scripts/seed_passwords.php
--
-- Demo login credentials (bcrypt hashes applied by seed script):
--   Partnership Director : agnes.kula@dwu.ac.pg  / director123
--   President            : president@dwu.ac.pg     / president123
--   Executive Officer    : jmete@dwu.ac.pg       / executive123
--   Campus Admin (all)   : asanki@dwu.ac.pg       / campus123
--                         mtavana@dwu.ac.pg
--                         pkari@dwu.ac.pg

INSERT INTO users (Campus_ID, First_name, Last_name, Gender, Email, Phone_Number, Role, password) VALUES
(1, 'Agnes', 'Kula', 'F', 'agnes.kula@dwu.ac.pg', '+675 7100 1122', 'Partnership Director', 'PLACEHOLDER_RUN_seed_passwords.php'),
(1, 'Fr. Philip', 'Gibbs', 'M', 'president@dwu.ac.pg', '+675 7100 3344', 'President', 'PLACEHOLDER_RUN_seed_passwords.php'),
(1, 'John', 'Mete', 'M', 'jmete@dwu.ac.pg', '+675 7100 5566', 'Executive Officer', 'PLACEHOLDER_RUN_seed_passwords.php'),
(2, 'Alois', 'Sanki', 'M', 'asanki@dwu.ac.pg', '+675 7200 8899', 'Campus Admin', 'PLACEHOLDER_RUN_seed_passwords.php'),
(3, 'Michael', 'Tavana', 'M', 'mtavana@dwu.ac.pg', '+675 7300 4455', 'Campus Admin', 'PLACEHOLDER_RUN_seed_passwords.php'),
(4, 'Peter', 'Kari', 'M', 'pkari@dwu.ac.pg', '+675 7400 6677', 'Campus Admin', 'PLACEHOLDER_RUN_seed_passwords.php');
